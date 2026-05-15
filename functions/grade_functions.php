<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';







require_once __DIR__ . '/evaluation_functions.php';
require_once __DIR__ . '/dtr_functions.php';


const DEFAULT_WEIGHTS = [
    'hours'   => 20,
    'midterm' => 20,
    'final'   => 40,
    'journal' => 10,
    'self'    => 10,
];


function studentBelongsToCoordinator($conn, string $studentUuid, string $coordinatorUuid): bool
{
    $stmt = $conn->prepare("\n        SELECT 1\n        FROM student_profiles\n        WHERE uuid = ?\n          AND coordinator_uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('ss', $studentUuid, $coordinatorUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function resolveActiveBatchUuid($conn, string $batchUuid = ''): ?string
{
    if (!empty($batchUuid)) {
        return $batchUuid;
    }

    $result = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return $row['uuid'] ?? null;
}

function ensureGradeTableExists($conn): bool
{
    $sql = "
        CREATE TABLE IF NOT EXISTS ojt_grades (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            uuid             CHAR(36)      NOT NULL UNIQUE,
            student_uuid     CHAR(36)      NOT NULL,
            application_uuid CHAR(36)      NOT NULL,
            batch_uuid       CHAR(36)      NOT NULL,
            finalized_by     CHAR(36)      NOT NULL,

            hours_score      DECIMAL(5,2)  NOT NULL DEFAULT 0,
            midterm_score    DECIMAL(5,2)  NOT NULL DEFAULT 0,
            final_score      DECIMAL(5,2)  NOT NULL DEFAULT 0,
            journal_score    DECIMAL(5,2)  NOT NULL DEFAULT 0,
            self_score       DECIMAL(5,2)  NOT NULL DEFAULT 0,

            hours_weight     DECIMAL(5,2)  NOT NULL DEFAULT 20,
            midterm_weight   DECIMAL(5,2)  NOT NULL DEFAULT 20,
            final_weight     DECIMAL(5,2)  NOT NULL DEFAULT 40,
            journal_weight   DECIMAL(5,2)  NOT NULL DEFAULT 10,
            self_weight      DECIMAL(5,2)  NOT NULL DEFAULT 10,

            weighted_score   DECIMAL(5,2)  NOT NULL,
            grade_equivalent VARCHAR(10)   NOT NULL,
            remarks          VARCHAR(50)   NOT NULL,

            coordinator_notes TEXT         NULL,
            is_finalized      TINYINT(1)   NOT NULL DEFAULT 0,
            finalized_at      DATETIME     NULL,

            created_at        DATETIME     NOT NULL DEFAULT NOW(),
            updated_at        DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW(),

            UNIQUE KEY uq_student_batch (student_uuid, batch_uuid),
            FOREIGN KEY (student_uuid)     REFERENCES student_profiles(uuid),
            FOREIGN KEY (application_uuid) REFERENCES ojt_applications(uuid),
            FOREIGN KEY (batch_uuid)       REFERENCES batches(uuid),
            FOREIGN KEY (finalized_by)     REFERENCES coordinator_profiles(uuid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    return (bool) $conn->query($sql);
}






function isReadyForGrading(
    $conn,
    string $studentUuid,
    string $batchUuid
): array {
    $issues = [];

    
    $dtrSummary = getDtrSummary($conn, $studentUuid, $batchUuid);
    if (!$dtrSummary['is_complete']) {
        $issues[] = "Hours not yet complete ({$dtrSummary['percentage']}% of {$dtrSummary['required_hours']} hrs).";
    }

    
    $evalSummary = getEvaluationSummary($conn, $studentUuid, $batchUuid);
    if (!$evalSummary['has_midterm']) $issues[] = 'Midterm evaluation not yet submitted.';
    if (!$evalSummary['has_final'])   $issues[] = 'Final evaluation not yet submitted.';
    if (!$evalSummary['has_self'])    $issues[] = 'Self-evaluation not yet submitted.';

    
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






function computeGradeComponents(
    $conn,
    string $studentUuid,
    string $batchUuid,
    array  $weights = []
): array {
    
    $weights = array_merge(DEFAULT_WEIGHTS, array_filter($weights));

    
    $weightSum = array_sum($weights);
    if (abs($weightSum - 100) > 0.01) {
        return [
            'success' => false,
            'error'   => "Weights must sum to 100. Current sum: {$weightSum}",
        ];
    }

    
    $dtrSummary  = getDtrSummary($conn, $studentUuid, $batchUuid);
    $hoursScore  = min(100, $dtrSummary['percentage']);

    
    $evalSummary  = getEvaluationSummary($conn, $studentUuid, $batchUuid);
    $midtermScore = $evalSummary['midterm_pct'] ?? 0;
    $finalScore   = $evalSummary['final_pct']   ?? 0;
    $selfScore    = $evalSummary['self_pct']     ?? 0;

    
    $journalScore = computeJournalScore($conn, $studentUuid, $batchUuid);

    
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
        
        'hours_score'      => round($hoursScore,   2),
        'midterm_score'    => round($midtermScore,  2),
        'final_score'      => round($finalScore,    2),
        'journal_score'    => round($journalScore,  2),
        'self_score'       => round($selfScore,     2),
        
        'weights'          => $weights,
        
        'weighted_score'   => $weightedScore,
        'grade_equivalent' => $gradeEquivalent,
        'remarks'          => $remarks,
        
        'dtr_summary'      => $dtrSummary,
        'eval_summary'     => $evalSummary,
    ];
}






function computeJournalScore(
    $conn,
    string $studentUuid,
    string $batchUuid
): float {
    
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
    $weeksExpected = max(1, ceil($daysNeeded / 5)); 

    
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






function saveGrade(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $coordinatorUuid,
    array  $weights = [],
    string $coordinatorNotes = ''
): array {
    
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

    $readiness = isReadyForGrading($conn, $studentUuid, $batchUuid);
    if (!$readiness['ready']) {
        return [
            'success' => false,
            'error'   => 'Student is not ready for grading yet.',
            'readiness' => $readiness,
        ];
    }

    
    $computed = computeGradeComponents($conn, $studentUuid, $batchUuid, $weights);

    if (!$computed['success']) {
        return $computed;
    }

    
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
        'ddddddddddd' .
        'sss' .
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






function finalizeGrade(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $coordinatorUuid,
    array  $weights = [],
    string $coordinatorNotes = ''
): array {
    
    $saveResult = saveGrade(
        $conn, $studentUuid, $batchUuid,
        $coordinatorUuid, $weights, $coordinatorNotes
    );

    if (!$saveResult['success']) {
        return $saveResult;
    }

    $readiness = isReadyForGrading($conn, $studentUuid, $batchUuid);
    if (!$readiness['ready']) {
        return [
            'success' => false,
            'error'   => 'Student is not ready for grading yet.',
            'readiness' => $readiness,
        ];
    }

    
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

    
    $userStmt = $conn->prepare("SELECT user_uuid FROM coordinator_profiles WHERE uuid = ? LIMIT 1");
    $userStmt->bind_param('s', $coordinatorUuid);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    $userUuid = $userRow['user_uuid'] ?? null;

    logActivity(
        conn: $conn,
        eventType: 'grade_finalized',
        description: "OJT grade finalized: {$saveResult['computed']['grade_equivalent']} ({$saveResult['computed']['weighted_score']}%)",
        module: 'grades',
        actorUuid: $userUuid,
        targetUuid: $studentUuid
    );

    return [
        'success'  => true,
        'computed' => $saveResult['computed'],
    ];
}





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

    
    if ($studentView && (int) $row['is_finalized'] === 0) {
        return null;
    }

    return formatGrade($row);
}





function getAllGrades(
    $conn,
    string $batchUuid,
    string $coordinatorUuid = null
): array {
    $safeBatch = $conn->real_escape_string($batchUuid);
    $conditions = ["g.batch_uuid = '{$safeBatch}'", "g.is_finalized = 1"];

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
        ORDER BY sp.last_name ASC, sp.first_name ASC
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
          COALESCE(dtr.approved_hours, 0) AS approved_hours,
          COALESCE(dtr.approved_count, 0)  AS approved_count,
          COALESCE(evalx.has_midterm, 0)   AS has_midterm,
          COALESCE(evalx.has_final, 0)     AS has_final,
          COALESCE(evalx.has_self, 0)      AS has_self,
          COALESCE(jrn.approved_journals, 0) AS approved_journals,
          g.uuid            AS grade_uuid,
          g.weighted_score,
          g.grade_equivalent,
          g.remarks,
          g.is_finalized

        FROM student_profiles sp
        LEFT JOIN programs p ON sp.program_uuid = p.uuid
        LEFT JOIN (
            SELECT student_uuid, batch_uuid,
                   SUM(hours_rendered) AS approved_hours,
                   COUNT(*) AS approved_count
            FROM dtr_entries
            WHERE batch_uuid = '{$safeBatch}'
              AND status = 'approved'
            GROUP BY student_uuid, batch_uuid
        ) dtr ON dtr.student_uuid = sp.uuid AND dtr.batch_uuid = '{$safeBatch}'
        LEFT JOIN (
            SELECT student_uuid, batch_uuid,
                   MAX(CASE WHEN eval_type = 'midterm' AND submitted_by_role = 'supervisor' THEN 1 ELSE 0 END) AS has_midterm,
                   MAX(CASE WHEN eval_type = 'final'   AND submitted_by_role = 'supervisor' THEN 1 ELSE 0 END) AS has_final,
                   MAX(CASE WHEN eval_type = 'self'    AND submitted_by_role = 'student'   THEN 1 ELSE 0 END) AS has_self
            FROM evaluations
            WHERE batch_uuid = '{$safeBatch}'
            GROUP BY student_uuid, batch_uuid
        ) evalx ON evalx.student_uuid = sp.uuid AND evalx.batch_uuid = '{$safeBatch}'
        LEFT JOIN (
            SELECT student_uuid, batch_uuid,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_journals
            FROM weekly_journals
            WHERE batch_uuid = '{$safeBatch}'
            GROUP BY student_uuid, batch_uuid
        ) jrn ON jrn.student_uuid = sp.uuid AND jrn.batch_uuid = '{$safeBatch}'
        LEFT JOIN ojt_grades g
          ON g.student_uuid = sp.uuid AND g.batch_uuid = '{$safeBatch}'
        WHERE sp.batch_uuid = '{$safeBatch}'
          {$coordFilter}
        ORDER BY sp.last_name ASC, sp.first_name ASC
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





function gradeRemarks(string $grade): string
{
    return match(true) {
        in_array($grade, ['1.00','1.25','1.50','1.75','2.00','2.25','2.50','2.75','3.00']) => 'Passed',
        $grade === '5.00' => 'Failed',
        default           => 'Incomplete',
    };
}





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

        
        'hours_score'       => (float) $row['hours_score'],
        'midterm_score'     => (float) $row['midterm_score'],
        'final_score'       => (float) $row['final_score'],
        'journal_score'     => (float) $row['journal_score'],
        'self_score'        => (float) $row['self_score'],

        
        'hours_weight'      => (float) $row['hours_weight'],
        'midterm_weight'    => (float) $row['midterm_weight'],
        'final_weight'      => (float) $row['final_weight'],
        'journal_weight'    => (float) $row['journal_weight'],
        'self_weight'       => (float) $row['self_weight'],

        
        'hours_contribution'   => round($row['hours_score']   * $row['hours_weight']   / 100, 2),
        'midterm_contribution' => round($row['midterm_score'] * $row['midterm_weight'] / 100, 2),
        'final_contribution'   => round($row['final_score']   * $row['final_weight']   / 100, 2),
        'journal_contribution' => round($row['journal_score'] * $row['journal_weight'] / 100, 2),
        'self_contribution'    => round($row['self_score']    * $row['self_weight']    / 100, 2),

        'created_at'        => date('M j, Y', strtotime($row['created_at'])),
    ];
}