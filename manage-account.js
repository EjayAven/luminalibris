//search-delete-account.js
//Automatic search by role selection
document.getElementById('search-by').addEventListener('change', function() {
    document.getElementById('searchForm').submit();
});

//Automatic search ID after pressing enter
document.getElementById('user-id').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault(); // Prevent default Enter key behavior
        document.getElementById('searchForm').submit(); // Submit the form
    }
});

//Automatic general search after pressing enter
document.getElementById('general-search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault(); // Prevent default Enter key behavior
        document.getElementById('searchForm').submit(); // Submit the form
    }
});

// Handling search button click
document.getElementById('searchButton').addEventListener('click', function() {
    document.getElementById('searchForm').submit(); // Submit the form when the search button is clicked
});

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
            window.location.href = `delete_account.php?id=${deleteId}`; // Redirect to delete script
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

// Function to reset the search form and redirect back to manage-account.php
document.getElementById('resetButton').addEventListener('click', function() {
    // Clear the form (reset all form fields)
    var form = document.getElementById('searchForm');
    form.reset();

    // Optionally manually clear the input fields
    document.getElementById('general-search').value = '';
    document.getElementById('user-id').value = '';
    document.getElementById('search-by').selectedIndex = 0; // Reset the dropdown

    // Redirect to manage-account.php to reset the URL and clear search parameters
    window.location.href = 'manage-account.php';
});
