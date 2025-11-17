<?php
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$balance = getUserBalance($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $wallet_address = $_POST['wallet_address'];
    $password = $_POST['password'];
    
    global $pdo;
    
    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!password_verify($password, $user['password'])) {
        $error = "Invalid password";
    } elseif ($amount > $balance) {
        $error = "Insufficient funds";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Create withdrawal record
            $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, currency, wallet_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $amount, $currency, $wallet_address]);
            
            // Create transaction record
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status) VALUES (?, 'withdrawal', ?, ?, 'pending')");
            $stmt->execute([$user_id, $amount, $currency]);
            
            // Deduct from balance
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            
            $pdo->commit();
            $success = "Withdrawal request submitted! It will be processed by admin.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error processing withdrawal: " . $e->getMessage();
        }
    }
}

// Get withdrawal history
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$withdrawals = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Overview</a></li>
            <li><a href="deposit.php">Deposit</a></li>
            <li><a href="withdraw.php" class="active">Withdraw</a></li>
            <li><a href="trading.php">Trading</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </div>
    
    <div class="dashboard-content">
        <h2 class="card-title">Withdraw Funds</h2>
        
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
                    <label for="wallet_address">Wallet Address</label>
                    <input type="text" id="wallet_address" name="wallet_address" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (USD)</label>
                    <input type="number" id="amount" name="amount" class="form-control" min="10" step="0.01" max="<?php echo $balance; ?>" required>
                    <small>Available balance: $<?php echo number_format($balance, 2); ?></small>
                </div>
                
                <div class="form-group">
                    <label for="password">Confirm Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn">Request Withdrawal</button>
            </form>
        </div>
        
        <div class="card">
            <h3 class="card-title">Withdrawal History</h3>
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
                    <?php foreach ($withdrawals as $withdrawal): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($withdrawal['created_at'])); ?></td>
                        <td>$<?php echo number_format($withdrawal['amount'], 2); ?></td>
                        <td><?php echo $withdrawal['currency']; ?></td>
                        <td class="status-<?php echo $withdrawal['status']; ?>">
                            <?php echo ucfirst($withdrawal['status']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($withdrawals)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No withdrawals yet</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>