<?php
session_start(); 

// Check if the user is logged in and has the role of a patron
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'patron') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';  // Include your database connection file

// Get user ID from session
$user_id = $_SESSION['username'];

// Retrieve user details
$stmt = $conn->prepare("SELECT firstname, lastname, role, photo FROM accounts WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $fullname = $user_data['firstname'] . " " . $user_data['lastname'];
    $role = $user_data['role'];
    $photo_src = $user_data['photo'] ? 'data:image/jpeg;base64,' . base64_encode($user_data['photo']) : 'default-profile-pic.jpg';
} else {
    $fullname = "Unknown User";
    $role = "Unknown Role";
    $photo_src = 'default-profile-pic.jpg';
}
$stmt->close();

// Get the material's type and details based on referenceID
$referenceID = $_GET['referenceID'] ?? null;

if ($referenceID) {
    $stmt = $conn->prepare("SELECT * FROM materials WHERE referenceID = ?");
    $stmt->bind_param("s", $referenceID);
    $stmt->execute();
    $material_result = $stmt->get_result();

    if ($material_result->num_rows > 0) {
        $material = $material_result->fetch_assoc(); 
        $types = $material['types'];

        // Get image data if it exists
        $image_data = $material['image'] ? base64_encode($material['image']) : null;
    } else {
        echo "<script>alert('Material not found.'); window.location.href='patron-catalog.php';</script>";
        exit();
    }
    $stmt->close();
} else {
    echo "<script>alert('No material selected.'); window.location.href='patron-catalog.php';</script>";
    exit();
}

// Handle reservation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    // Validate the reservation dates
    if ($startDate && $endDate && strtotime($endDate) >= strtotime($startDate)) {
        // Insert reservation into the database
        $reserve_stmt = $conn->prepare(
            "INSERT INTO reservation (ref_id, title, author, borrower, borrow_date, return_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $reserve_stmt->bind_param(
            "ssssss", 
            $material['referenceID'], 
            $material['title'], 
            $material['author'], 
            $fullname,  // Use the full name of the borrower
            $startDate, 
            $endDate
        );

        if ($reserve_stmt->execute()) {
            // Update the material's status to 'reserved'
            $update_stmt = $conn->prepare("UPDATE materials SET status = 'reserved' WHERE referenceID = ?");
            $update_stmt->bind_param("s", $referenceID);
            $update_stmt->execute();
            $update_stmt->close();

            echo "<script>alert('Reservation approved and material status updated successfully!'); window.location.href='patron-catalog.php';</script>";
        } else {
            echo "<script>alert('Failed to submit reservation. Please try again.'); window.location.href='patron-catalog.php';</script>";
        }

        $reserve_stmt->close();
    } else {
        echo "<script>alert('Invalid reservation dates. Please select valid start and end dates.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Material - Lumina Libris</title>
    <link rel="stylesheet" href="patron-catalog-material.css">
    <link href='Poppins.css' rel='stylesheet'>
    <script src="logout.js"></script>
    <script src="calendar.js"></script>
    <style>
        .reserve-button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            text-align: center;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            margin-right: 100px; /* Align button properly */
        }

        .reserve-button:hover {
            background-color: #218838;
        }

        .form-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .form-column {
            width: 30%;
        }

        .input-group {
            margin-bottom: 15px;
        }

        form {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
    </style>
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
            <span class="menu-text active">Catalog</span>
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
        <a href="javascript:history.back()">
            <img src="back-outline.png" alt="Logo" class="header-logo">
        </a>
        <img src="lumina-blue.png" alt="Logo" class="header-logo"> 
    </div>
    <div class="header">
        <span>&emsp;&emsp;&emsp;Material Details</span>
    </div>

    <div class="separator"></div>
    <br>
    
    <!-- Material Details Form -->
    <div class="form-container">
        <div class="form-column left-column">
            <div class="input-group">
                <label for="title">Title</label>
                <input type="text" id="title" value="<?php echo htmlspecialchars($material['title']); ?>" readonly>
            </div>
            <div class="input-group">
                <label for="author">Author</label>
                <input type="text" id="author" value="<?php echo htmlspecialchars($material['author']); ?>" readonly>
            </div>
            <div class="input-group">
                <label for="publisher">Publisher</label>
                <input type="text" id="publisher" value="<?php echo htmlspecialchars($material['publisher']); ?>" readonly>
            </div>
            <div class="input-group">
                <label for="date_published">Date Published</label>
                <input type="text" id="date_published" value="<?php echo htmlspecialchars($material['date_published']); ?>" readonly>
            </div>
            <div class="input-group">
                <label for="isbn">ISBN</label>
                <input type="text" id="isbn" value="<?php echo htmlspecialchars($material['isbn']); ?>" readonly>
            </div>
        </div>

        <div class="form-column middle-column">
            <div class="input-group">
                <label for="status">Status</label>
                <input type="text" id="status" value="<?php echo htmlspecialchars($material['status']); ?>" readonly>
            </div>
            <div class="input-group">
                <label for="condition">Condition</label>
                <input type="text" id="condition" value="<?php echo htmlspecialchars($material['condition']); ?>" readonly>
            </div>
            <div class="input-group">
                <label for="date_acquired">Date Acquired</label>
                <input type="text" id="date_acquired" value="<?php echo htmlspecialchars($material['date_acquired']); ?>" readonly>
            </div>
        </div>

        <div class="form-column right-column">
            <div class="photo-box">
                <?php if ($image_data): ?>
                    <img id="photo-preview" src="data:image/jpeg;base64,<?php echo $image_data; ?>" alt="Material Image">
                <?php else: ?>
                    <img id="photo-preview" src="default-material-image.jpg" alt="Material Image">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reservation Form -->
    <form method="POST" action="">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" required>

        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" required>

        <button type="submit" class="reserve-button">Confirm Reservation</button>
    </form>
</div>

<!-- Modal for reserving materials -->
<div id="reserveModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Reserve Material</h2>
        <p>Select the reservation dates:</p>
        <label for="startDate">Start Date:</label>
        <input type="date" id="startDate">
        
        <label for="endDate">End Date:</label>
        <input type="date" id="endDate">

        <div class="modal-buttons">
            <div class="save-button" id="saveReservation">Save</div>
            <div class="cancel-button" id="cancelReservation">Cancel</div>
        </div>
    </div>
</div>

<script>
    // Get modal elements
    var modal = document.getElementById("reserveModal");
    var reserveBtn = document.getElementById("reserveBtn");
    var closeModal = document.getElementById("closeModal");
    var cancelReservation = document.getElementById("cancelReservation");

    // Show modal when reserve button is clicked
    reserveBtn.onclick = function() {
        modal.style.display = "block";
    }

    // Close modal when close button is clicked
    closeModal.onclick = function() {
        modal.style.display = "none";
    }

    // Close modal when cancel button is clicked
    cancelReservation.onclick = function() {
        modal.style.display = "none";
    }

    // Close modal if clicking outside the modal
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Action for save button
    document.getElementById("saveReservation").onclick = function() {
        var startDate = document.getElementById("startDate").value;
        var endDate = document.getElementById("endDate").value;

        if (!startDate || !endDate) {
            alert("Please select both start and end dates.");
            return;
        }

        if (new Date(endDate) < new Date(startDate)) {
            alert("End date cannot be earlier than the start date.");
            return;
        }

        alert("Reservation saved from: " + startDate + " to " + endDate);
        modal.style.display = "none";
    }
</script>

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
