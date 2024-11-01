<?php
session_start();
include 'db_connection.php';

if (isset($_GET['referenceID'])) {
    $referenceID = $_GET['referenceID'];

    $stmt = $conn->prepare("DELETE FROM materials WHERE referenceID = ?");
    $stmt->bind_param("s", $referenceID);

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

// Redirect back to manage-material.php
header("Location: manage-material.php");
exit();
?>
