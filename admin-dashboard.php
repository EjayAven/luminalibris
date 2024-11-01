<?php
session_start(); 

// Check if the user is logged in and has an admin role
if (!isset($_SESSION['username']) || !in_array(strtolower($_SESSION['role']), ['admin', 'administrator'])) {
    header("Location: login-page.php"); // Redirect to login page if not logged in or not an admin
    exit();
}

include 'db_connection.php';

// The user ID is retrieved from the session
$user_id = $_SESSION['username'];

// Prepare and execute the statement to get user details
$stmt = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) AS fullname, role, photo FROM accounts WHERE id = ?");
$stmt->bind_param("s", $user_id); // "s" specifies the type of the parameter (string)
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullname = $row['fullname'];
    $role = $row['role'];
    
    // Handle the BLOB data
    $photo = $row['photo'];
    if ($photo) {
        // Convert BLOB to Base64
        $photo_base64 = base64_encode($photo);
        $photo_mime = 'image/jpeg'; // Adjust MIME type based on actual image type (e.g., image/png, image/gif)
        $photo_src = 'data:' . $photo_mime . ';base64,' . $photo_base64;
    } else {
        $photo_src = 'default-profile-pic.jpg'; // Default profile picture if none is found
    }
} else {
    $fullname = "Unknown User";
    $role = "Unknown Role";
    $photo_src = 'default-profile-pic.jpg'; // Default profile picture if none is found
}

$stmt->close(); // Close the statement

// Function to get counts of users and materials
function getCount($conn, $query) {
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row[array_key_first($row)];
    }
    return 0;
}

// Get counts for librarian, patron, and library materials
$librarian_count = getCount($conn, "SELECT COUNT(*) AS librarian_count FROM accounts WHERE role = 'librarian'");
$patron_count = getCount($conn, "SELECT COUNT(*) AS patron_count FROM accounts WHERE role = 'patron'");
$material_count = getCount($conn, "SELECT COUNT(*) AS material_count FROM materials");

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard- Lumina Libris</title>
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
            <a href="admin-dashboard.php" class="menu-item active">
                <img src="dashboard-icon.png" alt="Dashboard Icon" class="menu-icon">
                <span class="menu-text active">Dashboard</span>
            </a>
            <div class="menu-item">
                <img src="accounts-icon.png" alt="Accounts Icon" class="menu-icon">
                <span class="menu-text">Accounts</span>
                <!-- Submenu for Accounts -->
                <div class="submenu">
                    <a href="manage-account.php">Manage</a>
                    <hr>
                    <a href="add-account.php">Add</a>
                </div>
            </div>
            <div class="menu-item">
                <img src="reports-icon.png" alt="Reports Icon" class="menu-icon">
                <span class="menu-text">Reports</span>
                <!-- Submenu for Reports -->
                <div class="submenu">
                    <a href="admin-patron-record.php">Patron Record</a>
                    <hr>
                    <a href="admin-catalog-report.php">Catalog</a>
                    <hr>
                    <a href="admin-circulation-report.php">Circulation</a>
                    <hr>
                    <a href="admin-inventory-report.php">Inventory</a>
                </div>
            </div>
        </div>
        <div class="logout">
            <img src="exit-icon.png" alt="Exit Icon" class="exit-icon" id="logoutButton">
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <span>Administrator Dashboard</span>
            <img src="lumina-blue.png" alt="Logo" class="header-logo"> 
        </div>
        <div class="separator"></div>
        
        <div class="box-container">
            <div class="box box-librarian">
                <div class="label">LIBRARIAN</div>
                <div class="number"><?php echo $librarian_count; ?></div>
            </div>
            <div class="box box-patron">
                <div class="label">PATRON</div>
                <div class="number"><?php echo $patron_count; ?></div>
            </div>
            <div class="box box-material">
                <div class="label">TOTAL NUMBER OF<br>LIBRARY<br>MATERIALS</div>
                <div class="number"><?php echo $material_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Modal -->
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
