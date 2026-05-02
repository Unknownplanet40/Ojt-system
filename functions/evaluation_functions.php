<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}
// functions/evaluation_functions.php
// -----------------------------------------------
// Module:    Evaluation Forms
// Primary:   Supervisor (midterm + final)
//            Student (self-evaluation)
// Secondary: Coordinator (view + monitor)
// -----------------------------------------------

require_once __DIR__ . '/../helpers/helpers.php';


// evaluation criteria for supervisor
const SUPERVISOR_CRITERIA = [
    'technical_skills' => 'Technical Skills',
    'work_attitude'    => 'Work Attitude & Professionalism',
    'communication'    => 'Communication Skills',
    'teamwork'         => 'Teamwork & Collaboration',
    'problem_solving'  => 'Problem Solving & Initiative',
];

// self-evaluation criteria for student
const SELF_EVAL_CRITERIA = [
    'technical_skills'   => 'Technical Skills Gained',
    'work_attitude'      => 'Work Attitude & Discipline',
    'communication'      => 'Communication Skills',
    'teamwork'           => 'Teamwork & Adaptability',
    'problem_solving'    => 'Problem Solving Ability',
    'overall_experience' => 'Overall OJT Experience',
];

// rating labels
const RATING_LABELS = [
    1 => 'Poor',
    2 => 'Fair',
    3 => 'Satisfactory',
    4 => 'Good',
    5 => 'Excellent',
];


