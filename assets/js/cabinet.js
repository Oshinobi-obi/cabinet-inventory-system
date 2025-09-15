// Cabinet Management JavaScript Functions

// Global variables
let editItemCount = 0;
let categories = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cabinet management page initialized');
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Load categories for edit form
    loadCategories();
    
    // Initialize event listeners
    initializeEventListeners();
});

// Load categories from the page
function loadCategories() {
    // Use categories from global variable if available
    if (window.cabinetCategories) {
        categories = window.cabinetCategories;
    } else {
        // Fallback: load from DOM
        const categorySelects = document.querySelectorAll('select[name*="category_id"]');
        if (categorySelects.length > 0) {
            const firstSelect = categorySelects[0];
            categories = Array.from(firstSelect.options).map(option => ({
                id: option.value,
                name: option.text
            }));
        }
    }
}

// Initialize all event listeners
function initializeEventListeners() {
    // View cabinet buttons
    document.querySelectorAll('.view-cabinet-btn').forEach(button => {
        button.addEventListener('click', function() {
            const cabinetId = this.getAttribute('data-cabinet-id');
            viewCabinet(cabinetId);
        });
    });
    
    // Edit cabinet buttons
    document.querySelectorAll('.edit-cabinet-btn').forEach(button => {
        button.addEventListener('click', function() {
            const cabinetId = this.getAttribute('data-cabinet-id');
            editCabinet(cabinetId);
        });
    });
    
    // Delete cabinet buttons
    document.querySelectorAll('.delete-cabinet-btn').forEach(button => {
        button.addEventListener('click', function() {
            const cabinetId = this.getAttribute('data-cabinet-id');
            const cabinetName = this.getAttribute('data-cabinet-name');
            showDeleteConfirmation(cabinetId, cabinetName);
        });
    });
    
    // Add item button
    const addItemBtn = document.getElementById('add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', addNewItem);
    }
    
    // Edit add item button
    const editAddItemBtn = document.getElementById('add-edit-item');
    if (editAddItemBtn) {
        editAddItemBtn.addEventListener('click', addNewItem);
    }
    
    // Form submission
    const editForm = document.getElementById('editCabinetForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditFormSubmit);
    }
}

// View cabinet function
function viewCabinet(cabinetId) {
    console.log('viewCabinet called with ID:', cabinetId);
    const url = `cabinet_api.php?action=get_cabinet&id=${cabinetId}`;
    console.log('Fetching URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);
            if (data.success) {
                displayCabinetView(data.cabinet, data.items);
                const modal = new bootstrap.Modal(document.getElementById('viewCabinetModal'));
                modal.show();
            } else {
                alert('Error loading cabinet details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error loading cabinet details: ' + error.message);
        });
}

