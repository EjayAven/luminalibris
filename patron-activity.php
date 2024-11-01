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
    
    // Handle the BLOB data for profile photo
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


$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patron Dashboard - Lumina Libris</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="patron-activity.css">
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
                    <a href="patron-profile.php">Profile</a>
                    <hr>
                    <a href="patron-activity.php">
                    <span class="menu-text active">Activity</span></a>
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
                <img src="back-outline.png" alt="Back" class="header-logo">
            </a>
            <img src="lumina-blue.png" alt="Logo" class="header-logo">
        </div>

        <div class="header">
            <span>&emsp;&emsp;&emsp;Account Activity</span>
        </div>
        <div class="separator"></div>

        <!-- Borrowing History Section -->
        <div class="table-title">BORROWING HISTORY</div>
        <table class="borrowing-table">
            <thead>
                <tr>
                    <th>Borrower_ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Publisher</th>
                    <th>Date Published</th>
                    <th>Type</th>
                    <th>Date Borrowed</th>
                    <th>Date Returned</th>
                    <th>Fines</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($borrowing_result->num_rows > 0): ?>
                    <?php while ($row = $borrowing_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['borrower_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['author']); ?></td>
                            <td><?php echo htmlspecialchars($row['publisher']); ?></td>
                            <td><?php echo htmlspecialchars($row['date_published']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo htmlspecialchars($row['date_borrowed']); ?></td>
                            <td><?php echo htmlspecialchars($row['date_returned']); ?></td>
                            <td><?php echo htmlspecialchars($row['fines']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9">No borrowing history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="separator"></div>

        <!-- Reservation Section -->
        <div class="table-title">RESERVATION</div>
        <table class="reservation-table">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Ref ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Publisher</th>
                    <th>Date Published</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Reserve Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reservation_result->num_rows > 0): ?>
                    <?php while ($row = $reservation_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['reservation_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['ref_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['author']); ?></td>
                            <td><?php echo htmlspecialchars($row['publisher']); ?></td>
                            <td><?php echo htmlspecialchars($row['date_published']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo htmlspecialchars($row['reserve_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['return_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11">No reservations found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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
