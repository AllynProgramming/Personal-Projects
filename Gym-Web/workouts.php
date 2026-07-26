<?php
// workouts.php
// Display all logged workout sessions organized by week

require_once __DIR__ . '/api/includes/db.php';
require_once __DIR__ . '/api/includes/auth.php';

requireLogin();

$userId = getUserId();
$user = getUserInfo($conn, $userId);

// Get all workout sessions for this user, ordered by date descending
$stmt = $conn->prepare("
    SELECT ws.id, ws.workout_plan_id, ws.session_date, ws.duration_minutes, ws.notes, ws.mood, wp.plan_name
    FROM workout_sessions ws
    LEFT JOIN workout_plans wp ON ws.workout_plan_id = wp.id AND wp.user_id = ws.user_id
    WHERE ws.user_id = ?
    ORDER BY ws.session_date DESC
    LIMIT 200
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$workouts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get exercise details for each session
$workoutDetails = [];
foreach ($workouts as $workout) {
    $stmt = $conn->prepare("
        SELECT id, exercise_name, weight, reps, sets, notes
        FROM exercises
        WHERE session_id = ?
        ORDER BY id ASC
    ");
    $stmt->bind_param("i", $workout['id']);
    $stmt->execute();
    $workoutDetails[$workout['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Group workouts by week
$workoutsByWeek = [];
foreach ($workouts as $workout) {
    $date = new DateTime($workout['session_date']);
    $week = $date->format('W'); // ISO week number
    $year = $date->format('Y');
    $weekKey = $year . '-W' . $week;
    
    if (!isset($workoutsByWeek[$weekKey])) {
        $workoutsByWeek[$weekKey] = [
            'week' => $week,
            'year' => $year,
            'startDate' => $date->modify('Monday this week')->format('Y-m-d'),
            'endDate' => $date->modify('Sunday this week')->format('Y-m-d'),
            'workouts' => []
        ];
    }
    
    $workoutsByWeek[$weekKey]['workouts'][] = $workout;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workouts - GymTrack</title>
    <style>
        :root {
            color-scheme: dark;
            --bg-dark: #05030a;
            --panel: rgba(15, 8, 28, 0.95);
            --panel-2: rgba(20, 12, 40, 0.98);
            --text-main: #f6f7ff;
            --text-muted: #adb2d4;
            --accent: #7851A9;
            --accent-strong: #9b6af0;
            --border: rgba(151, 109, 222, 0.22);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        .navbar h1 { font-size: 1.9rem; letter-spacing: 0.03em; }

        .nav-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border: 1px solid rgba(151, 109, 222, 0.3);
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
        }

        .nav-toggle:hover,
        .nav-toggle:focus-visible {
            background: rgba(120, 81, 169, 0.2);
            border-color: rgba(155, 106, 240, 0.6);
            transform: translateY(-1px);
        }

        .nav-toggle.is-active {
            background: rgba(120, 81, 169, 0.24);
            border-color: rgba(155, 106, 240, 0.7);
        }

        .barbell-icon {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .barbell-icon .bar {
            width: 18px;
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, #fff, #c284ff);
            box-shadow: 0 0 12px rgba(194, 132, 255, 0.3);
        }

        .barbell-icon .plate {
            width: 8px;
            height: 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, #a755ff, #7a3ecf);
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 0 4px rgba(255, 255, 255, 0.2);
        }

        .navbar-right {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .navbar-right a {
            color: var(--text-main);
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 999px;
            transition: background 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 600;
            font-size: 0.92rem;
        }

        .navbar-right a:hover {
            background: rgba(120, 81, 169, 0.18);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 24px 60px;
        }

        .page-head {
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .page-head-text h2 {
            font-size: clamp(1.8rem, 2.5vw, 2.2rem);
            margin-bottom: 6px;
        }

        .page-head-text p { 
            color: var(--text-muted); 
            font-size: 1rem; 
        }

        .page-head-action a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #a755ff 0%, #7d3fd0 55%, #632a9f 100%);
            color: #f8f9ff;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 16px 30px rgba(167, 85, 255, 0.32);
            border: 1px solid rgba(177, 109, 255, 0.35);
            white-space: nowrap;
        }

        .page-head-action a:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(194, 132, 255, 0.42);
        }

        .empty-state {
            text-align: center;
            padding: 60px 32px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 24px;
        }

        .empty-state h3 {
            font-size: 1.4rem;
            margin-bottom: 12px;
            color: var(--text-main);
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 24px;
            font-size: 1rem;
        }

        .empty-state a {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(151, 109, 222, 0.2);
            border: 1px solid rgba(151, 109, 222, 0.4);
            color: #d8b8ff;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .empty-state a:hover {
            background: rgba(151, 109, 222, 0.3);
            border-color: rgba(155, 106, 240, 0.6);
        }

        .week-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 24px;
            transition: all 0.2s ease;
        }

        .week-card:hover {
            background: rgba(18, 10, 37, 0.98);
            border-color: rgba(155, 106, 240, 0.3);
        }

        .week-header {
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(151, 109, 222, 0.15);
        }

        .week-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .week-range {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .week-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .day-badge {
            padding: 8px 12px;
            background: rgba(151, 109, 222, 0.1);
            border: 1px solid rgba(151, 109, 222, 0.2);
            border-radius: 8px;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: #d8b8ff;
        }

        .workouts-list {
            display: grid;
            gap: 16px;
        }

        .workout-item {
            background: var(--panel-2);
            border: 1px solid rgba(151, 109, 222, 0.12);
            border-radius: 16px;
            padding: 16px;
            transition: all 0.2s ease;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: start;
        }

        .workout-item:hover {
            background: rgba(30, 15, 50, 0.9);
            border-color: rgba(155, 106, 240, 0.2);
        }

        .workout-info {
            min-width: 0;
        }

        .workout-date-day {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .workout-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
            word-break: break-word;
        }

        .workout-stats {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .stat {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 600;
        }

        .stat-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--accent-strong);
        }

        .exercises-summary {
            font-size: 0.88rem;
            color: var(--text-muted);
            padding: 8px 12px;
            background: rgba(151, 109, 222, 0.08);
            border-radius: 8px;
            margin-top: 8px;
        }

        .workout-actions {
            display: flex;
            gap: 8px;
            flex-direction: column;
            white-space: nowrap;
        }

        .workout-actions a, .workout-actions button {
            padding: 8px 14px;
            border: 1px solid rgba(151, 109, 222, 0.3);
            background: rgba(151, 109, 222, 0.08);
            color: #d8b8ff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }

        .workout-actions a:hover, .workout-actions button:hover {
            background: rgba(151, 109, 222, 0.15);
            border-color: rgba(155, 106, 240, 0.5);
        }

        .workout-actions button.delete {
            background: rgba(255, 94, 94, 0.1);
            border-color: rgba(255, 94, 94, 0.3);
            color: #ffb3b3;
        }

        .workout-actions button.delete:hover {
            background: rgba(255, 94, 94, 0.18);
            border-color: rgba(255, 94, 94, 0.5);
        }

        @media (max-width: 720px) {
            .page-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .week-card {
                padding: 20px;
            }

            .workout-item {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .workout-actions {
                flex-direction: row;
                gap: 8px;
            }

            .workout-actions a, .workout-actions button {
                flex: 1;
            }

            .week-summary {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            }

            .container { 
                padding: 20px 16px 40px; 
            }

            .navbar { 
                padding: 16px 20px; 
            }

            .navbar h1 { 
                font-size: 1.6rem; 
            }

            .nav-toggle { display: inline-flex; }

            .navbar-right {
                display: none;
                position: absolute;
                top: calc(100% + 10px);
                right: 20px;
                left: 20px;
                flex-direction: column;
                align-items: stretch;
                padding: 14px;
                background: rgba(5, 5, 15, 0.98);
                border: 1px solid rgba(151, 109, 222, 0.24);
                border-radius: 18px;
                box-shadow: 0 16px 32px rgba(0, 0, 0, 0.24);
            }

            .navbar-right.is-open { display: flex; }

            .navbar-right a {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>💪 GymTrack</h1>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" type="button">
            <span class="barbell-icon" aria-hidden="true">
                <span class="plate"></span>
                <span class="bar"></span>
                <span class="plate"></span>
            </span>
        </button>
        <div class="navbar-right" id="navMenu">
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="friends.php">Friends</a>
            <a href="api/logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-head">
            <div class="page-head-text">
                <h2>My Workouts</h2>
                <p>Track your weekly progress and workout history.</p>
            </div>
            <div class="page-head-action">
                <a href="log-workout.php">+ Log Workout</a>
            </div>
        </div>

        <?php if (empty($workouts)): ?>
            <div class="empty-state">
                <h3>No workouts logged yet</h3>
                <p>Start tracking your fitness journey by logging your first workout session.</p>
                <a href="log-workout.php">Log Your First Workout</a>
            </div>
        <?php else: ?>
            <?php foreach ($workoutsByWeek as $weekKey => $weekData): ?>
                <div class="week-card" id="week-<?php echo htmlspecialchars($weekKey); ?>">
                    <div class="week-header">
                        <div class="week-title">
                            Week <?php echo $weekData['week']; ?> of <?php echo $weekData['year']; ?>
                        </div>
                        <div class="week-range">
                            <?php 
                                $startDate = new DateTime($weekData['startDate']);
                                $endDate = new DateTime($weekData['endDate']);
                                echo $startDate->format('M j') . ' - ' . $endDate->format('M j, Y');
                            ?>
                        </div>
                        <div class="week-summary">
                            <?php
                                $daysInWeek = [];
                                foreach ($weekData['workouts'] as $workout) {
                                    $date = new DateTime($workout['session_date']);
                                    $day = $date->format('D');
                                    $daysInWeek[$day] = true;
                                }
                                $dayOrder = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                foreach ($dayOrder as $day) {
                                    if (isset($daysInWeek[$day])) {
                                        echo '<div class="day-badge">' . $day . '</div>';
                                    }
                                }
                            ?>
                        </div>
                    </div>

                    <div class="workouts-list">
                        <?php foreach ($weekData['workouts'] as $workout): ?>
                            <div class="workout-item">
                                <div class="workout-info">
                                    <div class="workout-date-day">
                                        <?php 
                                            $date = new DateTime($workout['session_date']);
                                            echo $date->format('l, F j');
                                        ?>
                                    </div>
                                    <div class="workout-name">
                                        <?php echo htmlspecialchars($workout['plan_name'] ?: 'Workout'); ?>
                                    </div>
                                    <div class="workout-stats">
                                        <?php if ($workout['duration_minutes']): ?>
                                            <div class="stat">
                                                <span class="stat-label">Duration</span>
                                                <span class="stat-value"><?php echo $workout['duration_minutes']; ?> min</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="stat">
                                            <span class="stat-label">Exercises</span>
                                            <span class="stat-value"><?php echo count($workoutDetails[$workout['id']]); ?></span>
                                        </div>
                                        <?php if ($workout['mood']): ?>
                                            <div class="stat">
                                                <span class="stat-label">Mood</span>
                                                <span class="stat-value"><?php echo ucfirst($workout['mood']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($workoutDetails[$workout['id']])): ?>
                                        <div class="exercises-summary">
                                            <?php 
                                                $exerciseNames = array_map(function($ex) { return htmlspecialchars($ex['exercise_name']); }, $workoutDetails[$workout['id']]);
                                                echo implode(', ', $exerciseNames);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="workout-actions">
                                    <a href="#" onclick="viewDetails(<?php echo $workout['id']; ?>); return false;">View</a>
                                    <button class="delete" onclick="deleteWorkout(<?php echo $workout['id']; ?>)">Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');

        if (navToggle && navMenu) {
            navToggle.addEventListener('click', function () {
                navMenu.classList.toggle('is-open');
                navToggle.classList.toggle('is-active');
            });

            document.addEventListener('click', function (event) {
                if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                    navMenu.classList.remove('is-open');
                    navToggle.classList.remove('is-active');
                }
            });
        }

        function viewDetails(workoutId) {
            alert('Detailed view for workout ' + workoutId + ' coming soon!');
        }

        function deleteWorkout(workoutId) {
            if (!confirm('Are you sure you want to delete this workout?')) return;

            fetch('api/delete-workout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ workout_id: workoutId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Could not delete workout'));
                }
            })
            .catch(() => alert('Could not reach the server'));
        }
    </script>
</body>
</html>
