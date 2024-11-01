//Script for Photo preview in Add Account
        document.getElementById('photo-upload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const photoPreview = document.getElementById('photo-preview');
                    photoPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                // Clear the preview if no file is selected
                document.getElementById('photo-preview').src = '';
            }
        });

        function toggleType() {
            var role = document.getElementById("role").value;
            var type = document.getElementById("type");
            
            if (role === "Patron") {
                type.disabled = false;
            } else {
                type.disabled = true;
                type.value = "";
            }
        }

        function validateForm() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirm-password").value;

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }
            return true;
        }

        // Function to open the modal and reset its state
        function openChangePasswordModal() {
            //document.getElementById('changePasswordModal').style.display = 'flex';

            const modal = document.getElementById('changePasswordModal');
            const alertMessage = document.getElementById('alert-message');
        
            // Display the modal
            modal.style.display = 'flex';
        
            // Reset the alert message and form inputs
            alertMessage.style.display = 'none';
            alertMessage.innerText = ''; // Clear any previous messages
        
            // Optionally, clear the form inputs
            document.getElementById('current-password').value = '';
            document.getElementById('new-password').value = '';
            document.getElementById('confirm-new-password').value = '';
        }

        function closeChangePasswordModal() {
            //document.getElementById('changePasswordModal').style.display = 'none';

            const modal = document.getElementById('changePasswordModal');
            const alertMessage = document.getElementById('alert-message');

            // Hide the modal
            modal.style.display = 'none';

            // Reset alert message
            alertMessage.style.display = 'none';
            alertMessage.innerText = ''; // Clear any text from the alert

            // Optionally, clear the form inputs
            document.getElementById('current-password').value = '';
            document.getElementById('new-password').value = '';
            document.getElementById('confirm-new-password').value = '';
        }

        // Close the modal when the close button is clicked
        closeButton.addEventListener('click', function() {
            changePasswordModal.style.display = 'none';
        });

        // Close the modal when clicking outside of the modal content
        window.addEventListener('click', function(event) {
            if (event.target === changePasswordModal) {
                changePasswordModal.style.display = 'none';
            }
        });

        
        