<?php
require_once 'includes/auth.php';
redirectIfNotAdmin();

global $pdo;

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'confirm_deposit':
                $deposit_id = $_POST['deposit_id'];
                $stmt = $pdo->prepare("UPDATE deposits SET status = 'confirmed' WHERE id = ?");
                $stmt->execute([$deposit_id]);
                
                // Get deposit info and update user balance
                $stmt = $pdo->prepare("SELECT user_id, amount FROM deposits WHERE id = ?");
                $stmt->execute([$deposit_id]);
                $deposit = $stmt->fetch();
                
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$deposit['amount'], $deposit['user_id']]);
                
                // Update transaction status
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE user_id = ? AND type = 'deposit' AND amount = ? AND status = 'pending'");
                $stmt->execute([$deposit['user_id'], $deposit['amount']]);
                
                $success = "Deposit confirmed successfully!";
                break;
                
            case 'reject_deposit':
                $deposit_id = $_POST['deposit_id'];
                $stmt = $pdo->prepare("UPDATE deposits SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$deposit_id]);
                
                // Update transaction status
                $stmt = $pdo->prepare("SELECT user_id, amount FROM deposits WHERE id = ?");
                $stmt->execute([$deposit_id]);
                $deposit = $stmt->fetch();
                
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE user_id = ? AND type = 'deposit' AND amount = ? AND status = 'pending'");
                $stmt->execute([$deposit['user_id'], $deposit['amount']]);
                
                $success = "Deposit rejected!";
                break;
                
            case 'approve_withdrawal':
                $withdrawal_id = $_POST['withdrawal_id'];
                $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?");
                $stmt->execute([$withdrawal_id]);
                
                // Update transaction status
                $stmt = $pdo->prepare("SELECT user_id, amount FROM withdrawals WHERE id = ?");
                $stmt->execute([$withdrawal_id]);
                $withdrawal = $stmt->fetch();
                
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE user_id = ? AND type = 'withdrawal' AND amount = ? AND status = 'pending'");
                $stmt->execute([$withdrawal['user_id'], $withdrawal['amount']]);
                
                $success = "Withdrawal approved!";
                break;
                
            case 'decline_withdrawal':
                $withdrawal_id = $_POST['withdrawal_id'];
                $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'declined' WHERE id = ?");
                $stmt->execute([$withdrawal_id]);
                
                // Return funds to user
                $stmt = $pdo->prepare("SELECT user_id, amount FROM withdrawals WHERE id = ?");
                $stmt->execute([$withdrawal_id]);
                $withdrawal = $stmt->fetch();
                
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$withdrawal['amount'], $withdrawal['user_id']]);
                
                // Update transaction status
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE user_id = ? AND type = 'withdrawal' AND amount = ? AND status = 'pending'");
                $stmt->execute([$withdrawal['user_id'], $withdrawal['amount']]);
                
                $success = "Withdrawal declined and funds returned!";
                break;
                
            case 'freeze_user':
                $user_id = $_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE users SET status = 'frozen' WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = "User frozen!";
                break;
                
            case 'unfreeze_user':
                $user_id = $_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = "User activated!";
                break;
                
            case 'update_balance':
                $user_id = $_POST['user_id'];
                $new_balance = $_POST['new_balance'];
                $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $stmt->execute([$new_balance, $user_id]);
                $success = "Balance updated successfully!";
                break;
        }
    }
}

// Get stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = FALSE")->fetchColumn();
$pending_deposits = $pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn();
$pending_withdrawals = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$total_balance = $pdo->query("SELECT SUM(balance) FROM users")->fetchColumn();

// Get all users (for user management)
$users = $pdo->query("SELECT * FROM users WHERE is_admin = FALSE ORDER BY created_at DESC")->fetchAll();

// Get pending deposits
$deposits = $pdo->query("SELECT d.*, u.name as user_name, u.email as user_email FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.status = 'pending' ORDER BY d.created_at DESC")->fetchAll();

// Get all deposits for history
$all_deposits = $pdo->query("SELECT d.*, u.name as user_name FROM deposits d JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC LIMIT 50")->fetchAll();

// Get pending withdrawals
$withdrawals = $pdo->query("SELECT w.*, u.name as user_name, u.email as user_email FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.created_at DESC")->fetchAll();

// Get all withdrawals for history
$all_withdrawals = $pdo->query("SELECT w.*, u.name as user_name FROM withdrawals w JOIN users u ON w.user_id = u.id ORDER BY w.created_at DESC LIMIT 50")->fetchAll();

// Get all transactions for history
$all_transactions = $pdo->query("SELECT t.*, u.name as user_name FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 100")->fetchAll();

include 'includes/header.php';
?>

<h2 class="card-title">Admin Dashboard</h2>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="admin-grid">
    <div class="admin-card">
        <h3>Total Users</h3>
        <div class="admin-stats"><?php echo $total_users; ?></div>
    </div>
    
    <div class="admin-card">
        <h3>Pending Deposits</h3>
        <div class="admin-stats"><?php echo $pending_deposits; ?></div>
    </div>
    
    <div class="admin-card">
        <h3>Pending Withdrawals</h3>
        <div class="admin-stats"><?php echo $pending_withdrawals; ?></div>
    </div>
    
    <div class="admin-card">
        <h3>Total Balance</h3>
        <div class="admin-stats">$<?php echo number_format($total_balance, 2); ?></div>
    </div>
</div>

