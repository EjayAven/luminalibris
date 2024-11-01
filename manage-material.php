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

// Get the selected filters from the URL
$selectedMaterialType = isset($_GET['materialType']) ? $_GET['materialType'] : '';
$selectedSection = isset($_GET['section']) ? $_GET['section'] : '';

// Query to fetch library materials
$material_query = "
    SELECT 
        referenceID,
        title,  
        author, 
        publisher,
        date_published,
        types AS type, 
        section, 
        status
    FROM materials
    WHERE 1=1";

// Apply filter based on material type if selected
if (!empty($selectedMaterialType)) {
    $material_query .= " AND types = '" . $conn->real_escape_string($selectedMaterialType) . "'";
}

// Apply filter based on section if selected
if (!empty($selectedSection)) {
    $material_query .= " AND section = '" . $conn->real_escape_string($selectedSection) . "'";
}

// Add ordering to the query
$material_query .= " ORDER BY created_at DESC";

// Execute the modified query
$material_result = $conn->query($material_query);


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

// Display the "Material Deleted" modal
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

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Materials- Lumina Libris</title>
    <link rel="stylesheet" href="style-manage-material.css">
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
            <a href="librarian-dashboard.php" class="menu-item">
                <img src="dashboard-icon.png" alt="Dashboard Icon" class="menu-icon">
                <span class="menu-text active">Dashboard</span>
            </a>
            <a href="library-requests.php" class="menu-item">
                <img src="requests-icon.png" alt="Requests Icon" class="menu-icon">
                <span class="menu-text">Requests</span>
            </a>
            <div class="menu-item">
                <img src="materials-icon.png" alt="Materials Icon" class="menu-icon">
                <span class="menu-text active">Materials</span>
                <!-- Submenu for Materials -->
                <div class="submenu">
                    <a href="add-material.php">Add Materials</a>
                    <hr>
                    <a href="manage-material.php" class="submenu-item active">Manage Materials</a>
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
            <span>&emsp;&emsp;&emsp;Manage Materials</span>
        </div>
        
        <div class="separator"></div>

        <form id="filterForm" method="GET" action="manage-material.php">
            <div class="sort-container">
                <span><b>SORT</b></span>
                <div class="sort">
                    <label for="materialType">Type of Material:</label>
                    <select id="materialType" name="materialType">
                        <option value="" disabled selected>Select Type</option>
                        <option value="Book">Book</option>
                        <option value="Magazine">Magazine</option>
                        <option value="Journal">Journal</option>
                        <option value="Newspaper">Newspaper</option>
                        <option value="DVD">DVD</option>
                        <option value="CD">CD</option>
                        <option value="Tape Recording">Tape Recording</option>
                    </select>

                    <label for="section">&emsp;Section:</label>
                    <select id="section" name="section">
                        <option value="" disabled selected>Select Section</option>
                        <option value="Philosophy">Philosophy</option>
                        <option value="Literature">Literature</option>
                        <option value="Science">Science</option>
                        <option value="History">History</option>
                        <option value="Fiction">Fiction</option>
                    </select>

                    &emsp;
                    <button type="submit" class="generate-btn">Generate</button>
                    <button type="button" class="reset-btn" id="resetButton">Reset</button>
                </div>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Ref ID</th>
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
                if ($material_result->num_rows > 0) {
                    // Loop through each row and display in the table
                    while ($row = $material_result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['referenceID']) . "</td>
                                <td>" . htmlspecialchars($row['title']) . "</td>
                                <td>" . htmlspecialchars($row['author']) . "</td>
                                <td>" . htmlspecialchars($row['publisher']) . "</td>
                                <td>" . htmlspecialchars($row['date_published']) . "</td>
                                <td>" . htmlspecialchars($row['type']) . "</td>
                                <td>" . htmlspecialchars($row['section']) . "</td>
                                <td>" . htmlspecialchars($row['status']) . "</td>
                                <td>
                                    <img src='borrow-icon.png' class='action-icon borrow' alt='Borrow' id='borrowButton'>
                                    <a href='edit-material.php?referenceID={$row['referenceID']}'><img src='edit-icon.png' class='action-icon edit' alt='Edit'></a>
                                    <img src='delete-icon.png' class='action-icon delete' alt='Delete' id='deleteButton'>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No materials found</td></tr>";
                }
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
            <p><strong>LIBRARY MATERIAL DELETED!</strong><br>You successfully deleted a material.</p>
        </div>
    </div> 
    
    <!-- Add Material Successful Modal HTML -->
    <div id="addSuccessModal" class="addSuccess-modal">
    <div class="addSuccess-modal-content">
        <p><strong>NEW LIBRARY MATERIAL ADDED!</strong><br>You successfully added a material.</p>
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

    <script src="manage-material.js"></script>
</body>
</html>
