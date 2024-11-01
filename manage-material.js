//Delete Account
document.addEventListener('DOMContentLoaded', function () {
    let deleteId = null; // To store the ID of the record to delete
    // Show the delete modal when the delete icon is clicked
    document.querySelectorAll('.action-icon.delete').forEach(img => {
        img.addEventListener('click', function () {
            const row = img.closest('tr');
            deleteId = row.querySelector('td:first-child').innerText; // Get the ID from the table row
            document.getElementById('deleteModal').style.display = 'flex'; // Show the modal
        });
    });

// Confirm deletion
document.getElementById('confirmDelete').addEventListener('click', function () {
    if (deleteId) {
        // Send a request to the server to delete the record
        window.location.href = delete_material.php?referenceID=${deleteId}; // Redirect to delete script
    }
});

    // Close modal when close button is clicked
    document.getElementById('deleteCloseButton').addEventListener('click', function () {
        document.getElementById('deleteModal').style.display = 'none';
    });

    // Close modal when cancel button is clicked
    document.getElementById('cancelDelete').addEventListener('click', function () {
        document.getElementById('deleteModal').style.display = 'none';
    });

    // Close the modal when clicking outside of the modal content
    window.addEventListener('click', function(event) {
        if (event.target === deleteModal) {
            deleteModal.style.display = 'none';
        }
    });
});

// Handle the generate button click event
document.addEventListener('DOMContentLoaded', function () {
    // Handle the generate button click event
    document.getElementById('generateButton').addEventListener('click', function () {
        const materialType = document.getElementById('materialType').value;
        const section = document.getElementById('section').value;
        
        // Redirect to the current page with query parameters for filtering
        window.location.href = manage-material.php?materialType=${materialType}&section=${section};
    });
});

    // Handle the reset button click event
    document.getElementById('resetButton').addEventListener('click', function () {
        // Reload the page without any query parameters to reset the search
        window.location.href = 'manage-material.php';
    });