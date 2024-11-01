<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json'); // To return JSON data

$response = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get user inputs
    $id = $_POST['id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Check if new passwords match
    if ($new_password !== $confirm_new_password) {
        $response['error'] = 'New passwords do not match!';
        echo json_encode($response);
        exit();
    }

    // Fetch the current password from the database
    $stmt = $conn->prepare("SELECT password FROM accounts WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['error'] = 'User not found!';
        echo json_encode($response);
        exit();
    }

    $row = $result->fetch_assoc();
    $db_password = $row['password'];
    $stmt->close();

    // Check if the current password is correct
    if ($current_password !== $db_password) {
        $response['error'] = 'Current password is incorrect!';
        echo json_encode($response);
        exit();
    }

    // Update the password
    $stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE id = ?");
    $stmt->bind_param("ss", $new_password, $id);

    if ($stmt->execute()) {
        $response['success'] = 'Password changed successfully!';
    } else {
        $response['error'] = 'Error updating password: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    echo json_encode($response); // Return the response as JSON
}
?>
