<?php
session_start();
include 'db_connection.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        // Record deleted successfully
        $_SESSION['delete_success'] = true; // Set session variable
    } else {
        // Error deleting record
        $_SESSION['delete_success'] = false; // Set session variable
    }

    $stmt->close();
} else {
    $_SESSION['delete_success'] = false; // Set session variable
}

$conn->close();

// Redirect back to manage-account.php
header("Location: manage-account.php");
exit();
?>