// Display cabinet view in modal
function displayCabinetView(cabinet, items) {
    let itemsHtml = '';
    if (items && items.length > 0) {
        itemsHtml = '<h6 class="mt-3">Items:</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Item Name</th><th>Category</th><th>Quantity</th></tr></thead><tbody>';
        items.forEach(item => {
            itemsHtml += `<tr><td>${item.name}</td><td>${item.category}</td><td>${item.quantity}</td></tr>`;
        });
        itemsHtml += '</tbody></table></div>';
    } else {
        itemsHtml = '<p class="text-muted mt-3">No items in this cabinet.</p>';
    }
    
    let photoHtml = '';
    if (cabinet.photo_path) {
        photoHtml = `<img src="${cabinet.photo_path}" alt="Cabinet Photo" class="img-fluid mb-3" style="max-height: 200px;">`;
    }
    
    let qrHtml = '';
    if (cabinet.qr_path) {
        qrHtml = `
            <div class="mt-3">
                <h6><i class="fas fa-qrcode me-2"></i>QR Code</h6>
                <div class="border rounded p-2 bg-light text-center" style="max-width: 150px;">
                    <img src="${cabinet.qr_path}" alt="QR Code" class="img-fluid" style="max-width: 120px;">
                </div>
                <small class="text-muted">Scan to view cabinet details</small>
            </div>
        `;
    } else {
        qrHtml = `
            <div class="mt-3">
                <h6><i class="fas fa-qrcode me-2"></i>QR Code</h6>
                <span class="badge bg-secondary">Not Generated</span>
                <br><small class="text-muted">Click "Generate QR Code" to create</small>
            </div>
        `;
    }
    
    document.getElementById('viewCabinetContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>${cabinet.name}</h5>
                <p><strong>Cabinet Number:</strong> ${cabinet.cabinet_number}</p>
                <p><strong>Total Items:</strong> ${cabinet.item_count}</p>
                <p><strong>Last Updated:</strong> ${new Date(cabinet.updated_at).toLocaleDateString()}</p>
                ${photoHtml}
                ${qrHtml}
            </div>
            <div class="col-md-6">
                ${itemsHtml}
            </div>
        </div>
    `;
}

// Edit cabinet function
function editCabinet(cabinetId) {
    console.log('editCabinet called with ID:', cabinetId);
    const url = `cabinet_api.php?action=get_cabinet&id=${cabinetId}`;
    console.log('Fetching URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);
            if (data.success) {
                populateEditForm(data.cabinet, data.items);
                const modal = new bootstrap.Modal(document.getElementById('editCabinetModal'));
                modal.show();
            } else {
                alert('Error loading cabinet details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error loading cabinet details: ' + error.message);
        });
}

// Populate edit form
function populateEditForm(cabinet, items) {
    document.getElementById('edit_cabinet_id').value = cabinet.id;
    document.getElementById('edit_cabinet_number').value = cabinet.cabinet_number;
    document.getElementById('edit_name').value = cabinet.name;
    document.getElementById('edit_existing_photo').value = cabinet.photo_path || '';
    
    // Show current photo if exists
    const photoPreview = document.getElementById('current_photo_preview');
    if (cabinet.photo_path) {
        // Create img element with error handling
        const img = document.createElement('img');
        img.src = cabinet.photo_path;
        img.alt = 'Current Photo';
        img.style.maxHeight = '100px';
        img.className = 'img-thumbnail';
        img.onerror = function() {
            photoPreview.innerHTML = '<small class="text-muted text-warning">Photo file not found</small>';
        };
        photoPreview.innerHTML = '<small class="text-muted">Current photo:</small><br>';
        photoPreview.appendChild(img);
    } else {
        photoPreview.innerHTML = '';
    }
    
    // Clear and populate items
    const container = document.getElementById('edit-items-container');
    container.innerHTML = '';
    editItemCount = 0;
    
    if (items && items.length > 0) {
        items.forEach(item => {
            addEditItem(item);
        });
    }
}

// Add new item to edit form
function addEditItem(item = null) {
    const container = document.getElementById('edit-items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    
    let categoriesOptions = '<option value="">Select Category</option>';
    categories.forEach(category => {
        if (category.id) {
            const selected = item && item.category_id == category.id ? 'selected' : '';
            categoriesOptions += `<option value="${category.id}" ${selected}>${category.name}</option>`;
        }
    });
    
    newRow.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Item Name</label>
                <input type="text" class="form-control" name="edit_items[${editItemCount}][name]" value="${item ? item.name : ''}" required>
                ${item ? `<input type="hidden" name="edit_items[${editItemCount}][id]" value="${item.id}">` : ''}
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select class="form-control" name="edit_items[${editItemCount}][category_id]" required>
                    ${categoriesOptions}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control" name="edit_items[${editItemCount}][quantity]" value="${item ? item.quantity : 1}" min="1" required>
            </div>
            <div class="col-md-3 align-self-end">
                <button type="button" class="btn btn-danger remove-edit-item">
                    <i class="fas fa-trash me-1"></i> Remove
                </button>
            </div>
        </div>
    `;
    
    // Add remove functionality
    newRow.querySelector('.remove-edit-item').addEventListener('click', function() {
        newRow.remove();
    });
    
    container.appendChild(newRow);
    editItemCount++;
}

// Add new item button
function addNewItem() {
    addEditItem();
}

// Handle edit form submission
function handleEditFormSubmit(event) {
    console.log('handleEditFormSubmit called');
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('edit_cabinet', '1');
    
    // Look for submit button associated with this form
    const submitBtn = event.target.querySelector('button[type="submit"]') || 
                     document.querySelector('button[type="submit"][form="editCabinetForm"]');
    if (!submitBtn) {
        console.error('Submit button not found in form or associated with form');
        return;
    }
    
    console.log('Submit button found:', submitBtn);
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
    submitBtn.disabled = true;
    
    fetch('cabinet.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Close modal and reload page
        const modal = bootstrap.Modal.getInstance(document.getElementById('editCabinetModal'));
        modal.hide();
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating cabinet: ' + error.message);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show delete confirmation
function showDeleteConfirmation(cabinetId, cabinetName) {
    const modal = new bootstrap.Modal(document.getElementById('deleteCabinetModal'));
    
    document.getElementById('deleteCabinetName').textContent = cabinetName;
    document.getElementById('confirmDeleteBtn').onclick = function() {
        deleteCabinet(cabinetId);
    };
    
    modal.show();
}

// Delete cabinet
function deleteCabinet(cabinetId) {
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    if (!deleteBtn) {
        console.error('Delete button not found');
        return;
    }
    
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Deleting...';
    deleteBtn.disabled = true;
    
    window.location.href = `cabinet.php?action=delete&id=${cabinetId}`;
}

// QR Code generation for specific cabinet
function generateQRForCabinet(cabinetId, cabinetNumber, cabinetName) {
    const button = event.target;
    if (!button) {
        console.error('QR generation button not found');
        return;
    }
    
    const originalContent = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';
    button.disabled = true;
    
    // Make request to generate QR code
    fetch(`cabinet.php?action=generate_qr&id=${cabinetId}`)
        .then(response => {
            if (response.redirected || response.url.includes('cabinet.php')) {
                // Page will redirect, reload to show the updated QR
                window.location.reload();
            } else {
                return response.text();
            }
        })
        .catch(error => {
            console.error('Error generating QR code:', error);
            alert('Failed to generate QR code. Please try again.');
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalContent;
            button.disabled = false;
        });
}
