<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pure Linen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .body-1 {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .container-1 {
            width: 100%;
            max-width: 450px;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-title {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            color: #1B365D;
            margin-bottom: 30px;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #6c757d;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            outline: none;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .input-group input:focus {
            border-color: #1B365D;
        }
        
        .input-group label {
            position: absolute;
            top: -10px;
            left: 10px;
            background: #fff;
            padding: 0 5px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #1B365D;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            /*transition: background 0.3s;*/
        }
        
        .btn:hover {
            background: #152a4a;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .links {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }
        
        .links p {
            margin-right: 10px;
            color: #6c757d;
        }
        
        .links button {
            background: none;
            border: none;
            color: #1B365D;
            cursor: pointer;
            font-weight: 600;
        }
        
        .links button:hover {
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: #1B365D;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .password-rules {
            margin-top: 5px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .password-rule {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .password-rule i {
            margin-right: 5px;
            font-size: 12px;
        }
        
        .valid {
            color: #28a745;
        }
        
        .invalid {
            color: #dc3545;
        }
        
        .password-match {
            font-size: 12px;
            margin-top: -20px;
            margin-bottom: 20px;
            text-align: right;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="body-1">
    <!-- Sign Up Form -->
    <div class="container-1" id="SignUp" style="display: none;">
        <h1 class="form-title">Register</h1>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <form method="post" action="register.php" id="signupForm">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fname" id="fname" placeholder="First Name" required>
                <label for="fname">First Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lname" id="lname" placeholder="Last Name" required>
                <label for="lname">Last Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="emailSignUp" placeholder="Email" required>
                <label for="emailSignUp">Email</label>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="passwordSignUp" placeholder="Password" required>
                <label for="passwordSignUp">Password</label>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm Password" required>
                <label for="confirmPassword">Confirm Password</label>
            </div>
            <div class="password-match" id="passwordMatch"></div>
            
            <div class="password-rules">
                <div class="password-rule" id="length">
                    <i class="fas fa-times-circle"></i> At least 8 characters
                </div>
                <div class="password-rule" id="uppercase">
                    <i class="fas fa-times-circle"></i> At least one uppercase letter
                </div>
                <div class="password-rule" id="lowercase">
                    <i class="fas fa-times-circle"></i> At least one lowercase letter
                </div>
                <div class="password-rule" id="number">
                    <i class="fas fa-times-circle"></i> At least one number
                </div>
            </div>
            
            <input type="submit" class="btn" value="Sign Up" name="SignUp" id="signupBtn" disabled>
        </form>
        <div class="links">
            <p>Already Have Account ?</p>
            <button id="signInButton">SIGN IN</button>
        </div>
    </div>

    <!-- Sign In Form -->
    <div class="container-1" id="SignIn">
        <h1 class="form-title">Sign In</h1>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <form method="post" action="register.php">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="emailSignIn" placeholder="Email" required>
                <label for="emailSignIn">Email</label>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="passwordSignIn" placeholder="Password" required>
                <label for="passwordSignIn">Password</label>
            </div>
            
            <div class="forgot-password">
                <a href="forgot_password.php">Forgot password?</a>
            </div>
            
            <input type="submit" class="btn" value="Sign In" name="SignIn">
        </form>
        <div class="links">
            <p>Don't have an Account ?</p>
            <button id="signUpButton">SIGN UP</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle between Sign In and Sign Up forms
            const signInButton = document.getElementById('signInButton');
            const signUpButton = document.getElementById('signUpButton');
            const signInForm = document.getElementById('SignIn');
            const signUpForm = document.getElementById('SignUp');
            
            // Check if there's a URL parameter to show signup form
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('form') === 'signup') {
                signInForm.style.display = 'none';
                signUpForm.style.display = 'block';
            }
            
            signInButton.addEventListener('click', function() {
                signUpForm.style.display = 'none';
                signInForm.style.display = 'block';
                window.history.replaceState({}, document.title, window.location.pathname);
            });
            
            signUpButton.addEventListener('click', function() {
                signInForm.style.display = 'none';
                signUpForm.style.display = 'block';
                window.history.replaceState({}, document.title, window.location.pathname + '?form=signup');
            });
            
            // Password validation for signup
            if (document.getElementById('passwordSignUp')) {
                const password = document.getElementById('passwordSignUp');
                const confirmPassword = document.getElementById('confirmPassword');
                const signupBtn = document.getElementById('signupBtn');
                const passwordMatch = document.getElementById('passwordMatch');
                
                // Password rule elements
                const lengthRule = document.getElementById('length');
                const uppercaseRule = document.getElementById('uppercase');
                const lowercaseRule = document.getElementById('lowercase');
                const numberRule = document.getElementById('number');
                
                function checkPasswordStrength(password) {
                    let valid = true;
                    
                    // Check length
                    if (password.length >= 8) {
                        lengthRule.classList.add('valid');
                        lengthRule.classList.remove('invalid');
                        lengthRule.innerHTML = '<i class="fas fa-check-circle"></i> At least 8 characters';
                    } else {
                        lengthRule.classList.add('invalid');
                        lengthRule.classList.remove('valid');
                        lengthRule.innerHTML = '<i class="fas fa-times-circle"></i> At least 8 characters';
                        valid = false;
                    }
                    
                    // Check for uppercase letters
                    if (/[A-Z]/.test(password)) {
                        uppercaseRule.classList.add('valid');
                        uppercaseRule.classList.remove('invalid');
                        uppercaseRule.innerHTML = '<i class="fas fa-check-circle"></i> At least one uppercase letter';
                    } else {
                        uppercaseRule.classList.add('invalid');
                        uppercaseRule.classList.remove('valid');
                        uppercaseRule.innerHTML = '<i class="fas fa-times-circle"></i> At least one uppercase letter';
                        valid = false;
                    }
                    
                    // Check for lowercase letters
                    if (/[a-z]/.test(password)) {
                        lowercaseRule.classList.add('valid');
                        lowercaseRule.classList.remove('invalid');
                        lowercaseRule.innerHTML = '<i class="fas fa-check-circle"></i> At least one lowercase letter';
                    } else {
                        lowercaseRule.classList.add('invalid');
                        lowercaseRule.classList.remove('valid');
                        lowercaseRule.innerHTML = '<i class="fas fa-times-circle"></i> At least one lowercase letter';
                        valid = false;
                    }
                    
                    // Check for numbers
                    if (/\d/.test(password)) {
                        numberRule.classList.add('valid');
                        numberRule.classList.remove('invalid');
                        numberRule.innerHTML = '<i class="fas fa-check-circle"></i> At least one number';
                    } else {
                        numberRule.classList.add('invalid');
                        numberRule.classList.remove('valid');
                        numberRule.innerHTML = '<i class="fas fa-times-circle"></i> At least one number';
                        valid = false;
                    }
                    
                    return valid;
                }
                
                function checkPasswordsMatch() {
                    if (confirmPassword.value === '') {
                        passwordMatch.innerHTML = '';
                        return false;
                    } else if (password.value === confirmPassword.value) {
                        passwordMatch.innerHTML = 'Passwords match';
                        passwordMatch.className = 'password-match valid';
                        return true;
                    } else {
                        passwordMatch.innerHTML = 'Passwords do not match';
                        passwordMatch.className = 'password-match invalid';
                        return false;
                    }
                }
                
                function validateForm() {
                    const passwordValid = checkPasswordStrength(password.value);
                    const passwordsMatch = checkPasswordsMatch();
                    
                    if (passwordValid && passwordsMatch) {
                        signupBtn.disabled = false;
                    } else {
                        signupBtn.disabled = true;
                    }
                }
                
                
                password.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    if (confirmPassword.value !== '') {
                        checkPasswordsMatch();
                    }
                    validateForm();
                });
                
                confirmPassword.addEventListener('input', function() {
                    checkPasswordsMatch();
                    validateForm();
                });
                
                // Initial validation on page load
                if (password.value) {
                    checkPasswordStrength(password.value);
                }
                if (confirmPassword.value) {
                    checkPasswordsMatch();
                }
                validateForm();
            }
        });
    </script>
</body>
</html>