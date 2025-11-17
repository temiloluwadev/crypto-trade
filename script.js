// Update cryptocurrency prices every 30 seconds with real data
function updateCryptoPrices() {
    // Only fetch prices if on trading page or dashboard
    if (!document.getElementById('btc-price') && !document.getElementById('eth-price')) {
        return;
    }
    
    fetch('includes/get_prices.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const btcElement = document.getElementById('btc-price');
                const ethElement = document.getElementById('eth-price');
                
                if (btcElement) {
                    btcElement.textContent = '$' + parseFloat(data.prices.BTC).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
                
                if (ethElement) {
                    ethElement.textContent = '$' + parseFloat(data.prices.ETH).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error fetching prices:', error);
        });
}

// Mobile menu toggle for small screens
function initMobileMenu() {
    const nav = document.querySelector('.nav-links');
    const header = document.querySelector('header');
    
    if (window.innerWidth <= 768 && nav) {
        const menuButton = document.createElement('button');
        menuButton.innerHTML = '☰';
        menuButton.style.background = 'none';
        menuButton.style.border = 'none';
        menuButton.style.color = 'white';
        menuButton.style.fontSize = '1.5rem';
        menuButton.style.cursor = 'pointer';
        menuButton.style.padding = '5px';
        
        menuButton.addEventListener('click', () => {
            nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
        });
        
        header.querySelector('.navbar').prepend(menuButton);
    }
}

// Form validation improvements for mobile
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = 'red';
                    
                    // Show error message on mobile
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('field-error')) {
                        const error = document.createElement('small');
                        error.className = 'field-error';
                        error.style.color = 'red';
                        error.style.display = 'block';
                        error.textContent = 'This field is required';
                        field.parentNode.insertBefore(error, field.nextSibling);
                    }
                } else {
                    field.style.borderColor = '';
                    const error = field.nextElementSibling;
                    if (error && error.classList.contains('field-error')) {
                        error.remove();
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
                if (window.innerWidth <= 768) {
                    alert('Please fill in all required fields.');
                }
            }
        });
    });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Update prices immediately if needed
    updateCryptoPrices();
    
    // Then update every 30 seconds
    setInterval(updateCryptoPrices, 30000);
    
    // Initialize mobile features
    initMobileMenu();
    initFormValidation();
    
    // Handle window resize
    window.addEventListener('resize', initMobileMenu);
});

// Add touch event improvements for mobile
document.addEventListener('touchstart', function() {}, {passive: true});