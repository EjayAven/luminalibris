<?php
session_start(); 

// Check if the user is logged in and has an admin role
if (!isset($_SESSION['username']) || !in_array(strtolower($_SESSION['role']), ['admin', 'administrator'])) {
    header("Location: login-page.php"); // Redirect to login page if not logged in or not an admin
    exit();
}

include 'db_connection.php';

// Display the "Account Deleted" modal
if (isset($_SESSION['delete_success'])) {
    if ($_SESSION['delete_success']) {
        echo "<script>
                window.onload = function() {
                    var modal = document.getElementById('deleteSuccessModal');
                    modal.style.display = 'flex';
                    
                    // Auto-close the modal after 3 seconds
                    setTimeout(function() {
                        modal.style.display = 'none';
                    }, 3000);

                    // Close the modal when the user clicks anywhere outside of the modal
                    window.onclick = function(event) {
                        if (event.target == modal) {
                            modal.style.display = 'none';
                        }
                    }
                }
              </script>";
    }
    unset($_SESSION['delete_success']); // Clear the flag
}

if (isset($_SESSION['add_success']) && $_SESSION['add_success']) {
    unset($_SESSION['add_success']); // Clear the flag
    echo "<script>
            window.onload = function() {
                var modal = document.getElementById('addSuccessModal');
                var span = document.getElementsByClassName('close')[0];
                modal.style.display = 'flex';
                
                // Auto-close the modal after 3 seconds
                setTimeout(function() {
                    modal.style.display = 'none';
                }, 3000);
                
                // Close the modal when the user clicks anywhere outside of the modal
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                }
            }
          </script>";
}

if (isset($_SESSION['update_success']) && $_SESSION['update_success']) {
    unset($_SESSION['update_success']); // Clear the flag
    echo "<script>
            window.onload = function() {
                var modal = document.getElementById('successModal');
                var span = document.getElementsByClassName('close')[0];
                modal.style.display = 'flex';
                
                // Auto-close the modal after 3 seconds
                setTimeout(function() {
                    modal.style.display = 'none';
                }, 3000);
                
                // Close the modal when the user clicks anywhere outside of the modal
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                }
            }
          </script>";
}

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

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts- Lumina Libris</title>
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
            <div class="menu-item" class="menu-item active">
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
            <span>&emsp;&emsp;&emsp;Manage Accounts</span>
        </div>
        
        <div class="separator"></div>
        
        <div class="search-container">
            <span><b>SEARCH</b></span>    
            <form method="GET" action="manage-account.php" id="searchForm">
                <label for="search-by">Search by:</label>
                <select name="search-by" id="search-by">
                    <option value="">All</option>
                    <option value="Administrator" <?php if (isset($_GET['search-by']) && $_GET['search-by'] == 'Administrator') echo 'selected'; ?>>Administrator</option>
                    <option value="Librarian" <?php if (isset($_GET['search-by']) && $_GET['search-by'] == 'Librarian') echo 'selected'; ?>>Librarian</option>
                    <option value="Patron" <?php if (isset($_GET['search-by']) && $_GET['search-by'] == 'Patron') echo 'selected'; ?>>Patron</option>
                </select>

                <label for="user-id">&emsp;User ID:</label>
                <input type="text" name="user-id" id="user-id" placeholder="" value="<?php echo htmlspecialchars(isset($_GET['user-id']) ? $_GET['user-id'] : ''); ?>">

                <label for="general-search"></label>
                <input type="text" name="general-search" id="general-search" placeholder="Search any word" value="<?php echo htmlspecialchars(isset($_GET['general-search']) ? $_GET['general-search'] : ''); ?>">

                &emsp;
                <button class="search-btn" id="searchButton">Search</button>
                <button class="reset-btn" id="resetButton">Reset</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Birthdate</th>
                    <th>Contact Number</th>
                    <th>Status</th>
                    <th>Email Address</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include 'db_connection.php';

                // Build the query based on search preferences
                $search_by = isset($_GET['search-by']) ? $_GET['search-by'] : '';
                $user_id = isset($_GET['user-id']) ? $_GET['user-id'] : '';
                $general_search = isset($_GET['general-search']) ? $_GET['general-search'] : '';

                $query = "SELECT * FROM accounts WHERE 1=1";

                // Search by role (Administrator, Librarian, Patron)
                if ($search_by) {
                    $query .= " AND role = '" . $conn->real_escape_string($search_by) . "'";
                }

                // Search by User ID
                if ($user_id !== '') {  // Check if User ID is provided
                    //$query .= " AND id = " . intval($user_id);
                    $query .= " AND id = '" . $conn->real_escape_string($user_id) . "'";
                }

                // General search (search by any keyword in multiple columns)
                if ($general_search !== '') {  // Check if General Search is provided
                    $query .= " AND (lastname LIKE '%" . $conn->real_escape_string($general_search) . "%' OR 
                                    firstname LIKE '%" . $conn->real_escape_string($general_search) . "%' OR
                                    midname LIKE '%" . $conn->real_escape_string($general_search) . "%' OR
                                    emailaddress LIKE '%" . $conn->real_escape_string($general_search) . "%')";
                }

                $result = $conn->query($query);


                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['lastname']}</td>
                                <td>{$row['firstname']}</td>
                                <td>{$row['midname']}</td>
                                <td>{$row['birthdate']}</td>
                                <td>{$row['contactnum']}</td>
                                <td>{$row['status']}</td>
                                <td>{$row['emailaddress']}</td>
                                <td>
                                    <a href='edit-account.php?id=" . $row['id'] . "'><img src='edit-icon.png' class='action-icon edit' alt='Edit'></a>
                                    <img src='delete-icon.png' class='action-icon delete' alt='Delete' id='deleteButton'>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No records found</td></tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>

    <!-- Delete Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <span class="close-button" id="deleteCloseButton">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this record?</p>
            <button class="modal-button" id="confirmDelete">Delete</button>
            <button class="modal-button" id="cancelDelete">Cancel</button>
        </div>
    </div>

    <!-- Delete Successful Modal HTML -->
    <div id="deleteSuccessModal" class="deleteSuccess-modal">
        <div class="deleteSuccess-modal-content">
            <p><strong>ACCOUNT DELETED!</strong><br>Account has been successfully deleted.</p>
        </div>
    </div>

    <!-- Add Account Successful Modal HTML -->
    <div id="addSuccessModal" class="addSuccess-modal">
    <div class="addSuccess-modal-content">
        <p><strong>NEW ACCOUNT ADDED!</strong><br>Account has been successfully added.</p>
    </div>
    </div>

    <!-- Update Successful Modal HTML -->
    <div id="successModal" class="updateSuccess-modal">
    <div class="updateSuccess-modal-content">
        <p><strong>ACCOUNT DETAILS UPDATED!</strong><br>Account has been successfully updated.</p>
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

    <script src="manage-account.js"></script>
</body>
</html>
