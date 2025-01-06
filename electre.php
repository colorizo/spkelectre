<?php
require_once 'config.php';

// Cek jika ada request untuk menghapus data
if(isset($_POST['delete']) && isset($_POST['id'])) {
    try {
        $pdo->beginTransaction();
        
        // Hapus data dari tabel-tabel terkait
        $stmt = $pdo->prepare("DELETE FROM hasil_perhitungan WHERE perhitungan_id = ?");
        $stmt->execute([$_POST['id']]);
        
        $stmt = $pdo->prepare("DELETE FROM matriks_keputusan WHERE perhitungan_id = ?");
        $stmt->execute([$_POST['id']]);
        
        $stmt = $pdo->prepare("DELETE FROM bobot_kriteria WHERE perhitungan_id = ?");
        $stmt->execute([$_POST['id']]);
        
        $stmt = $pdo->prepare("DELETE FROM perhitungan WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        
        $pdo->commit();
        $deleteMessage = "Data berhasil dihapus";
    } catch(Exception $e) {
        $pdo->rollBack();
        $deleteError = "Gagal menghapus data: " . $e->getMessage();
    }
}

// Ambil data perhitungan
$query = "
    SELECT 
        p.id,
        p.tanggal,
        p.lokasi,
        p.keterangan,
        GROUP_CONCAT(DISTINCT CONCAT(mk.alternatif, ': (', mk.c1, ',', mk.c2, ',', mk.c3, ',', mk.c4, ',', mk.c5, ')') SEPARATOR '<br>') as matriks,
        GROUP_CONCAT(DISTINCT CONCAT(hp.alternatif, ' (Nilai: ', ROUND(hp.nilai_akhir, 2), ', Peringkat: ', hp.peringkat, ')') SEPARATOR '<br>') as hasil,
        CONCAT(bk.w1, ', ', bk.w2, ', ', bk.w3, ', ', bk.w4, ', ', bk.w5) as bobot
    FROM perhitungan p
    LEFT JOIN matriks_keputusan mk ON p.id = mk.perhitungan_id
    LEFT JOIN hasil_perhitungan hp ON p.id = hp.perhitungan_id
    LEFT JOIN bobot_kriteria bk ON p.id = bk.perhitungan_id
    GROUP BY p.id
    ORDER BY p.tanggal DESC
";

$hasil = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Perhitungan ELECTRE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c5282;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-primary {
            background-color: #4CAF50;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .action-buttons button {
            white-space: nowrap;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background-color: #138496;
        }
        .sidebar {
            position: fixed;
            left: -250px;
            top: 0;
            width: 250px;
            height: 100%;
            background: #2c5282;
            transition: 0.3s;
            padding-top: 60px;
            z-index: 997;
        }
        .sidebar.active {
            left: 0;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li a {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
            font-size: 16px;
        }
        .sidebar ul li a:hover {
            background: #1a365d;
        }
        .sidebar ul li a.active {
            background: #4CAF50;
        }
        .toggle-btn {
            position: fixed;
            left: 20px;
            top: 20px;
            background: #4CAF50;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 999;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <button class="toggle-btn" onclick="toggleSidebar()">☰ Menu</button>
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="index.html">Beranda</a></li>
            <li><a href="kriteria.html">Kriteria</a></li>
            <li><a href="bobot.html">Bobot</a></li>
            <li><a href="electre.html">Analisis ELECTRE</a></li>
            <li><a href="electre.php" class="active">Riwayat Perhitungan</a></li>
        </ul>
    </div>

    <div class="container">
        <div class="header">
            <h1>Riwayat Perhitungan ELECTRE</h1>
            <p>Daftar seluruh perhitungan yang telah dilakukan</p>
        </div>

        <?php if(isset($deleteMessage)): ?>
            <div class="alert alert-success"><?php echo $deleteMessage; ?></div>
        <?php endif; ?>

        <?php if(isset($deleteError)): ?>
            <div class="alert alert-danger"><?php echo $deleteError; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Lokasi</th>
                    <th>Matriks Keputusan</th>
                    <th>Bobot Kriteria</th>
                    <th>Hasil</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($hasil as $index => $row): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                    <td><?php echo htmlspecialchars($row['lokasi']); ?></td>
                    <td><?php echo $row['matriks']; ?></td>
                    <td><?php echo $row['bobot']; ?></td>
                    <td><?php echo $row['hasil']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button onclick="generatePDF(<?php echo $row['id']; ?>)" class="btn btn-info" style="margin-right: 5px;">
                                <i class="fas fa-print"></i> Print PDF
                            </button>
                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.querySelector('.container').classList.toggle('active');
        }

        // Tandai menu aktif
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const menuLinks = document.querySelectorAll('.sidebar ul li a');
            
            menuLinks.forEach(link => {
                if(link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });

        async function generatePDF(id) {
            try {
                // Tampilkan loading
                showLoadingMessage('Memuat data...');

                // Ambil data dari server
                const response = await fetch(`get_data_pdf.php?id=${id}`);
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message);
                }

                // Buat template PDF
                const pdfContent = `
                    <div style="padding: 20px; font-family: Arial, sans-serif;">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h2 style="color: #2c5282; margin-bottom: 10px;">Laporan Hasil Analisis ELECTRE</h2>
                            <h3 style="color: #4a5568; font-weight: normal;">Sistem Pendukung Keputusan Pemilihan Energi Terbarukan</h3>
                            <div style="margin-top: 20px; font-size: 16px;">
                                <p><strong>Lokasi:</strong> ${result.data.lokasi}</p>
                                <p><strong>Tanggal:</strong> ${new Date(result.data.tanggal).toLocaleDateString('id-ID')}</p>
                            </div>
                        </div>

                        <div style="margin-bottom: 30px;">
                            <h3 style="color: #2c5282; border-bottom: 2px solid #4CAF50; padding-bottom: 10px;">
                                Informasi Kriteria dan Bobot
                            </h3>
                            
                            <!-- Tabel Keterangan Kriteria -->
                            <h4 style="color: #4a5568; margin-top: 20px;">Keterangan Kriteria:</h4>
                            <table border="1" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                <tr style="background-color: #4CAF50; color: white;">
                                    <th>Kode</th>
                                    <th>Kriteria</th>
                                    <th>Keterangan</th>
                                    <th>Bobot</th>
                                </tr>
                                <tr>
                                    <td>C1</td>
                                    <td>Biaya Investasi</td>
                                    <td>Biaya yang dibutuhkan untuk implementasi (dalam jutaan rupiah)</td>
                                    <td>${result.data.bobot[0]}</td>
                                </tr>
                                <tr style="background-color: #f8f9fa;">
                                    <td>C2</td>
                                    <td>Efisiensi Energi</td>
                                    <td>Tingkat efisiensi konversi energi (%)</td>
                                    <td>${result.data.bobot[1]}</td>
                                </tr>
                                <tr>
                                    <td>C3</td>
                                    <td>Ketersediaan Sumber Daya</td>
                                    <td>Tingkat ketersediaan sumber energi di lokasi (1-5)</td>
                                    <td>${result.data.bobot[2]}</td>
                                </tr>
                                <tr style="background-color: #f8f9fa;">
                                    <td>C4</td>
                                    <td>Kemudahan Pemeliharaan</td>
                                    <td>Tingkat kemudahan dalam pemeliharaan dan perawatan (1-5)</td>
                                    <td>${result.data.bobot[3]}</td>
                                </tr>
                                <tr>
                                    <td>C5</td>
                                    <td>Dampak Lingkungan</td>
                                    <td>Tingkat ramah lingkungan (1-5)</td>
                                    <td>${result.data.bobot[4]}</td>
                                </tr>
                            </table>

                            <!-- Keterangan Skala Penilaian -->
                            <h4 style="color: #4a5568;">Skala Penilaian:</h4>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <p><strong>Biaya Investasi (C1):</strong></p>
                                <ul style="margin-bottom: 10px;">
                                    <li>1 = Sangat Tinggi (> 500 juta)</li>
                                    <li>2 = Tinggi (301-500 juta)</li>
                                    <li>3 = Sedang (201-300 juta)</li>
                                    <li>4 = Rendah (101-200 juta)</li>
                                    <li>5 = Sangat Rendah (≤ 100 juta)</li>
                                </ul>
                                <p><strong>Efisiensi Energi (C2):</strong></p>
                                <ul style="margin-bottom: 10px;">
                                    <li>1 = Sangat Rendah (≤ 20%)</li>
                                    <li>2 = Rendah (21-40%)</li>
                                    <li>3 = Sedang (41-60%)</li>
                                    <li>4 = Tinggi (61-80%)</li>
                                    <li>5 = Sangat Tinggi (> 80%)</li>
                                </ul>
                            </div>
                        </div>

                        <div style="margin-bottom: 30px;">
                            <h3 style="color: #2c5282; border-bottom: 2px solid #4CAF50; padding-bottom: 10px;">
                                Lanjutan Skala Penilaian
                            </h3>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <p><strong>Kriteria C3-C5:</strong></p>
                                <ul style="margin-bottom: 20px;">
                                    <p><strong>C3 - Ketersediaan Sumber Daya:</strong></p>
                                    <li>1 = Sangat Rendah</li>
                                    <li>2 = Rendah</li>
                                    <li>3 = Sedang</li>
                                    <li>4 = Tinggi</li>
                                    <li>5 = Sangat Tinggi</li>
                                </ul>
                                <ul style="margin-bottom: 20px;">
                                    <p><strong>C4 - Kemudahan Pemeliharaan:</strong></p>
                                    <li>1 = Sangat Sulit</li>
                                    <li>2 = Sulit</li>
                                    <li>3 = Cukup Mudah</li>
                                    <li>4 = Mudah</li>
                                    <li>5 = Sangat Mudah</li>
                                </ul>
                                <ul style="margin-bottom: 20px;">
                                    <p><strong>C5 - Dampak Lingkungan:</strong></p>
                                    <li>1 = Sangat Buruk</li>
                                    <li>2 = Buruk</li>
                                    <li>3 = Cukup Baik</li>
                                    <li>4 = Baik</li>
                                    <li>5 = Sangat Baik</li>
                                </ul>
                                <div style="margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 8px;">
                                    <p><strong>Catatan:</strong></p>
                                    <ul>
                                        <li>Ketersediaan Sumber Daya: Menunjukkan tingkat ketersediaan sumber energi di lokasi</li>
                                        <li>Kemudahan Pemeliharaan: Menunjukkan tingkat kesulitan dalam perawatan dan pemeliharaan</li>
                                        <li>Dampak Lingkungan: Menunjukkan seberapa ramah lingkungan alternatif tersebut</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Halaman 3 -->
                        <div style="page-break-before: always;">
                        <div style="margin-bottom: 30px;">
                            <h3 style="color: #2c5282; border-bottom: 2px solid #4CAF50; padding-bottom: 10px;">
                                Data Input Analisis
                            </h3>
                            <table border="1" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                                <tr style="background-color: #4CAF50; color: white;">
                                    <th>Alternatif</th>
                                    <th>Biaya Investasi</th>
                                    <th>Efisiensi Energi</th>
                                    <th>Ketersediaan</th>
                                    <th>Pemeliharaan</th>
                                    <th>Dampak Lingkungan</th>
                                </tr>
                                ${result.data.matriks.map((row, i) => `
                                    <tr style="background-color: ${i % 2 === 0 ? '#f8f9fa' : 'white'}">
                                        <td>${getAlternatifLabel(i)}</td>
                                        ${row.map(val => `<td>${val}</td>`).join('')}
                                    </tr>
                                `).join('')}
                            </table>
                        </div>

                        <div style="margin-bottom: 30px;">
                            <h3 style="color: #2c5282; border-bottom: 2px solid #4CAF50; padding-bottom: 10px;">
                                Hasil Analisis
                            </h3>
                            <table border="1" style="width: 100%; border-collapse: collapse;">
                                <tr style="background-color: #4CAF50; color: white;">
                                    <th>Peringkat</th>
                                    <th>Alternatif</th>
                                    <th>Nilai</th>
                                    <th>Status</th>
                                </tr>
                                ${result.data.hasil.sort((a, b) => a.peringkat - b.peringkat).map((h, i) => `
                                    <tr style="background-color: ${i === 0 ? '#d4edda' : i % 2 === 0 ? '#f8f9fa' : 'white'}">
                                        <td>${h.peringkat}</td>
                                        <td>${getAlternatifLabel(h.alternatif)}</td>
                                        <td>${Math.round(h.nilai)}</td>
                                        <td>${i === 0 ? 'Rekomendasi Terbaik' : 'Alternatif'}</td>
                                    </tr>
                                `).join('')}
                            </table>

                            <div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">
                                <h4 style="color: #2c5282;">Rekomendasi:</h4>
                                <p>Berdasarkan hasil analisis menggunakan metode ELECTRE, untuk lokasi <strong>${result.data.lokasi}</strong>
                                direkomendasikan untuk menggunakan <strong>${getAlternatifLabel(result.data.hasil.find(h => h.peringkat === 1).alternatif)}</strong>
                                sebagai sumber energi terbarukan.</p>
                            </div>
                        </div>

                        <div style="margin-top: 50px; text-align: center; font-size: 12px; color: #666;">
                            <p>Dokumen ini dihasilkan secara otomatis oleh Sistem Pendukung Keputusan ELECTRE</p>
                            <p>© 2024 Kelompok 5</p>
                        </div>
                        </div>
                    </div>
                `;

                // Buat elemen temporary untuk PDF
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = pdfContent;
                document.body.appendChild(tempDiv);

                // Generate PDF
                const opt = {
                    margin: 10,
                    filename: `hasil_analisis_electre_${result.data.lokasi}.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                await html2pdf().set(opt).from(tempDiv).save();
                document.body.removeChild(tempDiv);
                hideLoadingMessage();

            } catch (error) {
                console.error('Error:', error);
                hideLoadingMessage();
                alert('Gagal membuat PDF: ' + error.message);
            }
        }

        function getAlternatifLabel(index) {
            const labels = ['Panel Surya', 'Biogas', 'Mikrohidro'];
            return labels[index];
        }

        function showLoadingMessage(message) {
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'loadingMessage';
            loadingDiv.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 20px;
                border-radius: 8px;
                z-index: 9999;
            `;
            loadingDiv.innerHTML = message;
            document.body.appendChild(loadingDiv);
        }

        function hideLoadingMessage() {
            const loadingDiv = document.getElementById('loadingMessage');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }
    </script>
</body>
</html> 