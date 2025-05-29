let conf = document.getElementById('conf');
let lastConfirm = document.getElementById('lastConfirm');
let cancel = document.getElementById('cancel');

conf.addEventListener('click', function() {
    lastConfirm.style.display = 'flex';
    document.body.classList.add('no-scroll');
});

cancel.addEventListener('click', function() {
    lastConfirm.style.display = 'none';
    document.body.classList.remove('no-scroll');
});