<?php
session_start(); 

// Check if the user is logged in and has an admin role
if (!isset($_SESSION['username']) || !in_array(strtolower($_SESSION['role']), ['admin', 'administrator'])) {
    header("Location: login-page.php"); // Redirect to login page if not logged in or not an admin
    exit();
}

include 'db_connection.php';

$user_id = $_SESSION['username'];

$stmt = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) AS fullname, role, photo FROM accounts WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullname = $row['fullname'];
    $role = $row['role'];
    $photo = $row['photo'];
    if ($photo) {
        $photo_base64 = base64_encode($photo);
        $photo_mime = 'image/jpeg'; 
        $photo_src = 'data:' . $photo_mime . ';base64,' . $photo_base64;
    } else {
        $photo_src = 'default-profile-pic.jpg'; 
    }
} else {
    $fullname = "Unknown User";
    $role = "Unknown Role";
    $photo_src = 'default-profile-pic.jpg'; 
}
$stmt->close(); 

// Handle form submission
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];  // New User ID field
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $midname = $_POST['midname'];
    $address = $_POST['address'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $birthdate = $_POST['birthdate'] ?: NULL;
    $contactnum = $_POST['contactnum'] ?: NULL;
    $emailaddress = $_POST['emailaddress'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $type = empty($_POST['type']) ? NULL : $_POST['type']; // Set to NULL if empty


    // Validate form input and handle file upload
    if ($password !== $confirm_password) {
        $error_message = 'Passwords do not match!';
    } else {
        // File upload handling
        if (isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
            if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['photo']['size'] > 65535) {
                    $error_message = 'File size must be less than 64 KB.';
                } else {
                    $photo = file_get_contents($_FILES['photo']['tmp_name']);
                    if ($photo === false) {
                        $error_message = 'Failed to read file.';
                    }
                }
            } else {
                $error_message = 'File upload error: ' . $_FILES['photo']['error'];
            }
        } else {
            $photo = NULL;
        }

        if (!$error_message) {
            $stmt = $conn->prepare("INSERT INTO accounts (id, lastname, firstname, midname, address, role, status, birthdate, contactnum, emailaddress, password, type, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            // Bind parameters except for the photo (handled separately)
            $stmt->bind_param("ssssssssssssb", $id, $lastname, $firstname, $midname, $address, $role, $status, $birthdate, $contactnum, $emailaddress, $password, $type, $photo);

            // Bind the binary data for the 'photo' field
            if ($photo !== NULL) {
                $stmt->send_long_data(12, $photo); // Index 12 corresponds to the 13th parameter in 0-based index.
            }

            if ($stmt->execute()) {
                $_SESSION['add_success'] = true; // Set a session flag
                header('Location: manage-account.php');
                exit();
            } else {
                $error_message = 'Error: ' . $stmt->error;
            }

            $stmt->close();
        }
    }
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Account- Lumina Libris</title>
    <link rel="stylesheet" href="style.css">
    <link href='Poppins.css' rel='stylesheet'>
    <script src="logout.js"></script>
</head>
<body>
    <div class="navbar">
        <div class="profile">
            <img src="<?php echo htmlspecialchars($photo_src); ?>" alt="Profile Picture" class="profile-pic">
            <div class="profile-info">
                <div class="name"><?php echo htmlspecialchars($fullname); ?></div>
                <hr>
                <div class="role"><?php echo htmlspecialchars($role); ?></div>
            </div>
        </div>
        <div class="menu">
            <a href="admin-dashboard.php" class="menu-item">
                <img src="dashboard-icon.png" alt="Dashboard Icon" class="menu-icon">
                <span class="menu-text">Dashboard</span>
            </a>
            <div class="menu-item">
                <img src="accounts-icon.png" alt="Accounts Icon" class="menu-icon">
                <span class="menu-text active">Accounts</span>
                <!-- Submenu for Accounts -->
                <div class="submenu">
                    <a href="manage-account.php" class="submenu-item">Manage</a> 
                    <hr>
                    <a href="add-account.php" class="submenu-item active">Add</a>
                </div>
            </div>
            <div class="menu-item">
                <img src="reports-icon.png" alt="Reports Icon" class="menu-icon">
                <span class="menu-text">Reports</span>
                <!-- Submenu for Reports -->
                <div class="submenu">
                    <a href="#">Patron Record</a>
                    <hr>
                    <a href="#">Catalog</a>
                    <hr>
                    <a href="#">Circulation</a>
                    <hr>
                    <a href="#">Inventory</a>
                </div>
            </div>
        </div>
        <div class="logout">
            <img src="exit-icon.png" alt="Exit Icon" class="exit-icon" id="logoutButton">
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <a href="javascript:history.back()">
                <img src="back-outline.png" alt="Logo" class="header-logo">
            </a>
                <img src="lumina-blue.png" alt="Logo" class="header-logo"> 
        </div>
        <div class="header">
            <span>&emsp;&emsp;&emsp;Add Account</span>
        </div>
        
        <div class="separator"></div>

        <div class="form-container">
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form id="user-form" action="add-account.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="form-columns">
                    <div class="form-column left-column">
                        <div class="photo-box">
                            <img id="photo-preview" src="#" alt="Photo Preview">
                        </div>
                        <!-- Custom upload button -->
                        <div id="photo-upload-container">
                            <!-- Hidden file input -->
                            <input type="file" id="photo-upload" name="photo" accept="image/*" onchange="previewImage(event)" style="display: none;">
                            
                            <!-- Label acting as the custom button -->
                            <label for="photo-upload" class="upload-button"><i>Upload Photo</i></label>
                        </div>
                        <div class="input-group">
                            <label for="last-name">Last Name</label>
                            <input type="text" id="last-name" name="lastname" required value="<?php echo htmlspecialchars($lastname ?? ''); ?>">
                        </div>
                        <div class="input-group">
                            <label for="first-name">First Name</label>
                            <input type="text" id="first-name" name="firstname" required value="<?php echo htmlspecialchars($firstname ?? ''); ?>">
                        </div>
                        <div class="input-group">
                            <label for="middle-name">Middle Name</label>
                            <input type="text" id="middle-name" name="midname" required value="<?php echo htmlspecialchars($midname ?? ''); ?>">
                        </div>
                        <div class="input-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($address ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-column middle-column">
                        <div class="input-group">
                            <label for="user-id">User ID</label>
                            <input type="text" id="user-id" name="id" required value="<?php echo htmlspecialchars($userid ?? ''); ?>">
                        </div>
                        <div class="input-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required onchange="toggleType()">
                                <option value="Administrator">Administrator</option>
                                <option value="Librarian">Librarian</option>
                                <option value="Patron">Patron</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="birthdate">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate">
                        </div>
                        <div class="input-group">
                            <label for="contact-number">Contact Number</label>
                            <input type="tel" id="contact-number" name="contactnum">
                        </div>
                        <div class="input-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="emailaddress" required>
                        </div>
                        <div class="input-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="input-group">
                            <label for="confirm-password">Confirm Password</label>
                            <input type="password" id="confirm-password" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="form-column right-column">
                        <div class="input-group">
                            <label for="type">Type</label>
                            <select id="type" name="type" disabled>
                                <option value="Student">Student</option>
                                <option value="Visitor">Visitor</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" id="addAccount">Save</button>
                    <button type="button" id="cancelAdd" onclick="window.location.href='manage-account.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
   
    <!-- Logout Modal -->
    <div class="modal" id="logoutModal">
        <div class="modal-content">
            <span class="close-button" id="closeButton">&times;</span>
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to log out?</p>
            <button class="modal-button" id="confirmLogout">Logout</button>
            <button class="modal-button" id="cancelLogout">Cancel</button>
        </div>
    </div>
    
    <script src="account-form.js"></script>
</body>
</html>
