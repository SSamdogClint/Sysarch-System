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

document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById('loginForm');

    // Handle the form submission
    loginForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent the default form submission behavior

        // Simulate successful login by setting a flag in localStorage
        localStorage.setItem('isLoggedIn', 'true'); // Save the login state

        // Redirect to home page (temporary simulation of a successful login)
        window.location.href = 'home.html'; // Redirects the user to home.html
    });
});