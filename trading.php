<?php
require_once 'includes/auth.php';
require_once 'includes/price_service.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

// Get user balances
global $pdo;
$stmt = $pdo->prepare("SELECT balance, btc_balance, eth_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if user exists and set default values if not
if ($user) {
    $balance = $user['balance'];
    $btc_balance = $user['btc_balance'] ?? 0;
    $eth_balance = $user['eth_balance'] ?? 0;
} else {
    // Default values if user not found (shouldn't happen but safe fallback)
    $balance = 0;
    $btc_balance = 0;
    $eth_balance = 0;
}

// Get real-time crypto prices
$prices = getRealTimeCryptoPrices();
$btc_price = $prices['BTC'];
$eth_price = $prices['ETH'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset = $_POST['asset'];
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    
    $price = $asset === 'BTC' ? $btc_price : $eth_price;
    
    try {
        $pdo->beginTransaction();
        
        if ($type === 'buy') {
            // BUY LOGIC
            if ($amount > $balance) {
                throw new Exception("Insufficient USD funds. You have: $" . number_format($balance, 2));
            }
            
            if ($amount < 10) {
                throw new Exception("Minimum trade amount is $10.00");
            }
            
            // Calculate how much crypto they get
            $crypto_amount = $amount / $price;
            
            // Record trade
            $stmt = $pdo->prepare("INSERT INTO trades (user_id, type, asset, amount, price, crypto_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $asset, $amount, $price, $crypto_amount]);
            
            // Update balances: deduct USD, add crypto
            if ($asset === 'BTC') {
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ?, btc_balance = btc_balance + ? WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ?, eth_balance = eth_balance + ? WHERE id = ?");
            }
            $stmt->execute([$amount, $crypto_amount, $user_id]);
            
            // Record transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, details) VALUES (?, 'trade', ?, ?, 'completed', ?)");
            $details = "Bought " . number_format($crypto_amount, 8) . " $asset for $" . number_format($amount, 2) . " at $" . number_format($price, 2);
            $stmt->execute([$user_id, $amount, $asset, $details]);
            
            $success = "Successfully bought " . number_format($crypto_amount, 8) . " $asset for $" . number_format($amount, 2);
            
        } else {
            // SELL LOGIC
            // Calculate how much crypto they're selling based on USD amount
            $crypto_amount = $amount / $price;
            
            // Check if user has enough crypto to sell
            if ($asset === 'BTC') {
                if ($crypto_amount > $btc_balance) {
                    throw new Exception("Insufficient BTC balance. You have: " . number_format($btc_balance, 8) . " BTC");
                }
            } else {
                if ($crypto_amount > $eth_balance) {
                    throw new Exception("Insufficient ETH balance. You have: " . number_format($eth_balance, 8) . " ETH");
                }
            }
            
            if ($amount < 10) {
                throw new Exception("Minimum trade amount is $10.00");
            }
            
            // Record trade
            $stmt = $pdo->prepare("INSERT INTO trades (user_id, type, asset, amount, price, crypto_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $asset, $amount, $price, $crypto_amount]);
            
            // Update balances: add USD, deduct crypto
            if ($asset === 'BTC') {
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ?, btc_balance = btc_balance - ? WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ?, eth_balance = eth_balance - ? WHERE id = ?");
            }
            $stmt->execute([$amount, $crypto_amount, $user_id]);
            
            // Record transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, details) VALUES (?, 'trade', ?, ?, 'completed', ?)");
            $details = "Sold " . number_format($crypto_amount, 8) . " $asset for $" . number_format($amount, 2) . " at $" . number_format($price, 2);
            $stmt->execute([$user_id, $amount, $asset, $details]);
            
            $success = "Successfully sold " . number_format($crypto_amount, 8) . " $asset for $" . number_format($amount, 2);
        }
        
        $pdo->commit();
        
        // Refresh user balances after trade
        $stmt = $pdo->prepare("SELECT balance, btc_balance, eth_balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user) {
            $balance = $user['balance'];
            $btc_balance = $user['btc_balance'];
            $eth_balance = $user['eth_balance'];
        }
        
        // Refresh prices
        $prices = getRealTimeCryptoPrices();
        $btc_price = $prices['BTC'];
        $eth_price = $prices['ETH'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Get trade history
$stmt = $pdo->prepare("SELECT * FROM trades WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$trades = $stmt->fetchAll();

include 'includes/header.php';
?>

<h2 class="card-title">Cryptocurrency Trading - Live Prices</h2>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="trading-container">
    <div class="trading-card">
        <h3>Bitcoin (BTC)</h3>
        <div class="price-display price-up" id="btc-price">
            $<?php echo number_format($btc_price, 2); ?>
        </div>
        <div class="balance-info">
            <strong>Your Balance:</strong> <?php echo number_format($btc_balance, 8); ?> BTC
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="asset" value="BTC">
            
            <div class="form-group">
                <label for="btc-amount">Amount to Trade (USD)</label>
                <input type="number" id="btc-amount" name="amount" class="form-control" min="10" step="0.01" required>
                <small>Min: $10.00 | Available: $<?php echo number_format($balance, 2); ?></small>
            </div>
            
            <button type="submit" name="type" value="buy" class="btn btn-success">
                Buy BTC
            </button>
            <button type="submit" name="type" value="sell" class="btn btn-danger mt-2" 
                <?php echo ($btc_balance <= 0) ? 'disabled' : ''; ?>>
                Sell BTC
            </button>
            <?php if ($btc_balance <= 0): ?>
                <small class="text-danger">You don't have any BTC to sell</small>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="trading-card">
        <h3>Ethereum (ETH)</h3>
        <div class="price-display price-up" id="eth-price">
            $<?php echo number_format($eth_price, 2); ?>
        </div>
        <div class="balance-info">
            <strong>Your Balance:</strong> <?php echo number_format($eth_balance, 8); ?> ETH
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="asset" value="ETH">
            
            <div class="form-group">
                <label for="eth-amount">Amount to Trade (USD)</label>
                <input type="number" id="eth-amount" name="amount" class="form-control" min="10" step="0.01" required>
                <small>Min: $10.00 | Available: $<?php echo number_format($balance, 2); ?></small>
            </div>
            
            <button type="submit" name="type" value="buy" class="btn btn-success">
                Buy ETH
            </button>
            <button type="submit" name="type" value="sell" class="btn btn-danger mt-2"
                <?php echo ($eth_balance <= 0) ? 'disabled' : ''; ?>>
                Sell ETH
            </button>
            <?php if ($eth_balance <= 0): ?>
                <small class="text-danger">You don't have any ETH to sell</small>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card mt-3">
    <h3 class="card-title">Account Balances</h3>
    <div class="balances-grid">
        <div class="balance-item">
            <strong>USD Balance:</strong> $<?php echo number_format($balance, 2); ?>
        </div>
        <div class="balance-item">
            <strong>BTC Balance:</strong> <?php echo number_format($btc_balance, 8); ?> BTC
        </div>
        <div class="balance-item">
            <strong>ETH Balance:</strong> <?php echo number_format($eth_balance, 8); ?> ETH
        </div>
    </div>
    <p><small>Prices update every 30 seconds. Last updated: <?php echo date('H:i:s'); ?></small></p>
</div>

<div class="card mt-3">
    <h3 class="card-title">Trading History</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Asset</th>
                <th>USD Amount</th>
                <th>Crypto Amount</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trades as $trade): ?>
            <tr>
                <td><?php echo date('M j, Y H:i', strtotime($trade['created_at'])); ?></td>
                <td>
                    <span class="trade-type-<?php echo $trade['type']; ?>">
                        <?php echo ucfirst($trade['type']); ?>
                    </span>
                </td>
                <td><?php echo $trade['asset']; ?></td>
                <td>$<?php echo number_format($trade['amount'], 2); ?></td>
                <td><?php echo number_format($trade['crypto_amount'], 8); ?></td>
                <td>$<?php echo number_format($trade['price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($trades)): ?>
            <tr>
                <td colspan="6" class="text-center">No trades yet</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>