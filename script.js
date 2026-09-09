function showRegistration(role) {
    let formHTML = `
        <h2>${role.charAt(0).toUpperCase() + role.slice(1)} Registration</h2>
        <input type="text" id="username" class="form-control" placeholder="Username" required>
        <input type="email" id="email" class="form-control" placeholder="Email" required>
        <input type="password" id="password" class="form-control" placeholder="Password" minlength="6" required>
        <input type="password" id="confirm-password" class="form-control" placeholder="Confirm Password" minlength="6" required>
        <button class="btn btn-primary mt-2" onclick="register('${role}')">Register</button>
        <button class="btn btn-link" onclick="showLogin('${role}')">Already have an account? Login</button>
    `;
    document.getElementById('form-container').innerHTML = formHTML;
    document.getElementById('form-container').style.display = 'block';
}

function showLogin(role) {
    let formHTML = `
        <h2>${role.charAt(0).toUpperCase() + role.slice(1)} Login</h2>
        <input type="email" id="login-email" class="form-control" placeholder="Email" required>
        <input type="password" id="login-password" class="form-control" placeholder="Password" required>
        <button class="btn btn-primary mt-2" onclick="login('${role}')">Login</button>
        <button class="btn btn-link" onclick="showRegistration('${role}')">Don't have an account? Register</button>
        <button class="btn btn-link" onclick="showPasswordReset()">Forgot Password?</button>
    `;
    document.getElementById('form-container').innerHTML = formHTML;
}

function showPasswordReset() {
    let formHTML = `
        <h2>Password Reset</h2>
        <input type="email" id="reset-email" class="form-control" placeholder="Email" required>
        <button class="btn btn-primary mt-2" onclick="resetPassword()">Send Reset Link</button>
    `;
    document.getElementById('form-container').innerHTML = formHTML;
}

function resetPassword() {
    const email = document.getElementById('reset-email').value;

    if (!email) {
        alert('Please enter your email address.');
        return;
    }

    fetch('password_reset.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);

        if (data.status === 'success') {
            showLogin('user');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error resetting your password. Please try again.');
    });
}

function register(role) {
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    if (!username || !email || !password || !confirmPassword) {
        alert("All fields are required.");
        return;
    }

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "register.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
        const response = JSON.parse(this.responseText);
        alert(response.message);

        if (response.status === 'success') {
            showLogin(role);
        }

        if (response.status === 'error' && response.message.includes('maximum number of admins 👥 reached')) {
            alert("Registration failed: Maximum number of admins 👥 reached.");
            return;
        }
    };

    xhr.send(`username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&role=${role}`);
}

function login(role) {
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    if (!email || !password) {
        alert("All fields are required.");
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "login.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
        const response = JSON.parse(this.responseText);

        if (response.status === 'success') {
            window.location.href = role === 'admin' ? 'admin_dashboard.php' : 'dashboard.php';
        } else {
            alert(response.message);
        }
    };

    xhr.send(`email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`);
}

document.addEventListener("DOMContentLoaded", function () {
    const h1 = document.querySelector('h1');
    const text = h1.textContent;

    const coloredText = Array.from(text).map((char, index) => {
        return `<span style="color: ${index % 2 === 0 ? 'brown' : 'black'};">${char}</span>`;
    }).join('');

    h1.innerHTML = coloredText;
});