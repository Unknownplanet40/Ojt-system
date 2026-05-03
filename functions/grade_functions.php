<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

// functions/grade_functions.php
// -----------------------------------------------
// Module:    Grade Computation
// Primary:   Coordinator (computes + finalizes)
// Secondary: Student (view own) · Admin (view all)
// -----------------------------------------------
require_once __DIR__ . '/evaluation_functions.php';
require_once __DIR__ . '/dtr_functions.php';

// default weights — must sum to 100
const DEFAULT_WEIGHTS = [
    'hours'   => 20,
    'midterm' => 20,
    'final'   => 40,
    'journal' => 10,
    'self'    => 10,
];


// -----------------------------------------------
// CHECK if student is ready for grading
// all components must be present
// -----------------------------------------------
function isReadyForGrading(
    $conn,
    string $studentUuid,
    string $batchUuid
): array {
    $issues = [];

    // check hours completion
    $dtrSummary = getDtrSummary($conn, $studentUuid, $batchUuid);
    if (!$dtrSummary['is_complete']) {
        $issues[] = "Hours not yet complete ({$dtrSummary['percentage']}% of {$dtrSummary['required_hours']} hrs).";
    }

    // check evaluations
    $evalSummary = getEvaluationSummary($conn, $studentUuid, $batchUuid);
    if (!$evalSummary['has_midterm']) $issues[] = 'Midterm evaluation not yet submitted.';
    if (!$evalSummary['has_final'])   $issues[] = 'Final evaluation not yet submitted.';
    if (!$evalSummary['has_self'])    $issues[] = 'Self-evaluation not yet submitted.';

    // check journal submissions
    $stmt = $conn->prepare("
        SELECT
          COUNT(*) AS total_journals,
          SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_journals
        FROM weekly_journals
        WHERE student_uuid = ? AND batch_uuid = ?
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $journalRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalJournals    = (int) $journalRow['total_journals'];
    $approvedJournals = (int) $journalRow['approved_journals'];

    if ($totalJournals === 0) {
        $issues[] = 'No journal entries submitted.';
    }

    return [
        'ready'            => empty($issues),
        'issues'           => $issues,
        'dtr_summary'      => $dtrSummary,
        'eval_summary'     => $evalSummary,
        'total_journals'   => $totalJournals,
        'approved_journals'=> $approvedJournals,
    ];
}


// -----------------------------------------------
// COMPUTE grade components
// returns all scores without saving
// -----------------------------------------------
function computeGradeComponents(
    $conn,
    string $studentUuid,
    string $batchUuid,
    array  $weights = []
): array {
    // merge with defaults
    $weights = array_merge(DEFAULT_WEIGHTS, array_filter($weights));

    // validate weights sum to 100
    $weightSum = array_sum($weights);
    if (abs($weightSum - 100) > 0.01) {
        return [
            'success' => false,
            'error'   => "Weights must sum to 100. Current sum: {$weightSum}",
        ];
    }

    // hours score — percentage of completion (capped at 100)
    $dtrSummary  = getDtrSummary($conn, $studentUuid, $batchUuid);
    $hoursScore  = min(100, $dtrSummary['percentage']);

    // evaluation scores — convert from 5-point to percentage
    $evalSummary  = getEvaluationSummary($conn, $studentUuid, $batchUuid);
    $midtermScore = $evalSummary['midterm_pct'] ?? 0;
    $finalScore   = $evalSummary['final_pct']   ?? 0;
    $selfScore    = $evalSummary['self_pct']     ?? 0;

    // journal score — based on approved journals vs expected weeks
    $journalScore = computeJournalScore($conn, $studentUuid, $batchUuid);

    // weighted total
    $weightedScore = round(
        ($hoursScore   * $weights['hours']   / 100) +
        ($midtermScore * $weights['midterm'] / 100) +
        ($finalScore   * $weights['final']   / 100) +
        ($journalScore * $weights['journal'] / 100) +
        ($selfScore    * $weights['self']    / 100),
        2
    );

    $gradeEquivalent = percentageToGrade($weightedScore);
    $remarks         = gradeRemarks($gradeEquivalent);

    return [
        'success'          => true,
        // component scores
        'hours_score'      => round($hoursScore,   2),
        'midterm_score'    => round($midtermScore,  2),
        'final_score'      => round($finalScore,    2),
        'journal_score'    => round($journalScore,  2),
        'self_score'       => round($selfScore,     2),
        // weights used
        'weights'          => $weights,
        // result
        'weighted_score'   => $weightedScore,
        'grade_equivalent' => $gradeEquivalent,
        'remarks'          => $remarks,
        // context
        'dtr_summary'      => $dtrSummary,
        'eval_summary'     => $evalSummary,
    ];
}


// -----------------------------------------------
// COMPUTE journal score
// approved journals / expected weeks * 100
// -----------------------------------------------
function computeJournalScore(
    $conn,
    string $studentUuid,
    string $batchUuid
): float {
    // get OJT start date and required hours
    $stmt = $conn->prepare("
        SELECT osc.start_date, p.required_hours, osc.working_hours_per_day
        FROM ojt_applications a
        JOIN ojt_start_confirmations osc ON osc.application_uuid = a.uuid
        JOIN student_profiles sp ON a.student_uuid = sp.uuid
        JOIN programs p ON sp.program_uuid = p.uuid
        WHERE a.student_uuid = ?
          AND a.batch_uuid   = ?
          AND a.status       = 'active'
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return 0;

    $startDate     = $row['start_date'];
    $requiredHours = (int)   $row['required_hours'];
    $hoursPerDay   = (int)   ($row['working_hours_per_day'] ?? 8);
    $daysNeeded    = ceil($requiredHours / $hoursPerDay);
    $weeksExpected = max(1, ceil($daysNeeded / 5)); // 5 working days per week

    // count approved journals
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS approved
        FROM weekly_journals
        WHERE student_uuid = ?
          AND batch_uuid   = ?
          AND status       = 'approved'
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $approvedJournals = (int) $stmt->get_result()->fetch_assoc()['approved'];
    $stmt->close();

    if ($weeksExpected === 0) return 0;

    return min(100, round(($approvedJournals / $weeksExpected) * 100, 2));
}


// -----------------------------------------------
// SAVE / UPDATE grade (coordinator)
// can be called multiple times before finalizing
// -----------------------------------------------
function saveGrade(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $coordinatorUuid,
    array  $weights = [],
    string $coordinatorNotes = ''
): array {
    // check not already finalized
    $stmt = $conn->prepare("
        SELECT uuid, is_finalized FROM ojt_grades
        WHERE student_uuid = ? AND batch_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing && (int) $existing['is_finalized'] === 1) {
        return ['success' => false, 'error' => 'Grade is already finalized and cannot be changed.'];
    }

    // compute
    $computed = computeGradeComponents($conn, $studentUuid, $batchUuid, $weights);

    if (!$computed['success']) {
        return $computed;
    }

    // get application UUID
    $stmt = $conn->prepare("
        SELECT uuid FROM ojt_applications
        WHERE student_uuid = ? AND batch_uuid = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'No active OJT found for this student.'];
    }

    $uuid = $existing['uuid'] ?? generateUuid();
    $w    = $computed['weights'];

    $stmt = $conn->prepare("
        INSERT INTO ojt_grades
          (uuid, student_uuid, application_uuid, batch_uuid, finalized_by,
           hours_score, midterm_score, final_score, journal_score, self_score,
           hours_weight, midterm_weight, final_weight, journal_weight, self_weight,
           weighted_score, grade_equivalent, remarks,
           coordinator_notes, is_finalized)
        VALUES (?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, 0)
        ON DUPLICATE KEY UPDATE
          finalized_by      = VALUES(finalized_by),
          hours_score       = VALUES(hours_score),
          midterm_score     = VALUES(midterm_score),
          final_score       = VALUES(final_score),
          journal_score     = VALUES(journal_score),
          self_score        = VALUES(self_score),
          hours_weight      = VALUES(hours_weight),
          midterm_weight    = VALUES(midterm_weight),
          final_weight      = VALUES(final_weight),
          journal_weight    = VALUES(journal_weight),
          self_weight       = VALUES(self_weight),
          weighted_score    = VALUES(weighted_score),
          grade_equivalent  = VALUES(grade_equivalent),
          remarks           = VALUES(remarks),
          coordinator_notes = VALUES(coordinator_notes),
          updated_at        = NOW()
    ");
    $stmt->bind_param(
        'sssss' .
        'ddddd' .
        'ddddd' .
        'dss' .
        'si',
        $uuid, $studentUuid, $app['uuid'], $batchUuid, $coordinatorUuid,
        $computed['hours_score'],   $computed['midterm_score'],
        $computed['final_score'],   $computed['journal_score'],
        $computed['self_score'],
        $w['hours'], $w['midterm'], $w['final'], $w['journal'], $w['self'],
        $computed['weighted_score'], $computed['grade_equivalent'],
        $computed['remarks'],
        $coordinatorNotes, 0
    );
    $stmt->execute();
    $stmt->close();

    return [
        'success'  => true,
        'uuid'     => $uuid,
        'computed' => $computed,
    ];
}


// -----------------------------------------------
// FINALIZE grade — locks it permanently
// student can see grade after this
// -----------------------------------------------
function finalizeGrade(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $coordinatorUuid,
    array  $weights = [],
    string $coordinatorNotes = ''
): array {
    // save first with latest weights
    $saveResult = saveGrade(
        $conn, $studentUuid, $batchUuid,
        $coordinatorUuid, $weights, $coordinatorNotes
    );

    if (!$saveResult['success']) {
        return $saveResult;
    }

    // finalize
    $stmt = $conn->prepare("
        UPDATE ojt_grades
        SET is_finalized  = 1,
            finalized_at  = NOW(),
            finalized_by  = ?
        WHERE student_uuid = ? AND batch_uuid = ?
    ");
    $stmt->bind_param('sss', $coordinatorUuid, $studentUuid, $batchUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'grade_finalized',
        description: "OJT grade finalized: {$saveResult['computed']['grade_equivalent']} ({$saveResult['computed']['weighted_score']}%)",
        module: 'grades',
        actorUuid: $coordinatorUuid,
        targetUuid: $studentUuid
    );

    return [
        'success'  => true,
        'computed' => $saveResult['computed'],
    ];
}


// -----------------------------------------------
// GET grade for a student
// -----------------------------------------------
function getStudentGrade(
    $conn,
    string $studentUuid,
    string $batchUuid,
    bool   $studentView = false
): ?array {
    $stmt = $conn->prepare("
        SELECT g.*,
               CONCAT(cp.first_name, ' ', cp.last_name) AS finalized_by_name
        FROM ojt_grades g
        LEFT JOIN coordinator_profiles cp ON g.finalized_by = cp.uuid
        WHERE g.student_uuid = ?
          AND g.batch_uuid   = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;

    // students only see finalized grades
    if ($studentView && (int) $row['is_finalized'] === 0) {
        return null;
    }

    return formatGrade($row);
}


// -----------------------------------------------
// GET all grades — coordinator/admin view
// -----------------------------------------------
function getAllGrades(
    $conn,
    string $batchUuid,
    string $coordinatorUuid = null
): array {
    $safeBatch = $conn->real_escape_string($batchUuid);
    $conditions = ["g.batch_uuid = '{$safeBatch}'"];

    if ($coordinatorUuid) {
        $safeCoord    = $conn->real_escape_string($coordinatorUuid);
        $conditions[] = "sp.coordinator_uuid = '{$safeCoord}'";
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("
        SELECT
          g.*,
          sp.first_name,
          sp.last_name,
          sp.student_number,
          p.code AS program_code,
          CONCAT(cp.first_name, ' ', cp.last_name) AS finalized_by_name

        FROM ojt_grades g
        JOIN student_profiles sp ON g.student_uuid = sp.uuid
        LEFT JOIN programs p ON sp.program_uuid = p.uuid
        LEFT JOIN coordinator_profiles cp ON g.finalized_by = cp.uuid
        WHERE {$where}
        ORDER BY sp.last_name ASC
    ");

    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grade                   = formatGrade($row);
        $grade['full_name']      = $row['first_name'] . ' ' . $row['last_name'];
        $grade['student_number'] = $row['student_number'];
        $grade['program_code']   = $row['program_code'] ?? '—';
        $grades[]                = $grade;
    }

    return $grades;
}


// -----------------------------------------------
// GET grading overview — all students in batch
// shows who is ready, pending, finalized
// -----------------------------------------------
function getGradingOverview(
    $conn,
    string $batchUuid,
    string $coordinatorUuid = null
): array {
    $safeBatch = $conn->real_escape_string($batchUuid);
    $coordFilter = '';

    if ($coordinatorUuid) {
        $safeCoord   = $conn->real_escape_string($coordinatorUuid);
        $coordFilter = "AND sp.coordinator_uuid = '{$safeCoord}'";
    }

    $result = $conn->query("
        SELECT
          sp.uuid           AS student_uuid,
          sp.first_name,
          sp.last_name,
          sp.student_number,
          p.code            AS program_code,
          p.required_hours,

          -- hours
          COALESCE(SUM(CASE WHEN d.status = 'approved' THEN d.hours_rendered END), 0) AS approved_hours,

          -- evaluations
          MAX(CASE WHEN e.eval_type = 'midterm' AND e.submitted_by_role = 'supervisor' THEN 1 ELSE 0 END) AS has_midterm,
          MAX(CASE WHEN e.eval_type = 'final'   AND e.submitted_by_role = 'supervisor' THEN 1 ELSE 0 END) AS has_final,
          MAX(CASE WHEN e.eval_type = 'self'    AND e.submitted_by_role = 'student'   THEN 1 ELSE 0 END) AS has_self,

          -- journals
          SUM(CASE WHEN wj.status = 'approved' THEN 1 ELSE 0 END) AS approved_journals,

          -- grade status
          g.uuid            AS grade_uuid,
          g.weighted_score,
          g.grade_equivalent,
          g.remarks,
          g.is_finalized

        FROM student_profiles sp
        LEFT JOIN programs p ON sp.program_uuid = p.uuid
        LEFT JOIN dtr_entries d
          ON d.student_uuid = sp.uuid AND d.batch_uuid = '{$safeBatch}'
        LEFT JOIN evaluations e
          ON e.student_uuid = sp.uuid AND e.batch_uuid = '{$safeBatch}'
        LEFT JOIN weekly_journals wj
          ON wj.student_uuid = sp.uuid AND wj.batch_uuid = '{$safeBatch}'
        LEFT JOIN ojt_grades g
          ON g.student_uuid = sp.uuid AND g.batch_uuid = '{$safeBatch}'
        WHERE sp.batch_uuid = '{$safeBatch}'
          {$coordFilter}
        GROUP BY sp.uuid
        ORDER BY sp.last_name ASC
    ");

    $overview = [];
    while ($row = $result->fetch_assoc()) {
        $approvedHours = (float) $row['approved_hours'];
        $requiredHours = (int)   $row['required_hours'];
        $pct           = $requiredHours > 0
            ? min(100, round(($approvedHours / $requiredHours) * 100, 1))
            : 0;

        $hoursComplete  = $pct >= 100;
        $hasMidterm     = (int) $row['has_midterm'] === 1;
        $hasFinal       = (int) $row['has_final']   === 1;
        $hasSelf        = (int) $row['has_self']     === 1;
        $hasJournals    = (int) $row['approved_journals'] > 0;
        $isFinalized    = !is_null($row['grade_uuid']) && (int)$row['is_finalized'] === 1;
        $isComputed     = !is_null($row['grade_uuid']) && (int)$row['is_finalized'] === 0;

        $readyForGrading = $hoursComplete && $hasMidterm && $hasFinal && $hasSelf;

        $gradeStatus = match(true) {
            $isFinalized    => 'finalized',
            $isComputed     => 'computed',
            $readyForGrading => 'ready',
            default         => 'incomplete',
        };

        $overview[] = [
            'student_uuid'    => $row['student_uuid'],
            'full_name'       => $row['first_name'] . ' ' . $row['last_name'],
            'initials'        => strtoupper(substr($row['first_name'],0,1) . substr($row['last_name'],0,1)),
            'student_number'  => $row['student_number'],
            'program_code'    => $row['program_code']   ?? '—',
            'approved_hours'  => round($approvedHours,  2),
            'required_hours'  => $requiredHours,
            'hours_pct'       => $pct,
            'hours_complete'  => $hoursComplete,
            'has_midterm'     => $hasMidterm,
            'has_final'       => $hasFinal,
            'has_self'        => $hasSelf,
            'has_journals'    => $hasJournals,
            'approved_journals' => (int) $row['approved_journals'],
            'ready_for_grading' => $readyForGrading,
            'grade_status'    => $gradeStatus,
            'grade_uuid'      => $row['grade_uuid'],
            'weighted_score'  => $row['weighted_score']  ?? null,
            'grade_equivalent'=> $row['grade_equivalent'] ?? null,
            'remarks'         => $row['remarks']          ?? null,
            'is_finalized'    => $isFinalized,
        ];
    }

    return $overview;
}


// -----------------------------------------------
// PERCENTAGE to grade equivalent
// Philippine grading system
// -----------------------------------------------
function percentageToGrade(float $percentage): string
{
    return match(true) {
        $percentage >= 96 => '1.00',
        $percentage >= 92 => '1.25',
        $percentage >= 88 => '1.50',
        $percentage >= 84 => '1.75',
        $percentage >= 80 => '2.00',
        $percentage >= 76 => '2.25',
        $percentage >= 72 => '2.50',
        $percentage >= 68 => '2.75',
        $percentage >= 64 => '3.00',
        default           => '5.00',
    };
}


// -----------------------------------------------
// GRADE remarks
// -----------------------------------------------
function gradeRemarks(string $grade): string
{
    return match(true) {
        in_array($grade, ['1.00','1.25','1.50','1.75','2.00','2.25','2.50','2.75','3.00']) => 'Passed',
        $grade === '5.00' => 'Failed',
        default           => 'Incomplete',
    };
}


// -----------------------------------------------
// FORMAT grade row
// -----------------------------------------------
function formatGrade(array $row): array
{
    $isFinalized = (int) $row['is_finalized'] === 1;
    $score       = (float) $row['weighted_score'];
    $grade       = $row['grade_equivalent'];

    $gradeColors = [
        '1.00' => '#0F6E56', '1.25' => '#0F6E56',
        '1.50' => '#185FA5', '1.75' => '#185FA5',
        '2.00' => '#185FA5', '2.25' => '#185FA5',
        '2.50' => '#BA7517', '2.75' => '#BA7517',
        '3.00' => '#BA7517',
        '5.00' => '#DC2626',
    ];

    return [
        'uuid'              => $row['uuid'],
        'weighted_score'    => round($score, 2),
        'weighted_score_label' => round($score, 2) . '%',
        'grade_equivalent'  => $grade,
        'grade_color'       => $gradeColors[$grade] ?? '#6B7280',
        'remarks'           => $row['remarks'],
        'is_finalized'      => $isFinalized,
        'finalized_at'      => !empty($row['finalized_at'])
                                ? date('M j, Y g:i A', strtotime($row['finalized_at']))
                                : null,
        'finalized_by_name' => $row['finalized_by_name'] ?? null,
        'coordinator_notes' => $row['coordinator_notes'] ?? null,

        // component scores
        'hours_score'       => (float) $row['hours_score'],
        'midterm_score'     => (float) $row['midterm_score'],
        'final_score'       => (float) $row['final_score'],
        'journal_score'     => (float) $row['journal_score'],
        'self_score'        => (float) $row['self_score'],

        // weights
        'hours_weight'      => (float) $row['hours_weight'],
        'midterm_weight'    => (float) $row['midterm_weight'],
        'final_weight'      => (float) $row['final_weight'],
        'journal_weight'    => (float) $row['journal_weight'],
        'self_weight'       => (float) $row['self_weight'],

        // weighted contributions
        'hours_contribution'   => round($row['hours_score']   * $row['hours_weight']   / 100, 2),
        'midterm_contribution' => round($row['midterm_score'] * $row['midterm_weight'] / 100, 2),
        'final_contribution'   => round($row['final_score']   * $row['final_weight']   / 100, 2),
        'journal_contribution' => round($row['journal_score'] * $row['journal_weight'] / 100, 2),
        'self_contribution'    => round($row['self_score']    * $row['self_weight']    / 100, 2),

        'created_at'        => date('M j, Y', strtotime($row['created_at'])),
    ];
}