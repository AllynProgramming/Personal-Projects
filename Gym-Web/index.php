<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['userId'])) {
    header('Location: dashboard.php');
    exit;
}

// Check for success/error messages
$message = '';
$messageType = '';

if (isset($_GET['signup_success'])) {
    $message = 'Account created! Please log in.';
    $messageType = 'success';
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'invalid_login') {
        $message = 'Invalid username or password.';
    } elseif ($_GET['error'] == 'user_exists') {
        $message = 'Username or email already exists.';
    } elseif ($_GET['error'] == 'password_mismatch') {
        $message = 'Passwords do not match.';
    } elseif ($_GET['error'] == 'invalid_input') {
        $message = 'Please fill in all fields.';
    }
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Progression Tracker - Login</title>
    <style>
        :root {
            color-scheme: dark;
            --bg-dark: #05030a;
            --bg-panel: rgba(18, 10, 37, 0.94);
            --text-main: #f6f7ff;
            --text-muted: #adb2d4;
            --accent: #7851A9;
            --accent-2: #9b6af0;
            --border: rgba(151, 109, 222, 0.22);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(135, 78, 187, 0.16), transparent 20%),
                radial-gradient(circle at bottom right, rgba(120, 81, 169, 0.12), transparent 18%),
                var(--bg-dark);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
        }

        .page-shell {
            width: min(1180px, 100%);
            display: grid;
            grid-template-columns: 1.06fr 0.94fr;
            gap: 24px;
            align-items: stretch;
        }

        .hero-panel, .form-panel {
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.32);
            backdrop-filter: blur(16px);
            overflow: hidden;
        }

        .hero-panel {
            position: relative;
            background: linear-gradient(135deg, rgba(17, 9, 31, 0.98), rgba(14, 8, 32, 0.96));
            padding: 38px;
        }

        .hero-panel::before {
            content: '';
            position: absolute;
            inset: auto -80px -80px auto;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(151, 109, 222, 0.32), transparent 70%);
            pointer-events: none;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(151, 109, 222, 0.14);
            border: 1px solid rgba(151, 109, 222, 0.28);
            color: #e9ebff;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .hero-panel h1 {
            font-size: clamp(2rem, 3.2vw, 3rem);
            line-height: 1.05;
            margin-bottom: 14px;
            max-width: 560px;
            letter-spacing: -0.04em;
        }

        .hero-panel p {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.75;
            max-width: 560px;
            margin-bottom: 24px;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 26px;
        }

        .hero-stat {
            background: rgba(120, 81, 169, 0.1);
            border: 1px solid rgba(151, 109, 222, 0.18);
            border-radius: 18px;
            padding: 16px;
        }

        .hero-stat strong {
            display: block;
            font-size: 1.15rem;
            margin-bottom: 6px;
            color: #fff;
        }

        .hero-stat span {
            color: var(--text-muted);
            font-size: 0.88rem;
        }

        .feature-list {
            list-style: none;
            display: grid;
            gap: 10px;
            margin-top: 8px;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e7ebff;
        }

        .feature-list li::before {
            content: '✓';
            display: inline-grid;
            place-items: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a755ff 0%, #7d3fd0 55%, #632a9f 100%);
            color: #08111f;
            font-weight: 700;
            font-size: 0.86rem;
        }

        .form-panel {
            background: rgba(18, 10, 37, 0.96);
            padding: 36px;
            position: relative;
        }

        .form-card {
            display: grid;
            gap: 16px;
            animation: fadeUp 0.35s ease;
        }

        .form-card.hidden {
            display: none;
        }

        .form-heading h2 {
            font-size: 1.7rem;
            margin-bottom: 6px;
        }

        .form-heading p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .message {
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 600;
            border: 1px solid transparent;
            font-size: 0.95rem;
        }

        .message.success {
            background: rgba(151, 109, 222, 0.12);
            border-color: rgba(151, 109, 222, 0.28);
            color: #e7d6ff;
        }

        .message.error {
            background: rgba(255, 94, 94, 0.16);
            border-color: rgba(255, 94, 94, 0.24);
            color: #ffd7d7;
        }

        .form-group {
            display: grid;
            gap: 8px;
        }

        label {
            color: #d7dcf5;
            font-size: 0.95rem;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(151, 109, 222, 0.22);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            font-size: 0.96rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        input::placeholder {
            color: #7e89ab;
        }

        input:focus {
            outline: none;
            border-color: rgba(155, 106, 240, 0.8);
            box-shadow: 0 0 0 3px rgba(155, 106, 240, 0.16);
            transform: translateY(-1px);
        }

        button {
            border: none;
            cursor: pointer;
            font-weight: 700;
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            width: 100%;
            padding: 15px 16px;
            background: linear-gradient(135deg, #a755ff 0%, #7d3fd0 55%, #632a9f 100%);
            color: #f8f9ff;
            box-shadow: 0 16px 30px rgba(167, 85, 255, 0.32);
            border: 1px solid rgba(177, 109, 255, 0.35);
            margin-top: 24px;
        }

        .btn-primary:hover {
            box-shadow: 0 18px 34px rgba(194, 132, 255, 0.42);
        }

        .login-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(151, 109, 222, 0.22);
        }

        .google-login-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid rgba(151, 109, 222, 0.22);
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.96rem;
            transition: all 0.2s ease;
            cursor: pointer;
            width: 100%;
            font-family: inherit;
        }

        .google-login-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(155, 106, 240, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(155, 106, 240, 0.16);
        }

        .google-login-btn img {
            width: 20px;
            height: 20px;
        }

        .form-footer {
            margin-top: 8px;
            display: flex;
            justify-content: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 0.95rem;
            flex-wrap: wrap;
        }

        .switch-btn {
            background: transparent;
            color: #d8dcff;
            padding: 0;
            font-size: 0.95rem;
        }

        .switch-btn:hover {
            text-decoration: underline;
            transform: none;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 900px) {
            .page-shell {
                grid-template-columns: 1fr;
            }

            .hero-panel {
                order: 2;
            }
        }

        @media (max-width: 640px) {
            body { padding: 14px; }

            .page-shell { gap: 18px; grid-template-columns: 1fr; }

            .hero-panel, .form-panel {
                padding: 18px;
                border-radius: 16px;
            }

            .hero-badge { font-size: 0.85rem; padding: 8px 12px; }

            .hero-panel h1 { font-size: 1.6rem; }
            .hero-panel p { font-size: 0.95rem; }

            .hero-stats { grid-template-columns: 1fr; gap: 10px; }

            .form-panel { order: 1; }

            input, button { font-size: 0.98rem; }

            .btn-primary { padding: 12px 14px; }
        }

        @media (max-width: 480px) {
            .hero-panel h1 { font-size: 1.4rem; }
            .hero-stat strong { font-size: 1rem; }
            .feature-list li { font-size: 0.95rem; }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <section class="hero-panel">
            <div class="hero-badge">💪 GymTrack • Premium fitness tracking</div>
            <h1>Train smarter. Track every milestone.</h1>
            <p>Elevate your routine with a clean, motivating workspace built for progress, consistency, and confidence.</p>

            <div class="hero-stats">
                <div class="hero-stat">
                    <strong>+40%</strong>
                    <span>strength growth</span>
                </div>
                <div class="hero-stat">
                    <strong>24/7</strong>
                    <span>progress insight</span>
                </div>
                <div class="hero-stat">
                    <strong>1 click</strong>
                    <span>workout logging</span>
                </div>
            </div>

            <ul class="feature-list">
                <li>Visualize your strength trends over time</li>
                <li>Log sessions in seconds and stay consistent</li>
                <li>Use a polished dashboard that feels built for athletes</li>
            </ul>
        </section>

        <section class="form-panel">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="form-card" id="loginForm">
                <div class="form-heading">
                    <h2>Welcome back</h2>
                    <p>Sign in to your account and keep your progress moving.</p>
                </div>

                <form action="api/login.php" method="POST">
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <input type="text" id="login-username" name="username" required placeholder="Enter your username">
                    </div>

                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required placeholder="Enter your password">
                    </div>

                    <button class="btn-primary" type="submit">Login</button>
                </form>

                <div class="login-divider">or</div>

                <a href="api/google-login.php" class="google-login-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Sign in with Google
                </a>

                <div class="form-footer">
                    <span>New here?</span>
                    <button class="switch-btn" type="button" onclick="toggleForms('signup')">Create account</button>
                </div>
            </div>

            <div class="form-card hidden" id="signupForm">
                <div class="form-heading">
                    <h2>Create your account</h2>
                    <p>Start your journey with a stronger, clearer training routine.</p>
                </div>

                <form action="api/signup.php" method="POST">
                    <div class="form-group">
                        <label for="signup-username">Username</label>
                        <input type="text" id="signup-username" name="username" required placeholder="Choose a username">
                    </div>

                    <div class="form-group">
                        <label for="signup-email">Email</label>
                        <input type="email" id="signup-email" name="email" required placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="signup-firstname">First Name</label>
                        <input type="text" id="signup-firstname" name="first_name" placeholder="Your first name">
                    </div>

                    <div class="form-group">
                        <label for="signup-lastname">Last Name</label>
                        <input type="text" id="signup-lastname" name="last_name" placeholder="Your last name">
                    </div>

                    <div class="form-group">
                        <label for="signup-password">Password</label>
                        <input type="password" id="signup-password" name="password" required placeholder="Create a strong password (min 8 characters)">
                    </div>

                    <div class="form-group">
                        <label for="signup-password-confirm">Confirm Password</label>
                        <input type="password" id="signup-password-confirm" name="password_confirm" required placeholder="Confirm your password">
                    </div>

                    <button class="btn-primary" type="submit">Create Account</button>
                </form>

                <div class="login-divider">or</div>

                <a href="api/google-login.php" class="google-login-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Sign up with Google
                </a>

                <div class="form-footer">
                    <span>Already have an account?</span>
                    <button class="switch-btn" type="button" onclick="toggleForms('login')">Back to login</button>
                </div>
            </div>
        </section>
    </div>

    <script>
        function toggleForms(target) {
            const loginCard = document.getElementById('loginForm');
            const signupCard = document.getElementById('signupForm');

            if (target === 'signup') {
                loginCard.classList.add('hidden');
                signupCard.classList.remove('hidden');
            } else {
                signupCard.classList.add('hidden');
                loginCard.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
