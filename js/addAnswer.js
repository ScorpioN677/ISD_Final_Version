// Ensure DOM is fully loaded before attaching event handlers
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to the add answer button
    document.getElementById('addAnswerBtn').addEventListener('click', addAnswer);
});

// Keep track of how many answers we have
let answerCount = 2;
const MAX_ANSWERS = 4;

function addAnswer() {
    if (answerCount >= MAX_ANSWERS) {
        alert('Maximum of ' + MAX_ANSWERS + ' answers allowed.');
        return;
    }
    
    answerCount++;
    
    const more = document.getElementsByClassName('more')[0];

    // Create answer container with enhanced styling
    const container = document.createElement('div');
    container.className = 'answer-input-container';
    container.setAttribute('data-number', answerCount);
    container.style.cssText = `
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.3s ease;
    `;

    // Create the input with enhanced styling
    const newAnswer = document.createElement('input');
    newAnswer.type = 'text';
    newAnswer.name = 'answers[]';
    newAnswer.required = true;
    newAnswer.placeholder = 'Answer ' + answerCount;
    newAnswer.style.cssText = `
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        font-family: 'Kameron', serif;
        background-color: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    `;

    // Create delete button with enhanced styling
    const del = document.createElement('button');
    del.type = 'button';
    del.textContent = '×';
    del.className = 'delete-answer';
    del.title = 'Remove this option';
    del.style.cssText = `
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 20px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 12px rgba(255, 107, 107, 0.3);
        flex-shrink: 0;
    `;

    // Add hover effects to input
    newAnswer.addEventListener('mouseenter', function() {
        this.style.borderColor = '#BE6AFF';
        this.style.backgroundColor = '#f8f9ff';
        this.style.transform = 'translateY(-1px)';
        this.style.boxShadow = '0 4px 12px rgba(190, 106, 255, 0.15)';
    });

    newAnswer.addEventListener('mouseleave', function() {
        if (this !== document.activeElement) {
            this.style.borderColor = '#e0e0e0';
            this.style.backgroundColor = '#ffffff';
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.05)';
        }
    });

    newAnswer.addEventListener('focus', function() {
        this.style.outline = 'none';
        this.style.borderColor = '#BE6AFF';
        this.style.boxShadow = '0 0 0 3px rgba(190, 106, 255, 0.1)';
        this.style.transform = 'translateY(-1px)';
    });

    newAnswer.addEventListener('blur', function() {
        this.style.borderColor = '#e0e0e0';
        this.style.backgroundColor = '#ffffff';
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.05)';
    });

    // Add hover effects to delete button
    del.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px) scale(1.1)';
        this.style.boxShadow = '0 6px 20px rgba(255, 107, 107, 0.4)';
    });

    del.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
        this.style.boxShadow = '0 3px 12px rgba(255, 107, 107, 0.3)';
    });

    del.addEventListener('focus', function() {
        this.style.outline = '2px solid #ff6b6b';
        this.style.outlineOffset = '2px';
    });

    del.addEventListener('blur', function() {
        this.style.outline = 'none';
    });

    // Delete functionality with animation
    del.onclick = function () {
        // Add exit animation
        container.style.transition = 'all 0.3s ease';
        container.style.opacity = '0';
        container.style.transform = 'translateX(-20px) scale(0.95)';
        
        setTimeout(() => {
            container.remove();
            answerCount--;
            updatePlaceholders();
            
            // Show the add button again if we're below max
            if (answerCount < MAX_ANSWERS) {
                const addBtn = document.getElementById('addAnswerBtn');
                addBtn.style.display = 'flex';
                addBtn.style.opacity = '1';
            }
        }, 300);
    };

    container.appendChild(newAnswer);
    container.appendChild(del);
    more.appendChild(container);

    // Animate in
    setTimeout(() => {
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';
    }, 10);

    updatePlaceholders();
    
    // Focus on the new input
    setTimeout(() => {
        newAnswer.focus();
    }, 350);
    
    // Hide add button if we reached max with enhanced animation
    if (answerCount >= MAX_ANSWERS) {
        const addBtn = document.getElementById('addAnswerBtn');
        addBtn.style.transition = 'all 0.3s ease';
        addBtn.style.opacity = '0';
        addBtn.style.transform = 'scale(0.8)';
        setTimeout(() => {
            addBtn.style.display = 'none';
        }, 300);
    }
}

function updatePlaceholders() {
    let counter = 3;
    const inputs = document.querySelectorAll('.more input[type="text"]');

    inputs.forEach(input => {
        input.placeholder = 'Answer ' + counter++;
        
        // Update the data-number attribute on the container
        const container = input.closest('.answer-input-container');
        if (container) {
            container.setAttribute('data-number', counter - 1);
        }
    });
}