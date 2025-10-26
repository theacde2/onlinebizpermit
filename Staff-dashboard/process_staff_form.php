<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = $_POST['application_id'];
    $form_data = json_encode($_POST);

    try {
        // Check if data for this application already exists
        $stmt = $conn->prepare("SELECT id FROM staff_form_data WHERE application_id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing data
            $stmt = $conn->prepare("UPDATE staff_form_data SET form_data = ? WHERE application_id = ?");
            $stmt->bind_param("si", $form_data, $application_id);
        } else {
            // Insert new data
            $stmt = $conn->prepare("INSERT INTO staff_form_data (application_id, form_data) VALUES (?, ?)");
            $stmt->bind_param("is", $application_id, $form_data);
        }

        if ($stmt->execute()) {
            // Redirect back to the application view page
            header("Location: view_application.php?id=" . $application_id);
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>