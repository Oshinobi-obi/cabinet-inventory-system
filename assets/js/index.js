// Index Page JavaScript Functions

// QR Display Functions (No scanner needed - just display QR code)
function initializeQRDisplay() {
    // QR Display modal doesn't need special initialization
    // The QR code is already generated server-side and displayed in the modal
    console.log('QR Display initialized');
}

// Generate QR Code for Cabinet
function generateQRForCabinet(cabinetId, cabinetNumber, cabinetName) {
    const button = event.target;
    const originalContent = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';
    button.disabled = true;
    
    // Make AJAX request to generate QR code
    fetch(`cabinet.php?action=generate_qr&id=${cabinetId}`)
        .then(response => {
            if (response.redirected) {
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

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeQRDisplay();
    
    // Add any other initialization code here
    console.log('Index page initialized');
});