// -----------------------------------------------
// CHECK if evaluation is unlocked
// midterm → student reached 50% of required hours
// final   → student reached 100% of required hours
// self    → supervisor final evaluation submitted
// -----------------------------------------------
function isEvaluationUnlocked(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $evalType
): array {
    // get required hours + approved hours
    $stmt = $conn->prepare("
        SELECT
          p.required_hours,
          COALESCE(SUM(d.hours_rendered), 0) AS approved_hours
        FROM student_profiles sp
        JOIN programs p ON sp.program_uuid = p.uuid
        LEFT JOIN dtr_entries d
          ON d.student_uuid = sp.uuid
          AND d.batch_uuid  = ?
          AND d.status      = 'approved'
        WHERE sp.uuid = ?
        GROUP BY sp.uuid
    ");
    $stmt->bind_param('ss', $batchUuid, $studentUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $requiredHours = (int)   ($row['required_hours'] ?? 486);
    $approvedHours = (float) ($row['approved_hours'] ?? 0);
    $percentage    = $requiredHours > 0
        ? ($approvedHours / $requiredHours) * 100
        : 0;

    if ($evalType === 'midterm') {
        $unlocked = $percentage >= 50;
        return [
            'unlocked'         => $unlocked,
            'reason'           => $unlocked
                ? null
                : "Midterm evaluation unlocks at 50% hours. Current: " . round($percentage, 1) . "%",
            'percentage'       => round($percentage, 1),
            'required_hours'   => $requiredHours,
            'approved_hours'   => round($approvedHours, 2),
        ];
    }

    if ($evalType === 'final') {
        $unlocked = $percentage >= 100;
        return [
            'unlocked'         => $unlocked,
            'reason'           => $unlocked
                ? null
                : "Final evaluation unlocks at 100% hours. Current: " . round($percentage, 1) . "%",
            'percentage'       => round($percentage, 1),
            'required_hours'   => $requiredHours,
            'approved_hours'   => round($approvedHours, 2),
        ];
    }

    if ($evalType === 'self') {
        // self-eval unlocks when supervisor final is submitted
        $stmt = $conn->prepare("
            SELECT uuid FROM evaluations
            WHERE student_uuid = ?
              AND batch_uuid   = ?
              AND eval_type    = 'final'
              AND submitted_by_role = 'supervisor'
            LIMIT 1
        ");
        $stmt->bind_param('ss', $studentUuid, $batchUuid);
        $stmt->execute();
        $finalExists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return [
            'unlocked' => $finalExists,
            'reason'   => $finalExists
                ? null
                : 'Self-evaluation unlocks after your supervisor submits the final evaluation.',
        ];
    }

    return ['unlocked' => false, 'reason' => 'Invalid evaluation type.'];
}


// -----------------------------------------------
// SUBMIT supervisor evaluation (midterm or final)
// -----------------------------------------------
function submitSupervisorEvaluation(
    $conn,
    string $supervisorUuid,
    string $studentUuid,
    string $batchUuid,
    string $evalType,
    array  $data
): array {
    if (!in_array($evalType, ['midterm', 'final'])) {
        return ['success' => false, 'error' => 'Invalid evaluation type.'];
    }

    // verify supervisor is assigned to this student
    $stmt = $conn->prepare("
        SELECT id FROM student_profiles
        WHERE uuid = ? AND supervisor_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $supervisorUuid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'error' => 'Unauthorized. Student not assigned to you.'];
    }
    $stmt->close();

    // check if evaluation is unlocked
    $unlockCheck = isEvaluationUnlocked($conn, $studentUuid, $batchUuid, $evalType);
    if (!$unlockCheck['unlocked']) {
        return ['success' => false, 'error' => $unlockCheck['reason']];
    }

    // validate scores
    $errors   = [];
    $criteria = ['technical_skills','work_attitude','communication','teamwork','problem_solving'];

    foreach ($criteria as $criterion) {
        $score = (int) ($data[$criterion] ?? 0);
        if ($score < 1 || $score > 5) {
            $errors[$criterion] = SUPERVISOR_CRITERIA[$criterion] . ' rating must be between 1 and 5.';
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $technicalSkills = (int) $data['technical_skills'];
    $workAttitude    = (int) $data['work_attitude'];
    $communication   = (int) $data['communication'];
    $teamwork        = (int) $data['teamwork'];
    $problemSolving  = (int) $data['problem_solving'];
    $comments        = trim($data['comments'] ?? '');

    // compute total score (average of 5 criteria)
    $totalScore = round(
        ($technicalSkills + $workAttitude + $communication + $teamwork + $problemSolving) / 5,
        2
    );

    // get active application UUID
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

    $uuid = generateUuid();

    $stmt = $conn->prepare("
        INSERT INTO evaluations
          (uuid, student_uuid, application_uuid, batch_uuid,
           submitted_by, submitted_by_role, eval_type,
           technical_skills, work_attitude, communication,
           teamwork, problem_solving, total_score, comments)
        VALUES (?, ?, ?, ?, ?, 'supervisor', ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          technical_skills = VALUES(technical_skills),
          work_attitude    = VALUES(work_attitude),
          communication    = VALUES(communication),
          teamwork         = VALUES(teamwork),
          problem_solving  = VALUES(problem_solving),
          total_score      = VALUES(total_score),
          comments         = VALUES(comments),
          updated_at       = NOW()
    ");
    $stmt->bind_param(
        'ssssssiiiiiids',
        $uuid,
        $studentUuid,
        $app['uuid'],
        $batchUuid,
        $supervisorUuid,
        $evalType,
        $technicalSkills,
        $workAttitude,
        $communication,
        $teamwork,
        $problemSolving,
        $totalScore,
        $comments
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'evaluation_submitted',
        description: ucfirst($evalType) . " evaluation submitted for student",
        module: 'evaluation',
        actorUuid: $supervisorUuid,
        targetUuid: $studentUuid
    );

    return [
        'success'     => true,
        'uuid'        => $uuid,
        'total_score' => $totalScore,
        'percentage'  => round(($totalScore / 5) * 100, 1),
    ];
}


// -----------------------------------------------
// SUBMIT student self-evaluation
// -----------------------------------------------
function submitSelfEvaluation(
    $conn,
    string $studentUuid,
    string $batchUuid,
    array  $data
): array {
    // check if unlocked
    $unlockCheck = isEvaluationUnlocked($conn, $studentUuid, $batchUuid, 'self');
    if (!$unlockCheck['unlocked']) {
        return ['success' => false, 'error' => $unlockCheck['reason']];
    }

    // validate scores
    $errors   = [];
    $criteria = ['technical_skills','work_attitude','communication','teamwork','problem_solving','overall_experience'];

    foreach ($criteria as $criterion) {
        $score = (int) ($data[$criterion] ?? 0);
        if ($score < 1 || $score > 5) {
            $errors[$criterion] = (SELF_EVAL_CRITERIA[$criterion] ?? $criterion) . ' rating must be between 1 and 5.';
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $technicalSkills   = (int) $data['technical_skills'];
    $workAttitude      = (int) $data['work_attitude'];
    $communication     = (int) $data['communication'];
    $teamwork          = (int) $data['teamwork'];
    $problemSolving    = (int) $data['problem_solving'];
    $overallExperience = (int) $data['overall_experience'];
    $wouldRecommend    = (int) (!empty($data['would_recommend']) ? 1 : 0);
    $comments          = trim($data['comments'] ?? '');

    // total = average of 5 main criteria (excluding overall experience from score)
    $totalScore = round(
        ($technicalSkills + $workAttitude + $communication + $teamwork + $problemSolving) / 5,
        2
    );

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
        return ['success' => false, 'error' => 'No active OJT found.'];
    }

    $uuid = generateUuid();

    $stmt = $conn->prepare("
        INSERT INTO evaluations
          (uuid, student_uuid, application_uuid, batch_uuid,
           submitted_by, submitted_by_role, eval_type,
           technical_skills, work_attitude, communication,
           teamwork, problem_solving, overall_experience,
           would_recommend, total_score, comments)
        VALUES (?, ?, ?, ?, ?, 'student', 'self', ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          technical_skills   = VALUES(technical_skills),
          work_attitude      = VALUES(work_attitude),
          communication      = VALUES(communication),
          teamwork           = VALUES(teamwork),
          problem_solving    = VALUES(problem_solving),
          overall_experience = VALUES(overall_experience),
          would_recommend    = VALUES(would_recommend),
          total_score        = VALUES(total_score),
          comments           = VALUES(comments),
          updated_at         = NOW()
    ");
    $stmt->bind_param(
        'sssssiiiiiiids',
        $uuid,
        $studentUuid,
        $app['uuid'],
        $batchUuid,
        $studentUuid,
        $technicalSkills,
        $workAttitude,
        $communication,
        $teamwork,
        $problemSolving,
        $overallExperience,
        $wouldRecommend,
        $totalScore,
        $comments
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'evaluation_submitted',
        description: 'Student submitted self-evaluation',
        module: 'evaluation',
        actorUuid: $studentUuid,
        targetUuid: $studentUuid
    );

    return [
        'success'     => true,
        'uuid'        => $uuid,
        'total_score' => $totalScore,
    ];
}


// -----------------------------------------------
// GET evaluations for a student
// returns all 3 types if submitted
// -----------------------------------------------
function getStudentEvaluations(
    $conn,
    string $studentUuid,
    string $batchUuid
): array {
    $stmt = $conn->prepare("
        SELECT e.*
        FROM evaluations e
        WHERE e.student_uuid = ?
          AND e.batch_uuid   = ?
        ORDER BY FIELD(e.eval_type, 'midterm', 'final', 'self')
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // index by type for easy access
    $evaluations = [
        'midterm' => null,
        'final'   => null,
        'self'    => null,
    ];

    foreach ($rows as $row) {
        $evaluations[$row['eval_type']] = formatEvaluation($row);
    }

    // check unlock status for each type
    $midtermUnlock = isEvaluationUnlocked($conn, $studentUuid, $batchUuid, 'midterm');
    $finalUnlock   = isEvaluationUnlocked($conn, $studentUuid, $batchUuid, 'final');
    $selfUnlock    = isEvaluationUnlocked($conn, $studentUuid, $batchUuid, 'self');

    return [
        'evaluations'     => $evaluations,
        'unlock_status'   => [
            'midterm' => $midtermUnlock,
            'final'   => $finalUnlock,
            'self'    => $selfUnlock,
        ],
        'all_submitted'   => !is_null($evaluations['midterm'])
                             && !is_null($evaluations['final'])
                             && !is_null($evaluations['self']),
    ];
}


// -----------------------------------------------
// GET all evaluations — coordinator view
// -----------------------------------------------
function getAllEvaluations(
    $conn,
    string $batchUuid,
    string $coordinatorUuid = null
): array {
    $safeBatch = $conn->real_escape_string($batchUuid);
    $conditions = ["e.batch_uuid = '{$safeBatch}'"];

    if ($coordinatorUuid) {
        $safeCoord    = $conn->real_escape_string($coordinatorUuid);
        $conditions[] = "sp.coordinator_uuid = '{$safeCoord}'";
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("
        SELECT
          e.*,
          sp.first_name,
          sp.last_name,
          sp.student_number,
          p.code AS program_code

        FROM evaluations e
        JOIN student_profiles sp ON e.student_uuid = sp.uuid
        LEFT JOIN programs p ON sp.program_uuid = p.uuid
        WHERE {$where}
        ORDER BY sp.last_name ASC, e.eval_type ASC
    ");

    $evaluations = [];
    while ($row = $result->fetch_assoc()) {
        $eval                   = formatEvaluation($row);
        $eval['full_name']      = $row['first_name'] . ' ' . $row['last_name'];
        $eval['student_number'] = $row['student_number'];
        $eval['program_code']   = $row['program_code'] ?? '—';
        $evaluations[]          = $eval;
    }

    return $evaluations;
}


// -----------------------------------------------
// GET evaluation summary per student
// used for grade computation
// -----------------------------------------------
function getEvaluationSummary(
    $conn,
    string $studentUuid,
    string $batchUuid
): array {
    $stmt = $conn->prepare("
        SELECT
          eval_type,
          total_score,
          technical_skills,
          work_attitude,
          communication,
          teamwork,
          problem_solving,
          overall_experience,
          would_recommend,
          comments,
          submitted_at
        FROM evaluations
        WHERE student_uuid = ?
          AND batch_uuid   = ?
        ORDER BY FIELD(eval_type, 'midterm', 'final', 'self')
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $summary = [
        'midterm_score'  => null,
        'final_score'    => null,
        'self_score'     => null,
        'midterm_pct'    => null,
        'final_pct'      => null,
        'self_pct'       => null,
        'has_midterm'    => false,
        'has_final'      => false,
        'has_self'       => false,
    ];

    foreach ($rows as $row) {
        $score      = (float) $row['total_score'];
        $percentage = round(($score / 5) * 100, 2);

        switch ($row['eval_type']) {
            case 'midterm':
                $summary['midterm_score'] = $score;
                $summary['midterm_pct']   = $percentage;
                $summary['has_midterm']   = true;
                break;
            case 'final':
                $summary['final_score']   = $score;
                $summary['final_pct']     = $percentage;
                $summary['has_final']     = true;
                break;
            case 'self':
                $summary['self_score']    = $score;
                $summary['self_pct']      = $percentage;
                $summary['has_self']      = true;
                break;
        }
    }

    $summary['ready_for_grading'] = $summary['has_midterm']
                                    && $summary['has_final']
                                    && $summary['has_self'];

    return $summary;
}


// -----------------------------------------------
// FORMAT evaluation row
// -----------------------------------------------
function formatEvaluation(array $row): array
{
    $totalScore = (float) $row['total_score'];
    $percentage = round(($totalScore / 5) * 100, 1);

    $evalLabels = [
        'midterm' => 'Midterm Evaluation',
        'final'   => 'Final Evaluation',
        'self'    => 'Self Evaluation',
    ];

    $criteria = [];
    foreach (SUPERVISOR_CRITERIA as $key => $label) {
        if (!is_null($row[$key] ?? null)) {
            $score = (int) $row[$key];
            $criteria[] = [
                'key'     => $key,
                'label'   => $label,
                'score'   => $score,
                'rating'  => RATING_LABELS[$score] ?? '—',
            ];
        }
    }

    // add overall_experience for self-eval
    if ($row['eval_type'] === 'self' && !is_null($row['overall_experience'] ?? null)) {
        $score      = (int) $row['overall_experience'];
        $criteria[] = [
            'key'    => 'overall_experience',
            'label'  => 'Overall OJT Experience',
            'score'  => $score,
            'rating' => RATING_LABELS[$score] ?? '—',
        ];
    }

    return [
        'uuid'              => $row['uuid'],
        'eval_type'         => $row['eval_type'],
        'eval_label'        => $evalLabels[$row['eval_type']] ?? ucfirst($row['eval_type']),
        'submitted_by_role' => $row['submitted_by_role'],
        'total_score'       => $totalScore,
        'percentage'        => $percentage,
        'percentage_label'  => $percentage . '%',
        'grade_equivalent'  => scoreToGrade($totalScore),
        'criteria'          => $criteria,
        'comments'          => $row['comments']          ?? '',
        'would_recommend'   => isset($row['would_recommend'])
                                ? (int) $row['would_recommend'] === 1
                                : null,
        'submitted_at'      => date('M j, Y g:i A', strtotime($row['submitted_at'])),
        'time_ago'          => timeAgo($row['submitted_at']),
    ];
}


// -----------------------------------------------
// SCORE to grade equivalent
// based on Philippine grading system
// -----------------------------------------------
function scoreToGrade(float $score): string
{
    $percentage = ($score / 5) * 100;
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
