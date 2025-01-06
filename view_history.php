<?php
require_once 'config.php';

$stmt = $pdo->query("
    SELECT 
        p.id,
        p.tanggal,
        h.alternatif,
        h.nilai_akhir,
        h.peringkat
    FROM perhitungan p
    JOIN hasil_perhitungan h ON p.id = h.perhitungan_id
    ORDER BY p.tanggal DESC, h.peringkat ASC
");

$hasil = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Perhitungan ELECTRE</title>
    <style>
        /* Gunakan style yang sama dengan electre.html */
    </style>
</head>
<body>
    <div class="container">
        <h1>Riwayat Perhitungan ELECTRE</h1>
        <table>
            <tr>
                <th>Tanggal</th>
                <th>ID Perhitungan</th>
                <th>Alternatif</th>
                <th>Nilai Akhir</th>
                <th>Peringkat</th>
            </tr>
            <?php foreach ($hasil as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['alternatif']) ?></td>
                <td><?= htmlspecialchars($row['nilai_akhir']) ?></td>
                <td><?= htmlspecialchars($row['peringkat']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html> 