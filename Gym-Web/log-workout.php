<?php
// log-workout.php
// Form to log a new workout session (session details + one or more exercises)

require_once __DIR__ . '/api/includes/db.php';
require_once __DIR__ . '/api/includes/auth.php';

requireLogin();

$userId = getUserId();
$user = getUserInfo($conn, $userId);

// Pull distinct plan names this user has used before, so they can quickly reuse a custom split
$stmt = $conn->prepare("SELECT DISTINCT plan_name FROM workout_plans WHERE user_id = ? ORDER BY plan_name");
$stmt->bind_param("i", $userId);
$stmt->execute();
$planNames = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Common preset splits shown in the dropdown by default
$commonSplits = ['Upper Day', 'Lower Day', 'Push Day', 'Pull Day', 'Leg Day', 'Full Body', 'Rest / Recovery'];

// Only show past custom plans that aren't already covered by the common presets above
$customPlanNames = array_filter($planNames, function ($p) use ($commonSplits) {
    return !in_array($p['plan_name'], $commonSplits, true);
});

// Default to today's date, but allow the page to use a user-selected date when present
$selectedSessionDate = $_GET['session_date'] ?? date('Y-m-d');

// Exercise name suggestions: the user's own history first, topped up with common lifts
// they haven't logged yet, so the datalist is useful from day one.
$stmt = $conn->prepare("
    SELECT DISTINCT e.exercise_name
    FROM exercises e
    JOIN workout_sessions ws ON e.session_id = ws.id
    WHERE ws.user_id = ?
    ORDER BY e.exercise_name
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$loggedExerciseNames = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'exercise_name');
$stmt->close();

$commonExercises = [
    'Barbell Squat', 'Deadlift', 'Bench Press', 'Incline Bench Press', 'Overhead Press',
    'Barbell Row', 'Pull-Up', 'Lat Pulldown', 'Leg Press', 'Romanian Deadlift',
    'Bulgarian Split Squat', 'Hip Thrust', 'Bicep Curl', 'Tricep Pushdown', 'Lateral Raise',
    'Dumbbell Shoulder Press', 'Cable Row', 'Chest Fly', 'Leg Curl', 'Leg Extension',
    'Calf Raise', 'Plank', 'Hanging Leg Raise', 'Face Pull', 'Hip Abduction',
];

