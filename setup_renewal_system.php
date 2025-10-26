<?php
// Setup Renewal System
require_once 'Applicant-dashboard/db.php';

echo "<h2>Setting up Renewal System</h2>";

try {
    // Add renewal tracking columns to applications table
    echo "<p>Adding renewal tracking columns...</p>";
    
    $alter_queries = [
        "ALTER TABLE applications ADD COLUMN IF NOT EXISTS renewal_date DATE NULL",
        "ALTER TABLE applications ADD COLUMN IF NOT EXISTS renewal_reminder_sent TINYINT(1) DEFAULT 0",
        "ALTER TABLE applications ADD COLUMN IF NOT EXISTS renewal_status ENUM('active', 'expiring_soon', 'expired', 'renewed') DEFAULT 'active'",
        "ALTER TABLE applications ADD COLUMN IF NOT EXISTS original_application_id INT NULL",
        "ALTER TABLE applications ADD COLUMN IF NOT EXISTS renewal_count INT DEFAULT 0"
    ];
    
    foreach ($alter_queries as $query) {
        if ($conn->query($query)) {
            echo "<p>✅ Column added successfully</p>";
        } else {
            echo "<p>⚠️ Column might already exist: " . $conn->error . "</p>";
        }
    }
    
    // Add indexes
    echo "<p>Adding indexes...</p>";
    $index_queries = [
        "CREATE INDEX IF NOT EXISTS idx_applications_renewal_date ON applications(renewal_date)",
        "CREATE INDEX IF NOT EXISTS idx_applications_renewal_status ON applications(renewal_status)"
    ];
    
    foreach ($index_queries as $query) {
        if ($conn->query($query)) {
            echo "<p>✅ Index created successfully</p>";
        } else {
            echo "<p>⚠️ Index might already exist: " . $conn->error . "</p>";
        }
    }
    
    // Update existing approved applications to have renewal dates
    echo "<p>Updating existing applications with renewal dates...</p>";
    $update_query = "UPDATE applications 
                     SET renewal_date = DATE_ADD(submitted_at, INTERVAL 1 YEAR),
                         renewal_status = CASE 
                             WHEN DATE_ADD(submitted_at, INTERVAL 1 YEAR) < CURDATE() THEN 'expired'
                             WHEN DATE_ADD(submitted_at, INTERVAL 1 YEAR) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                             ELSE 'active'
                         END
                     WHERE status IN ('approved', 'complete') AND renewal_date IS NULL";
    
    if ($conn->query($update_query)) {
        $affected_rows = $conn->affected_rows;
        echo "<p>✅ Updated $affected_rows applications with renewal dates</p>";
    } else {
        echo "<p>❌ Error updating applications: " . $conn->error . "</p>";
    }
    
    // Create renewal notifications table
    echo "<p>Creating renewal notifications table...</p>";
    $create_table_query = "CREATE TABLE IF NOT EXISTS renewal_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        user_id INT NOT NULL,
        notification_type ENUM('expiring_soon', 'expired', 'renewal_reminder') NOT NULL,
        message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_read TINYINT(1) DEFAULT 0,
        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_table_query)) {
        echo "<p>✅ Renewal notifications table created successfully</p>";
    } else {
        echo "<p>❌ Error creating notifications table: " . $conn->error . "</p>";
    }
    
    // Add indexes for renewal notifications
    $notification_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_renewal_notifications_user_id ON renewal_notifications(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_renewal_notifications_application_id ON renewal_notifications(application_id)",
        "CREATE INDEX IF NOT EXISTS idx_renewal_notifications_sent_at ON renewal_notifications(sent_at)"
    ];
    
    foreach ($notification_indexes as $query) {
        if ($conn->query($query)) {
            echo "<p>✅ Notification index created successfully</p>";
        } else {
            echo "<p>⚠️ Notification index might already exist: " . $conn->error . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Renewal System Setup Complete!</h3>";
    
    // Show some statistics
    $stats_query = "SELECT 
        COUNT(*) as total_apps,
        SUM(CASE WHEN renewal_status = 'active' THEN 1 ELSE 0 END) as active_apps,
        SUM(CASE WHEN renewal_status = 'expiring_soon' THEN 1 ELSE 0 END) as expiring_soon,
        SUM(CASE WHEN renewal_status = 'expired' THEN 1 ELSE 0 END) as expired_apps
        FROM applications 
        WHERE status IN ('approved', 'complete')";
    
    $result = $conn->query($stats_query);
    if ($result && $row = $result->fetch_assoc()) {
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4>Application Renewal Statistics:</h4>";
        echo "<ul>";
        echo "<li><strong>Total Applications:</strong> " . $row['total_apps'] . "</li>";
        echo "<li><strong>Active Applications:</strong> " . $row['active_apps'] . "</li>";
        echo "<li><strong>Expiring Soon (30 days):</strong> " . $row['expiring_soon'] . "</li>";
        echo "<li><strong>Expired Applications:</strong> " . $row['expired_apps'] . "</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>The renewal system is now active</li>";
    echo "<li>Applications will show renewal dates and status</li>";
    echo "<li>Users and admins can see when renewals are needed</li>";
    echo "<li>Check the applicant dashboard and admin dashboard for renewal information</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
