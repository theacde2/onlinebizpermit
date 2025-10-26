<?php
require_once 'staff_header.php';
require_once 'db.php';

if (!isset($_GET['application_id'])) {
    echo "Application ID is missing.";
    exit;
}

$application_id = $_GET['application_id'];
?>

<div class="container">
    <h2>Staff Section Form</h2>
    <form action="process_staff_form.php" method="post">
        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
        
        <div class="form-group">
            <label for="assessment_notes">Assessment Notes</label>
            <textarea class="form-control" id="assessment_notes" name="assessment_notes" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label for="verification_status">Verification Status</label>
            <select class="form-control" id="verification_status" name="verification_status">
                <option value="Pending">Pending</option>
                <option value="Verified">Verified</option>
                <option value="Rejected">Rejected</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Submit for LGU Section</button>
    </form>
</div>

<?php require_once '../Admin-dashboard/admin_footer.php'; ?>