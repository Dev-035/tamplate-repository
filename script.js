/**
 * Main function to copy the template link to the clipboard.
 * Uses the modern Clipboard API with a fallback for older browsers.
 * @param {HTMLButtonElement} button - The button element that was clicked.
 */
function copyLink(button) {
    const link = button.getAttribute('data-link');
    
    // Use the modern Clipboard API (requires secure context - HTTPS/localhost)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link).then(() => {
            // Success feedback
            showCopyFeedback(button);
        }).catch(err => {
            console.error('Failed to copy link using clipboard API: ', err);
            // Fallback to old method if Clipboard API fails
            fallbackCopy(link, button);
        });
    } else {
        // Fallback for older browsers or insecure contexts
        fallbackCopy(link, button);
    }
}

/**
 * Fallback function for copying text using a hidden textarea field (old method).
 * @param {string} link - The URL string to copy.
 * @param {HTMLButtonElement} button - The button element for feedback.
 */
function fallbackCopy(link, button) {
    // Create a temporary textarea element
    let tempInput = document.createElement('textarea');
    tempInput.value = link;
    
    // Make the textarea invisible and off-screen
    tempInput.style.position = 'absolute';
    tempInput.style.left = '-9999px';
    tempInput.style.top = '-9999px';
    document.body.appendChild(tempInput);
    
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopyFeedback(button);
        } else {
            console.error('Fallback: document.execCommand("copy") failed.');
            alert("Could not automatically copy the link. Please copy manually: " + link);
        }

    } catch (err) {
        console.error('Failed to copy link via fallback method: ', err);
        button.textContent = 'Error Copying';
        setTimeout(() => {
            button.textContent = 'Copy Link';
        }, 1500);
    }
    
    // Clean up by removing the temporary element
    document.body.removeChild(tempInput);
}

/**
 * Provides visual feedback on the button after successful copy.
 * @param {HTMLButtonElement} button - The button element to update.
 */
function showCopyFeedback(button) {
    const originalText = button.textContent;
    button.textContent = 'Link Copied!';
    button.classList.add('copied');

    setTimeout(() => {
        button.textContent = originalText;
        button.classList.remove('copied');
    }, 1500);
}

/**
 * Search functionality for filtering templates
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');

    const templateGrid = document.querySelector('.template-grid');
    const templateCards = document.querySelectorAll('.template-card');

    // Search input event listener
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            clearButton.style.display = 'none';
            showAllTemplates();
        } else {
            clearButton.style.display = 'block';
            filterTemplates(searchTerm);
        }
    });

    // Clear button event listener
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        clearButton.style.display = 'none';
        showAllTemplates();
        searchInput.focus();
    });

    // Filter templates based on search term
    function filterTemplates(searchTerm) {
        let visibleCount = 0;
        const noResultsMessage = document.querySelector('.no-results');
        
        // Remove existing no results message
        if (noResultsMessage) {
            noResultsMessage.remove();
        }

        templateCards.forEach(card => {
            const keywords = card.getAttribute('data-keywords').toLowerCase();
            const templateName = card.querySelector('.template-name').textContent.toLowerCase();
            
            // Check if search term matches keywords or template name
            if (keywords.includes(searchTerm) || templateName.includes(searchTerm)) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });



        // Show no results message if needed
        if (visibleCount === 0) {
            showNoResults(searchTerm);
        }
    }

    // Show all templates
    function showAllTemplates() {
        const noResultsMessage = document.querySelector('.no-results');
        if (noResultsMessage) {
            noResultsMessage.remove();
        }

        templateCards.forEach(card => {
            card.classList.remove('hidden');
        });


    }



    // Show no results message
    function showNoResults(searchTerm) {
        const noResultsDiv = document.createElement('div');
        noResultsDiv.className = 'no-results';
        noResultsDiv.innerHTML = `
            <i class="fas fa-search"></i>
            <h3>No templates found</h3>
            <p>No templates match your search for "<strong>${searchTerm}</strong>"</p>
            <p>Try searching for: finance, portfolio, shop, restaurant, fitness, etc.</p>
        `;
        templateGrid.appendChild(noResultsDiv);
    }

    // Initialize with all templates visible
});
