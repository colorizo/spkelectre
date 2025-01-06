<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID tidak ditemukan');
    }

    $id = $_GET['id'];
    
    // Ambil data perhitungan
    $query = "
        SELECT 
            p.*,
            GROUP_CONCAT(DISTINCT CONCAT(mk.alternatif, ':', mk.c1, ',', mk.c2, ',', mk.c3, ',', mk.c4, ',', mk.c5) SEPARATOR '|') as matriks_data,
            GROUP_CONCAT(DISTINCT CONCAT(hp.alternatif, ':', hp.nilai_akhir, ':', hp.peringkat) SEPARATOR '|') as hasil_data,
            bk.w1, bk.w2, bk.w3, bk.w4, bk.w5
        FROM perhitungan p
        LEFT JOIN matriks_keputusan mk ON p.id = mk.perhitungan_id
        LEFT JOIN hasil_perhitungan hp ON p.id = hp.perhitungan_id
        LEFT JOIN bobot_kriteria bk ON p.id = bk.perhitungan_id
        WHERE p.id = ?
        GROUP BY p.id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    
    if (!$data) {
        throw new Exception('Data tidak ditemukan');
    }
    
    // Format data untuk PDF
    $matriks = [];
    foreach (explode('|', $data['matriks_data']) as $row) {
        list($alt, $values) = explode(':', $row);
        $matriks[] = explode(',', $values);
    }
    
    $hasil = [];
    foreach (explode('|', $data['hasil_data']) as $row) {
        list($alt, $nilai, $peringkat) = explode(':', $row);
        $hasil[] = [
            'alternatif' => substr($alt, 1) - 1,
            'nilai' => floatval($nilai),
            'peringkat' => intval($peringkat)
        ];
    }
    
    $bobot = [$data['w1'], $data['w2'], $data['w3'], $data['w4'], $data['w5']];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'tanggal' => $data['tanggal'],
            'lokasi' => $data['lokasi'],
            'matriks' => $matriks,
            'bobot' => $bobot,
            'hasil' => $hasil
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 