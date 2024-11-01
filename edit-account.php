<?php
session_start(); 

// Check if the user is logged in and has an admin role
if (!isset($_SESSION['username']) || !in_array(strtolower($_SESSION['role']), ['admin', 'administrator'])) {
    header("Location: login-page.php"); // Redirect to login page if not logged in or not an admin
    exit();
}

include 'db_connection.php';

// Fetch the logged-in user's photo for the navbar
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
        $photo_src_navbar = 'data:' . $photo_mime . ';base64,' . $photo_base64;
    } else {
        $photo_src_navbar = 'default-profile-pic.jpg'; 
    }
} else {
    $fullname = "Unknown User";
    $role = "Unknown Role";
    $photo_src_navbar = 'default-profile-pic.jpg'; 
}
$stmt->close();

// Fetch the data for the user being edited
if (isset($_GET['id'])) {
    $edit_user_id = $_GET['id'];

    // Fetch user data from the database
    $stmt = $conn->prepare("SELECT id, lastname, firstname, midname, address, role, status, birthdate, contactnum, emailaddress, photo, type FROM accounts WHERE id = ?");
    $stmt->bind_param("s", $edit_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        // Prepare the photo for display
        if (!empty($user_data['photo'])) {
            $photo_base64 = base64_encode($user_data['photo']);
            $photo_mime = 'image/jpeg'; // Update MIME type based on your image storage format
            $photo_src_form = 'data:' . $photo_mime . ';base64,' . $photo_base64;
        } else {
            $photo_src_form = 'default-profile-pic.jpg'; // Default image if no photo exists
        }
    } else {
        echo "<script>alert('User not found.'); window.location.href='manage-account.php';</script>";
        exit();
    }

    $stmt->close();
} else {
    echo "<script>alert('No user ID specified.'); window.location.href='manage-account.php';</script>";
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Accounts- Lumina Libris</title>
    <link rel="stylesheet" href="style-edit-account.css">
    <link href='Poppins.css' rel='stylesheet'>
    <script src="logout.js"></script>
</head>
<body>
    <div class="navbar">
        <div class="profile">
            <img src="<?php echo htmlspecialchars($photo_src_navbar); ?>" alt="Profile Picture" class="profile-pic">
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
                    <a href="manage-account.php" class="submenu-item active">Manage</a> 
                    <hr>
                    <a href="add-account.php" class="submenu-item">Add</a>
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
            <span>&emsp;&emsp;&emsp;Edit Account</span>
        </div>
        
        <div class="separator"></div>

        <form id="user-form" action="update-account.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="form-columns">
                <div class="form-column left-column">
                    <!-- Photo Preview and Upload -->
                    <div class="photo-box">
                        <img id="photo-preview" src="<?php echo htmlspecialchars($photo_src_form); ?>" alt="Photo Preview" >
                    </div>
                    <!-- Custom upload button -->
                    <div id="photo-upload-container">
                        <!-- Hidden file input -->
                        <input type="file" id="photo-upload" name="photo" accept="image/*" onchange="previewImage(event)" style="display: none;">
                            
                        <!-- Label acting as the custom button -->
                        <label for="photo-upload" class="upload-button"><i><u>Upload Photo</u></i></label>
                    </div> 
                    <!-- Input Fields for Personal Details -->
                    <div class="input-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="lastname" required value="<?php echo htmlspecialchars($user_data['lastname']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="firstname" required value="<?php echo htmlspecialchars($user_data['firstname']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="midname" required value="<?php echo htmlspecialchars($user_data['midname']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($user_data['address']); ?>">
                    </div>
                </div>

                <div class="form-column middle-column" style="flex-grow: 1;">
                    <!-- Input Fields for Account Details -->
                    <div class="input-group">
                        <label for="user-id">User ID</label>
                        <input type="text" id="user-id" name="id" required value="<?php echo htmlspecialchars($user_data['id']); ?>" readonly>
                    </div>
                    <div class="input-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required onchange="toggleType()">
                            <option value="Administrator" <?php if ($user_data['role'] == 'Administrator') echo 'selected'; ?>>Administrator</option>
                            <option value="Librarian" <?php if ($user_data['role'] == 'Librarian') echo 'selected'; ?>>Librarian</option>
                            <option value="Patron" <?php if ($user_data['role'] == 'Patron') echo 'selected'; ?>>Patron</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="Active" <?php if ($user_data['status'] == 'Active') echo 'selected'; ?>>Active</option>
                            <option value="Inactive" <?php if ($user_data['status'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="birthdate">Birthdate</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($user_data['birthdate']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="contact-number">Contact Number</label>
                        <input type="tel" id="contact-number" name="contactnum" value="<?php echo htmlspecialchars($user_data['contactnum']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="emailaddress" required value="<?php echo htmlspecialchars($user_data['emailaddress']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" value="********" readonly>
                        <button type="button" id="changePasswordButton" onclick="openChangePasswordModal()">Change Password</button>
                    </div>
                </div>

                <div class="form-column right-column">
                    <!-- Additional Field for Type -->
                    <div class="input-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" disabled>
                            <option value="Student" <?php if ($user_data['type'] == 'Student') echo 'selected'; ?>>Student</option>
                            <option value="Visitor" <?php if ($user_data['type'] == 'Visitor') echo 'selected'; ?>>Visitor</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="form-buttons">
                <button type="submit" id="addAccount">Save</button>
                <button type="button" id="cancelAdd" onclick="window.location.href='manage-account.php'">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content" id="changePass">
            <form id="change-password-form" action="change-password.php" method="POST">
                <span class="close-button" id="closeButton">&times;</span>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_user_id); ?>">
                <div class="input-group">
                    <label for="current-password"><br>Current Password</label>
                    <input type="password" id="current-password" name="current_password" required>
                </div>
                <div class="input-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new_password" required>
                </div>
                <div class="input-group">
                    <label for="confirm-new-password">Confirm New Password</label>
                    <input type="password" id="confirm-new-password" name="confirm_new_password" required>
                </div>
                <div id="alert-message" style="color: red; display: none;"></div> <!-- Placeholder for alerts -->
                <div class="changePass-buttons">
                    <button type="submit" id="savePassword">Save</button>
                    <button type="button" id="cancelChangePass" onclick="closeChangePasswordModal()">Cancel</button>
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

    <script>
    document.getElementById('change-password-form').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        // Prepare form data
        var formData = new FormData(this);

        // Perform AJAX request
        fetch('change-password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const alertMessage = document.getElementById('alert-message');
            const currentPasswordInput = document.getElementById('current-password');
            const newPasswordInput = document.getElementById('new-password');
            const confirmNewPasswordInput = document.getElementById('confirm-new-password');

            if (data.error) {
                // Display error on the modal
                alertMessage.style.display = 'block';
                alertMessage.innerText = data.error;
                alertMessage.style.color = 'red';

                // Clear the input fields based on the error type
                if (data.error === 'New passwords do not match!' || data.error === 'Current password is incorrect!') {
                    // Clear password inputs
                    currentPasswordInput.value = '';
                    newPasswordInput.value = '';
                    confirmNewPasswordInput.value = '';
                }
            } else if (data.success) {
                // Display success message on the modal
                alertMessage.style.display = 'block';
                alertMessage.style.color = 'green';
                alertMessage.innerText = data.success;

                // Optionally close the modal after a timeout
                setTimeout(() => {
                    alertMessage.style.display = 'none';
                    closeChangePasswordModal(); // Close the modal
                }, 2000); // Delay to show the message
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
    </script>
    <script src="account-form.js"></script>   
</body>
</html>
