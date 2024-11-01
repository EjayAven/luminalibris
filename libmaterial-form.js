document.addEventListener('DOMContentLoaded', function() {
    var typesSelect = document.getElementById('types');
    var bookForm = document.getElementById('book-form');
    var periodicalForm = document.getElementById('periodical-form');
    var mediaForm = document.getElementById('media-form');

    function clearInputs(form) {
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.value = '';
        });
    }

    typesSelect.addEventListener('change', function() {
        var selectedType = this.value;

        // Hide all forms and clear input values initially
        bookForm.style.display = 'none';
        periodicalForm.style.display = 'none';
        mediaForm.style.display = 'none';

        clearInputs(bookForm);
        clearInputs(periodicalForm);
        clearInputs(mediaForm);

        // Show the form corresponding to the selected type
        if (selectedType === 'Book') {
            bookForm.style.display = 'flex';
        } else if (selectedType === 'Magazine' || selectedType === 'Journal' || selectedType === 'Newspaper') {
            periodicalForm.style.display = 'flex';
        } else if (selectedType === 'CD' || selectedType === 'DVD' || selectedType === 'Tape Recording') {
            mediaForm.style.display = 'flex';
        }
    });
});

// Image preview
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
        document.getElementById('photo-preview').src = '';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var typesSelect = document.getElementById('types');
    var bookForm = document.getElementById('book-form');
    var periodicalForm = document.getElementById('periodical-form');
    var mediaForm = document.getElementById('media-form');

    // Function to enable/disable required fields based on form visibility
    function toggleRequired(form, isRequired) {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (isRequired) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    }

    typesSelect.addEventListener('change', function() {
        var selectedType = this.value;

        // Hide all forms and remove 'required' attributes
        bookForm.style.display = 'none';
        periodicalForm.style.display = 'none';
        mediaForm.style.display = 'none';

        toggleRequired(bookForm, false);
        toggleRequired(periodicalForm, false);
        toggleRequired(mediaForm, false);

        // Show the form corresponding to the selected type and set 'required' attributes
        if (selectedType === 'Book') {
            bookForm.style.display = 'flex';
            toggleRequired(bookForm, true);
        } else if (selectedType === 'Magazine' || selectedType === 'Journal' || selectedType === 'Newspaper') {
            periodicalForm.style.display = 'flex';
            toggleRequired(periodicalForm, true);
        } else if (selectedType === 'CD' || selectedType === 'DVD' || selectedType === 'Tape Recording') {
            mediaForm.style.display = 'flex';
            toggleRequired(mediaForm, true);
        }
    });
});

// Image preview
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
        document.getElementById('photo-preview').src = '';
    }
});
