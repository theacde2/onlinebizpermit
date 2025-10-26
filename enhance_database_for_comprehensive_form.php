<?php
// Database Enhancement Script for Comprehensive Business Permit Form
// This script ensures the database can handle all the new form fields properly

require_once __DIR__ . '/Applicant-dashboard/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Enhancement</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
<div class='container'>
<h1>Database Enhancement for Comprehensive Business Permit Form</h1>";

try {
    echo "<h2>Checking Current Database Structure</h2>";
    
    // Check if applications table exists and show its structure
    $result = $conn->query("DESCRIBE applications");
    if ($result) {
        echo "<h3>Current Applications Table Structure:</h3>";
        echo "<pre>";
        while ($row = $result->fetch_assoc()) {
            echo sprintf("%-20s %-20s %-10s %-10s %-10s %-10s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key'], 
                $row['Default'], 
                $row['Extra']
            );
        }
        echo "</pre>";
    }
    
    echo "<h2>Enhancement Operations</h2>";
    
    // 1. Ensure form_details column can handle large JSON data
    echo "<h3>1. Optimizing form_details column</h3>";
    $alter_form_details = "ALTER TABLE applications MODIFY COLUMN form_details LONGTEXT";
    if ($conn->query($alter_form_details)) {
        echo "<p class='success'>✅ form_details column optimized for large JSON data</p>";
    } else {
        echo "<p class='warning'>⚠️ form_details column might already be optimized: " . $conn->error . "</p>";
    }
    
    // 2. Add application_type column for better categorization
    echo "<h3>2. Adding application_type column</h3>";
    $add_app_type = "ALTER TABLE applications ADD COLUMN IF NOT EXISTS application_type ENUM('New', 'Renewal') DEFAULT 'New'";
    if ($conn->query($add_app_type)) {
        echo "<p class='success'>✅ application_type column added</p>";
    } else {
        echo "<p class='warning'>⚠️ application_type column might already exist: " . $conn->error . "</p>";
    }
    
    // 3. Add mode_of_payment column
    echo "<h3>3. Adding mode_of_payment column</h3>";
    $add_payment_mode = "ALTER TABLE applications ADD COLUMN IF NOT EXISTS mode_of_payment ENUM('Annually', 'Semi-Annually', 'Quarterly') DEFAULT 'Annually'";
    if ($conn->query($add_payment_mode)) {
        echo "<p class='success'>✅ mode_of_payment column added</p>";
    } else {
        echo "<p class='warning'>⚠️ mode_of_payment column might already exist: " . $conn->error . "</p>";
    }
    
    // 4. Add DTI registration number column
    echo "<h3>4. Adding DTI registration number column</h3>";
    $add_dti_reg = "ALTER TABLE applications ADD COLUMN IF NOT EXISTS dti_reg_no VARCHAR(50) NULL";
    if ($conn->query($add_dti_reg)) {
        echo "<p class='success'>✅ dti_reg_no column added</p>";
    } else {
        echo "<p class='warning'>⚠️ dti_reg_no column might already exist: " . $conn->error . "</p>";
    }
    
    // 5. Add TIN number column
    echo "<h3>5. Adding TIN number column</h3>";
    $add_tin = "ALTER TABLE applications ADD COLUMN IF NOT EXISTS tin_no VARCHAR(20) NULL";
    if ($conn->query($add_tin)) {
        echo "<p class='success'>✅ tin_no column added</p>";
    } else {
        echo "<p class='warning'>⚠️ tin_no column might already exist: " . $conn->error . "</p>";
    }
    
    // 6. Add indexes for better performance
    echo "<h3>6. Adding performance indexes</h3>";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_applications_application_type ON applications(application_type)",
        "CREATE INDEX IF NOT EXISTS idx_applications_mode_of_payment ON applications(mode_of_payment)",
        "CREATE INDEX IF NOT EXISTS idx_applications_dti_reg_no ON applications(dti_reg_no)",
        "CREATE INDEX IF NOT EXISTS idx_applications_tin_no ON applications(tin_no)"
    ];
    
    foreach ($indexes as $index_query) {
        if ($conn->query($index_query)) {
            echo "<p class='success'>✅ Index created successfully</p>";
        } else {
            echo "<p class='warning'>⚠️ Index might already exist: " . $conn->error . "</p>";
        }
    }
    
    // 7. Update existing applications to extract data from form_details JSON
    echo "<h3>7. Updating existing applications</h3>";
    $update_existing = "
        UPDATE applications 
        SET 
            application_type = CASE 
                WHEN JSON_EXTRACT(form_details, '$.application_type') IS NOT NULL 
                THEN JSON_UNQUOTE(JSON_EXTRACT(form_details, '$.application_type'))
                ELSE 'New'
            END,
            mode_of_payment = CASE 
                WHEN JSON_EXTRACT(form_details, '$.mode_of_payment') IS NOT NULL 
                THEN JSON_UNQUOTE(JSON_EXTRACT(form_details, '$.mode_of_payment'))
                ELSE 'Annually'
            END,
            dti_reg_no = CASE 
                WHEN JSON_EXTRACT(form_details, '$.dti_reg_no') IS NOT NULL 
                THEN JSON_UNQUOTE(JSON_EXTRACT(form_details, '$.dti_reg_no'))
                ELSE NULL
            END,
            tin_no = CASE 
                WHEN JSON_EXTRACT(form_details, '$.tin_no') IS NOT NULL 
                THEN JSON_UNQUOTE(JSON_EXTRACT(form_details, '$.tin_no'))
                ELSE NULL
            END
        WHERE form_details IS NOT NULL AND form_details != ''
    ";
    
    if ($conn->query($update_existing)) {
        $affected_rows = $conn->affected_rows;
        echo "<p class='success'>✅ Updated {$affected_rows} existing applications with extracted data</p>";
    } else {
        echo "<p class='error'>❌ Error updating existing applications: " . $conn->error . "</p>";
    }
    
    echo "<h2>Final Database Structure</h2>";
    $result = $conn->query("DESCRIBE applications");
    if ($result) {
        echo "<h3>Updated Applications Table Structure:</h3>";
        echo "<pre>";
        while ($row = $result->fetch_assoc()) {
            echo sprintf("%-20s %-20s %-10s %-10s %-10s %-10s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key'], 
                $row['Default'], 
                $row['Extra']
            );
        }
        echo "</pre>";
    }
    
    echo "<h2>Summary</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Database Enhancement Complete!</strong></p>";
    echo "<p>The database has been optimized to handle the comprehensive business permit form with the following improvements:</p>";
    echo "<ul>";
    echo "<li>✅ Enhanced form_details column for large JSON data storage</li>";
    echo "<li>✅ Added application_type column for New/Renewal categorization</li>";
    echo "<li>✅ Added mode_of_payment column for payment frequency tracking</li>";
    echo "<li>✅ Added dti_reg_no column for DTI registration number storage</li>";
    echo "<li>✅ Added tin_no column for TIN number storage</li>";
    echo "<li>✅ Added performance indexes for faster queries</li>";
    echo "<li>✅ Updated existing applications with extracted data</li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test the comprehensive form at: <a href='Applicant-dashboard/business_permit_form.php'>business_permit_form.php</a></li>";
    echo "<li>Verify data is being stored correctly in both individual columns and JSON format</li>";
    echo "<li>Test the form processing at: <a href='Applicant-dashboard/process_business_permit.php'>process_business_permit.php</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";
?>
