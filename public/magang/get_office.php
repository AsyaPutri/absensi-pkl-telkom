<?php
// get_office.php - Production version yang connect langsung ke database
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// ===== DATABASE CONFIGURATION =====
// SESUAIKAN DENGAN SETTING DATABASE ANDA
$db_host = 'localhost';                  
$db_name = 'absensi';          
$db_user = 'root';                        
$db_pass = '';                            

// ===== FUNGSI UTAMA =====
try {
    // Connect ke database dengan error handling
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Query untuk ambil data kantor
    $query = "SELECT 
                office_name, 
                latitude, 
                longitude, 
                radius, 
                address,
                updated_at
              FROM office_config 
              ORDER BY id ASC 
              LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $officeData = $stmt->fetch();
    
    if ($officeData) {
        // Data ditemukan di database - return data asli
        $response = [
            'success' => true,
            'data' => [
                'office_name' => trim($officeData['office_name']),
                'latitude' => (float) $officeData['latitude'],
                'longitude' => (float) $officeData['longitude'], 
                'radius' => (int) $officeData['radius'],
                'address' => trim($officeData['address'] ?? ''),
                'last_updated' => $officeData['updated_at'] ?? null
            ],
            'source' => 'database'
        ];
        
        // Log untuk debugging (hapus di production)
        error_log("Office data loaded from DB: " . $officeData['office_name']);
        
    } else {
        // Tidak ada data di database - return error
        $response = [
            'success' => false,
            'message' => 'Data kantor tidak ditemukan di database',
            'error_code' => 'NO_DATA_FOUND'
        ];
        
        error_log("ERROR: No office data found in database");
    }
    
} catch (PDOException $e) {
    // Database connection error
    $response = [
        'success' => false,
        'message' => 'Gagal terhubung ke database: ' . $e->getMessage(),
        'error_code' => 'DB_CONNECTION_ERROR'
    ];
    
    error_log("Database error in get_office.php: " . $e->getMessage());
    
} catch (Exception $e) {
    // General error
    $response = [
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
        'error_code' => 'SYSTEM_ERROR'
    ];
    
    error_log("General error in get_office.php: " . $e->getMessage());
}

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT);

// ===== FUNGSI DEBUGGING (HAPUS DI PRODUCTION) =====
// Uncomment baris dibawah untuk debugging
// error_log("get_office.php called at " . date('Y-m-d H:i:s'));
?>