<?php
// progression.php
// Shows a weight-over-time chart plus session history for one exercise at a time.

require_once __DIR__ . '/api/includes/db.php';
require_once __DIR__ . '/api/includes/auth.php';

requireLogin();

$userId = getUserId();
$user = getUserInfo($conn, $userId);

// List of exercises this user has logged, most recently trained first
$stmt = $conn->prepare("
    SELECT e.exercise_name, MAX(ws.session_date) AS last_logged
    FROM exercises e
    JOIN workout_sessions ws ON e.session_id = ws.id
    WHERE ws.user_id = ?
    GROUP BY e.exercise_name
    ORDER BY last_logged DESC, e.exercise_name ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$exerciseList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$defaultExercise = $exerciseList[0]['exercise_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progression - GymTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            --good: #37d6a7;
            --bad: #ff7a7a;
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

        .navbar-right { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }

        .navbar-right a {
            color: var(--text-main);
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 999px;
            transition: background 0.3s ease, transform 0.2s ease;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 600;
            font-size: 0.92rem;
        }

        .navbar-right a:hover { background: rgba(120, 81, 169, 0.18); transform: translateY(-1px); }

        .container { max-width: 980px; margin: 0 auto; padding: 32px 24px 60px; }

        .page-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }

        .page-head h2 { font-size: clamp(1.7rem, 2.4vw, 2.2rem); margin-bottom: 6px; }
        .page-head p { color: var(--text-muted); font-size: 1rem; }

        .exercise-picker { display: grid; gap: 8px; min-width: 240px; }

        .exercise-picker label {
            color: var(--text-muted);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 12px;
            border: 1px solid rgba(151, 109, 222, 0.22);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            font-size: 0.95rem;
            font-family: inherit;
        }

        select:focus { outline: none; border-color: rgba(155, 106, 240, 0.8); box-shadow: 0 0 0 3px rgba(155, 106, 240, 0.16); }
        select option { background: #14092b; color: #fff; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .summary-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.18);
        }

        .summary-card p { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 8px; }
        .summary-card h3 { font-size: 1.6rem; letter-spacing: -0.02em; }
        .summary-card h3.good { color: var(--good); }
        .summary-card h3.bad { color: var(--bad); }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 26px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.22);
            margin-bottom: 22px;
        }

        .panel-title { font-size: 1.05rem; font-weight: 700; margin-bottom: 18px; color: #fff; }

        .chart-wrap { position: relative; height: 280px; }

        .entry-row {
            background: var(--panel-2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .entry-row .entry-date { font-weight: 700; min-width: 90px; }
        .entry-row .entry-plan { color: var(--text-muted); font-size: 0.85rem; }
        .entry-row .entry-stats { font-family: inherit; font-weight: 700; color: #d8b8ff; }
        .entry-row .entry-notes { color: var(--text-muted); font-size: 0.85rem; flex-basis: 100%; }

        .empty-state {
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 40px;
            border-radius: 24px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state h3 { color: #fff; margin-bottom: 10px; }
        .empty-state p { margin-bottom: 18px; }

        .empty-state a {
            background: linear-gradient(135deg, #a755ff 0%, #7d3fd0 55%, #632a9f 100%);
            color: #fff;
            padding: 12px 22px;
            border-radius: 999px;
            text-decoration: none;
            display: inline-block;
            font-weight: 700;
        }

        .loading-note { color: var(--text-muted); font-size: 0.9rem; padding: 20px 0; text-align: center; }

        @media (max-width: 720px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .page-head { flex-direction: column; align-items: stretch; }
            .exercise-picker { min-width: 100%; }
            .chart-wrap { height: 220px; }
            .summary-card { padding: 14px; }
        }

        @media (max-width: 420px) {
            .summary-grid { grid-template-columns: 1fr; }
            .chart-wrap { height: 180px; }
            .panel { padding: 16px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>💪 GymTrack</h1>
        <div class="navbar-right">
            <a href="dashboard.php">Dashboard</a>
            <a href="log-workout.php">Log Workout</a>
            <a href="profile.php">Profile</a>
            <a href="friends.php">Friends</a>
            <a href="api/logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (empty($exerciseList)): ?>
            <div class="page-head"><div><h2>Progression</h2><p>Track how your numbers move over time.</p></div></div>
            <div class="empty-state">
                <h3>Nothing to show yet</h3>
                <p>Log a workout first, then come back to see your progress.</p>
                <a href="log-workout.php">Log your first workout</a>
            </div>
        <?php else: ?>
            <div class="page-head">
                <div>
                    <h2>Progression</h2>
                    <p>Track how your numbers move over time.</p>
                </div>
                <div class="exercise-picker">
                    <label for="exerciseSelect">Exercise</label>
                    <select id="exerciseSelect">
                        <?php foreach ($exerciseList as $ex): ?>
                            <option value="<?php echo htmlspecialchars($ex['exercise_name']); ?>">
                                <?php echo htmlspecialchars($ex['exercise_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="summary-grid" id="summaryGrid">
                <div class="summary-card"><p>Personal best</p><h3 id="statBest">—</h3></div>
                <div class="summary-card"><p>Most recent avg</p><h3 id="statLatest">—</h3></div>
                <div class="summary-card"><p>Sessions logged</p><h3 id="statSessions">—</h3></div>
                <div class="summary-card"><p>Change since first</p><h3 id="statDelta">—</h3></div>
            </div>

            <div class="panel">
                <p class="panel-title">Average weight per session (kg)</p>
                <div class="chart-wrap"><canvas id="progressionChart"></canvas></div>
            </div>

            <div class="panel">
                <p class="panel-title">Session history</p>
                <div id="entryList"><p class="loading-note">Loading…</p></div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($exerciseList)): ?>
    <script>
        const defaultExercise = <?php echo json_encode($defaultExercise); ?>;
        const exerciseSelect = document.getElementById('exerciseSelect');
        let chartInstance = null;

        function fmtDate(iso) {
            const d = new Date(iso + 'T00:00:00');
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }

        function loadProgression(exerciseName) {
            document.getElementById('entryList').innerHTML = '<p class="loading-note">Loading…</p>';

            fetch('api/get-progression.php?exercise=' + encodeURIComponent(exerciseName))
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('entryList').innerHTML =
                            '<p class="loading-note">' + (data.error || 'Could not load progression.') + '</p>';
                        return;
                    }
                    renderSummary(data.summary);
                    renderChart(data.exercise, data.chart);
                    renderEntries(data.entries);
                })
                .catch(() => {
                    document.getElementById('entryList').innerHTML =
                        '<p class="loading-note">Could not reach the server.</p>';
                });
        }

        function renderSummary(summary) {
            document.getElementById('statBest').textContent = summary.best + ' kg';
            document.getElementById('statLatest').textContent = summary.latest + ' kg';
            document.getElementById('statSessions').textContent = summary.sessions;

            const deltaEl = document.getElementById('statDelta');
            const sign = summary.delta > 0 ? '+' : '';
            deltaEl.textContent = sign + summary.delta + ' kg';
            deltaEl.className = summary.delta > 0 ? 'good' : (summary.delta < 0 ? 'bad' : '');
        }

        function renderChart(exerciseName, chartData) {
            const labels = chartData.map(row => fmtDate(row.date));
            const values = chartData.map(row => row.avg_weight);

            const ctx = document.getElementById('progressionChart').getContext('2d');

            if (chartInstance) chartInstance.destroy();

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: exerciseName + ' — avg kg',
                        data: values,
                        borderColor: '#a755ff',
                        backgroundColor: 'rgba(167, 85, 255, 0.15)',
                        pointBackgroundColor: '#c284ff',
                        pointBorderColor: '#f6f7ff',
                        pointRadius: 4,
                        tension: 0.35,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            ticks: { color: '#adb2d4' },
                            grid: { color: 'rgba(151, 109, 222, 0.1)' }
                        },
                        y: {
                            ticks: { color: '#adb2d4' },
                            grid: { color: 'rgba(151, 109, 222, 0.1)' },
                            beginAtZero: false
                        }
                    }
                }
            });
        }

        function renderEntries(entries) {
            const list = document.getElementById('entryList');
            list.innerHTML = entries.map(e => `
                <div class="entry-row">
                    <span class="entry-date">${fmtDate(e.date)}</span>
                    <span class="entry-plan">${e.plan_name ? e.plan_name : 'No plan'}</span>
                    <span class="entry-stats">${e.weight}kg × ${e.reps} × ${e.sets}</span>
                    ${e.notes ? `<span class="entry-notes">${e.notes.replace(/</g, '&lt;')}</span>` : ''}
                </div>
            `).join('');
        }

        exerciseSelect.addEventListener('change', function () {
            loadProgression(this.value);
        });

        loadProgression(defaultExercise);
    </script>
    <?php endif; ?>
</body>
</html>
