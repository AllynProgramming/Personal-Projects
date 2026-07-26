<?php
// api/get-progression.php
// Returns chart + list data for one exercise, for the logged-in user. Called via fetch() from progression.php.

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You are not logged in. Please log in again.']);
    exit;
}

$userId = getUserId();
$exerciseName = trim($_GET['exercise'] ?? '');

if ($exerciseName === '') {
    echo json_encode(['success' => false, 'error' => 'No exercise specified.']);
    exit;
}

// Raw entries — every logged set/row for this exercise, most recent first.
// Also used to compute personal-best (true max single weight, not an average).
$stmt = $conn->prepare("
    SELECT ws.session_date AS date, wp.plan_name AS plan_name, e.weight, e.reps, e.sets, e.notes
    FROM exercises e
    JOIN workout_sessions ws ON e.session_id = ws.id
    LEFT JOIN workout_plans wp ON ws.workout_plan_id = wp.id
    WHERE ws.user_id = ? AND e.exercise_name = ?
    ORDER BY ws.session_date DESC, e.id DESC
");
$stmt->bind_param("is", $userId, $exerciseName);
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($entries)) {
    echo json_encode(['success' => false, 'error' => 'No entries found for that exercise.']);
    exit;
}

// Chart data — average weight per session date (matches how the app defines "progression":
// several sets on the same day are averaged into one point for that day), oldest first.
$stmt = $conn->prepare("
    SELECT ws.session_date AS date, AVG(e.weight) AS avg_weight
    FROM exercises e
    JOIN workout_sessions ws ON e.session_id = ws.id
    WHERE ws.user_id = ? AND e.exercise_name = ?
    GROUP BY ws.session_date
    ORDER BY ws.session_date ASC
");
$stmt->bind_param("is", $userId, $exerciseName);
$stmt->execute();
$chartRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$chart = array_map(function ($row) {
    return ['date' => $row['date'], 'avg_weight' => round((float) $row['avg_weight'], 1)];
}, $chartRows);

$weights = array_column($entries, 'weight');
$bestWeight = max($weights);
$firstAvg = $chart[0]['avg_weight'];
$latestAvg = $chart[count($chart) - 1]['avg_weight'];

echo json_encode([
    'success' => true,
    'exercise' => $exerciseName,
    'summary' => [
        'best' => (float) $bestWeight,
        'latest' => $latestAvg,
        'first' => $firstAvg,
        'delta' => round($latestAvg - $firstAvg, 1),
        'sessions' => count($chart),
    ],
    'chart' => $chart,
    'entries' => $entries,
]);
exit;
?>
