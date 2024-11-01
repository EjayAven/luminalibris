<?php
session_start(); 

// Check if the user is logged in and has a librarian role
if (!isset($_SESSION['username']) || !in_array(strtolower($_SESSION['role']), ['admin', 'administrator'])) {
    header("Location: login-page.php"); // Redirect to login page if not logged in or not an admin
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
    
    // Handle the BLOB data for profile picture
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

// Fetch data based on the SQL query
$sql = "
    SELECT 
        accounts.id AS 'id',
        accounts.lastname AS 'lastname',
        accounts.firstname AS 'firstname',
        accounts.midname AS 'midname',
        accounts.type AS 'type',
        accounts.status AS 'status',
        fines.borrow AS 'borrow',
        fines.returned AS 'returned',
        fines.fines AS 'fines'
    FROM 
        accounts
    LEFT JOIN 
        fines ON accounts.id = fines.id 
    WHERE 
        accounts.role = 'patron'
    AND 
        (accounts.type = 'student' OR accounts.type = 'visitor');
";

$result = $conn->query($sql);

$patronData = [];
if ($result->num_rows > 0) {
    // Store the results into an array
    while($row = $result->fetch_assoc()) {
        $patronData[] = $row;
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
    <link rel="stylesheet" href="report.css">
    <link href='Poppins.css' rel='stylesheet'>
    <script src="patron-report.js"></script>
    <script src="logout.js"></script>
    <!-- Include Font Awesome for icons -->
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
            <a href="javascript:history.back()">
                <img src="back-outline.png" alt="Logo" class="header-logo">
            </a>
            <img src="lumina-blue.png" alt="Logo" class="header-logo"> 
        </div>
        <div class="header1">
            <span>&emsp;&emsp;&emsp;Patron Reports</span>
        </div>
        <div class="header-icons">
            <button class="btn-print" onclick="printReport()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn-download" onclick="downloadCSV()">
                <i class="fas fa-download"></i> Download
            </button>
        </div>
        <div class="separator"></div>
        
        <!-- Filter section -->
        <div class="filters-section">
            <div class="filters-container">
                <div class="filter-column">
                    <label><input type="checkbox" id="filterAll"> All</label>
                    <label><input type="checkbox" id="filterBorrowed"> Who borrowed</label>
                    <label><input type="checkbox" id="filterFines"> Who have outstanding fines</label>
                </div>
                <div class="filter-column">
                    <label><input type="checkbox" id="filterStudent"> Student</label>
                    <label><input type="checkbox" id="filterVisitor"> Visitor</label>
                </div>
                <div class="filter-column">
                    <label><input type="checkbox" id="filterActive"> Active</label>
                    <label><input type="checkbox" id="filterInactive"> Inactive</label>
                </div>
                <div class="date-group">
                    <label>Start Date:</label>
                    <input type="date" id="startDate">
                    <label>End Date:</label>
                    <input type="date" id="endDate">
                </div>
                <div class="account-generate">
                    <label><input type="checkbox" id="filterAccount"> Account</label>
                    <input type="text" id="filterAccountId" placeholder="User ID">
                </div>
                <div class="account-generate">
                    <button class="btn-generate" onclick="generateReport()">
                        <i class="fas fa-magic"></i> Generate
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Table to display patron records -->
        <div class="report-section" id="reportSection" style="display:none;">
            <div class="report-header">
                <img src="logo.png" alt="Institution Logo" class="report-logo">
                <div class="institution-info">
                    <h3>Apostolic Vicariate of Calapan</h3>
                    <p>SAINT AUGUSTINE SEMINARY</p>
                    <p>Suqui, Calapan City, Oriental Mindoro</p>
                </div>
            </div>
            <h2 class="report-title">PATRON RECORD REPORT</h2>
            <p id="reportDate"></p> <!-- This will display the generated date -->
            <p id="reportGeneratedBy"></p> <!-- This will display who generated the report -->
            <table class="report-table" id="reportTable">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Borrowed</th>
                        <th>Returned</th>
                        <th>Fines</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($patronData)): ?>
                        <?php foreach ($patronData as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                                <td><?php echo htmlspecialchars($row['midname']); ?></td>
                                <td><?php echo htmlspecialchars($row['type']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td><?php echo htmlspecialchars($row['borrow']); ?></td>
                                <td><?php echo htmlspecialchars($row['returned']); ?></td>
                                <td><?php echo htmlspecialchars($row['fines']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No records found</td>
                        </tr>
                    <?php endif; ?>
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
    <script>
        function generateReport() {
            const filterAll = document.getElementById('filterAll').checked;
            const filterBorrowed = document.getElementById('filterBorrowed').checked;
            const filterFines = document.getElementById('filterFines').checked;
            const filterStudent = document.getElementById('filterStudent').checked;
            const filterVisitor = document.getElementById('filterVisitor').checked;
            const filterActive = document.getElementById('filterActive').checked;
            const filterInactive = document.getElementById('filterInactive').checked;
            const filterAccount = document.getElementById('filterAccount').checked;
            const filterAccountId = document.getElementById('filterAccountId').value.trim();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            const tableBody = document.getElementById('reportTable').getElementsByTagName('tbody')[0];
            tableBody.innerHTML = ''; // Clear the table before generating new data

            // Example data (replace with actual data)
            const data = <?php echo json_encode($patronData); ?>;

            const filteredData = data.filter(item => {
                if (filterAll) return true;
                if (filterBorrowed && item.borrow) return true;
                if (filterFines && item.fines) return true;
                if (filterStudent && item.type === 'student') return true;
                if (filterVisitor && item.type === 'visitor') return true;
                if (filterActive && item.status === 'active') return true;
                if (filterInactive && item.status === 'inactive') return true;
                if (filterAccount && item.id === filterAccountId) return true;
                return false;
            });

            filteredData.forEach(item => {
                const row = `<tr>
                    <td>${item.id}</td>
                    <td>${item.lastname}</td>
                    <td>${item.firstname}</td>
                    <td>${item.midname}</td>
                    <td>${item.type}</td>
                    <td>${item.status}</td>
                    <td>${item.borrow}</td>
                    <td>${item.returned}</td>
                    <td>${item.fines}</td>
                </tr>`;
                tableBody.innerHTML += row;
            });

            // Show the report section after generating the report
            document.getElementById('reportSection').style.display = 'block';

            // Add the current date to the report
            const currentDate = new Date();
            const dateString = currentDate.toLocaleDateString() + " " + currentDate.toLocaleTimeString();
            document.getElementById('reportDate').innerHTML = "Report Generated On: " + dateString;

            // Display who generated the report
            document.getElementById('reportGeneratedBy').innerHTML = "Generated by: <?php echo htmlspecialchars($fullname); ?>";
        }
        // Function to download the report as CSV
        function downloadCSV() {
            let csv = [];
            const rows = document.querySelectorAll("#reportTable tr");

            // Loop through each row and grab the data
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll("td, th");
                
                // Loop through each column
                for (let j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }
                
                csv.push(row.join(",")); // Join columns with a comma
            }

            // Create CSV file and trigger download
            const csvFile = new Blob([csv.join("\n")], { type: 'text/csv' });
            const downloadLink = document.createElement("a");
            downloadLink.download = "report.csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }

        // Function to print the report
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>
