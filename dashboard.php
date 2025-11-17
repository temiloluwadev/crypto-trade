<?php
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

// Get user balances including crypto
global $pdo;
$stmt = $pdo->prepare("SELECT balance, btc_balance, eth_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$balance = $user['balance'];
$btc_balance = $user['btc_balance'] ?? 0;
$eth_balance = $user['eth_balance'] ?? 0;

// Get recent transactions
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">Overview</a></li>
            <li><a href="deposit.php">Deposit</a></li>
            <li><a href="withdraw.php">Withdraw</a></li>
            <li><a href="trading.php">Trading</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </div>
    
    <div class="dashboard-content">
        <div class="balance-card">
            <h3>USD Balance</h3>
            <div class="balance-amount">$<?php echo number_format($balance, 2); ?></div>
            <p>Available for trading and withdrawal</p>
        </div>

        <div class="crypto-balances-grid">
            <div class="crypto-balance-card">
                <h4>Bitcoin (BTC)</h4>
                <div class="crypto-amount"><?php echo number_format($btc_balance, 8); ?></div>
                <p>BTC Balance</p>
            </div>
            <div class="crypto-balance-card">
                <h4>Ethereum (ETH)</h4>
                <div class="crypto-amount"><?php echo number_format($eth_balance, 8); ?></div>
                <p>ETH Balance</p>
            </div>
        </div>
        
        <div class="card">
            <h3 class="card-title">Recent Transactions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></td>
                        <td><?php echo ucfirst($transaction['type']); ?></td>
                        <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                        <td><?php echo $transaction['currency'] ?? 'USD'; ?></td>
                        <td class="status-<?php echo $transaction['status']; ?>">
                            <?php echo ucfirst($transaction['status']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No transactions yet</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h3 class="card-title">Quick Actions</h3>
            <div class="quick-actions-grid">
                <a href="deposit.php" class="btn btn-success">Deposit Funds</a>
                <a href="withdraw.php" class="btn btn-warning">Withdraw Funds</a>
                <a href="trading.php" class="btn">Start Trading</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>