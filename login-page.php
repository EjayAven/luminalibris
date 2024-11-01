<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In- Lumina Libris</title>
    <link rel="stylesheet" href="style-login.css">
    <link href='Poppins.css' rel='stylesheet'>
</head>
<body>
    <div class="container">
        <a href="login-page.php">
            <img src="lumina-logo.png" alt="Lumina Logo" class="logo">
        </a>
        <form action="login.php" method="post">
            <label for="userID">User ID</label>
                <input type="text" id="id" name="username" required>
            <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <p>New User? <a href="request.html">Request Here</a></p>
        <img src="sas-logo.png" alt="Bottom Logo" class="bottom-logo">
        <p>"Where Faith Finds Knowledge,<br>and Knowledge Finds Soul."</p>
    </div>

    <!-- Modals -->
    <div id="modal-error" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModalAndRedirect('modal-error')">&times;</span>
            <p id="modal-error-message"></p>
        </div>
    </div>

    <div id="modal-success" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModalAndRedirect('modal-success')">&times;</span>
            <p id="modal-success-message"></p>
        </div>
    </div>

    <script>
        // Function to display the modal with the message
        function showModal(id, message) {
            var modal = document.getElementById(id);
            var messageElement = document.getElementById(id + '-message');
            messageElement.textContent = message;
            modal.style.display = 'flex';

            // Auto-close modal after 5 seconds and redirect
            setTimeout(function() {
                closeModalAndRedirect(id);
            }, 3000); // Close after 3 seconds
        }

        // Function to close the modal
        function closeModal(id) {
            var modal = document.getElementById(id);
            modal.style.display = 'none';
        }

        // Function to close the modal and redirect
        function closeModalAndRedirect(modalId) {
            closeModal(modalId);
            setTimeout(function() {
                window.location.href = 'login-page.php'; // Redirect after closing the modal
            }, 300); // Adjust the timeout as needed to ensure the modal closes first
        }

        // Close the modal when clicking outside of the modal content
        function setupModalClickOutsideClose(modalId) {
            var modal = document.getElementById(modalId);
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModalAndRedirect(modalId);
                }
            });
        }

        setupModalClickOutsideClose('modal-error');
        setupModalClickOutsideClose('modal-success');

        // Function to get query parameters
        function getQueryParams() {
            var params = {};
            window.location.search.substring(1).split("&").forEach(function(part) {
                var item = part.split("=");
                if (item[0]) {
                    params[item[0]] = decodeURIComponent(item[1] || '');
                }
            });
            return params;
        }

        // Check for PHP messages
        var params = getQueryParams();
        if (params.message) {
            var modalId = params.message_type === 'success' ? 'modal-success' : 'modal-error';
            showModal(modalId, params.message);
        }
    </script>
</body>
</html>
