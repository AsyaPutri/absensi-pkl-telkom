<?php
// get_office.php - Sesuai dengan database absensi tabel office_config
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');

// ===== DATABASE CONFIGURATION =====
$db_host = 'localhost';                  
$db_name = 'absensi';          
$db_user = 'root';                        
$db_pass = '';                            

try {
    // Connect ke database
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Query sesuai struktur tabel office_config
    $query = "SELECT 
                id,
                office_name, 
                latitude, 
                longitude, 
                radius, 
                address,
                created_at,
                updated_at
              FROM office_config 
              ORDER BY id ASC 
              LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $officeData = $stmt->fetch();
    
    if ($officeData) {
        // Format data sesuai yang dibutuhkan maps.js
        $response = [
            'success' => true,
            'office' => [
                'id' => (int) $officeData['id'],
                'name' => trim($officeData['office_name']),
                'latitude' => (float) $officeData['latitude'],
                'longitude' => (float) $officeData['longitude'], 
                'radius' => (int) $officeData['radius'],
                'address' => trim($officeData['address'] ?? ''),
                'is_active' => true,
                'created_at' => $officeData['created_at'],
                'updated_at' => $officeData['updated_at']
            ],
            'message' => 'Office data loaded successfully from database',
            'source' => 'database',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log untuk debugging
        error_log("✅ Office data loaded: " . $officeData['office_name']);
        
    } else {
        // Tidak ada data di database
        throw new Exception('No office data found in office_config table');
    }
    
} catch (PDOException $e) {
    // Database connection error - fallback ke data default
    error_log("❌ Database error: " . $e->getMessage());
    
    $response = [
        'success' => true, // tetap success agar maps.js tidak error
        'office' => [
            'id' => 1,
            'name' => 'Telkom Witel Bekasi Karawang',
            'latitude' => -6.237846687485902,
            'longitude' => 106.99415622140583,
            'radius' => 100,
            'address' => 'Jl. Rw. Tembaga IV No.4, RT.006/RW.005, Marga Jaya',
            'is_active' => true,
            'created_at' => '2025-09-04 09:59:38',
            'updated_at' => '2025-09-08 15:39:10'
        ],
        'message' => 'Using fallback data (database connection failed)',
        'source' => 'fallback',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    // General error - fallback ke data default
    error_log("❌ General error: " . $e->getMessage());
    
    $response = [
        'success' => true, // tetap success agar maps.js tidak error
        'office' => [
            'id' => 1,
            'name' => 'Telkom Witel Bekasi Karawang',
            'latitude' => -6.237846687485902,
            'longitude' => 106.99415622140583,
            'radius' => 100,
            'address' => 'Jl. Rw. Tembaga IV No.4, RT.006/RW.005, Marga Jaya',
            'is_active' => true,
            'created_at' => '2025-09-04 09:59:38',
            'updated_at' => '2025-09-08 15:39:10'
        ],
        'message' => 'Using fallback data (system error)',
        'source' => 'fallback',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Cleanup
$pdo = null;
?>