<!-- User Management Section -->
<div class="card mt-3">
    <h3 class="card-title">User Management</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                <td>
                    $<?php echo number_format($user['balance'], 2); ?>
                    <form method="POST" style="display: inline-block; margin-left: 10px;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="number" name="new_balance" value="<?php echo $user['balance']; ?>" step="0.01" min="0" style="width: 100px; padding: 5px;">
                        <button type="submit" name="action" value="update_balance" class="btn" style="padding: 5px 10px; width: auto; margin-top: 5px;">Update Balance</button>
                    </form>
                </td>
                <td class="status-<?php echo $user['status']; ?>">
                    <?php echo ucfirst($user['status']); ?>
                </td>
                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                <td>
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <?php if ($user['status'] === 'active'): ?>
                            <button type="submit" name="action" value="freeze_user" class="btn btn-warning" style="padding: 5px 10px; width: auto;">Freeze User</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="unfreeze_user" class="btn btn-success" style="padding: 5px 10px; width: auto;">Activate User</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr>
                <td colspan="8" class="text-center">No users found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pending Deposits Section -->
<div class="card mt-3">
    <h3 class="card-title">Pending Deposits (<?php echo count($deposits); ?>)</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Email</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Wallet Address</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($deposits as $deposit): ?>
            <tr>
                <td><?php echo $deposit['id']; ?></td>
                <td><?php echo htmlspecialchars($deposit['user_name']); ?></td>
                <td><?php echo htmlspecialchars($deposit['user_email']); ?></td>
                <td>$<?php echo number_format($deposit['amount'], 2); ?></td>
                <td><?php echo $deposit['currency']; ?></td>
                <td style="font-family: monospace; font-size: 12px;"><?php echo $deposit['wallet_address']; ?></td>
                <td><?php echo date('M j, Y H:i', strtotime($deposit['created_at'])); ?></td>
                <td>
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                        <button type="submit" name="action" value="confirm_deposit" class="btn btn-success" style="padding: 5px 10px; width: auto;">Confirm</button>
                    </form>
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                        <button type="submit" name="action" value="reject_deposit" class="btn btn-danger" style="padding: 5px 10px; width: auto;">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deposits)): ?>
            <tr>
                <td colspan="8" class="text-center">No pending deposits</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Deposit History Section -->
<div class="card mt-3">
    <h3 class="card-title">Deposit History</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_deposits as $deposit): ?>
            <tr>
                <td><?php echo $deposit['id']; ?></td>
                <td><?php echo htmlspecialchars($deposit['user_name']); ?></td>
                <td>$<?php echo number_format($deposit['amount'], 2); ?></td>
                <td><?php echo $deposit['currency']; ?></td>
                <td class="status-<?php echo $deposit['status']; ?>">
                    <?php echo ucfirst($deposit['status']); ?>
                </td>
                <td><?php echo date('M j, Y H:i', strtotime($deposit['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($all_deposits)): ?>
            <tr>
                <td colspan="6" class="text-center">No deposit history</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pending Withdrawals Section -->
<div class="card mt-3">
    <h3 class="card-title">Pending Withdrawals (<?php echo count($withdrawals); ?>)</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Email</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Wallet Address</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($withdrawals as $withdrawal): ?>
            <tr>
                <td><?php echo $withdrawal['id']; ?></td>
                <td><?php echo htmlspecialchars($withdrawal['user_name']); ?></td>
                <td><?php echo htmlspecialchars($withdrawal['user_email']); ?></td>
                <td>$<?php echo number_format($withdrawal['amount'], 2); ?></td>
                <td><?php echo $withdrawal['currency']; ?></td>
                <td style="font-family: monospace; font-size: 12px;"><?php echo $withdrawal['wallet_address']; ?></td>
                <td><?php echo date('M j, Y H:i', strtotime($withdrawal['created_at'])); ?></td>
                <td>
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                        <button type="submit" name="action" value="approve_withdrawal" class="btn btn-success" style="padding: 5px 10px; width: auto;">Approve</button>
                    </form>
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                        <button type="submit" name="action" value="decline_withdrawal" class="btn btn-danger" style="padding: 5px 10px; width: auto;">Decline</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($withdrawals)): ?>
            <tr>
                <td colspan="8" class="text-center">No pending withdrawals</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Withdrawal History Section -->
<div class="card mt-3">
    <h3 class="card-title">Withdrawal History</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Wallet Address</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_withdrawals as $withdrawal): ?>
            <tr>
                <td><?php echo $withdrawal['id']; ?></td>
                <td><?php echo htmlspecialchars($withdrawal['user_name']); ?></td>
                <td>$<?php echo number_format($withdrawal['amount'], 2); ?></td>
                <td><?php echo $withdrawal['currency']; ?></td>
                <td style="font-family: monospace; font-size: 12px;"><?php echo $withdrawal['wallet_address']; ?></td>
                <td class="status-<?php echo $withdrawal['status']; ?>">
                    <?php echo ucfirst($withdrawal['status']); ?>
                </td>
                <td><?php echo date('M j, Y H:i', strtotime($withdrawal['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($all_withdrawals)): ?>
            <tr>
                <td colspan="7" class="text-center">No withdrawal history</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- All Transactions History Section -->
<div class="card mt-3">
    <h3 class="card-title">All Transactions History</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Status</th>
                <th>Details</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_transactions as $transaction): ?>
            <tr>
                <td><?php echo $transaction['id']; ?></td>
                <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                <td><?php echo ucfirst($transaction['type']); ?></td>
                <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                <td><?php echo $transaction['currency']; ?></td>
                <td class="status-<?php echo $transaction['status']; ?>">
                    <?php echo ucfirst($transaction['status']); ?>
                </td>
                <td><?php echo htmlspecialchars($transaction['details']); ?></td>
                <td><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($all_transactions)): ?>
            <tr>
                <td colspan="8" class="text-center">No transactions found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>