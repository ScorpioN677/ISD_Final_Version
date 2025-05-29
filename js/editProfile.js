let oldURL = null;
const fileInput = document.getElementById('edit_pic');
const imgPreview = document.getElementById('preview_pic');

fileInput.addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        if (oldURL) URL.revokeObjectURL(oldURL);
        oldURL = URL.createObjectURL(file);
        imgPreview.src = oldURL;
    }
});