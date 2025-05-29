const userInput = document.getElementById("userInput");
const sendBtn = document.getElementById("sendBtn");
const messagesContainer = document.getElementById("messages");

userInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !sendBtn.disabled) {
        sendMessage();
    }
});

sendBtn.addEventListener("click", sendMessage);

function sendMessage() {
    const message = userInput.value.trim();
    if (!message) return;

    appendMessage("user", message);
    userInput.value = "";

    setInputEnabled(false);

    const aiMessageDiv = appendMessage("bot", ""); 

    fetch("ai_proxy.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message })
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.reply) {
            typeWriter(aiMessageDiv, data.reply, () => setInputEnabled(true));
        } else {
            typeWriter(aiMessageDiv, "Sorry, I couldn't understand.", () => setInputEnabled(true));
        }
    })
    .catch(error => {
        console.error("Error:", error);
        typeWriter(aiMessageDiv, "Error communicating with the AI.", () => setInputEnabled(true));
    });
}

function appendMessage(sender, text) {
    const messageDiv = document.createElement("div");
    messageDiv.classList.add("message", sender);
    messageDiv.textContent = text;
    messagesContainer.appendChild(messageDiv);
    messageDiv.scrollIntoView({ behavior: "smooth" });
    return messageDiv;
}

function typeWriter(element, text, callback) {
    let i = 0;
    function type() {
        if (i < text.length) {
            element.textContent += text.charAt(i);
            i++;
            element.scrollIntoView({ behavior: "smooth" });
            setTimeout(type, 25);
        } else if (callback) {
            callback();
        }
    }
    type();
}

function setInputEnabled(enabled) {
    userInput.disabled = !enabled;
    sendBtn.disabled = !enabled;
    if (enabled) {
        userInput.focus();
        userInput.style.cursor = "text";
    }
    else {
        userInput.style.cursor = "url('Cursor/cursor.png'), auto";
    }
}