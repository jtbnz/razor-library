/**
 * Razor Library - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize mobile menu
    initMobileMenu();

    // Initialize image uploads
    initImageUpload();

    // Initialize usage counters
    initUsageCounters();

    // Initialize confirmations
    initConfirmations();

    // Initialize sort controls
    initSortControls();
});

/**
 * Mobile Menu Toggle
 */
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!menuToggle || !sidebar) return;

    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        if (overlay) {
            overlay.classList.toggle('open');
        }
        document.body.classList.toggle('menu-open');
    });

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.classList.remove('menu-open');
        });
    }
}

/**
 * Image Upload Preview
 */
function initImageUpload() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');

    imageInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const preview = document.querySelector(input.dataset.preview);
            if (!preview) return;

            const file = e.target.files[0];
            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, GIF, or WebP).');
                input.value = '';
                return;
            }

            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('Image must be less than 10MB.');
                input.value = '';
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });
}

/**
 * Usage Counter Controls
 */
function initUsageCounters() {
    const counters = document.querySelectorAll('.usage-counter');

    counters.forEach(function(counter) {
        const decrementBtn = counter.querySelector('.decrement');
        const incrementBtn = counter.querySelector('.increment');
        const countDisplay = counter.querySelector('.count');
        const form = counter.closest('form');

        if (decrementBtn) {
            decrementBtn.addEventListener('click', function(e) {
                e.preventDefault();
                updateCount(-1);
            });
        }

        if (incrementBtn) {
            incrementBtn.addEventListener('click', function(e) {
                e.preventDefault();
                updateCount(1);
            });
        }

        function updateCount(delta) {
            const currentCount = parseInt(countDisplay.textContent) || 0;
            const newCount = Math.max(0, currentCount + delta);

            // Update display optimistically
            countDisplay.textContent = newCount;

            // Submit via AJAX
            const formData = new FormData();
            formData.append('count', newCount);
            formData.append(document.querySelector('meta[name="csrf-token"]')?.content ? 'csrf_token' : '_token',
                document.querySelector('input[name="csrf_token"]')?.value || '');

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function(response) {
                if (!response.ok) {
                    // Revert on error
                    countDisplay.textContent = currentCount;
                }
            }).catch(function() {
                countDisplay.textContent = currentCount;
            });
        }
    });
}

/**
 * Delete Confirmations
 */
function initConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm]');

    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = button.dataset.confirm || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Sort Controls
 */
function initSortControls() {
    const sortSelects = document.querySelectorAll('.sort-select');

    sortSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', select.value);
            window.location.href = url.toString();
        });
    });
}

/**
 * Copy to Clipboard
 */
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(function() {
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('btn-success');

        setTimeout(function() {
            button.textContent = originalText;
            button.classList.remove('btn-success');
        }, 2000);
    }).catch(function() {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);

        button.textContent = 'Copied!';
        setTimeout(function() {
            button.textContent = 'Copy Link';
        }, 2000);
    });
}

/**
 * Image Gallery - Delete Image
 */
function deleteImage(form) {
    if (confirm('Are you sure you want to delete this image?')) {
        form.submit();
    }
}

/**
 * Tab Navigation
 */
function switchTab(tabName) {
    const url = new URL(window.location.href);
    url.searchParams.set('category', tabName);
    window.location.href = url.toString();
}

/**
 * Blade Usage - Add/Update
 */
function updateBladeUsage(razorId, bladeId, action) {
    const form = document.querySelector(`#blade-usage-form-${bladeId}`);
    if (!form) return;

    const countInput = form.querySelector('input[name="count"]');
    const currentCount = parseInt(countInput.value) || 0;

    let newCount;
    if (action === 'increment') {
        newCount = currentCount + 1;
    } else if (action === 'decrement') {
        newCount = Math.max(0, currentCount - 1);
    } else {
        newCount = action; // Direct value
    }

    countInput.value = newCount;

    // Submit form via AJAX
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function(response) {
        return response.json();
    }).then(function(data) {
        if (data.success) {
            // Update display
            const display = document.querySelector(`#blade-usage-display-${bladeId}`);
            if (display) {
                display.textContent = newCount;
            }
        }
    }).catch(function(error) {
        console.error('Error updating blade usage:', error);
    });
}

/**
 * Conditional Field Display (e.g., Badger Grade)
 */
function initConditionalFields() {
    const bristleTypeSelect = document.querySelector('#bristle_type');
    const badgerGradeGroup = document.querySelector('#badger-grade-group');

    if (!bristleTypeSelect || !badgerGradeGroup) return;

    function toggleBadgerGrade() {
        if (bristleTypeSelect.value === 'Badger') {
            badgerGradeGroup.style.display = 'block';
        } else {
            badgerGradeGroup.style.display = 'none';
        }
    }

    bristleTypeSelect.addEventListener('change', toggleBadgerGrade);
    toggleBadgerGrade(); // Initial state
}

// Initialize conditional fields if present
document.addEventListener('DOMContentLoaded', initConditionalFields);
