<?php
/**
 * Training Management AJAX Handler
 * Handles all AJAX requests for training management operations
 */

require_once 'config/config.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pdo = getDBConnection();

try {
    switch ($action) {
        case 'create_program':
            createTrainingProgram($pdo);
            break;
            
        case 'update_program':
            updateTrainingProgram($pdo);
            break;
            
        case 'delete_program':
            deleteTrainingProgram($pdo);
            break;
            
        case 'get_program':
            getTrainingProgram($pdo);
            break;
            
        case 'enroll_participant':
            enrollParticipant($pdo);
            break;
            
        case 'update_participant':
            updateParticipant($pdo);
            break;
            
        case 'remove_participant':
            removeParticipant($pdo);
            break;
            
        case 'create_schedule':
            createTrainingSchedule($pdo);
            break;
            
        case 'update_schedule':
            updateTrainingSchedule($pdo);
            break;
            
        case 'delete_schedule':
            deleteTrainingSchedule($pdo);
            break;
            
        case 'get_participants':
            getProgramParticipants($pdo);
            break;
            
        case 'update_completion':
            updateCompletion($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function createTrainingProgram($pdo) {
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $status = $_POST['status'] ?? 'Upcoming';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $instructor = $_POST['instructor'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO training_programs (title, category, duration, status, start_date, end_date, instructor, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $title,
        $category,
        $duration,
        $status,
        $start_date ?: null,
        $end_date ?: null,
        $instructor,
        $description
    ]);
    
    $programId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Training program created successfully',
        'program_id' => $programId
    ]);
}

function updateTrainingProgram($pdo) {
    $id = $_POST['id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $status = $_POST['status'] ?? 'Upcoming';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $instructor = $_POST['instructor'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($id) || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'ID and title are required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE training_programs 
        SET title = ?, category = ?, duration = ?, status = ?, start_date = ?, end_date = ?, instructor = ?, description = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $title,
        $category,
        $duration,
        $status,
        $start_date ?: null,
        $end_date ?: null,
        $instructor,
        $description,
        $id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Training program updated successfully'
    ]);
}

function deleteTrainingProgram($pdo) {
    $id = $_POST['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM training_programs WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Training program deleted successfully'
    ]);
}

function getTrainingProgram($pdo) {
    $id = $_GET['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM training_programs WHERE id = ?");
    $stmt->execute([$id]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($program) {
        echo json_encode([
            'success' => true,
            'program' => $program
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Training program not found'
        ]);
    }
}

function enrollParticipant($pdo) {
    $program_id = $_POST['program_id'] ?? 0;
    $employee_id = $_POST['employee_id'] ?? 0;
    
    if (empty($program_id) || empty($employee_id)) {
        echo json_encode(['success' => false, 'message' => 'Program ID and Employee ID are required']);
        return;
    }
    
    // Check if already enrolled
    $check = $pdo->prepare("SELECT id FROM training_participants WHERE training_program_id = ? AND employee_id = ?");
    $check->execute([$program_id, $employee_id]);
    
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Employee is already enrolled in this program']);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO training_participants (training_program_id, employee_id, status, completion_percentage)
        VALUES (?, ?, 'Enrolled', 0)
    ");
    
    $stmt->execute([$program_id, $employee_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Participant enrolled successfully'
    ]);
}

function updateParticipant($pdo) {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $completion_percentage = $_POST['completion_percentage'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $completed_at = ($status == 'Completed') ? date('Y-m-d H:i:s') : null;
    
    $stmt = $pdo->prepare("
        UPDATE training_participants 
        SET status = ?, completion_percentage = ?, completed_at = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $status,
        $completion_percentage,
        $completed_at,
        $id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Participant updated successfully'
    ]);
}

function removeParticipant($pdo) {
    $id = $_POST['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM training_participants WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Participant removed successfully'
    ]);
}

function createTrainingSchedule($pdo) {
    $program_id = $_POST['program_id'] ?? 0;
    $session_date = $_POST['session_date'] ?? '';
    $session_time = $_POST['session_time'] ?? '';
    $session_type = $_POST['session_type'] ?? '';
    $location = $_POST['location'] ?? '';
    $instructor = $_POST['instructor'] ?? '';
    
    if (empty($program_id) || empty($session_date) || empty($session_time)) {
        echo json_encode(['success' => false, 'message' => 'Program ID, date, and time are required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO training_schedule (training_program_id, session_date, session_time, session_type, location, instructor)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $program_id,
        $session_date,
        $session_time,
        $session_type,
        $location,
        $instructor
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Training schedule created successfully'
    ]);
}

function updateTrainingSchedule($pdo) {
    $id = $_POST['id'] ?? 0;
    $session_date = $_POST['session_date'] ?? '';
    $session_time = $_POST['session_time'] ?? '';
    $session_type = $_POST['session_type'] ?? '';
    $location = $_POST['location'] ?? '';
    $instructor = $_POST['instructor'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE training_schedule 
        SET session_date = ?, session_time = ?, session_type = ?, location = ?, instructor = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $session_date,
        $session_time,
        $session_type,
        $location,
        $instructor,
        $id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Training schedule updated successfully'
    ]);
}

function deleteTrainingSchedule($pdo) {
    $id = $_POST['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM training_schedule WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Training schedule deleted successfully'
    ]);
}

function getProgramParticipants($pdo) {
    $program_id = $_GET['program_id'] ?? 0;
    
    if (empty($program_id)) {
        echo json_encode(['success' => false, 'message' => 'Program ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT tpar.*, u.full_name, u.employee_id, u.email, u.department
        FROM training_participants tpar
        JOIN users u ON tpar.employee_id = u.id
        WHERE tpar.training_program_id = ?
        ORDER BY u.full_name ASC
    ");
    
    $stmt->execute([$program_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'participants' => $participants
    ]);
}

function updateCompletion($pdo) {
    $id = $_POST['id'] ?? 0;
    $completion_percentage = $_POST['completion_percentage'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $completion_percentage = max(0, min(100, intval($completion_percentage)));
    $status = $completion_percentage == 100 ? 'Completed' : ($completion_percentage > 0 ? 'In Progress' : 'Enrolled');
    $completed_at = $completion_percentage == 100 ? date('Y-m-d H:i:s') : null;
    
    $stmt = $pdo->prepare("
        UPDATE training_participants 
        SET completion_percentage = ?, status = ?, completed_at = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $completion_percentage,
        $status,
        $completed_at,
        $id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Completion updated successfully',
        'status' => $status
    ]);
}

