<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    <!-- Whole body font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
    <!-- Title font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            scroll-behavior: smooth;
            font-family: 'Kameron', serif;
        }

        html {
            cursor: url('Cursor/cursor.png'), auto;
        }

        body {
            font-family: 'Kameron', serif;
            color: #00025F;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(45deg, #9500ac, #0063a5);
            padding: 20px;
            margin: 0;
        }

        .title {
            text-align: center;
            font-size: 42px;
            font-weight: 700;
            margin: 20px 0 30px;
            font-family: "Inconsolata", monospace;
            color: #dd00ff;
            cursor: text;
            padding: 15px;
            border-bottom: 3px solid #dd00ff;
        }

        .title::selection {
            background-color: #D6E6FF;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .info p {
            font-size: 18px;
            margin-bottom: 20px;
            color: #00025F;
            cursor: text;
            line-height: 1.5;
        }

        .info p::selection {
            background-color: #D6E6FF;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
        }

        input {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #BE6AFF;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 16px;
            font-family: 'Kameron', serif;
            color: #00025F;
            transition: all 0.3s ease;
            width: 100%;
        }

        input:hover,
        input:focus {
            background: rgba(255, 255, 255, 1);
            border: 2px solid #a632ff;
            box-shadow: 0 2px 8px rgba(166, 50, 255, 0.2);
            outline: none;
        }

        input::selection {
            background-color: #D6E6FF;
        }

        input:focus {
            outline: 1px solid #a632ff;
        }

        ::placeholder {
            color: #888888;
            font-size: 16px;
        }

        button {
            background: linear-gradient(135deg, #F08FFF, #71C6FF);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Kameron', serif;
            cursor: url('Cursor/cursor_pointer.png'), auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(240, 143, 255, 0.3);
        }

        button:focus {
            outline: 1px solid #a632ff;
        }

        button::selection {
            background-color: #bdd6ff;
        }

        #codeEnter {
            display: none;
            flex-direction: column;
            gap: 20px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #dd00ff;
        }

        #codeEnter.show {
            display: flex;
        }

        #emailEntered {
            font-weight: 600;
            color: #dd00ff;
        }

        .note {
            font-size: 14px !important;
            color: #666 !important;
            font-style: italic;
            margin-top: 5px !important;
            margin-bottom: 15px !important;
        }

        .back-button {
            text-decoration: none;
            color: white;
            background: linear-gradient(135deg, #F08FFF, #71C6FF);
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            cursor: url('Cursor/cursor_pointer.png'), auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: inline-block;
            margin-bottom: 20px;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(240, 143, 255, 0.3);
        }

        .back-button:focus {
            outline: 1px solid #a632ff;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .title {
                font-size: 32px;
                margin: 15px 0 20px;
            }
            
            .container {
                padding: 30px 25px;
                max-width: 100%;
            }
            
            .info p {
                font-size: 16px;
            }
            
            input {
                padding: 12px 15px;
                font-size: 16px;
            }
            
            button {
                padding: 12px 16px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .title {
                font-size: 28px;
            }
            
            .container {
                padding: 25px 20px;
            }
            
            .info p {
                font-size: 15px;
            }
            
            input {
                padding: 10px 12px;
                font-size: 15px;
            }
            
            button {
                padding: 10px 14px;
                font-size: 15px;
            }
        }

        /* Preserve scrollbar styling */
        ::-webkit-scrollbar {
            background: linear-gradient(0deg, #dd00ff 0%, #0099ff 100%);
            width: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #00025F;
            border-radius: 15px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <a href="login.php" class="back-button">← Back to Login</a>
    
    <h1 class="title">Forget Password</h1>

    <div class="container">
        <div class="info">
            <p>Please enter your email address to receive a password reset code:</p>

            <form id="emailForm" method="POST">
                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                <button type="submit">Send Reset Code</button>
            </form>

            <div id="codeEnter">
                <p>A 6-digit code has been sent to: <span id="emailEntered"></span></p>
                <p class="note">If you don't see the email, please check your spam folder.</p>
                
                <form id="codeForm" method="POST">
                    <input type="text" name="code" placeholder="Enter 6-digit code" autocomplete="off" maxlength="6" required>
                    <button type="submit">Verify Code</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const emailForm = document.getElementById('emailForm');
            const codeForm = document.getElementById('codeForm');
            const emailInput = document.getElementById('email');
            const codeInput = document.querySelector('input[name="code"]');
            const codeEnterDiv = document.getElementById('codeEnter');
            const emailEnteredSpan = document.getElementById('emailEntered');
            
            emailForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const emailValue = emailInput.value.trim();
                
                if (emailValue === '') return;
                
                // Disable submit button during request
                const submitBtn = emailForm.querySelector('button');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Sending...';
                submitBtn.disabled = true;
                
                try {
                    const response = await fetch('sendResetCode.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ email: emailValue })
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        // Mask email for privacy
                        const emailParts = emailValue.split('@');
                        const namePart = emailParts[0];
                        const domainPart = emailParts[1];

                        const maskedName = namePart.length > 4 ? 
                            namePart.slice(0, 2) + '*'.repeat(namePart.length - 4) + namePart.slice(-2) :
                            namePart.slice(0, 1) + '*'.repeat(namePart.length - 1);
                        
                        emailEnteredSpan.textContent = maskedName + '@' + domainPart;
                        codeEnterDiv.classList.add('show');
                        
                        // Focus on code input
                        setTimeout(() => codeInput.focus(), 100);
                    } else {
                        alert(data.message || 'Failed to send reset code. Please try again.');
                    }
                } catch (err) {
                    console.error('Error:', err);
                    alert('Something went wrong. Please try again later.');
                } finally {
                    // Re-enable submit button
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });

            codeForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const codeValue = codeInput.value.trim();

                if (codeValue === '' || codeValue.length !== 6) {
                    alert('Please enter a valid 6-digit code.');
                    return;
                }

                // Disable submit button during request
                const submitBtn = codeForm.querySelector('button');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Verifying...';
                submitBtn.disabled = true;

                try {
                    const response = await fetch('verifyCode.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ code: codeValue })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        window.location.href = 'resetPassword.php';
                    } else {
                        alert(data.message || 'Invalid code. Please try again.');
                        codeInput.focus();
                        codeInput.select();
                    }
                } catch (err) {
                    console.error('Error:', err);
                    alert("Something went wrong while verifying the code.");
                } finally {
                    // Re-enable submit button
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });

            // Auto-submit when 6 digits are entered
            codeInput.addEventListener('input', function(e) {
                if (this.value.length === 6) {
                    setTimeout(() => codeForm.dispatchEvent(new Event('submit')), 500);
                }
            });
        });
    </script>
</body>
</html>