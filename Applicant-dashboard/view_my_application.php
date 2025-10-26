<?php
// Page-specific variables
$page_title = 'View Application';
$current_page = 'dashboard'; // Keep the dashboard sidebar item active

// Include Header
require_once __DIR__ . '/applicant_header.php';

$application = null;

if (isset($_GET['id'])) {
    $applicationId = (int)$_GET['id'];
    
    // Fetch application details, ensuring it belongs to the current user for security
    $stmt = $conn->prepare("SELECT * FROM applications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $applicationId, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $application = $result->fetch_assoc();
    }
    $stmt->close();

    // Fetch existing documents for this application
    $documents = [];
    if ($application) {
        $docs_stmt = $conn->prepare("SELECT * FROM documents WHERE application_id = ?");
        $docs_stmt->bind_param("i", $applicationId);
        $docs_stmt->execute();
        $docs_result = $docs_stmt->get_result();
        $documents = $docs_result->fetch_all(MYSQLI_ASSOC);
        $docs_stmt->close();
    }

}

// Include Sidebar
require_once __DIR__ . '/applicant_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <header class="header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="applicant_dashboard.php" class="btn" style="padding: 8px 12px;"><i class="fas fa-arrow-left"></i> Back</a>
            <h1>Application Details</h1>
        </div>
    </header>

    <?php if ($application): ?>
        <?php 
        // Parse form details JSON
        $form_details = json_decode($application['form_details'], true) ?? [];
        $status_class = strtolower(preg_replace('/[^a-z]/', '', $application['status']));
        ?>

        <?php // --- Permit Status Banner Logic --- ?>
        <?php if (!is_null($application['permit_released_at'])): ?>
            <div class="permit-ready-banner">
                <div class="icon">
                    <i class="fas fa-award"></i>
                </div>
                <div class="text">
                    <h4>Congratulations! Your Permit is Ready.</h4>
                    <p>Your business permit has been released and is available for printing.</p>
                </div>
                <a href="print_permit.php?id=<?= $application['id'] ?>" class="btn btn-success" target="_blank">
                    <i class="fas fa-print"></i> Print Your Permit
                </a>
            </div>
        <?php elseif (in_array($application['status'], ['approved', 'complete'])): ?>
            <div class="message info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <h4>Your application has been approved!</h4>
                    <p>A staff member is preparing your official business permit. You will receive a notification here once it has been released.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-container view-only">
            <!-- Section I: APPLICANT SECTION -->
            <div class="form-section">
                <h2>I. APPLICANT SECTION</h2>

                <!-- 1. BASIC INFORMATION -->
                <section>
                    <h3>1. BASIC INFORMATION</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Application Type:</label>
                            <div class="radio-options">
                                <input type="radio" id="new" name="application_type" value="New" <?= ($form_details['application_type'] ?? '') === 'New' ? 'checked' : '' ?> disabled> 
                                <label for="new">New</label>
                                <input type="radio" id="renewal" name="application_type" value="Renewal" <?= ($form_details['application_type'] ?? '') === 'Renewal' ? 'checked' : '' ?> disabled> 
                                <label for="renewal">Renewal</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mode of Payment:</label>
                            <div class="radio-options">
                                <input type="radio" id="annually" name="mode_of_payment" value="Annually" <?= ($form_details['mode_of_payment'] ?? '') === 'Annually' ? 'checked' : '' ?> disabled> 
                                <label for="annually">Annually</label>
                                <input type="radio" id="semi-annually" name="mode_of_payment" value="Semi-Annually" <?= ($form_details['mode_of_payment'] ?? '') === 'Semi-Annually' ? 'checked' : '' ?> disabled> 
                                <label for="semi-annually">Semi-Annually</label>
                                <input type="radio" id="quarterly" name="mode_of_payment" value="Quarterly" <?= ($form_details['mode_of_payment'] ?? '') === 'Quarterly' ? 'checked' : '' ?> disabled> 
                                <label for="quarterly">Quarterly</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_application">Date of Application:</label>
                            <input type="date" id="date_of_application" name="date_of_application" value="<?= htmlspecialchars($form_details['date_of_application'] ?? date('Y-m-d')) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="tin_no">TIN No.:</label>
                            <input type="text" id="tin_no" name="tin_no" value="<?= htmlspecialchars($form_details['tin_no'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="dti_reg_no">DTI/SCC/CDA Registration No.:</label>
                            <input type="text" id="dti_reg_no" name="dti_reg_no" value="<?= htmlspecialchars($form_details['dti_reg_no'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="dti_reg_date">DTI/SCC/CDA Date of Registration:</label>
                            <input type="date" id="dti_reg_date" name="dti_reg_date" value="<?= htmlspecialchars($form_details['dti_reg_date'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="business_name">Business Name:</label>
                            <input type="text" id="business_name" name="business_name" value="<?= htmlspecialchars($application['business_name'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="trade_name">Trade Name/Franchise:</label>
                            <input type="text" id="trade_name" name="trade_name" value="<?= htmlspecialchars($form_details['trade_name'] ?? '') ?>" disabled>
                        </div>
                    </div>
                </section>

                <!-- 2. OTHER INFORMATION (Copied from submit_application.php) -->
                <section>
                    <h3>2. OTHER INFORMATION</h3>

                    <div class="form-row">
                        <div class="form-group" style="flex: 3;">
                            <label for="business_address">Business Address:</label>
                            <input type="text" id="business_address" name="business_address" value="<?= htmlspecialchars($application['business_address'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="b_postal_code">Postal Code:</label>
                            <input type="text" id="b_postal_code" name="b_postal_code" value="<?= htmlspecialchars($form_details['b_postal_code'] ?? '') ?>" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="b_email">Business Email Address:</label>
                            <input type="email" id="b_email" name="b_email" value="<?= htmlspecialchars($form_details['b_email'] ?? '') ?>" disabled>
                        </div>
                         <div class="form-group">
                            <label for="b_telephone">Business Telephone No:</label>
                            <input type="text" id="b_telephone" name="b_telephone" value="<?= htmlspecialchars($form_details['b_telephone'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="b_mobile">Business Mobile No.:</label>
                            <input type="text" id="b_mobile" name="b_mobile" value="<?= htmlspecialchars($form_details['b_mobile'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <h4>Taxpayer/Registrant Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($form_details['last_name'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($form_details['first_name'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="middle_name">Middle Name:</label>
                            <input type="text" id="middle_name" name="middle_name" value="<?= htmlspecialchars($form_details['middle_name'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 3;">
                            <label for="owner_address">Owner's Address:</label>
                            <input type="text" id="owner_address" name="owner_address" value="<?= htmlspecialchars($form_details['owner_address'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="o_postal_code">Postal Code:</label>
                            <input type="text" id="o_postal_code" name="o_postal_code" value="<?= htmlspecialchars($form_details['o_postal_code'] ?? '') ?>" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="o_email">Owner's Email Address:</label>
                            <input type="email" id="o_email" name="o_email" value="<?= htmlspecialchars($form_details['o_email'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="o_telephone">Owner's Telephone No:</label>
                            <input type="text" id="o_telephone" name="o_telephone" value="<?= htmlspecialchars($form_details['o_telephone'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="o_mobile">Owner's Mobile No.:</label>
                            <input type="text" id="o_mobile" name="o_mobile" value="<?= htmlspecialchars($form_details['o_mobile'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <h4>Emergency Contact</h4>
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="emergency_contact_name">Contact Person:</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?= htmlspecialchars($form_details['emergency_contact_name'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="emergency_tel">Contact Tel/Mobile No.:</label>
                            <input type="text" id="emergency_tel" name="emergency_tel" value="<?= htmlspecialchars($form_details['emergency_tel'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="emergency_email">Contact Email Address:</label>
                            <input type="email" id="emergency_email" name="emergency_email" value="<?= htmlspecialchars($form_details['emergency_email'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="business_area">Business Area (in sq m.):</label>
                            <input type="text" id="business_area" name="business_area" value="<?= htmlspecialchars($form_details['business_area'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="total_employees">Total No. of Employees:</label>
                            <input type="number" id="total_employees" name="total_employees" value="<?= htmlspecialchars($form_details['total_employees'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="lgu_employees">No. of Employees in LGU:</label>
                            <input type="number" id="lgu_employees" name="lgu_employees" value="<?= htmlspecialchars($form_details['lgu_employees'] ?? '') ?>" disabled>
                        </div>
                    </div>
                </section>

            </div>

            <!-- Section II: UPLOADED DOCUMENTS -->
            <div class="form-section">
                <h2>II. UPLOADED DOCUMENTS</h2>
                <div class="document-list">
                    <?php if (empty($documents)): ?>
                        <p>No documents have been uploaded for this application.</p>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-item">
                                <div class="doc-preview">
                                    <?php
                                    $file_extension = strtolower(pathinfo($doc['document_name'], PATHINFO_EXTENSION));
                                    $file_path = '../uploads/' . htmlspecialchars($doc['file_path']);
                                    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])):
                                    ?>
                                        <a href="<?= $file_path ?>" target="_blank"><img src="<?= $file_path ?>" alt="<?= htmlspecialchars($doc['document_name']) ?>"></a>
                                    <?php elseif ($file_extension === 'pdf'): ?>
                                        <a href="<?= $file_path ?>" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                    <?php else: ?>
                                        <a href="<?= $file_path ?>" target="_blank"><i class="fas fa-file-alt"></i></a>
                                    <?php endif; ?>
                                </div>
                                <p title="<?= htmlspecialchars($doc['document_name']) ?>"><?= htmlspecialchars($doc['document_name']) ?></p>
                                <a href="<?= $file_path ?>" class="btn btn-secondary" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section III: Application Status & Actions -->
            <div class="form-section">
                <h2>III. Application Status & Actions</h2>
                <div class="status-details-grid">
                    <div class="status-item">
                        <label>Current Status</label>
                        <p><span class="status-badge status-<?= $status_class ?>"><?= ucfirst($application['status']) ?></span></p>
                    </div>
                    <div class="status-item">
                        <label>Submitted On</label>
                        <p><?= date('M d, Y, h:i A', strtotime($application['submitted_at'])) ?></p>
                    </div>
                    <?php if ($application['updated_at']): ?>
                    <div class="status-item">
                        <label>Last Updated</label>
                        <p><?= date('M d, Y, h:i A', strtotime($application['updated_at'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="action-buttons">
                    <?php // Allow editing unless the permit has been officially released. ?>
                    <?php if (is_null($application['permit_released_at'])): ?>
                        <a href="edit_application.php?id=<?= $application['id'] ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit Application</a>
                    <?php else: ?>
                        <p>This application is complete and can no longer be edited.</p>
                    <?php endif; ?>
                    <a href="applicant_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container"><div class="no-results-message"><i class="fas fa-exclamation-triangle"></i><div>Application not found or you do not have permission to view it.</div></div></div>
    <?php endif; ?>
</div>

<style>
/* --- Banner Styles --- */
.permit-ready-banner {
    background-color: #eafaf1;
    color: #155724;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
    display: flex;
    align-items: center;
    gap: 20px;
}
.permit-ready-banner .icon {
    font-size: 2rem;
    color: #2ecc71;
}
.permit-ready-banner .text {
    flex-grow: 1;
}
.permit-ready-banner h4 {
    margin: 0 0 5px;
    font-size: 1.2rem;
}
.permit-ready-banner p {
    margin: 0;
}
.permit-ready-banner .btn {
    flex-shrink: 0;
}

/* --- Message Box Styles --- */
.message {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    display: flex;
    align-items: center;
    gap: 15px;
}
.message i {
    font-size: 1.5rem;
}
.message h4 {
    margin: 0 0 5px;
    font-size: 1.1rem;
}
.message p {
    margin: 0;
    line-height: 1.5;
}
.message.info {
    background-color: #e3f2fd;
    color: #0d6efd;
    border-color: #b6d4fe;
}

/* --- Action Button Styles --- */
.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-edit {
    background: #4a69bd;
    color: #fff;
}

.btn-edit:hover {
    background: #3b5699;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-success {
    background: #28a745;
    color: #fff;
}
.btn-success:hover {
    background: #218838;
    transform: translateY(-1px);
}
/* --- Form Styles (Copied from submit_application.php) --- */
.form-container {
    max-width: 1100px;
    margin: auto;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 86, 179, 0.1);
    border: 2px solid #e2e8f0;
}

.form-section {
    border: 2px solid #e2e8f0;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 4px 12px rgba(0, 86, 179, 0.05);
}

.form-section h2 {
    color: #1e3a8a;
    border-bottom: 3px solid #3b82f6;
    padding-bottom: 10px;
    margin-bottom: 25px;
    font-size: 1.4rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
}

.form-section h3 {
    color: #1e40af;
    border-bottom: 2px solid #3b82f6;
    padding-bottom: 8px;
    margin-top: 30px;
    margin-bottom: 20px;
    font-size: 1.2rem;
    font-weight: 600;
}

.form-section h4 {
    color: #374151;
    margin-top: 25px;
    margin-bottom: 15px;
    font-size: 1.1rem;
    font-weight: 600;
    padding-left: 15px;
    border-left: 3px solid #3b82f6;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, transparent 100%);
    padding: 10px 15px;
    border-radius: 0 8px 8px 0;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 15px;
    gap: 20px;
}

.form-group {
    flex: 1;
    min-width: 250px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.view-only input[type="text"],
.view-only input[type="email"],
.view-only input[type="date"],
.view-only input[type="number"],
.view-only textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    box-sizing: border-box;
    font-size: 14px;
    background: #f8fafc;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    cursor: default;
}

.radio-options {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 5px;
}

.radio-options input[type="radio"] {
    margin-right: 5px;
}

.radio-options label {
    font-weight: normal;
    display: inline-block;
    margin-right: 15px;
}

/* Status Details */
.status-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}
.status-item label {
    font-weight: 600;
    color: #2965b8ff;
    font-size: 0.9rem;
}
p {
    color: #131313ff;
}
/* --- Document List Styles --- */
.document-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
}
.document-item {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    text-align: center;
    padding: 12px;
    transition: all 0.2s ease;
    background: #fff;
}
.document-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-3px);
}
.document-item .doc-preview {
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 10px;
}
.document-item .doc-preview img { max-height: 100%; max-width: 100%; object-fit: cover; border-radius: 4px; }
.document-item .doc-preview i { font-size: 2.5rem; color: #64748b; }
.document-item p { font-weight: 500; font-size: 0.8rem; margin-bottom: 10px; word-break: break-all; line-height: 1.3; }
.document-item .btn { padding: 6px 12px; font-size: 0.8rem; }

@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<?php
// Include Footer
require_once __DIR__ . '/applicant_footer.php';
?>