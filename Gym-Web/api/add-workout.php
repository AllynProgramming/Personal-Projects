<?php
// api/add-workout.php
// Saves a workout session + its exercises. Called via fetch() from log-workout.php.

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

// This is an AJAX endpoint, so we respond with JSON on auth failure
// instead of redirecting (requireLogin() would redirect, which fetch() can't follow usefully).
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You are not logged in. Please log in again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = getUserId();

$planName = trim($_POST['plan_name'] ?? '');
$sessionDate = trim($_POST['session_date'] ?? '');
$durationMinutes = trim($_POST['duration_minutes'] ?? '');
$durationMinutes = ($durationMinutes === '') ? null : (int) $durationMinutes;

$exerciseNames = $_POST['exercise_name'] ?? [];
$weights = $_POST['weight'] ?? [];
$reps = $_POST['reps'] ?? [];
$sets = $_POST['sets'] ?? [];
$notes = $_POST['notes'] ?? [];

// --- Validation ---
if (empty($sessionDate)) {
    echo json_encode(['success' => false, 'error' => 'Session date is required.']);
    exit;
}

$d = DateTime::createFromFormat('Y-m-d', $sessionDate);
if (!$d || $d->format('Y-m-d') !== $sessionDate) {
    echo json_encode(['success' => false, 'error' => 'That date does not look valid.']);
    exit;
}

// Build a clean list of exercises, skipping any fully-empty rows
$exercises = [];
for ($i = 0; $i < count($exerciseNames); $i++) {
    $name = trim($exerciseNames[$i] ?? '');
    $w = $weights[$i] ?? '';
    $r = $reps[$i] ?? '';
    $s = $sets[$i] ?? '';
    $n = trim($notes[$i] ?? '');

    if ($name === '' && $w === '' && $r === '' && $s === '') {
        continue; // fully empty row, skip
    }

    if ($name === '' || $w === '' || $r === '' || $s === '') {
        echo json_encode(['success' => false, 'error' => 'Each exercise needs a name, weight, reps, and sets.']);
        exit;
    }

    if (!is_numeric($w) || (float) $w < 0) {
        echo json_encode(['success' => false, 'error' => 'Weight must be a positive number.']);
        exit;
    }

    if (!ctype_digit((string) $r) || (int) $r < 1 || !ctype_digit((string) $s) || (int) $s < 1) {
        echo json_encode(['success' => false, 'error' => 'Reps and sets must be whole numbers of at least 1.']);
        exit;
    }

    $exercises[] = [
        'name' => $name,
        'weight' => (float) $w,
        'reps' => (int) $r,
        'sets' => (int) $s,
        'notes' => $n,
    ];
}

if (empty($exercises)) {
    echo json_encode(['success' => false, 'error' => 'Add at least one exercise before saving.']);
    exit;
}

// --- Resolve (or create) the workout plan ---
$workoutPlanId = null;

if ($planName !== '') {
    $stmt = $conn->prepare("SELECT id FROM workout_plans WHERE user_id = ? AND plan_name = ? LIMIT 1");
    $stmt->bind_param("is", $userId, $planName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $workoutPlanId = $row['id'];
    }
    $stmt->close();

    if ($workoutPlanId === null) {
        $stmt = $conn->prepare("INSERT INTO workout_plans (user_id, plan_name) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $planName);
        $stmt->execute();
        $workoutPlanId = $conn->insert_id;
        $stmt->close();
    }
}

// --- Insert the workout session ---
$stmt = $conn->prepare("
    INSERT INTO workout_sessions (user_id, workout_plan_id, session_date, duration_minutes)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iisi", $userId, $workoutPlanId, $sessionDate, $durationMinutes);

if (!$stmt->execute()) {
    // Unique constraint: (user_id, workout_plan_id, session_date) already exists
    if ($conn->errno === 1062) {
        echo json_encode([
            'success' => false,
            'error' => 'You already logged a workout for this plan on this date. Pick a different date or plan.'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not save the session. Please try again.']);
    }
    $stmt->close();
    exit;
}

$sessionId = $conn->insert_id;
$stmt->close();

// --- Insert each exercise ---
$stmt = $conn->prepare("
    INSERT INTO exercises (session_id, exercise_name, weight, reps, sets, notes)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($exercises as $ex) {
    $stmt->bind_param(
        "isdiis",
        $sessionId,
        $ex['name'],
        $ex['weight'],
        $ex['reps'],
        $ex['sets'],
        $ex['notes']
    );
    $stmt->execute();
}
$stmt->close();

echo json_encode(['success' => true, 'sessionId' => $sessionId]);
exit;
?>
