const toggle = document.getElementById('dropdownToggle');
const loginBtn = document.getElementById('loginItem');
const registerBtn = document.getElementById('registerItem');

// Function to update the text and then navigate
loginBtn.addEventListener('click', function(e) {
    e.preventDefault(); 
    toggle.textContent = 'Login';
    
    // Manually tell the browser to change the page
    window.location.href = 'login.html'; 
});

registerBtn.addEventListener('click', function(e) {
    e.preventDefault(); 
    toggle.textContent = 'Register';
    
    // Manually tell the browser to change the page
    window.location.href = 'register.html';
});