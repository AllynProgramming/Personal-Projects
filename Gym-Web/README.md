# 💪 Gym Progression Tracker - Setup Guide

A secure, multi-user gym workout tracking application built with PHP and MySQL. Track your workouts, visualize your progression, and share achievements with gym buddies.

## 📋 Project Structure

```
gym-tracker/
├── index.php                 # Login/Signup page
├── dashboard.php             # Main dashboard after login
├── api/
│   ├── login.php            # Login API endpoint
│   ├── signup.php           # Signup API endpoint
│   └── logout.php           # Logout endpoint
├── includes/
│   ├── db.php               # Database connection
│   └── auth.php             # Authentication helper functions
├── css/
│   └── style.css            # Global styles (optional)
├── js/
│   └── app.js               # JavaScript utilities (optional)
└── README.md                # This file
```

## 🚀 Quick Start

### Step 1: Import Database

1. Open **phpMyAdmin** (http://localhost/phpmyadmin) or MySQL command line
2. Import the `gym_tracker_database.sql` file
3. Verify all tables are created:
   ```
   USE gym_tracker;
   SHOW TABLES;
   ```

### Step 2: Set Up Files

1. Create a folder on your localhost:
   - **XAMPP:** `C:\xampp\htdocs\gym-tracker\`
   - **WAMP:** `C:\wamp\www\gym-tracker\`
   - **MAMP:** `/Applications/MAMP/htdocs/gym-tracker/`

2. Copy all files into this folder with the structure above

3. Make sure you have this folder structure:
   ```
   gym-tracker/
   ├── api/
   │   ├── login.php
   │   ├── signup.php
   │   └── logout.php
   ├── includes/
   │   ├── db.php
   │   └── auth.php
   ├── index.php
   ├── dashboard.php
   └── README.md
   ```

### Step 3: Run Locally

1. Start Apache and MySQL (via XAMPP, WAMP, or MAMP)
2. Visit: `http://localhost/gym-tracker/`
3. You should see the login page

## 🔐 Security Features Implemented

✅ **Password Security**
- Bcrypt hashing with `password_hash()` and `password_verify()`
- Minimum 8-character passwords required

✅ **Database Security**
- Prepared statements (prevents SQL injection)
- Input validation on all fields
- UTF-8 encoding to prevent encoding-based attacks

✅ **Session Security**
- Session regeneration after login (prevents session fixation)
- Session-based authentication

✅ **Authorization**
- Users can only access their own data
- Ownership verification before showing/editing data
- Friend-based sharing with permission controls

## 📝 Test Credentials (Sample Users)

After importing the database, you can test with:

**User 1:**
- Username: `allyn_gym`
- Password: `TestPassword123`

**User 2:**
- Username: `gymbuddy`
- Password: `TestPassword123`

> ⚠️ These are sample users with placeholder hashes. To create custom test accounts, use the signup form.

## 🔄 Database Schema Overview

| Table | Purpose |
|-------|---------|
| `users` | User accounts and authentication |
| `workout_plans` | Workout splits (Upper/Lower, PPL, etc.) |
| `workout_sessions` | Logged workout days |
| `exercises` | Individual exercises per session |
| `friendships` | User connections for sharing |
| `exercise_history` | Cached progression data (for graphs) |

## 🛠️ Configuration

### Database Connection (includes/db.php)

Update these settings if needed:
```php
$servername = "localhost";   // Your MySQL host
$username = "root";          // Your MySQL username
$password = "";              // Your MySQL password
$database = "gym_tracker";   // Database name
```

## 🚧 Current Status (Prototype Phase)

✅ **Implemented:**
- User authentication (login/signup)
- Secure password handling
- Database schema
- Dashboard (basic)
- Session management
- Logout functionality

⏳ **Coming Next:**
- Workout logging feature
- Progression graphs
- Friend sharing functionality
- User profile page
- Exercise history and stats

## 📱 Browser Support

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## 🐛 Troubleshooting

### "Connection failed" error
- Check MySQL is running
- Verify database credentials in `includes/db.php`
- Ensure database `gym_tracker` exists

### Blank page
- Check PHP is enabled in XAMPP/WAMP
- Look at server error logs
- Enable error reporting in `php.ini`: `display_errors = On`

### Login not working
- Verify `api/login.php` is in correct folder
- Check file permissions (should be readable)
- Verify user exists in database

## 📚 Next Development Steps

1. **Workout Logging** (`log-workout.php`)
   - Create form to log exercises
   - Link exercises to workout sessions
   - Use AJAX for smooth UX

2. **Progression Visualization** (`progression.php`)
   - Integrate Chart.js for graphs
   - Show weight progression over time
   - Display personal records

3. **Workout History** (`workouts.php`)
   - List all user workouts
   - Filter by exercise or date
   - View/edit individual workouts

4. **Friend System** (`friends.php`)
   - Send friend requests
   - View friends' public workouts
   - Share specific workouts

5. **User Profile** (`profile.php`)
   - Edit user information
   - Change password
   - Privacy settings

## 💡 Development Tips

- Use `requireLogin()` at the top of protected pages:
  ```php
  require_once 'includes/auth.php';
  requireLogin();
  ```

- Always verify data ownership before displaying:
  ```php
  if (!verifyOwnership($conn, 'workout_sessions', $sessionId)) {
      die('Unauthorized');
  }
  ```

- Use prepared statements for all database queries:
  ```php
  $stmt = $conn->prepare("SELECT * FROM exercises WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  ```

## 📞 Support

For issues or questions:
1. Check the troubleshooting section
2. Verify file structure matches the template
3. Check PHP/MySQL error logs

## 📄 License

This is a learning/portfolio project. Feel free to modify and extend as needed.

---

**Happy tracking! 🏋️‍♂️**
