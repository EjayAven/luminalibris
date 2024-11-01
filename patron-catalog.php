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
        $photo_mime = 'image/jpeg'; // Update MIME type based on your image storage format
        $photo_src_form = 'data:' . $photo_mime . ';base64,' . $photo_base64;
    } else {
        $photo_src_form = 'default-profile-pic.jpg'; // Default image if no photo exists
    }
} else {
    echo "<script>alert('Account not found.'); window.location.href='login-page.php';</script>";
    exit();
}

$stmt->close();

// Fetch the filters from the form submission
$type = isset($_GET['type']) ? $_GET['type'] : 'none';
$section = isset($_GET['section']) ? $_GET['section'] : 'none';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Start building the SQL query with optional filters
$materials_sql = "SELECT referenceID, title, author, publisher, date_published, types, section, status FROM materials WHERE 1=1";

// Add filters to the query if the user has selected any filter other than "None"
if ($type != 'none') {
    $materials_sql .= " AND types = '$type'";
}
if ($section != 'none') {
    $materials_sql .= " AND section = '$section'";
}
if (!empty($search)) {
    $materials_sql .= " AND (title LIKE '%$search%' OR author LIKE '%$search%' OR publisher LIKE '%$search%')";
}

// Execute the query with filters
$materials_result = $conn->query($materials_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patron Account - Lumina Libris</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="patron-catalog.css">
    <link href='Poppins.css' rel='stylesheet'>
    <script src="logout.js"></script>

    <!-- Include Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        // Function to submit the form when an option is changed
        function submitForm() {
            document.getElementById("filterForm").submit();
        }
    </script>
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
                <span class="menu-text active">Catalog</span>
            </div>
            <div class="menu-item">
                <a href="patron-profile.php">
                <img src="profile-icon.ico" alt="Profile Icon" class="menu-icon">
                <span class="menu-text">Account</span>
                 <div class="submenu">             
                    <a href="patron-profile.php"></a>
                    <span class="menu-text">Profile</span>
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
            <a href="javascript:history.back()">
                <img src="back-outline.png" alt="Back Icon" class="header-logo">
            </a>
            <img src="lumina-blue.png" alt="Logo" class="header-logo"> 
        </div>
        <div class="header1">
            <span>&emsp;&emsp;&emsp;Materials</span>
        </div>
        <div class="separator"></div>

        <!-- Form for filters -->
        <form method="GET" action="" id="filterForm">
            <div class="filters-section">
                <div class="filters-container">
                    <label for="materials">Type of Material:</label>
                    <select id="materials" name="type" onchange="submitForm()">
                        <option value="none" <?php if($type == 'none') echo 'selected'; ?>>None</option>
                        <option value="book" <?php if($type == 'book') echo 'selected'; ?>>Book</option>
                        <option value="magazine" <?php if($type == 'magazine') echo 'selected'; ?>>Magazine</option>
                        <option value="newspaper" <?php if($type == 'newspaper') echo 'selected'; ?>>Newspaper</option>
                        <option value="journal" <?php if($type == 'journal') echo 'selected'; ?>>Journal</option>
                        <option value="cd" <?php if($type == 'cd') echo 'selected'; ?>>CD</option>
                        <option value="dvd" <?php if($type == 'dvd') echo 'selected'; ?>>DVD</option>
                        <option value="tape_recording" <?php if($type == 'tape_recording') echo 'selected'; ?>>Tape Recording</option>
                    </select>

                    <label for="section">Section:</label>
                    <select id="section" name="section" onchange="submitForm()">
                        <option value="none" <?php if($section == 'none') echo 'selected'; ?>>None</option>
                        <option value="philosophy" <?php if($section == 'philosophy') echo 'selected'; ?>>Philosophy</option>
                        <!-- Add more sections as necessary -->
                    </select>

                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">

                    <button type="submit" class="btn-generate">Generate</button>
                </div>
            </div>
        </form>

        <!-- Table Section -->
        <div class="table-section">
            <table class="materials-table">
                <thead>
                    <tr>
                        <th>Reference ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Publisher</th>
                        <th>Date Published</th>
                        <th>Type</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($materials_result->num_rows > 0) {
                        // Output data of each row
                        while($row = $materials_result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['referenceID']}</td>
                                    <td>{$row['title']}</td>
                                    <td>{$row['author']}</td>
                                    <td>{$row['publisher']}</td>
                                    <td>{$row['date_published']}</td>
                                    <td>{$row['types']}</td>
                                    <td>{$row['section']}</td>
                                    <td>{$row['status']}</td>
                                    <td><a href='patron-catalog-material.php?referenceID={$row['referenceID']}' class='action-button'><i class='fas fa-eye'></i></a></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10'>No materials found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

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
