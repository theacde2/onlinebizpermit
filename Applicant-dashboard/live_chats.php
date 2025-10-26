<?php
$page_title = 'Live Chats';
$current_page = 'live_chats';

require_once './staff_header.php';

// Fetch all live chat sessions
$chats = [];
$sql = "SELECT lc.id, lc.status, lc.created_at, u.name as applicant_name, s.name as staff_name
        FROM live_chats lc
        JOIN users u ON lc.user_id = u.id
        LEFT JOIN users s ON lc.staff_id = s.id
        ORDER BY 
            CASE lc.status
                WHEN 'Pending' THEN 1
                WHEN 'Active' THEN 2
                ELSE 3
            END,
            lc.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    $chats = $result->fetch_all(MYSQLI_ASSOC);
}

require_once './staff_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <div class="main-header">
        <h1>Live Chat Management</h1>
    </div>

    <div class="table-container">
        <table class="applicants-table">
            <thead>
                <tr>
                    <th>Chat ID</th>
                    <th>Applicant</th>
                    <th>Status</th>
                    <th>Assigned Staff</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($chats)): ?>
                    <tr><td colspan="6" style="text-align:center; padding: 40px;">No live chat sessions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($chats as $chat): ?>
                        <tr>
                            <td>#<?= $chat['id'] ?></td>
                            <td><?= htmlspecialchars($chat['applicant_name']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($chat['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($chat['status'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($chat['staff_name'] ?? 'Unassigned') ?></td>
                            <td><?= date('M d, Y, h:i A', strtotime($chat['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="live_chat.php?id=<?= $chat['id'] ?>" class="btn-action" title="Open Chat">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Using styles from applicants.php for consistency */
    .status-badge.status-active {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    .status-badge.status-closed {
        background: #e9ecef;
        color: #6c757d;
    }
</style>

<?php require_once './staff_footer.php'; ?>