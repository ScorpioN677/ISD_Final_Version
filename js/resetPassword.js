let newPass = document.getElementById('newPass');
let confPass = document.getElementById('confPass');
let eye = document.getElementById('newEye');

eye.addEventListener('click', function() {
    newPass.type = newPass.type === 'password' ? 'text' : 'password';
    confPass.type = confPass.type === 'password' ? 'text' : 'password';
    eye.src = newPass.type === 'password' ? 'Images/eye-fill.png' : 'Images/eye-slash.png';
});