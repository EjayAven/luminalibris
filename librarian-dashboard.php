<?php
session_start(); 

// Check if the user is logged in and has a librarian role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'librarian') {
    header("Location: login-page.php"); // Redirect to login page if not logged in or not a librarian
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

// Get counts for patron and library materials
$request_count = getCount($conn, "SELECT COUNT(*) AS request_count FROM reservation WHERE status = 'pending'");
$librarian_count = getCount($conn, "SELECT COUNT(*) AS librarian_count FROM accounts WHERE role = 'librarian'");
$patron_count = getCount($conn, "SELECT COUNT(*) AS patron_count FROM accounts WHERE role = 'patron'");
$material_count = getCount($conn, "SELECT COUNT(*) AS material_count FROM materials");
$materialtype_count = getCount($conn, "SELECT COUNT(DISTINCT types) AS materialtype_count FROM materials");
$damaged_material_count = getCount($conn, "SELECT COUNT(*) AS material_count FROM materials WHERE `condition` = 'Damaged'");

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard- Lumina Libris</title>
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
            <a href="librarian-dashboard.php" class="menu-item active">
                <img src="dashboard-icon.png" alt="Dashboard Icon" class="menu-icon">
                <span class="menu-text active">Dashboard</span>
            </a>
            <a href="request.php" class="menu-item">
                <img src="requests-icon.png" alt="Requests Icon" class="menu-icon">
                <span class="menu-text">Requests</span>
            </a>
            <div class="menu-item">
                <img src="materials-icon.png" alt="Materials Icon" class="menu-icon">
                <span class="menu-text">Materials</span>
                <!-- Submenu for Materials -->
                <div class="submenu">
                    <a href="add-material.php">Add Materials</a>
                    <hr>
                    <a href="manage-material.php">Manage Materials</a>
                </div>
            </div>
            <div class="menu-item">
                <img src="accounts-icon.png" alt="Accounts Icon" class="menu-icon">
                <span class="menu-text">Accounts</span>
                <!-- Submenu for Accounts -->
                <div class="submenu">
                    <a href="#">Librarian</a>
                    <hr>
                    <a href="#">Patron</a>
                </div>
            </div>
            <div class="menu-item">
                <img src="reports-icon.png" alt="Reports Icon" class="menu-icon">
                <span class="menu-text">Reports</span>
                <!-- Submenu for Reports -->
                <div class="submenu">
                    <a href="librarian-patron-record.php">Patron Record</a>
                    <hr>
                    <a href="librarian-catalog-report.php">Catalog</a>
                    <hr>
                    <a href="librarian-circulation-report.php">Circulation</a>
                    <hr>
                    <a href="librarian-inventory-report.php">Inventory</a>
                </div>
            </div>
        </div>
        <div class="logout">
            <img src="exit-icon.png" alt="Exit Icon" class="exit-icon" id="logoutButton">
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <span>Librarian Dashboard</span>
            <img src="lumina-blue.png" alt="Logo" class="header-logo"> 
        </div>
        <div class="separator"></div>
        
        <div class="box-container">
            <div class="box box-request">
                <div class="label">REQUESTS</div>
                <div class="number"><?php echo $request_count;?></div>
            </div>
            <div class="box box-patron">
                <div class="label">PATRON</div>
                <div class="number"><?php echo $patron_count; ?></div>
            </div>
        </div>

        <div class="box-container">
            <div class="box box-materials">
                <div class="label">LIBRARY<br>MATERIALS</div>
                <div class="number"><?php echo $material_count; ?></div>
            </div>
            <div class="box box-materialtype">
                <div class="label">TYPES OF<br>LIBRARY<br>MATERIALS</div>
                <div class="number"><?php echo $materialtype_count; ?></div> 
            </div>
        </div>

        <div class="box-container">
            <div class="box box-issuedmaterials">
                <div class="label">ISSUED<br>MATERIALS</div>
                <div class="number">0</div> <!-- Static 0 value for testing -->
            </div>
            <div class="box box-returnedmaterials">
                <div class="label">RETURNED<br>MATERIALS</div>
                <div class="number">0</div>
            </div>
            <div class="box box-overduematerials">
                <div class="label">OVERDUE<br>MATERIALS</div>
                <div class="number">0</div>
            </div>
            <div class="box box-damagedmaterials">
                <div class="label">DAMAGED<br>MATERIALS</div>
                <div class="number"><?php echo $damaged_material_count; ?></div>
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
