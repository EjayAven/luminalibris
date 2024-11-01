<?php
session_start(); 

// Check if the user is logged in and has a patron role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'patron') {
    header("Location: login.html"); // Redirect to login page if not logged in or not a patron
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
    <title>Patron Dashboard- Lumina Libris</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="patron.css">
    <link href='Poppins.css' rel='stylesheet'>
    <script src ="logout.js "></script>
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
            <a href="patron-dashboard.php" class="menu-item active">
                <img src="dashboard-icon.png" alt="Dashboard Icon" class="menu-icon">
                <span class="menu-text active">Dashboard</span>
            </a>
            <div class="menu-item">
                <a href="patron-catalog.php">
                <img src="catalog-icon.png" alt="Catalog Icon" class="menu-icon">
                <span class="menu-text">Catalog</span>
            </div>
            <div class="menu-item">
                <a href="patron-profile.php">
                <img src="profile-icon.ico" alt="Profile Icon" class="menu-icon">
                <span class="menu-text">Account</span>
                 <div class="submenu">             
                    <a href="patron-profile.php">Profile</a>
                    <hr>
                    <a href="patron-activity.php">Activity</a>
                </div>
                </div>            
        </div>
        <div class="logout">
            <img src="exit-icon.png" alt="Exit Icon" class="exit-icon" id="logoutButton">
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <span>Patron Dashboard</span>
            <img src="lumina-blue.png" alt="Logo" class="header-logo"> 
        </div>
        <div class="separator"></div>
        
        <div class="box-container">
            <div class="box box-borrowed">
                <div class="label">BORROWED<BR>MATERIALS</div>
                <div class="number"><?php echo $librarian_count; ?></div>
            </div>
            <div class="box box-returned">
                <div class="label">RETURNED<BR> MATERIALS</div>
                <div class="number"><?php echo $patron_count; ?></div>
            </div>
            <div class="box box-overdue">
                <div class="label">OVERDUE<br>MATERIALS</div>
                <div class="number"><?php echo $material_count; ?></div>
            </div>
            </div>
             <div class="box-container">
              <div class="box box-reserved">
                <div class="label">RESERVED<br>MATERIALS</div>
                <div class="number"><?php echo $material_count; ?></div>
            </div>
              <div class="box box-fines">
                <div class="label">FINES</div>
                <div class="number"><?php echo $material_count; ?></div>
            </div>

        </div>
         <div class="header">
         <span><br>Recommended Materials</span>
        </div>
        <div class="separator"></div>
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

    <script src="script.js"></script>
</body>
</html>
