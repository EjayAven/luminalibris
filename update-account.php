<?php
session_start(); 

// Check if the user is logged in and has an admin role
if (!isset($_SESSION['username']) || !in_array(strtolower($_SESSION['role']), ['admin', 'administrator'])) {
    header("Location: login-page.php"); // Redirect to login page if not logged in or not an admin
    exit();
}

include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form values
    $id = $_POST['id'];
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $midname = $_POST['midname'];
    $address = $_POST['address'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $birthdate = empty($_POST['birthdate']) ? NULL : $_POST['birthdate']; // Set to NULL if empty
    $contactnum = empty($_POST['contactnum']) ? NULL : $_POST['contactnum']; // Set to NULL if empty
    $emailaddress = $_POST['emailaddress'];
    $type = empty($_POST['type']) ? NULL : $_POST['type']; // Set to NULL if empty

    // Handle file upload for the photo
    $photo = NULL;
    //if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
      //  $photo = file_get_contents($_FILES['photo']['tmp_name']); // Read file content as binary data
    //}
    if (isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
        if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['photo']['size'] <= 65535) { // Ensure file size is less than or equal to 64 KB
                $photo = file_get_contents($_FILES['photo']['tmp_name']);
                if ($photo === false) {
                    echo "<script>alert('Failed to read file.'); window.history.back();</script>";
                    exit();
                }
            } else {
                echo "<script>alert('File size must be less than 64 KB.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('File upload error: " . addslashes($_FILES['photo']['error']) . "'); window.history.back();</script>";
            exit();
        }
    }

    // Prepare the SQL statement for updating the account
    $sql = "UPDATE accounts 
            SET lastname = ?, firstname = ?, midname = ?, address = ?, role = ?, status = ?, birthdate = ?, contactnum = ?, emailaddress = ?, type = ?, photo = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    // Check if photo is provided
    if ($photo !== NULL) {
        // Bind parameters including photo
        $stmt->bind_param("ssssssssssbi", $lastname, $firstname, $midname, $address, $role, $status, $birthdate, $contactnum, $emailaddress, $type, $photo, $id);
        $stmt->send_long_data(10, $photo); // 10 is the index of the photo in the bind_param call (0-based index)
    } else {
        // Bind parameters without photo (setting photo to NULL)
        $stmt = $conn->prepare("
            UPDATE accounts 
            SET lastname = ?, firstname = ?, midname = ?, address = ?, role = ?, status = ?, birthdate = ?, contactnum = ?, emailaddress = ?, type = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssssssi", $lastname, $firstname, $midname, $address, $role, $status, $birthdate, $contactnum, $emailaddress, $type, $id);
    }

    // Execute the statement
    if ($stmt->execute()) {
        $_SESSION['update_success'] = true; // Set a session flag
        header('Location: manage-account.php');
        exit();    
    } else {
        echo "<script>alert('Error updating account: " . $stmt->error . "'); window.location.href='manage-account.php';</script>";
    }

    // Close the statement
    $stmt->close();
} else {
    echo "<script>alert('Invalid request method.'); window.location.href='manage-account.php';</script>";
}

// Close the connection
$conn->close();
?>


