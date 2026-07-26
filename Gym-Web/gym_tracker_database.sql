-- Gym Progression Tracker Database Schema
-- Secure, multi-user fitness tracking system

-- Database creation is handled by InfinityFree; import this file into the existing database.

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    profile_picture VARCHAR(255),
    bio TEXT,
    is_profile_public BOOLEAN DEFAULT FALSE,
    google_id VARCHAR(255) UNIQUE,
    auth_provider VARCHAR(50) DEFAULT 'local',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_google_id (google_id),
    INDEX idx_auth_provider (auth_provider)
) ENGINE=InnoDB;

-- ============================================
-- WORKOUT PLANS TABLE
-- ============================================
CREATE TABLE workout_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_name VARCHAR(255) NOT NULL,
    plan_type VARCHAR(50), -- 'Upper/Lower', 'PPL', 'Full Body', 'Push/Pull/Legs', etc.
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- ============================================
-- WORKOUT SESSIONS TABLE
-- ============================================
CREATE TABLE workout_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    workout_plan_id INT,
    session_date DATE NOT NULL,
    duration_minutes INT,
    notes TEXT,
    mood VARCHAR(50), -- 'great', 'good', 'okay', 'tired', etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (workout_plan_id) REFERENCES workout_plans(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_session_date (session_date),
    UNIQUE KEY unique_session (user_id, workout_plan_id, session_date)
) ENGINE=InnoDB;

-- ============================================
-- EXERCISES TABLE (Individual exercises logged)
-- ============================================
CREATE TABLE exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    exercise_name VARCHAR(255) NOT NULL,
    weight DECIMAL(6, 2) NOT NULL, -- Weight in kg/lbs
    reps INT NOT NULL,
    sets INT NOT NULL,
    notes TEXT,
    rest_seconds INT, -- Rest time between sets
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES workout_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_exercise_name (exercise_name)
) ENGINE=InnoDB;

-- ============================================
-- FRIENDSHIPS TABLE (For sharing workouts)
-- ============================================
CREATE TABLE friendships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    can_view_workouts BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_friend_id (friend_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_friendship (user_id, friend_id)
) ENGINE=InnoDB;

-- ============================================
-- EXERCISE HISTORY TABLE (For progression tracking)
-- ============================================
CREATE TABLE exercise_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    exercise_name VARCHAR(255) NOT NULL,
    total_sessions INT DEFAULT 0,
    best_weight DECIMAL(6, 2),
    best_weight_date DATE,
    average_weight DECIMAL(6, 2),
    last_done DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_exercise_name (exercise_name),
    UNIQUE KEY unique_exercise_track (user_id, exercise_name)
) ENGINE=InnoDB;

-- ============================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================

-- Sample User (password: TestPassword123 - hashed with bcrypt)
INSERT INTO users (username, email, password_hash, first_name, last_name, is_profile_public)
VALUES ('allyn_gym', 'allyn@example.com', '$2y$12$1nBNUHWr77ze8lEjP4efIO8s8D2S0rUj/4yEYx8Y/rfWkcjlVZGqe', 'Allyn', 'Doe', TRUE);

INSERT INTO users (username, email, password_hash, first_name, last_name, is_profile_public)
VALUES ('gymbuddy', 'buddy@example.com', '$2y$12$1nBNUHWr77ze8lEjP4efIO8s8D2S0rUj/4yEYx8Y/rfWkcjlVZGqe', 'Gym', 'Buddy', TRUE);

-- Sample Workout Plans
INSERT INTO workout_plans (user_id, plan_name, plan_type, description)
VALUES (1, 'Upper Day', 'Upper/Lower', 'Chest, back, shoulders, arms');

INSERT INTO workout_plans (user_id, plan_name, plan_type, description)
VALUES (1, 'Lower Day', 'Upper/Lower', 'Legs, glutes, hamstrings');

-- Sample Workout Session
INSERT INTO workout_sessions (user_id, workout_plan_id, session_date, duration_minutes, mood)
VALUES (1, 1, '2024-07-20', 60, 'great');

INSERT INTO workout_sessions (user_id, workout_plan_id, session_date, duration_minutes, mood)
VALUES (1, 1, '2024-07-25', 65, 'good');

-- Sample Exercises
INSERT INTO exercises (session_id, exercise_name, weight, reps, sets, notes)
VALUES (1, 'Incline Bench Press', 50.00, 8, 4, 'Felt strong today');

INSERT INTO exercises (session_id, exercise_name, weight, reps, sets, notes)
VALUES (1, 'Barbell Rows', 60.00, 8, 4, 'Good form');

INSERT INTO exercises (session_id, exercise_name, weight, reps, sets, notes)
VALUES (2, 'Incline Bench Press', 55.00, 8, 4, 'Progressing well!');

INSERT INTO exercises (session_id, exercise_name, weight, reps, sets, notes)
VALUES (2, 'Barbell Rows', 65.00, 8, 4, 'Strength improving');

-- Sample Friendship (pending)
INSERT INTO friendships (user_id, friend_id, status, can_view_workouts)
VALUES (1, 2, 'accepted', TRUE);

-- ============================================
-- VIEWS (For easier querying)
-- ============================================

-- View: Exercise Progression for a user
CREATE VIEW exercise_progression_view AS
SELECT 
    e.exercise_name,
    ws.session_date,
    e.weight,
    e.reps,
    e.sets,
    (e.weight * e.reps * e.sets) as total_volume,
    ws.user_id
FROM exercises e
JOIN workout_sessions ws ON e.session_id = ws.id
ORDER BY ws.user_id, e.exercise_name, ws.session_date;

-- View: User workout summary
CREATE VIEW user_workout_summary AS
SELECT 
    u.id,
    u.username,
    COUNT(DISTINCT ws.id) as total_sessions,
    COUNT(DISTINCT e.exercise_name) as unique_exercises,
    MAX(ws.session_date) as last_workout_date,
    AVG(ws.duration_minutes) as avg_duration
FROM users u
LEFT JOIN workout_sessions ws ON u.id = ws.user_id
LEFT JOIN exercises e ON ws.id = e.session_id
GROUP BY u.id, u.username;

-- ============================================
-- INDEXES for Performance
-- ============================================
CREATE INDEX idx_exercises_user_date ON exercises (
    session_id
) USING BTREE;

CREATE INDEX idx_workouts_user_date ON workout_sessions (
    user_id,
    session_date
) USING BTREE;

-- ============================================
-- NOTES:
-- ============================================
-- 1. Update the sample user passwords with actual bcrypt hashes
--    Use PHP: password_hash("YourPassword", PASSWORD_BCRYPT)
-- 2. All sensitive queries use prepared statements in PHP
-- 3. Foreign keys ensure data integrity
-- 4. Indexes optimize common queries for graphs/progression
-- 5. UNIQUE constraints prevent duplicate entries
-- ============================================
