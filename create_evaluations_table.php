<?php
require_once __DIR__ . '/config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS evaluations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    uuid             CHAR(36)     NOT NULL UNIQUE,
    student_uuid     CHAR(36)     NOT NULL,
    application_uuid CHAR(36)     NOT NULL,
    batch_uuid       CHAR(36)     NOT NULL,
    submitted_by     CHAR(36)     NOT NULL,
    submitted_by_role ENUM('supervisor','student') NOT NULL,
    eval_type        ENUM('midterm','final','self') NOT NULL,
    technical_skills   TINYINT  NULL,
    work_attitude      TINYINT  NULL,
    communication      TINYINT  NULL,
    teamwork           TINYINT  NULL,
    problem_solving    TINYINT  NULL,
    overall_experience TINYINT  NULL,
    would_recommend    TINYINT(1) NULL,
    total_score      DECIMAL(4,2) NULL,
    comments         TEXT         NULL,
    submitted_at     DATETIME     NOT NULL DEFAULT NOW(),
    updated_at       DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_student_eval_type (student_uuid, batch_uuid, eval_type),
    FOREIGN KEY (student_uuid)     REFERENCES student_profiles(uuid) ON DELETE CASCADE,
    FOREIGN KEY (application_uuid) REFERENCES ojt_applications(uuid) ON DELETE CASCADE,
    FOREIGN KEY (batch_uuid)       REFERENCES batches(uuid) ON DELETE CASCADE
);";

if ($conn->query($sql) === TRUE) {
    echo "Table evaluations created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
