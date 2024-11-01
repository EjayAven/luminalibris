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

// Fetch data from materials table
$sql = "
    SELECT
        referenceID,
        title,
        types,
        section,
        date_acquired,
        `condition`,
        status
    FROM
        materials;
";

$result = $conn->query($sql);

$materialData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $materialData[] = $row;
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
            <a href="librarian-dashboard.php" class="menu-item active">
                <img src="dashboard-icon.png" alt="Dashboard Icon" class="menu-icon">
                <span class="menu-text active">Dashboard</span>
            </a>
            <a href="request.php" class="menu-item active">
                <img src="requests-icon.png" alt="Requests Icon" class="menu-icon">
                <span class="menu-text">Requests</span>
            </a>
            <div class="menu-item">
                <img src="materials-icon.png" alt="Materials Icon" class="menu-icon">
                <span class="menu-text">Materials</span>
                <div class="submenu">
                    <a href="add-material.php">Add Materials</a>
                    <hr>
                    <a href="manage-material.php">Manage Materials</a>
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
                    <a href="librarian-catalog-record.php">Catalog</a>
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
            <a href="javascript:history.back()">
                <img src="back-outline.png" alt="Logo" class="header-logo">
            </a>
            <img src="lumina-blue.png" alt="Logo" class="header-logo"> 
        </div>
        <div class="header1">
            <span>&emsp;&emsp;&emsp;Inventory Reports</span>
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
        <div class="filters-section">
            <div class="filters-container">
                <div class="filter-column">
                    <label for="materials">Material Type</label>
                    <select id="materials">
                        <option value="all">All</option>
                        <option value="book">Book</option>
                        <option value="magazine">Magazine</option>
                        <option value="newspaper">Newspaper</option>
                        <option value="journal">Journal</option>
                    </select>
                </div>
                <div class="filter-column">
                    <label><input type="checkbox" id="filterBorrowed"> Borrowed Materials</label>
                    <label><input type="checkbox" id="filterReserved"> Reserved Materials</label>
                </div>
                <div class="filter-column">
                    <label for="section">Section</label>
                    <select id="section">
                        <option value="all">All</option>
                        <option value="fiction">Fiction</option>
                        <option value="non-fiction">Non-Fiction</option>
                        <option value="reference">Reference</option>
                    </select>
                </div>
                <div class="filter-column">
                    <label for="subject">Subject</label>
                    <select id="subject">
                        <option value="all">All</option>
                        <option value="science">Science</option>
                        <option value="literature">Literature</option>
                        <option value="history">History</option>
                    </select>
                </div>
                <div class="date-group-cat">
                    <label>Start Date:</label>
                    <input type="date" id="startDate">
                    <div class="date-group">
                        <label>End Date:</label>
                        <input type="date" id="endDate">
                    </div>
                </div>
                <!-- Generate Button -->
                <div class="account-generate">
                    <button id="generateButton" class="btn-generate" onclick="generateReport()" disabled>
                        <i class="fas fa-magic"></i> Generate
                    </button>
                </div>
            </div>
        </div>
        <div class="report-section" id="reportSection" style="display:none;">
            <div class="report-header">
                <img src="logo.png" alt="Institution Logo" class="report-logo">
                <div class="institution-info">
                    <h3>Apostolic Vicariate of Calapan</h3>
                    <p>SAINT AUGUSTINE SEMINARY</p>
                    <p>Suqui, Calapan City, Oriental Mindoro</p>
                </div>
            </div>
            <h2 class="report-title">INVENTORY REPORT</h2>
            <p id="reportDate"></p>
            <p id="reportGeneratedBy"></p>
            <table class="report-table" id="reportTable">
                <thead>
                    <tr>
                        <th>Reference ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Section</th>
                        <th>Date Acquired</th>
                        <th>Condition</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Data will be injected here after clicking Generate -->
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
        // Enable Generate Button based on checkbox or filter selection
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.filters-container input[type="checkbox"], #materials, #section, #subject');
            const generateButton = document.getElementById('generateButton');
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    generateButton.disabled = !Array.from(checkboxes).some(cb => cb.checked || cb.value !== 'all');
                });
            });
        });

        // Fetch PHP data and convert it to JavaScript
        const data = <?php echo json_encode($materialData); ?>;

        function generateReport() {
            const materials = document.getElementById('materials').value;
            const filterBorrowed = document.getElementById('filterBorrowed').checked;
            const filterReserved = document.getElementById('filterReserved').checked;
            const section = document.getElementById('section').value;
            const subject = document.getElementById('subject').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            // Filter data based on selected filters
            const filteredData = data.filter(item => {
                if (materials !== 'all' && item.types !== materials) return false;
                if (section !== 'all' && item.section !== section) return false;
                if (filterBorrowed && item.status !== 'Borrowed') return false;
                if (filterReserved && item.status !== 'Reserved') return false;
                if (startDate && new Date(item.date_acquired) < new Date(startDate)) return false;
                if (endDate && new Date(item.date_acquired) > new Date(endDate)) return false;
                return true;
            });

            const tableBody = document.getElementById('tableBody');
            tableBody.innerHTML = ''; // Clear the table before populating

            filteredData.forEach(item => {
                const row = `
                    <tr>
                        <td>${item.referenceID}</td>
                        <td>${item.title}</td>
                        <td>${item.types}</td>
                        <td>${item.section}</td>
                        <td>${item.date_acquired}</td>
                        <td>${item.condition}</td>
                        <td>${item.status}</td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });

            // Show the report section
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

            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }
                csv.push(row.join(",")); // Join columns with a comma
            }

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
