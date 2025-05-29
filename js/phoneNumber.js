const input = document.querySelector("#phone");

const iti = window.intlTelInput(input, {
    initialCountry: "auto",
    excludeCountries: ["il"], // Exclude Israel
    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js",
    geoIpLookup: function (callback) {
    fetch("https://ipapi.co/json")
        .then((res) => res.json())
        .then((data) => callback(data.country_code))
        .catch(() => callback("us"));
    }
});

// Prevent non-numeric characters
input.addEventListener("input", () => {
    input.value = input.value.replace(/\D/g, "");
});

// Optional: you can check if valid before submitting (validation can be added here)
document.querySelector("form").addEventListener("submit", function (e) {
    if (!iti.isValidNumber()) {
    e.preventDefault(); // stop form from submitting
    alert("Please enter a valid phone number.");
    }
});