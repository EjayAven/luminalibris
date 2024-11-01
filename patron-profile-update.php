<?php
session_start();

// Check if the user is logged in and has the role of "Patron"
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'patron') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

// Get the current user's ID from the session
$user_id = $_SESSION['username'];

// Fetch form data
$lastname = $_POST['lastname'];
$firstname = $_POST['firstname'];
$midname = $_POST['midname'];
$address = $_POST['address'];
$birthdate = $_POST['birthdate'];
$contactnum = $_POST['contactnum'];
$emailaddress = $_POST['emailaddress'];

// Check if a file was uploaded and process it
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photo_tmp_path = $_FILES['photo']['tmp_name'];
    $photo_type = $_FILES['photo']['type'];
    $allowed_types = ['image/jpeg', 'image/png'];

    // Check if the file type is valid
    if (in_array($photo_type, $allowed_types)) {
        // Read the file contents
        $photo_data = file_get_contents($photo_tmp_path);

        // Check if the file contents were read correctly
        if ($photo_data === false) {
            echo "<script>alert('Error reading the file contents.');</script>";
        } else {
            echo "<script>alert('File read successfully.');</script>";

            // Prepare and execute the SQL query to update the profile with the new photo
            $stmt = $conn->prepare("UPDATE accounts SET photo = ?, lastname = ?, firstname = ?, midname = ?, address = ?, birthdate = ?, contactnum = ?, emailaddress = ? WHERE id = ?");
            $stmt->bind_param("sssssssss", $photo_data, $lastname, $firstname, $midname, $address, $birthdate, $contactnum, $emailaddress, $user_id);

            if ($stmt->execute()) {
                echo "<script>alert('Profile updated successfully with photo!'); window.location.href='patron-profile.php';</script>";
            } else {
                echo "<script>alert('Error updating the profile in the database.');</script>";
            }

            $stmt->close();
        }
    } else {
        echo "<script>alert('Invalid file type. Please upload a JPEG or PNG image.');</script>";
    }
} else {
    // No file was uploaded, update the profile without changing the photo
    echo "<script>alert('No photo uploaded or an error occurred during upload.');</script>";

    $stmt = $conn->prepare("UPDATE accounts SET lastname = ?, firstname = ?, midname = ?, address = ?, birthdate = ?, contactnum = ?, emailaddress = ? WHERE id = ?");
    $stmt->bind_param("ssssssss", $lastname, $firstname, $midname, $address, $birthdate, $contactnum, $emailaddress, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('Profile updated without photo.'); window.location.href='patron-profile.php';</script>";
    } else {
        echo "<script>alert('Error updating the profile without photo.');</script>";
    }

    $stmt->close();
}

$conn->close();
?>
