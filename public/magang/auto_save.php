<?php
/**
 * Auto Save Activity - untuk menyimpan draft aktivitas
 */

session_start();
include_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }
    
    $userId = intval($_SESSION['user_id']);
    $today = date('Y-m-d');
    $activity = isset($_POST['activity']) ? trim($_POST['activity']) : '';
    $kendala = isset($_POST['kendala']) ? trim($_POST['kendala']) : '';
    
    // Check if record exists
    $checkQuery = "SELECT id FROM absen WHERE user_id = ? AND tanggal = ? AND jam_masuk IS NOT NULL";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("is", $userId, $today);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $updateQuery = "UPDATE absen SET aktivitas_keluar = ?, kendala_keluar = ?, updated_at = NOW() WHERE user_id = ? AND tanggal = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssis", $activity, $kendala, $userId, $today);
        
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Auto-saved successfully']);
        } else {
            throw new Exception('Failed to save');
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No check-in record found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
