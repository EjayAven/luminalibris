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
    <script src="circulation-report.js"></script>
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
            <span>&emsp;&emsp;&emsp;Circulation Reports</span>
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
                    <label><input type="checkbox" id="filterBorrowed"> Borrowed Materials</label>
                    <label><input type="checkbox" id="filterReserved"> Reserved Materials</label>
                    <label><input type="checkbox" id="filterPopular"> Popular Materials</label>
                </div>
                <div class="filter-column">
                    <select id="materials">
                        <option value="all">All</option>
                        <option value="book">Book</option>
                        <option value="magazine">Magazine</option>
                        <option value="newspaper">Newspaper</option>
                        <option value="journal">Journal</option>
                    </select>
                </div>
                <div class="filter-column">
                    <label><input type="checkbox" id="filterReturned"> Returned Materials</label>
                    <label><input type="checkbox" id="filterOverdue"> Overdue Materials</label> 
                </div>
                <div class="date-group-cat">
                    <label>Start Date:</label>
                    <input type="date" id="startDate">
                    <div class="date-group">
                        <label>End Date:</label>
                        <input type="date" id="endDate">
                    </div>
                </div>
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
            <h2 class="report-title">CIRCULATION REPORT</h2>
            <p id="reportDate"></p> <!-- Display generated date -->
            <p id="reportGeneratedBy"></p> <!-- Display who generated the report -->
            <table class="report-table" id="reportTable">
                <thead>
                    <tr>
                        <th>Borrower ID</th>
                        <th>Ref ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Publisher</th>
                        <th>Date Published</th>
                        <th>Type</th>
                        <th>Borrower</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Fines</th>
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
            const checkboxes = document.querySelectorAll('.filters-container input[type="checkbox"], #materials');
            const generateButton = document.getElementById('generateButton');
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    generateButton.disabled = !Array.from(checkboxes).some(cb => cb.checked || cb.value !== 'all');
                });
            });
        });

        // Example Data for testing
        const data = [
            { borrowerId: '001', refId: 'B001', title: 'Book 1', author: 'Author A', publisher: 'Publisher A', datePublished: '2020-01-15', type: 'Book', borrower: 'John Doe', borrowDate: '2022-05-01', returnDate: '2022-05-10', fines: 'None' },
            { borrowerId: '002', refId: 'M001', title: 'Magazine 1', author: 'Author B', publisher: 'Publisher B', datePublished: '2021-03-10', type: 'Magazine', borrower: 'Jane Smith', borrowDate: '2022-06-15', returnDate: '2022-06-20', fines: 'None' },
            { borrowerId: '003', refId: 'N001', title: 'Newspaper 1', author: 'Author C', publisher: 'Publisher C', datePublished: '2019-05-10', type: 'Newspaper', borrower: 'Alex Green', borrowDate: '2022-07-01', returnDate: '2022-07-05', fines: '$5' },
            { borrowerId: '004', refId: 'J001', title: 'Journal 1', author: 'Author D', publisher: 'Publisher D', datePublished: '2020-08-20', type: 'Journal', borrower: 'Emily Blue', borrowDate: '2022-08-01', returnDate: '2022-08-10', fines: 'None' }
        ];

        function generateReport() {
            const materials = document.getElementById('materials').value;
            const filterBorrowed = document.getElementById('filterBorrowed').checked;
            const filterReserved = document.getElementById('filterReserved').checked;
            const filterPopular = document.getElementById('filterPopular').checked;
            const filterReturned = document.getElementById('filterReturned').checked;
            const filterOverdue = document.getElementById('filterOverdue').checked;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            // Filter data based on selected filters
            const filteredData = data.filter(item => {
                if (materials !== 'all' && item.type !== materials) return false;
                if (filterBorrowed && item.returnDate !== '') return false; 
                if (filterReserved && item.borrower !== '') return false; // Example for reserved logic
                if (filterPopular && item.borrower === '') return false; // Example for popular logic
                if (filterReturned && item.returnDate === '') return false;
                if (filterOverdue && new Date(item.returnDate) <= new Date(item.borrowDate)) return false;
                if (startDate && new Date(item.borrowDate) < new Date(startDate)) return false;
                if (endDate && new Date(item.returnDate) > new Date(endDate)) return false;
                return true;
            });

            const tableBody = document.getElementById('tableBody');
            tableBody.innerHTML = ''; // Clear the table before populating

            filteredData.forEach(item => {
                const row = `
                    <tr>
                        <td>${item.borrowerId}</td>
                        <td>${item.refId}</td>
                        <td>${item.title}</td>
                        <td>${item.author}</td>
                        <td>${item.publisher}</td>
                        <td>${item.datePublished}</td>
                        <td>${item.type}</td>
                        <td>${item.borrower}</td>
                        <td>${item.borrowDate}</td>
                        <td>${item.returnDate}</td>
                        <td>${item.fines}</td>
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
