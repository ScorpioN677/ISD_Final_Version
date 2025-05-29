let pass = document.getElementById('password');
let eye = document.getElementById('eye');

eye.addEventListener('click', function() {
    pass.type = pass.type === 'password' ? 'text' : 'password';
    eye.src = pass.type === 'password' ? 'Images/eye-fill.png' : 'Images/eye-slash.png';
});