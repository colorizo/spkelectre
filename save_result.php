<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Debug: Log raw input
    error_log('Raw input: ' . file_get_contents('php://input'));

    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log decoded data
    error_log('Decoded data: ' . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }

    $pdo->beginTransaction();

    // Simpan data perhitungan
    $stmt = $pdo->prepare("INSERT INTO perhitungan (user_id, lokasi, keterangan) VALUES (?, ?, ?)");
    $stmt->execute([1, $data['lokasi'], 'Perhitungan ELECTRE']); // 1 adalah default user_id
    $perhitungan_id = $pdo->lastInsertId();

    // Debug: Log perhitungan_id
    error_log('Perhitungan ID: ' . $perhitungan_id);

    // Simpan matriks keputusan
    $stmt = $pdo->prepare("INSERT INTO matriks_keputusan (perhitungan_id, alternatif, c1, c2, c3, c4, c5) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($data['matriks'] as $i => $alternatif) {
        $stmt->execute([
            $perhitungan_id,
            'A'.($i+1),
            $alternatif[0],
            $alternatif[1],
            $alternatif[2],
            $alternatif[3],
            $alternatif[4]
        ]);
    }

    // Simpan bobot kriteria
    $stmt = $pdo->prepare("INSERT INTO bobot_kriteria (perhitungan_id, w1, w2, w3, w4, w5) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $perhitungan_id,
        $data['bobot'][0],
        $data['bobot'][1],
        $data['bobot'][2],
        $data['bobot'][3],
        $data['bobot'][4]
    ]);

    // Simpan hasil perhitungan
    $stmt = $pdo->prepare("INSERT INTO hasil_perhitungan (perhitungan_id, alternatif, nilai_akhir, peringkat) VALUES (?, ?, ?, ?)");
    foreach ($data['peringkat'] as $rank => $hasil) {
        $stmt->execute([
            $perhitungan_id,
            'A'.($hasil['alternatif']+1),
            $hasil['nilai'],
            $rank+1
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Data berhasil disimpan',
        'id' => $perhitungan_id
    ]);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Error in save_result.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 