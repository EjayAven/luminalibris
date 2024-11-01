<?php
session_start(); 

// Check if the user is logged in and has the role of "Patron"
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'patron') {
    header("Location: login.html"); // Redirect to login page if not logged in or not a Patron
    exit();
}

include 'db_connection.php'; // Include your database connection file

// Fetch the logged-in user's account details (Patron)
$user_id = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id, lastname, firstname, midname, address, role, status, birthdate, contactnum, emailaddress, photo, type, password FROM accounts WHERE id = ? AND role = 'Patron'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    
    // Prepare the photo for display
    if (!empty($user_data['photo'])) {
        $photo_base64 = base64_encode($user_data['photo']);
        $photo_src_form = 'data:image/jpeg;base64,' . $photo_base64; // Assuming JPEG, adjust to PNG if necessary
    } else {
        $photo_src_form = 'default-profile-pic.jpg'; // Default image if no photo exists
    }
} else {
    echo "<script>alert('Account not found.'); window.location.href='login-page.php';</script>";
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patron Account - Lumina Libris</title>
    <link rel="stylesheet" href="style.css">
    <link href='Poppins.css' rel='stylesheet'>
    <script src="logout.js"></script>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="profile">
            <img src="<?php echo htmlspecialchars($photo_src_form); ?>" alt="Profile Picture" class="profile-pic">
            <div class="profile-info">
                <div class="name"><?php echo htmlspecialchars($user_data['firstname'] . " " . $user_data['lastname']); ?></div>
                <hr>
                <div class="role"><?php echo htmlspecialchars($user_data['role']); ?></div>
            </div>
        </div>
        <div class="menu">
            <a href="patron-dashboard.php" class="menu-item active">
                <img src="dashboard-icon.png" alt="Dashboard Icon" class="menu-icon">
                <span class="menu-text">Dashboard</span>
            </a>
            <div class="menu-item">
                <a href="patron-catalog.php">
                <img src="catalog-icon.png" alt="Catalog Icon" class="menu-icon">
                <span class="menu-text">Catalog</span>
            </div>
            <div class="menu-item">
                <a href="patron-profile.php">
                <img src="profile-icon.ico" alt="Profile Icon" class="menu-icon">
                <span class="menu-text active">Account</span>
                <div class="submenu">             
                    <a href="patron-profile.php"></a>
                    <span class="menu-text active">Profile</span>
                    <hr>
                    <a href="patron-activity.php">Activity</a>
                </div>
                </div>            
        </div>
        <div class="logout">
            <img src="exit-icon.png" alt="Exit Icon" class="exit-icon" id="logoutButton">
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <a href="javascript:history.back()">
                <img src="back-outline.png" alt="Back" class="header-logo">
            </a>
            <img src="lumina-blue.png" alt="Logo" class="header-logo">
        </div>

        <div class="header">
            <span>&emsp;&emsp;&emsp;Account Details</span>
        </div>
        <div class="separator"></div>

        <!-- Account Form -->
        <form id="user-form" action="patron-profile-update.php" method="POST" enctype="multipart/form-data">
            <div class="form-columns">
                <div class="form-column left-column">
                    <!-- Photo Preview -->
                    <div class="photo-box">
                        <img id="photo-preview" src="<?php echo htmlspecialchars($photo_src_form); ?>" alt="Photo Preview" class="profile-img">
                    </div>
                    <!-- Upload Photo Input -->
                    <div id="photo-upload-container">
                        <label for="photo-upload" class="photo-upload">Upload Photo</label>
                        <input type="file" id="photo-upload" name="photo" accept="image/*" class="upload-photo-input">
                    </div>

                    
                    <!-- Editable Personal Details -->
                    <br>
                    <div class="input-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="lastname" value="<?php echo htmlspecialchars($user_data['lastname']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="firstname" value="<?php echo htmlspecialchars($user_data['firstname']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="midname" value="<?php echo htmlspecialchars($user_data['midname']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user_data['address']); ?>">
                    </div>
                </div>

                <div class="form-column middle-column">
                    <!-- Non-editable Account Details -->
                    <div class="input-group">
                        <label for="user-id">User ID</label>
                        <input type="text" id="user-id" name="id" value="<?php echo htmlspecialchars($user_data['id']); ?>" readonly>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" value="********" readonly>
                    </div>
                    <div class="input-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" disabled>
                            <option value="Active" <?php if ($user_data['status'] == 'Active') echo 'selected'; ?>>Active</option>
                            <option value="Inactive" <?php if ($user_data['status'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Editable Contact Number and Birthdate -->
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
                        <input type="email" id="email" name="emailaddress" value="<?php echo htmlspecialchars($user_data['emailaddress']); ?>">
                    </div>
                    <div class="input-group">
                        <label for="type">Type</label>
                        <input type="text" id="type" name="type" value="<?php echo htmlspecialchars($user_data['type']); ?>" readonly>
                    </div>
                </div>
            </div>
            <button type="submit" class="update-button">Update Profile</button>
        </form>
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
</body>
</html>
