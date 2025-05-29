let oldPass = document.getElementById('oldPass');
let newPass = document.getElementById('newPass');
let confPass = document.getElementById('confPass');
let oldEye = document.getElementById('oldEye');
let newEye = document.getElementById('newEye');

oldEye.addEventListener('click', function() {
    oldPass.type = oldPass.type === 'password' ? 'text' : 'password';
    oldEye.src = oldPass.type === 'password' ? 'Images/eye-fill.png' : 'Images/eye-slash.png';
});

newEye.addEventListener('click', function() {
    newPass.type = newPass.type === 'password' ? 'text' : 'password';
    confPass.type = confPass.type === 'password' ? 'text' : 'password';
    newEye.src = newPass.type === 'password' ? 'Images/eye-fill.png' : 'Images/eye-slash.png';
});