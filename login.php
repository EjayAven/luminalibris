<?php
session_start(); // Start the session

include 'db_connection.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the submitted username and password
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Prepare and execute the SQL statement
    $sql = "SELECT * FROM accounts WHERE id = '$username'";
    $result = $conn->query($sql);

    // Check if a user exists with the given username
    if ($result->num_rows > 0) {
        // Fetch the user data
        $row = $result->fetch_assoc();

        // Verify the password
        if ($password === $row['password']) {
            // Password is correct, set session variables
            $_SESSION['username'] = $username;
            $_SESSION['role'] = strtolower($row['role']); // Convert role to lowercase

            // Redirect based on the user's role
            if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'administrator') {
                header("Location: admin-dashboard.php");
            } elseif ($_SESSION['role'] === 'librarian') {
                header("Location: librarian-dashboard.php");
            } elseif ($_SESSION['role'] === 'patron') {
                header("Location: patron-dashboard.php");
            } else {
                header("Location: login-page.php?message=User%20role%20is%20not%20defined:%20" . urlencode($row['role']) . "&message_type=error");
            }
            exit();
        } else {
            // Incorrect password
            header("Location: login-page.php?message=Invalid%20password.%20Please%20try%20again.&message_type=error");
        }
    } else {
        // UserID not found
        header("Location: login-page.php?message=No%20user%20found.&message_type=error");
    }
}

$conn->close();
?>
