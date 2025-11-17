<?php
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    
    // Wallet addresses
    $wallet_addresses = [
        'BTC' => 'bc1qmq23rhaya48n2fely8pk5478fvv37g8l4xvlt0',
        'ETH' => '0xf5063A6D2F25Ce3BCa476C24e69be8bE9500b1c2'
    ];
    
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Create deposit record
        $stmt = $pdo->prepare("INSERT INTO deposits (user_id, amount, currency, wallet_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $amount, $currency, $wallet_addresses[$currency]]);
        
        // Create transaction record
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status) VALUES (?, 'deposit', ?, ?, 'pending')");
        $stmt->execute([$user_id, $amount, $currency]);
        
        $pdo->commit();
        $success = "Deposit request submitted! Send $currency to the address shown.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error processing deposit: " . $e->getMessage();
    }
}

// Get deposit history
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$deposits = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Overview</a></li>
            <li><a href="deposit.php" class="active">Deposit</a></li>
            <li><a href="withdraw.php">Withdraw</a></li>
            <li><a href="trading.php">Trading</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </div>
    
    <div class="dashboard-content">
        <h2 class="card-title">Deposit Funds</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="currency">Select Cryptocurrency</label>
                    <select id="currency" name="currency" class="form-control" required>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (USD)</label>
                    <input type="number" id="amount" name="amount" class="form-control" min="10" step="0.01" required>
                </div>
                
                <button type="submit" class="btn">Get Deposit Address</button>
            </form>
            
            <?php if (isset($wallet_addresses) && isset($currency)): ?>
            <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4>Send Funds to This Address</h4>
                <p style="word-break: break-all; font-family: monospace; background: white; padding: 10px; border-radius: 4px;">
                    <?php echo $wallet_addresses[$currency]; ?>
                </p>
                <p><strong>Amount:</strong> $<?php echo number_format($_POST['amount'], 2); ?></p>
                <p class="status-pending">Status: Pending Admin Confirmation</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 class="card-title">Deposit History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deposits as $deposit): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($deposit['created_at'])); ?></td>
                        <td>$<?php echo number_format($deposit['amount'], 2); ?></td>
                        <td><?php echo $deposit['currency']; ?></td>
                        <td class="status-<?php echo $deposit['status']; ?>">
                            <?php echo ucfirst($deposit['status']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($deposits)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No deposits yet</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>