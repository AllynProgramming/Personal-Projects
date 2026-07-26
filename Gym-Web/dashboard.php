<?php
// dashboard.php
// Main dashboard after login

require_once __DIR__ . '/api/includes/db.php';
require_once __DIR__ . '/api/includes/auth.php';

// Require login
requireLogin();

// Get user info
$userId = getUserId();
$user = getUserInfo($conn, $userId);

// Get user's stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT ws.id) as total_sessions,
        COUNT(DISTINCT e.exercise_name) as unique_exercises,
        MAX(ws.session_date) as last_workout
    FROM workout_sessions ws
    LEFT JOIN exercises e ON ws.id = e.session_id
    WHERE ws.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent workouts
$stmt = $conn->prepare("
    SELECT 
        ws.id,
        ws.session_date,
        wp.plan_name,
        COUNT(e.id) as exercise_count,
        ws.duration_minutes
    FROM workout_sessions ws
    LEFT JOIN workout_plans wp ON ws.workout_plan_id = wp.id
    LEFT JOIN exercises e ON ws.id = e.session_id
    WHERE ws.user_id = ?
    GROUP BY ws.id
    ORDER BY ws.session_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentWorkouts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gym Progression Tracker</title>
    <style>
        /* Theme colors — tweak these variables first to change the dashboard palette quickly. */
        :root {
            color-scheme: dark;
            --bg-dark: #05030a;                 /* Main page background */
            --panel: rgba(15, 8, 28, 0.95);     /* Card / stat panel background */
            --panel-2: rgba(20, 12, 40, 0.98);  /* Secondary panel background */
            --text-main: #f6f7ff;              /* Main text color */
            --text-muted: #adb2d4;             /* Secondary text color */
            --accent: #7851A9;                 /* Royal purple accent color */
            --accent-strong: #9b6af0;          /* Bright purple accent */
            --accent-soft: rgba(120, 81, 169, 0.22); /* Soft purple glow */
            --border: rgba(151, 109, 222, 0.22); /* Border color */
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
                radial-gradient(circle at top left, rgba(120, 81, 169, 0.18), transparent 20%),
                radial-gradient(circle at bottom right, rgba(120, 81, 169, 0.12), transparent 18%),
                var(--bg-dark);
            color: var(--text-main);
        }

        .navbar {
            background: rgba(5, 5, 15, 0.96);
            border-bottom: 1px solid rgba(151, 109, 222, 0.2);
            color: white;
            padding: 22px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(16px);
        }

        .navbar h1 {
            font-size: 1.9rem;
            letter-spacing: 0.03em;
        }

        .navbar-right {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .navbar-right span {
            color: var(--text-muted);
            font-size: 0.95rem;
            white-space: nowrap;
        }

        .navbar-right a {
            color: var(--text-main);
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 999px;
            transition: background 0.3s ease, transform 0.2s ease;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 600;
        }

        .navbar-right a:hover {
            background: rgba(120, 81, 169, 0.18);
            transform: translateY(-1px);
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 32px 24px 40px;
        }

        .top-panel {
            display: grid;
            grid-template-columns: 1.65fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }

        .welcome-panel,
        .overview-panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.22);
        }

        .welcome-panel {
            min-height: 260px;
            background: linear-gradient(180deg, rgba(18, 10, 37, 0.98), rgba(15, 8, 28, 0.96));
            display: grid;
            grid-template-columns: 1.2fr 0.85fr;
            gap: 30px;
            align-items: center;
        }

        .welcome-copy {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .welcome-panel h2 {
            color: #fff;
            margin-bottom: 0;
            font-size: clamp(2rem, 2.3vw, 2.6rem);
            line-height: 1.05;
        }

        .welcome-panel p {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 560px;
            line-height: 1.8;
        }

        .welcome-chart {
            background: rgba(120, 81, 169, 0.08);
            border: 1px solid rgba(120, 81, 169, 0.18);
            border-radius: 22px;
            padding: 22px;
            display: grid;
            gap: 18px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .chart-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.9rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .chart-scale {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            align-items: end;
            min-height: 160px;
        }

        .chart-bar {
            position: relative;
            height: 100%;
            background: linear-gradient(180deg, rgba(167, 85, 255, 0.92), rgba(120, 81, 169, 0.95));
            border-radius: 18px 18px 6px 6px;
            box-shadow: inset 0 2px 12px rgba(255,255,255,0.12), 0 0 18px rgba(167, 85, 255, 0.2);
        }

        .chart-bar::after {
            content: attr(data-value);
            position: absolute;
            top: -24px;
            left: 50%;
            transform: translateX(-50%);
            color: #f4f6ff;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .chart-labels {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-top: 12px;
            color: var(--text-muted);
            font-size: 0.85rem;
            text-align: center;
        }

        .overview-panel {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 24px;
        }

        .overview-title {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .overview-title span {
            font-size: 0.85rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent-soft);
        }

        .overview-title h3 {
            font-size: 1.55rem;
            color: #fff;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .overview-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .metric-card {
            background: rgba(120, 81, 169, 0.08);
            border: 1px solid rgba(120, 81, 169, 0.18);
            border-radius: 18px;
            padding: 18px 16px;
            color: #f4f6ff;
        }

        .metric-card p {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .metric-card h4 {
            font-size: 1.45rem;
            margin: 0;
            color: #fff;
        }

        .overview-actions {
            display: grid;
            gap: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: rgba(18, 10, 37, 0.96);
            padding: 24px;
            border-radius: 24px;
            border: 1px solid rgba(120, 81, 169, 0.18);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02), 0 16px 30px rgba(0, 0, 0, 0.20);
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(120, 81, 169, 0.45);
            box-shadow: 0 22px 42px rgba(120, 81, 169, 0.22);
        }

        .stat-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 10px;
            letter-spacing: 0.03em;
        }

        .stat-card h3 {
            color: #fff;
            font-size: 2rem;
            letter-spacing: -0.03em;
        }

        .action-button {
            background: linear-gradient(135deg, #a755ff 0%, #7d3fd0 55%, #632a9f 100%);
            color: #f8f9ff;
            padding: 18px 22px;
            border-radius: 22px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            font-weight: 700;
            box-shadow: 0 0 22px rgba(167, 85, 255, 0.38), 0 18px 35px rgba(95, 43, 166, 0.18);
            border: 1px solid rgba(177, 109, 255, 0.45);
        }

        .action-button:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #c284ff 0%, #925cdd 45%, #7e39c2 100%);
            box-shadow: 0 0 26px rgba(194, 132, 255, 0.65), 0 20px 38px rgba(126, 55, 204, 0.28);
        }

        .section-title {
            color: #fff;
            margin: 28px 0 16px 0;
            font-size: 1.35rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            padding-bottom: 12px;
        }

        .workout-item {
            background: var(--panel-2);
            padding: 18px 20px;
            border-radius: 16px;
            margin-bottom: 12px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16);
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .workout-item h4 {
            color: #fff;
            margin-bottom: 5px;
        }

        .workout-item p {
            color: var(--text-muted);
            font-size: 0.92rem;
        }

        .workout-item a {
            background: rgba(124, 140, 255, 0.16);
            color: #e6ebff;
            padding: 8px 14px;
            border-radius: 999px;
            text-decoration: none;
            transition: background 0.2s ease;
            white-space: nowrap;
        }

        .workout-item a:hover {
            background: rgba(124, 140, 255, 0.26);
        }

        .empty-state {
            background: var(--panel-2);
            padding: 34px;
            border-radius: 18px;
            text-align: center;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .empty-state h3 {
            color: #fff;
            margin-bottom: 10px;
        }

        .empty-state p {
            margin-bottom: 18px;
        }

        .empty-state a {
            background: var(--accent);
            color: #f4f6ff;
            padding: 10px 18px;
            border-radius: 999px;
            text-decoration: none;
            display: inline-block;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .navbar-right {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .workout-item {
                flex-direction: column;
                text-align: center;
            }
            .top-panel {
                grid-template-columns: 1fr;
            }

            .welcome-panel,
            .overview-panel {
                padding: 24px;
            }

            .welcome-chart {
                gap: 14px;
            }

            .overview-metrics {
                grid-template-columns: 1fr;
            }

            .overview-actions {
                grid-template-columns: 1fr;
            }

            .overview-actions a {
                width: 100%;
            }

            .chart-scale,
            .chart-labels {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .chart-bar {
                height: auto;
                min-height: 80px;
            }

            .workout-item a {
                width: 100%;
                white-space: normal;
            }

            .section-title {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .navbar { padding: 14px 18px; }
            .navbar-right span { display: none; }
            .navbar-right a { padding: 8px 10px; font-size: 0.88rem; border-radius: 12px; }

            .container { padding: 20px 14px 40px; }

            .welcome-panel { grid-template-columns: 1fr !important; padding: 18px; }
            .welcome-copy h2 { font-size: 1.45rem; }
            .welcome-copy p { font-size: 0.95rem; }

            .welcome-chart { padding: 12px; border-radius: 14px; }
            .chart-scale { min-height: 90px; gap: 8px; }
            .chart-bar { min-height: 56px; border-radius: 12px; }

            .overview-panel { padding: 18px; }
            .overview-actions a { font-size: 0.95rem; padding: 12px; }

            .workout-item { padding: 14px; }
            .workout-item h4 { font-size: 1rem; }
            .workout-item p { font-size: 0.9rem; }

            .section-title { font-size: 1rem; }
        }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>💪 GymTrack</h1>
        <div class="navbar-right">
            <span>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</span>
            <a href="profile.php">Profile</a>
            <a href="friends.php">Friends</a>
            <a href="api/logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="top-panel">
            <section class="welcome-panel">
                <div class="welcome-copy">
                    <h2>Welcome back, <?php echo htmlspecialchars($user['first_name'] ?? $user['username']); ?>.</h2>
                    <p>GymTrack is your premium performance hub — review your progress, lock in consistency, and plan every workout with confidence.</p>
                    <div class="welcome-cta">
                        <span style="color: var(--text-muted); font-size:0.95rem;">Last 4 workouts</span>
                        <strong style="color:#fff; font-size:1.1rem;">Workout intensity trend</strong>
                    </div>
                </div>
                <div class="welcome-chart">
                    <div class="chart-header">
                        <div>
                            <p>Volume trend</p>
                            <strong style="color:#fff; font-size:1.1rem;">Stable progress</strong>
                        </div>
                        <span style="color:#c284ff; font-size:0.95rem; font-weight:700;">⬆ 12% from last week</span>
                    </div>
                    <div class="chart-scale">
                        <div class="chart-bar" data-value="62%" style="height: 62%;"></div>
                        <div class="chart-bar" data-value="78%" style="height: 78%;"></div>
                        <div class="chart-bar" data-value="68%" style="height: 68%;"></div>
                        <div class="chart-bar" data-value="84%" style="height: 84%;"></div>
                    </div>
                    <div class="chart-labels">
                        <span>Mon</span>
                        <span>Wed</span>
                        <span>Fri</span>
                        <span>Sun</span>
                    </div>
                </div>
            </section>

            <aside class="overview-panel">
                <div class="overview-title">
                    <span>Dashboard overview</span>
                    <h3>Today’s momentum</h3>
                </div>

                <div class="overview-metrics">
                    <div class="metric-card">
                        <p>Total Workouts</p>
                        <h4><?php echo $stats['total_sessions'] ?? 0; ?></h4>
                    </div>
                    <div class="metric-card">
                        <p>Unique Exercises</p>
                        <h4><?php echo $stats['unique_exercises'] ?? 0; ?></h4>
                    </div>
                    <div class="metric-card">
                        <p>Last Workout</p>
                        <h4><?php echo $stats['last_workout'] ? date('M d', strtotime($stats['last_workout'])) : 'Never'; ?></h4>
                    </div>
                </div>

                <div class="overview-actions">
                    <a href="log-workout.php" class="action-button">📝 Log New Workout</a>
                    <a href="progression.php" class="action-button">📊 View Progression</a>
                    <a href="workouts.php" class="action-button">🏋️ My Workouts</a>
                </div>
            </aside>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <p>Workout count</p>
                <h3><?php echo $stats['total_sessions'] ?? 0; ?></h3>
            </div>
            <div class="stat-card">
                <p>Different moves</p>
                <h3><?php echo $stats['unique_exercises'] ?? 0; ?></h3>
            </div>
            <div class="stat-card">
                <p>Last active</p>
                <h3><?php echo $stats['last_workout'] ? date('M d', strtotime($stats['last_workout'])) : 'Never'; ?></h3>
            </div>
        </div>

        <h2 class="section-title">Recent Workouts</h2>

        <?php if (!empty($recentWorkouts)): ?>
            <?php foreach ($recentWorkouts as $workout): ?>
                <div class="workout-item">
                    <div>
                        <h4><?php echo htmlspecialchars($workout['plan_name'] ?? 'Workout'); ?></h4>
                        <p>
                            <?php echo date('M d, Y', strtotime($workout['session_date'])); ?> • 
                            <?php echo $workout['exercise_count']; ?> exercises • 
                            <?php echo $workout['duration_minutes'] ? $workout['duration_minutes'] . ' min' : 'Time not recorded'; ?>
                        </p>
                    </div>
                    <a href="under-development.php">View</a> 
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>No workouts yet</h3>
                <p>Start tracking your fitness journey today!</p>
                <a href="log-workout.php">Log Your First Workout</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
