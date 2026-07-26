# Gym-Web V2

A fitness tracking application built with PHP and MySQL. Log workouts, track progression, and visualize your training data with interactive charts.

## Features

- **User Authentication** — Local login/signup and Google OAuth 2.0 integration
- **Workout Logging** — Log exercises with date, weight, reps, and sets
- **Progression Tracking** — View personal bests and exercise history with charts
- **Weekly Dashboard** — Interactive charts showing weekly workout volume
- **Responsive Design** — Mobile-optimized UI with hamburger menu navigation
- **Account Isolation** — Secure per-user data storage (workouts only visible to account owner)

## Prerequisites

- PHP 7.4+ with MySQLi extension
- MySQL 5.7+
- XAMPP, MAMP, or similar local development environment (optional, any PHP server works)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/Gym-Web-V2.git
cd Gym-Web-V2
```

### 2. Set Up the Database

1. Import the database schema:
   - Open phpMyAdmin or MySQL client
   - Create a new database: `CREATE DATABASE gym_tracker;`
   - Import `gym_tracker_database.sql` into the new database

2. Update database credentials in `api/includes/db.php`:
   ```php
   $conn = new mysqli("localhost", "your_username", "your_password", "gym_tracker");
   ```

### 3. Configure Google OAuth (Optional)

To enable Google Sign-In:

1. **Create a Google Cloud project:**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project
   - Enable Google+ API
   - Create OAuth 2.0 credentials (Web application)
   - Add authorized redirect URI: `http://localhost:3000/api/google-callback.php` (adjust to your local URL)

2. **Configure environment variables:**
   - Copy `.env.example` to `.env`:
     ```bash
     cp .env.example .env
     ```
   - Edit `.env` and add your credentials:
     ```
     GOOGLE_CLIENT_ID=your_client_id_here
     GOOGLE_CLIENT_SECRET=your_client_secret_here
     ```

3. **Update the redirect URI in code** (if deploying):
   - Edit `config/google-oauth.php` or update your `.env` to match your production URL

### 4. Run the Application

**Using XAMPP:**
```bash
# Copy the project folder to htdocs/
cp -r Gym-Web-V2 /Applications/XAMPP/xamppfiles/htdocs/

# Start XAMPP and navigate to http://localhost/Gym-Web-V2/
```

**Using PHP Built-in Server:**
```bash
cd Gym-Web-V2
php -S localhost:8000
# Open http://localhost:8000 in your browser
```

## Project Structure

```
Gym-Web-V2/
├── api/
│   ├── includes/
│   │   ├── auth.php          # Authentication helpers
│   │   └── db.php            # Database connection
│   ├── add-workout.php       # Save workouts (AJAX)
│   ├── get-progression.php   # Fetch progression data (AJAX)
│   ├── google-callback.php   # OAuth callback handler
│   ├── google-login.php      # OAuth initiation
│   ├── login.php             # Local login endpoint
│   ├── logout.php            # Logout handler
│   └── signup.php            # Local signup endpoint
├── config/
│   └── google-oauth.php      # OAuth configuration
├── dashboard.php             # Main dashboard with weekly charts
├── log-workout.php           # Workout logging form
├── progression.php           # Exercise progression tracking
├── workouts.php              # Workout history view
├── profile.php               # User profile
├── friends.php               # Social features (in development)
├── under-development.php     # Placeholder for WIP features
├── index.php                 # Login/signup page
├── gym_tracker_database.sql  # Database schema
├── .env.example              # Environment variables template
├── .gitignore                # Git ignore rules
└── README.md                 # This file
```

## Database Schema

**Key Tables:**
- `users` — User accounts and authentication data
- `workout_plans` — User-created workout plans
- `workout_sessions` — Individual workout logs (date, duration, plan)
- `exercises` — Exercise data (name, weight, reps, sets per session)

All data is scoped to the authenticated user (`user_id`). Cross-account data leakage is prevented at the query level.

## Security Notes

- **Secrets Protection** — API keys and database credentials are loaded from `.env` (excluded from git)
- **SQL Injection Prevention** — All queries use prepared statements with parameterized bindings
- **Session Management** — Login state verified on every authenticated page
- **CORS & CSRF** — Origin-aware endpoints; form submissions include session validation

## Development

### Making Updates

1. Make changes to your local files
2. Test in your browser (XAMPP or PHP server)
3. Commit changes:
   ```bash
   git add .
   git commit -m "Description of changes"
   git push origin main
   ```

**Note:** `.env` and `config/google-oauth.php` are gitignored and won't be committed.

### Database Migrations

If you modify `gym_tracker_database.sql`:
1. Test changes locally
2. Document schema changes in your commit message
3. Include migration instructions in PRs

## Troubleshooting

**Login not working:**
- Verify `api/includes/db.php` has correct MySQL credentials
- Check that the database and tables exist (import `gym_tracker_database.sql`)

**Google OAuth not working:**
- Ensure `.env` file exists with valid credentials
- Verify redirect URI matches in Google Cloud Console
- Check that PHP can read `.env` (check file permissions)

**Workouts not appearing:**
- Verify you're logged into the correct account
- Check browser console for AJAX errors (F12 → Console)
- Inspect `api/add-workout.php` response in Network tab

## Future Enhancements

- [ ] Friends/social sharing
- [ ] Advanced analytics (RPE tracking, volume progression)
- [ ] Workout templates and pre-built programs
- [ ] Export data to CSV/PDF
- [ ] Mobile native app

## License

[Add your license here]

## Contributing

Pull requests welcome! Please include:
- Description of changes
- Testing evidence (screenshots, console output)
- Any database schema changes documented

## Support

For issues or questions, create a GitHub issue in the repository.

---

**Happy training! 💪**
