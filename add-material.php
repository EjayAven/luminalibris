<?php
session_start(); 

// Check if the user is logged in and has a librarian role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'librarian') {
    header("Location: login-page.php");
    exit();
}

include 'db_connection.php';  // Include your database connection file

// Get user ID from session
$user_id = $_SESSION['username'];

// Retrieve user details
$stmt = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) AS fullname, role, photo FROM accounts WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullname = $row['fullname'];
    $role = $row['role'];
    $photo_src = $row['photo'] ? 'data:image/jpeg;base64,'.base64_encode($row['photo']) : 'default-profile-pic.jpg';
} else {
    $fullname = "Unknown User";
    $role = "Unknown Role";
    $photo_src = 'default-profile-pic.jpg';
}
$stmt->close();

// Handling form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Common fields
    $types = $_POST['types'];
    
    if ($types === 'Book') {
        // Book specific fields
        $referenceID = $_POST['book_referenceID']; 
        $title = $_POST['book_title'];
        $author = $_POST['author'];
        $publisher = $_POST['book_publisher'];
        $date_published = $_POST['book_date_published'];
        $place_published = $_POST['book_place_published'];
        $isbn = $_POST['isbn'];
        $binding = $_POST['binding'] ?? null;
        $edition = $_POST['edition'] ?? null;
        $printing = $_POST['book_printing'] ?? null;
        $section = $_POST['book_section'];
        $subject = $_POST['book_subject'];
        $synopsis = $_POST['synopsis'] ?? null;
        $page_number = $_POST['page_number'] ?? null;
        $status = $_POST['status'];
        $condition = $_POST['condition'];
        $date_acquired = $_POST['book_date_acquired'];

        // File upload (for image)
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['image']['size'] > 16777215 ) {
                    echo "<script>alert('File size must be less than 16 MB.');</script>";
                } else {
                    $image = file_get_contents($_FILES['image']['tmp_name']);
                    if ($image === false) {
                        echo "<script>alert('Failed to read file.');</script>";
                    }
                }
            } else {
                echo "<script>alert('File upload error: " . $_FILES['image']['error'] . "');</script>";
            }
        } else {
            $image = NULL;
        }
    
        $query = "INSERT INTO materials (types, referenceID, title, author, publisher, date_published, place_published, isbn, binding, edition, printing, section, subject, synopsis, page_number, status, `condition`, date_acquired, image)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssisssb", $types, $referenceID, $title, $author, $publisher, $date_published, $place_published, $isbn, $binding, $edition, $printing, $section, $subject, $synopsis, $page_number, $status, $condition, $date_acquired, $image);
    
        // Handle image (BLOB) upload
        if ($image) {
            $stmt->send_long_data(18, $image); // 18th parameter is the image (for MEDIUMBLOB)
        }
        
    } elseif (in_array($types, ['Newspaper', 'Journal', 'Magazine'])) {
        // Newspaper/Journal/Magazine specific fields
        $referenceID = $_POST['periodical_referenceID']; 
        $title = $_POST['periodical_title'];
        $publisher = $_POST['periodical_publisher'];
        $date_published = $_POST['periodical_date_published'];
        $place_published = $_POST['periodical_place_published'];
        $issn = $_POST['issn'];
        $volume = $_POST['periodical_volume'];
        $issue_number = $_POST['issue_number'] ?? null;
        $printing = $_POST['periodical_printing'] ?? null;
        $section = $_POST['periodical_section'];
        $subject = $_POST['periodical_subject'] ?? null;
        $status = $_POST['status'];
        $condition = $_POST['condition'];
        $date_acquired = $_POST['periodical_date_acquired'];

        $query = "INSERT INTO materials (types, referenceID, title, publisher, date_published, place_published, issn, volume, issue_number, printing, section, subject, status, `condition`, date_acquired)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssss", $types, $referenceID, $title, $publisher, $date_published, $place_published, $issn, $volume, $issue_number, $printing, $section, $subject, $status, $condition, $date_acquired);
    
    } elseif (in_array($types, ['CD', 'DVD', 'Tape Recording'])) {
        $referenceID = $_POST['pmedia_referenceID']; 
        $title = $_POST['pmedia_title'];
        $publisher = $_POST['pmedia_publisher'];
        $date_published = $_POST['pmedia_date_published'];
        $place_published = $_POST['pmedia_place_published'];
        $volume = $_POST['pmedia_volume'];
        $section = $_POST['pmedia_section'];
        $subject = $_POST['pmedia_subject'] ?? null;
        $status = $_POST['status'];
        $condition = $_POST['condition'];
        $date_acquired = $_POST['pmedia_date_acquired'];

        $query = "INSERT INTO materials (types, referenceID, title, publisher, date_published, place_published, volume, section, subject, status, `condition`, date_acquired)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        
        $stmt->bind_param("ssssssssssss", $types, $referenceID, $title, $publisher, $date_published, $place_published, $volume, $section, $subject, $status, $condition, $date_acquired);
    }

    // Execute the query and check result
    if ($stmt->execute()) {
        $_SESSION['add_success'] = true; // Set a session flag
        header('Location: manage-material.php');
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Material- Lumina Libris</title>
    <link rel="stylesheet" href="style-material.css">
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
                <span class="menu-text">Dashboard</span>
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
                    <a href="add-material.php" class="submenu-item active">Add Materials</a>
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
            <span>&emsp;&emsp;&emsp;Add Material</span>
        </div>
        
        <div class="separator"></div>
        
        <div class="form-container">
            <form id="material-form" action="add-material.php" method="POST" enctype="multipart/form-data">
                <div class="input-group">
                    <label for="types">Type of Material:</label>
                    <select id="types" name="types" required>
                        <option value="" disabled selected>Select Type</option>
                        <option value="Book">Book</option>
                        <option value="Magazine">Magazine</option>
                        <option value="Journal">Journal</option>
                        <option value="Newspaper">Newspaper</option>
                        <option value="DVD">DVD</option>
                        <option value="CD">CD</option>
                        <option value="Tape Recording">Tape Recording</option>
                    </select>
                </div>
                
                <div class="form-separator"></div>

                <!-- Book Form -->
                 <div class="form-columns" id="book-form" style="display:none;">
                    <div class="form-column left-column">
                        <div class="input-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="book_title" required>
                        </div>
                        <div class="input-group">
                            <label for="author">Author</label>
                            <input type="text" id="author" name="author" required>
                        </div>
                        <div class="input-group">
                            <label for="publisher">Publisher</label>
                            <input type="text" id="publisher" name="book_publisher" required>
                        </div>
                        <div class="input-group">
                            <label for="date_published">Date Published</label>
                            <input type="date" id="date_published" name="book_date_published" required>
                        </div>
                        <div class="input-group">
                            <label for="place_published">Place Published</label>
                            <input type="text" id="place_published" name="book_place_published" required>
                        </div>
                        <div class="input-group">
                            <label for="isbn">ISBN</label>
                            <input type="text" id="isbn" name="isbn" required>
                        </div>
                        <div class="input-group">
                            <label for="binding">Binding</label>
                            <input type="text" id="binding" name="binding">
                        </div>
                        <div class="input-group">
                            <label for="edition">Edition</label>
                            <input type="text" id="edition" name="edition">
                        </div>
                        <div class="input-group">
                            <label for="printing">Printing</label>
                            <input type="text" id="printing" name="book_printing">
                        </div>
                        <div class="input-group">
                            <label for="section">Section</label>
                            <input type="text" id="section" name="book_section" required>
                        </div>
                        <div class="input-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="book_subject" required>
                        </div>
                    </div>

                    <div class="form-column middle-column">
                        <div class="input-group">
                            <label for="synopsis">Synopsis</label>
                            <textarea id="synopsis" name="synopsis"></textarea>
                        </div>
                        <div class="input-group">
                            <label for="page_number">Page Numbers</label>
                            <input type="text" id="page_number" name="page_number">
                        </div>
                        <div class="input-group">
                            <label for="status">Status</label required>
                            <select id="status" name="status">
                                <option value="Available">Available</option>
                                <option value="Checked Out">Checked Out</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Lost">Lost</option>
                                <option value="In Repair">In Repair</option>
                                <option value="Under Review">Under Review</option>
                                <option value="Withdrawn">Withdrawn</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="condition">Condition</label required>
                            <select id="condition" name="condition">
                                <option value="New">New</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Missing/Torn Pages">Missing/Torn Pages</option>
                                <option value="Worn">Worn</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="referenceID">Reference ID</label>
                            <input type="text" id="referenceID" name="book_referenceID" required>
                        </div>
                        <div class="input-group">
                            <label for="date_acquired">Date Acquired</label>
                            <input type="date" id="date_acquired" name="book_date_acquired" required>
                        </div>
                    </div>

                    <div class="form-column right-column">
                        <div class="photo-box">
                            <img id="photo-preview" src="#" alt="Photo Preview">
                        </div>
                        <!-- Custom upload button -->
                        <div id="photo-upload-container">
                            <!-- Hidden file input -->
                            <input type="file" id="photo-upload" name="image" accept="image/*" onchange="previewImage(event)" style="display: none;">
                            
                            <!-- Label acting as the custom button -->
                            <label for="photo-upload" class="upload-button"><i>Upload Photo</i></label>
                        </div>   
                    </div>
                </div>

                <!--Periodical Form: for Newspaper, Journal, Magazine-->
                <div class="form-columns" id="periodical-form" style="display:none;">
                    <div class="form-column left-column">
                        <div class="input-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="periodical_title" required>
                        </div>
                        <div class="input-group">
                            <label for="publisher">Publisher</label>
                            <input type="text" id="publisher" name="periodical_publisher" required>
                        </div>
                        <div class="input-group">
                            <label for="date_published">Date Published</label>
                            <input type="date" id="date_published" name="periodical_date_published" required>
                        </div>
                        <div class="input-group">
                            <label for="place_published">Place Published</label>
                            <input type="text" id="place_published" name="periodical_place_published" required>
                        </div>
                        <div class="input-group">
                            <label for="issn">ISSN</label>
                            <input type="text" id="issn" name="issn" required>
                        </div>
                        <div class="input-group">
                            <label for="volume">Volume</label>
                            <input type="text" id="volume" name="periodical_volume" required>
                        </div>
                        <div class="input-group">
                            <label for="issue_number">Issue Number</label>
                            <input type="text" id="issue_number" name="issue_number">
                        </div>
                        <div class="input-group">
                            <label for="printing">Printing</label>
                            <input type="text" id="printing" name="periodical_printing">
                        </div>
                        <div class="input-group">
                            <label for="section">Section</label>
                            <input type="text" id="section" name="periodical_section" required>
                        </div>
                        <div class="input-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="periodical_subject">
                        </div>
                    </div>

                    <div class="form-column middle-column">
                        <div class="input-group">
                            <label for="status">Status</label required>
                            <select id="status" name="status">
                                <option value="Available">Available</option>
                                <option value="Checked Out">Checked Out</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Lost">Lost</option>
                                <option value="In Repair">In Repair</option>
                                <option value="Under Review">Under Review</option>
                                <option value="Withdrawn">Withdrawn</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="condition">Condition</label required>
                            <select id="condition" name="condition">
                                <option value="New">New</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Missing/Torn Pages">Missing/Torn Pages</option>
                                <option value="Worn">Worn</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="referenceID">Reference ID</label>
                            <input type="text" id="referenceID" name="periodical_referenceID" required>
                        </div>
                        <div class="input-group">
                            <label for="date_acquired">Date Acquired</label>
                            <input type="date" id="date_acquired" name="periodical_date_acquired" required>
                        </div>
                    </div>

                    <div class="form-column right-column"></div>
                </div>

                <!--Media Form: for CD, DVD, Tape recordings-->
                <div class="form-columns" id="media-form" style="display:none;">
                    <div class="form-column left-column">
                        <div class="input-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="pmedia_title" required>
                        </div>
                        <div class="input-group">
                            <label for="publisher">Publisher</label>
                            <input type="text" id="publisher" name="pmedia_publisher" required>
                        </div>
                        <div class="input-group">
                            <label for="date_published">Date Published</label>
                            <input type="date" id="date_published" name="pmedia_date_published" required>
                        </div>
                        <div class="input-group">
                            <label for="place_published">Place Published</label>
                            <input type="text" id="place_published" name="pmedia_place_published" required>
                        </div>
                        <div class="input-group">
                            <label for="volume">Volume</label>
                            <input type="text" id="volume" name="pmedia_volume" required>
                        </div>
                        <div class="input-group">
                            <label for="section">Section</label>
                            <input type="text" id="section" name="pmedia_section" required>
                        </div>
                        <div class="input-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="pmedia_subject">
                        </div>
                    </div>

                    <div class="form-column middle-column">
                        <div class="input-group">
                            <label for="status">Status</label required>
                            <select id="status" name="status">
                                <option value="Available">Available</option>
                                <option value="Checked Out">Checked Out</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Lost">Lost</option>
                                <option value="In Repair">In Repair</option>
                                <option value="Under Review">Under Review</option>
                                <option value="Withdrawn">Withdrawn</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="condition">Condition</label required>
                            <select id="condition" name="condition">
                                <option value="New">New</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Missing/Torn Pages">Missing/Torn Pages</option>
                                <option value="Worn">Worn</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="referenceID">Reference ID</label>
                            <input type="text" id="referenceID" name="pmedia_referenceID" required>
                        </div>
                        <div class="input-group">
                            <label for="date_acquired">Date Acquired</label>
                            <input type="date" id="date_acquired" name="pmedia_date_acquired" required>
                        </div>
                    </div>

                    <div class="form-column right-column"></div>
                </div>

                <div class="form-buttons">
                    <button type="submit" id="addMaterial">Save</button>
                    <button type="button" id="cancelAdd" onclick="window.location.href='add-material.php'">Cancel</button>
                </div>
            </form>
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

    <script src="libmaterial-form.js"></script>
</body>
</html>