$exerciseSuggestions = array_unique(array_merge($loggedExerciseNames, $commonExercises));
sort($exerciseSuggestions, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Workout - GymTrack</title>
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

        /* ---------- Navbar ---------- */
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
            transition: transform 0.25s ease, opacity 0.25s ease;
            will-change: transform, opacity;
        }

        .navbar.navbar-hidden { transform: translateY(-100%); opacity: 0; pointer-events: none; }
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

        .nav-toggle:hover, .nav-toggle:focus-visible {
            background: rgba(120, 81, 169, 0.2);
            border-color: rgba(155, 106, 240, 0.6);
            transform: translateY(-1px);
        }

        .nav-toggle.is-active { background: rgba(120, 81, 169, 0.24); border-color: rgba(155, 106, 240, 0.7); }

        .barbell-icon { display: inline-flex; align-items: center; gap: 4px; }
        .barbell-icon .bar { width: 18px; height: 4px; border-radius: 999px; background: linear-gradient(90deg, #fff, #c284ff); box-shadow: 0 0 12px rgba(194, 132, 255, 0.3); }
        .barbell-icon .plate { width: 8px; height: 12px; border-radius: 999px; background: linear-gradient(135deg, #a755ff, #7a3ecf); border: 1px solid rgba(255, 255, 255, 0.28); box-shadow: inset 0 0 4px rgba(255, 255, 255, 0.2); }

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

        /* ---------- Layout ---------- */
        .container { max-width: 760px; margin: 0 auto; padding: 28px 20px 56px; }

        .page-head { margin-bottom: 20px; }
        .page-head h2 { font-size: clamp(1.6rem, 2.4vw, 2.1rem); margin-bottom: 4px; }
        .page-head p { color: var(--text-muted); font-size: 0.95rem; }

        .message {
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 600;
            border: 1px solid transparent;
            font-size: 0.92rem;
            margin-bottom: 18px;
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .message.show { display: flex; }
        .message.success { background: rgba(151, 109, 222, 0.14); border-color: rgba(151, 109, 222, 0.3); color: #e7d6ff; }
        .message.error { background: rgba(255, 94, 94, 0.16); border-color: rgba(255, 94, 94, 0.24); color: #ffd7d7; }
        .message a { color: inherit; text-decoration: underline; font-weight: 700; white-space: nowrap; }

        /* ---------- Panels & fields ---------- */
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 16px 34px rgba(0, 0, 0, 0.2);
            margin-bottom: 18px;
        }

        .panel-title { font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: #fff; }

        .field-grid {
            display: grid;
            grid-template-columns: minmax(220px, 1.6fr) minmax(140px, 0.85fr) minmax(140px, 0.7fr);
            gap: 14px;
            align-items: end;
        }

        .form-group { display: grid; gap: 7px; }
        label { color: #d7dcf5; font-size: 0.86rem; font-weight: 600; }

        input, select {
            width: 100%;
            padding: 12px 14px;
            min-height: 46px;
            border-radius: 12px;
            border: 1px solid rgba(151, 109, 222, 0.22);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input::placeholder { color: #7e89ab; }

        input:focus, select:focus {
            outline: none;
            border-color: rgba(155, 106, 240, 0.8);
            box-shadow: 0 0 0 3px rgba(155, 106, 240, 0.16);
        }

        select option { background: #14092b; color: #fff; }
        input[type="date"] { color-scheme: dark; }

        /* ---------- Exercise rows ---------- */
        #exerciseList { display: grid; gap: 10px; margin-bottom: 14px; }

        .exercise-row {
            background: var(--panel-2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
        }

        .exercise-row-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .exercise-row-head span {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .remove-row-btn {
            background: rgba(255, 94, 94, 0.12);
            border: 1px solid rgba(255, 94, 94, 0.28);
            color: #ffb3b3;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 1rem;
            line-height: 1;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: background 0.2s ease;
            flex-shrink: 0;
        }

        .remove-row-btn:hover { background: rgba(255, 94, 94, 0.22); }
        .remove-row-btn:disabled { opacity: 0.3; cursor: not-allowed; }

        .exercise-fields {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .add-row-btn {
            width: 100%;
            padding: 13px 0;
            min-height: 46px;
            background: rgba(151, 109, 222, 0.12);
            border: 1px dashed rgba(155, 106, 240, 0.5);
            color: #d8b8ff;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .add-row-btn:hover { background: rgba(151, 109, 222, 0.2); border-color: rgba(155, 106, 240, 0.8); }

        /* ---------- Save bar ---------- */
        .save-bar { display: flex; gap: 12px; justify-content: flex-end; }

        button.btn-primary, button.btn-ghost {
            border: none;
            cursor: pointer;
            font-weight: 700;
            border-radius: 999px;
            padding: 13px 26px;
            min-height: 46px;
            font-size: 0.92rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #a755ff 0%, #7d3fd0 55%, #632a9f 100%);
            color: #f8f9ff;
            box-shadow: 0 14px 26px rgba(167, 85, 255, 0.3);
            border: 1px solid rgba(177, 109, 255, 0.35);
        }

        .btn-primary:hover { transform: translateY(-2px); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .btn-ghost { background: rgba(255, 255, 255, 0.05); color: var(--text-main); border: 1px solid rgba(255, 255, 255, 0.1); }
        .btn-ghost:hover { background: rgba(255, 255, 255, 0.1); }

        .hidden { display: none; }

        /* ---------- Mobile ---------- */
        @media (max-width: 860px) {
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
            .navbar-right a { width: 100%; text-align: center; justify-content: center; padding: 12px 16px; }
        }

        @media (max-width: 720px) {
            .navbar { padding: 16px 20px; }
            .container { padding: 20px 16px 48px; }
            .panel { padding: 18px; border-radius: 18px; }

            .field-grid { grid-template-columns: 1fr; gap: 12px; align-items: stretch; }

            /* Name gets its own full-width row; weight/reps/sets share a compact triplet below it */
            .exercise-fields { grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .exercise-fields .field-name { grid-column: 1 / -1; }

            .save-bar { flex-direction: column-reverse; gap: 10px; }
            button.btn-primary, button.btn-ghost { width: 100%; }
        }

        @media (max-width: 380px) {
            .exercise-row { padding: 12px; }
            .exercise-fields { gap: 6px; }
            input, select { padding: 11px 12px; }
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
            <h2>Log a workout</h2>
            <p>Add today's session — plan, exercises, and the numbers that matter.</p>
        </div>

        <div class="message" id="formMessage"></div>

        <form id="workoutForm">
            <div class="panel">
                <p class="panel-title">Session details</p>
                <div class="field-grid">
                    <div class="form-group">
                        <label for="plan_select">Workout plan</label>
                        <select id="plan_select">
                            <option value="">No plan / not sure yet</option>
                            <optgroup label="Common splits">
                                <?php foreach ($commonSplits as $split): ?>
                                    <option value="<?php echo htmlspecialchars($split); ?>"><?php echo htmlspecialchars($split); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php if (!empty($customPlanNames)): ?>
                                <optgroup label="Your plans">
                                    <?php foreach ($customPlanNames as $p): ?>
                                        <option value="<?php echo htmlspecialchars($p['plan_name']); ?>"><?php echo htmlspecialchars($p['plan_name']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <option value="__custom__">Custom split…</option>
                        </select>
                        <input type="text" id="plan_name" name="plan_name" class="hidden" placeholder="e.g. Chest + Legs, Shoulders + Back" style="margin-top: 8px;">
                    </div>
                    <div class="form-group">
                        <label for="session_date">Date</label>
                        <input type="date" id="session_date" name="session_date" value="<?php echo htmlspecialchars($selectedSessionDate); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="duration_minutes">Duration (min)</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="0" placeholder="60">
                    </div>
                </div>
            </div>

            <div class="panel">
                <p class="panel-title">Exercises</p>
                <div id="exerciseList"></div>
                <button type="button" class="add-row-btn" id="addRowBtn">+ Add exercise</button>
            </div>

            <div class="save-bar">
                <a href="dashboard.php"><button type="button" class="btn-ghost">Cancel</button></a>
                <button type="submit" class="btn-primary" id="saveBtn">Save workout</button>
            </div>
        </form>
    </div>

    <datalist id="exerciseNames">
        <?php foreach ($exerciseSuggestions as $name): ?>
            <option value="<?php echo htmlspecialchars($name); ?>">
        <?php endforeach; ?>
    </datalist>

    <template id="exerciseRowTemplate">
        <div class="exercise-row">
            <div class="exercise-row-head">
                <span class="row-number">#1</span>
                <button type="button" class="remove-row-btn" aria-label="Remove exercise">×</button>
            </div>
            <div class="exercise-fields">
                <div class="form-group field-name">
                    <label>Name</label>
                    <input type="text" name="exercise_name[]" list="exerciseNames" placeholder="Incline Bench Press" required>
                </div>
                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" name="weight[]" step="0.5" min="0" placeholder="50" required>
                </div>
                <div class="form-group">
                    <label>Reps</label>
                    <input type="number" name="reps[]" min="1" placeholder="8" required>
                </div>
                <div class="form-group">
                    <label>Sets</label>
                    <input type="number" name="sets[]" min="1" placeholder="4" required>
                </div>
            </div>
            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes[]" placeholder="Felt strong today">
            </div>
        </div>
    </template>

    <script>
        // ---------- Plan dropdown <-> custom plan text field ----------
        const planSelect = document.getElementById('plan_select');
        const planNameInput = document.getElementById('plan_name');

        planSelect.addEventListener('change', function () {
            if (this.value === '__custom__') {
                planNameInput.value = '';
                planNameInput.classList.remove('hidden');
                planNameInput.required = true;
                planNameInput.focus();
            } else {
                planNameInput.value = this.value;
                planNameInput.classList.add('hidden');
                planNameInput.required = false;
            }
        });

        // ---------- Exercise rows (add/remove via one delegated listener) ----------
        const exerciseList = document.getElementById('exerciseList');
        const template = document.getElementById('exerciseRowTemplate');

        function renumberRows() {
            const rows = exerciseList.querySelectorAll('.exercise-row');
            rows.forEach((row, i) => {
                row.querySelector('.row-number').textContent = '#' + (i + 1);
                row.querySelector('.remove-row-btn').disabled = rows.length <= 1;
            });
        }

        function addRow(focus = false) {
            const clone = template.content.cloneNode(true);
            exerciseList.appendChild(clone);
            renumberRows();
            if (focus) {
                exerciseList.querySelector('.exercise-row:last-child input').focus();
            }
        }

        exerciseList.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-row-btn');
            if (!btn || btn.disabled) return;
            btn.closest('.exercise-row').remove();
            renumberRows();
        });

        document.getElementById('addRowBtn').addEventListener('click', () => addRow(true));

        // ---------- Nav toggle (mobile) ----------
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

        // ---------- Hide navbar on scroll down, show on scroll up ----------
        const navbar = document.querySelector('.navbar');
        let lastScrollY = window.scrollY;

        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            if (currentScrollY > lastScrollY && currentScrollY > 80) {
                navbar.classList.add('navbar-hidden');
            } else if (currentScrollY < lastScrollY) {
                navbar.classList.remove('navbar-hidden');
            }
            lastScrollY = currentScrollY;
        });

        // Start with one exercise row
        addRow();

        // ---------- Form submit via AJAX — stays on this page after saving ----------
        const form = document.getElementById('workoutForm');
        const messageEl = document.getElementById('formMessage');
        const saveBtn = document.getElementById('saveBtn');

        function showMessage(html, type) {
            messageEl.innerHTML = html;
            messageEl.className = 'message show ' + type;
        }

        function resetFormForNextEntry() {
            // Keep plan + date (you're often logging several exercises for the same session),
            // clear duration and exercises back to a single blank row.
            document.getElementById('duration_minutes').value = '';
            exerciseList.innerHTML = '';
            addRow();
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            fetch('api/add-workout.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showMessage('Workout saved! <a href="workouts.php">View in My Workouts</a>', 'success');
                    resetFormForNextEntry();
                } else {
                    showMessage(data.error || 'Something went wrong. Please try again.', 'error');
                }
            })
            .catch(() => {
                showMessage('Could not reach the server. Please try again.', 'error');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save workout';
            });
        });
    </script>
</body>
</html>