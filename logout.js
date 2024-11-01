// script.js
//script for logging out
document.addEventListener('DOMContentLoaded', function() {
    const logoutButton = document.getElementById('logoutButton');
    const logoutModal = document.getElementById('logoutModal');
    const closeButton = document.getElementById('closeButton');
    const confirmLogout = document.getElementById('confirmLogout');
    const cancelLogout = document.getElementById('cancelLogout');

    // Show modal when logout button is clicked
    logoutButton.addEventListener('click', function() {
        logoutModal.style.display = 'flex'; // Display the modal
    });

    // Close the modal when the close button is clicked
    closeButton.addEventListener('click', function() {
        logoutModal.style.display = 'none';
    });

    // Handle logout functionality
    confirmLogout.addEventListener('click', function() {
        // Send a request to the logout PHP script
        window.location.href = 'logout.php'; // Redirect to logout script
    });

    // Close the modal when cancel button is clicked
    cancelLogout.addEventListener('click', function() {
        logoutModal.style.display = 'none';
    });

    // Close the modal when clicking outside of the modal content
    window.addEventListener('click', function(event) {
        if (event.target === logoutModal) {
            logoutModal.style.display = 'none';
        }
    });
});
