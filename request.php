<?php
session_start();

// Check if the user is logged in and has a librarian role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'librarian') {
    header("Location: login-page.php");
    exit();
}

include 'db_connection.php';

// The user ID is retrieved from the session
$user_id = $_SESSION['username'];

// Prepare and execute the statement to get user details
$stmt = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) AS fullname, role, photo FROM accounts WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullname = $row['fullname'];
    $role = $row['role'];

    $photo = $row['photo'];
    if ($photo) {
        $photo_base64 = base64_encode($photo);
        $photo_mime = 'image/jpeg';
        $photo_src = 'data:' . $photo_mime . ';base64,' . $photo_base64;
    } else {
        $photo_src = 'default-profile-pic.jpg';
    }
} else {
    $fullname = "Unknown User";
    $role = "Unknown Role";
    $photo_src = 'default-profile-pic.jpg';
}

$stmt->close();

// Retrieve only pending reservation requests
$reservation_query = "SELECT reserve_id, ref_id, title, author, publisher, date_published, type, borrower, borrow_date, return_date 
                      FROM reservation 
                      WHERE status = 'pending'";
$reservation_result = $conn->query($reservation_query);

if (!$reservation_result) {
    die("Error retrieving reservations: " . $conn->error);
}

$reservations = [];
if ($reservation_result->num_rows > 0) {
    while ($row = $reservation_result->fetch_assoc()) {
        $reservations[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Lumina Libris</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="modal.css">
    <link href='Poppins.css' rel='stylesheet'>
    <script src="logout.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
            <a href="library-requests.php" class="menu-item active">
                <img src="requests-icon.png" alt="Requests Icon" class="menu-icon">
                <span class="menu-text">Requests</span>
            </a>
            <div class="menu-item">
                <img src="materials-icon.png" alt="Materials Icon" class="menu-icon">
                <span class="menu-text">Materials</span>
                <div class="submenu">
                    <a href="#">Add Materials</a>
                    <hr>
                    <a href="#">Manage Materials</a>
                </div>
            </div>
            <div class="menu-item">
                <img src="accounts-icon.png" alt="Accounts Icon" class="menu-icon">
                <span class="menu-text">Accounts</span>
                <div class="submenu">
                    <a href="#">Librarian</a>
                    <hr>
                    <a href="patron-acc-lib.php">Patron</a>
                </div>
            </div>
            <div class="menu-item">
                <img src="reports-icon.png" alt="Reports Icon" class="menu-icon">
                <span class="menu-text">Reports</span>
                <div class="submenu">
                <a href="librarian-patron-record.php">Patron Record</a>
                    <hr>
                    <a href="librarian-catalog-report.php">Catalog</a>
                    <hr>
                    <a href="circulation-librarian-report.php">Circulation</a>
                    <hr>
                    <a href="inventory-librarian-report.php">Inventory</a>
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
            <span>&emsp;&emsp;&emsp;Requests</span>
        </div>
        <div class="separator"></div>

        <!-- Display Success or Error messages -->
        <?php if (isset($_GET['message'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!-- Reservation Table -->
        <table>
            <thead>
                <tr>
                    <th>Reservation Id</th>
                    <th>Ref ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Publisher</th>
                    <th>Date Published</th>
                    <th>Type</th>
                    <th>Borrower</th>
                    <th>Borrow Date</th>
                    <th>Return Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reservations)): ?>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['reserve_id']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['ref_id']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['title']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['author']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['publisher']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['date_published']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['type']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['borrower']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['borrow_date']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['return_date']); ?></td>
                            <td>
                                <i class="fas fa-check-circle fa-2x" style="color: green;" onclick="showAcceptModal(<?php echo $reservation['reserve_id']; ?>)"></i>
                                <i class="fas fa-times-circle fa-2x" style="color: red;" onclick="showDenyModal(<?php echo $reservation['reserve_id']; ?>)"></i>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center;">No reservations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Accept Modal -->
    <div id="acceptModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('acceptModal')">&times;</span>
            <h2>Confirm Accept</h2>
            <p>Are you sure you want to accept this reservation?</p>
            <div class="modal-buttons">
                <button class="modal-button" id="confirmAccept" onclick="confirmAction('accept')">Accept</button>
                <button class="modal-button deny" onclick="closeModal('acceptModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Deny Modal -->
    <div id="denyModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('denyModal')">&times;</span>
            <h2>Confirm Deny</h2>
            <p>Are you sure you want to deny this reservation?</p>
            <div class="modal-buttons">
                <button class="modal-button" id="confirmDeny" onclick="confirmAction('deny')">Deny</button>
                <button class="modal-button deny" onclick="closeModal('denyModal')">Cancel</button>
            </div>
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

<script>
    let currentReservationId = null;

    function showAcceptModal(reserveId) {
        currentReservationId = reserveId;
        document.getElementById('acceptModal').style.display = 'block';
    }

    function showDenyModal(reserveId) {
        currentReservationId = reserveId;
        document.getElementById('denyModal').style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        currentReservationId = null;
    }

    function confirmAction(actionType) {
        if (actionType === 'accept') {
            window.location.href = 'accept-reservation.php?id=' + currentReservationId;
        } else if (actionType === 'deny') {
            window.location.href = 'deny-reservation.php?id=' + currentReservationId;
        }
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal('acceptModal');
            closeModal('denyModal');
        }
    }
    
  </script>
</body>
</html>
