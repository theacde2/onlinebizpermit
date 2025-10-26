<?php
// Create test data for renewal system
require_once 'Applicant-dashboard/db.php';

echo "<h2>Creating Renewal Test Data</h2>";

// Update some existing applications to have different renewal dates for testing
$test_updates = [
    // Expired application (1 year ago)
    [
        'renewal_date' => date('Y-m-d', strtotime('-1 year')),
        'renewal_status' => 'expired',
        'description' => 'Expired 1 year ago'
    ],
    // Expiring soon (15 days from now)
    [
        'renewal_date' => date('Y-m-d', strtotime('+15 days')),
        'renewal_status' => 'expiring_soon',
        'description' => 'Expiring in 15 days'
    ],
    // Expiring soon (5 days from now)
    [
        'renewal_date' => date('Y-m-d', strtotime('+5 days')),
        'renewal_status' => 'expiring_soon',
        'description' => 'Expiring in 5 days'
    ],
    // expiring soon (30 days)
    [
        'renewal_date' => date('Y-m-d', strtotime('30 days')),
        'renewal_status' => 'expiring_soon',
        'description' => 'Expiring in 30 days'
    ]
];

// Get some approved applications to update
$stmt = $conn->prepare("SELECT id, business_name FROM applications WHERE status IN ('approved', 'complete') LIMIT 4");
$stmt->execute();
$result = $stmt->get_result();
$applications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (count($applications) >= 4) {
    echo "<p>Updating applications with test renewal dates...</p>";
    
    for ($i = 0; $i < min(4, count($applications)); $i++) {
        $app = $applications[$i];
        $update = $test_updates[$i];
        
        $stmt = $conn->prepare("UPDATE applications SET renewal_date = ?, renewal_status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $update['renewal_date'], $update['renewal_status'], $app['id']);
        
        if ($stmt->execute()) {
            echo "<p>✅ Updated '{$app['business_name']}' - {$update['description']}</p>";
        } else {
            echo "<p>❌ Error updating application: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
} else {
    echo "<p>⚠️ Not enough approved applications found. Please create some test applications first.</p>";
}

// Show current renewal statistics
echo "<hr>";
echo "<h3>Current Renewal Statistics:</h3>";

$stats_query = "SELECT 
    renewal_status,
    COUNT(*) as count
    FROM applications 
    WHERE status IN ('approved', 'complete') 
    GROUP BY renewal_status";

$result = $conn->query($stats_query);

if ($result) {
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>Renewal Status Breakdown:</h4>";
    echo "<ul>";
    
    while ($row = $result->fetch_assoc()) {
        $status = ucfirst(str_replace('_', ' ', $row['renewal_status']));
        $count = $row['count'];
        echo "<li><strong>$status:</strong> $count applications</li>";
    }
    
    echo "</ul>";
    echo "</div>";
}

echo "<h3>Test the Renewal System:</h3>";
echo "<ol>";
echo "<li>Go to <a href='Applicant-dashboard/applicant_dashboard.php' target='_blank'>Applicant Dashboard</a> to see renewal alerts and information</li>";
echo "<li>Go to <a href='Admin-dashboard/dashboard.php' target='_blank'>Admin Dashboard</a> to see renewal alerts</li>";
echo "<li>Check the applications table for renewal dates and status indicators</li>";
echo "<li>Look for 'Renew' buttons on expiring/expired applications</li>";
echo "</ol>";

echo "<h3>What You Should See:</h3>";
echo "<ul>";
echo "<li><strong>Expired Applications:</strong> Red alerts and 'Expired' status</li>";
echo "<li><strong>Expiring Soon:</strong> Yellow alerts and countdown days</li>";
echo "<li><strong>Active Applications:</strong> Green status indicators</li>";
echo "<li><strong>Renewal Buttons:</strong> On applications that need renewal</li>";
echo "</ul>";

$conn->close();
?>
