# Gym Progression Tracker

A simple web-based fitness tracking app built with PHP and MySQL. It lets users sign up, log workouts, track exercise progression over time, and view a personal dashboard of recent training activity.

## Features

- User registration and login
- Dashboard with workout stats and recent sessions
- Workout logging with exercise entries
- Progression tracking for individual exercises
- Workout plan support
- MySQL database schema included

## Tech Stack

- PHP
- MySQL
- HTML/CSS/JavaScript
- XAMPP for local development

## Project Structure

```text
Gym-Web/
├── api/
│   ├── add-workout.php
│   ├── get-progression.php
│   ├── login.php
│   ├── logout.php
│   ├── signup.php
│   └── includes/
│       ├── auth.php
│       └── db.php
├── dashboard.php
├── friends.php
├── index.php
├── log-workout.php
├── profile.php
├── progression.php
├── workouts.php
├── gym_tracker_database.sql
└── README.md
```

## Installation

1. Place the project folder inside your XAMPP htdocs directory:
   - Example: `C:/xampp/htdocs/Gym-Web`
   - On macOS with XAMPP: `/Applications/XAMPP/xamppfiles/htdocs/Gym-Web`

2. Start Apache and MySQL from XAMPP.

3. Create a MySQL database and import the SQL file:
   - Open phpMyAdmin
   - Create a new database
   - Import `gym_tracker_database.sql`

4. Update the database connection settings in `api/includes/db.php` if needed.

5. Open the app in your browser:
   - `http://localhost/Gym-Web/`

## Configuration

The app uses the database connection settings defined in `api/includes/db.php`.
If you want to use your own local database, update the values for:

- `DB_HOST`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_NAME`

You can also switch to environment variables if preferred.

## Usage

- Register a new account or sign in
- Go to the dashboard to view your stats
- Use the workout logger to save your training sessions
- Visit the progression page to track your exercise improvement over time

## Notes

- Some pages such as profile and friends are currently marked as under development.
- The project uses prepared statements and password hashing for basic security.

## License

This project does not currently include a license file. If you plan to publish it publicly on GitHub, you may want to add one such as MIT or Apache 2.0.
