<?php
require_once 'config.php';
$conn = getConnection();

// Query list provinsi
$queryProvinsi = "SELECT DISTINCT Provinsi FROM produksi_bandeng_all ORDER BY Provinsi";
$resultProvinsi = $conn->query($queryProvinsi);

// Ambil data real jika provinsi dan tahun dipilih
$dataReal = null;
$provinsiTerpilih = isset($_GET['provinsi']) ? $_GET['provinsi'] : '';
$tahunTerpilih = isset($_GET['tahun']) ? $_GET['tahun'] : '';

if (!empty($provinsiTerpilih) && !empty($tahunTerpilih)) {
    if ($provinsiTerpilih == 'SEMUA') {
        $queryReal = "
            SELECT 
                'SEMUA PROVINSI' as Provinsi,
                ? as Tahun,
                SUM(Volume_Ton) as Volume_Ton,
                SUM(Nilai_Juta) as Nilai_Juta,
                ROUND(AVG(Harga_Tertimbang_Kg), 2) as Harga_Tertimbang_Kg
            FROM produksi_bandeng_all
            WHERE Tahun = ?
        ";
        $stmt = $conn->prepare($queryReal);
        $stmt->bind_param("ii", $tahunTerpilih, $tahunTerpilih);
    } else {
        $queryReal = "SELECT * FROM produksi_bandeng_all WHERE Provinsi = ? AND Tahun = ?";
        $stmt = $conn->prepare($queryReal);
        $stmt->bind_param("si", $provinsiTerpilih, $tahunTerpilih);
    }
    
    $stmt->execute();
    $dataReal = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Proses simulasi
$simulationResult = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simulate'])) {
    $provinsi = $_POST['provinsi_sim'];
    $tahun = $_POST['tahun_sim'];
    $volumeBaru = floatval($_POST['volume_baru']);
    $hargaBaru = floatval($_POST['harga_baru']);
    
    if ($provinsi == 'SEMUA') {
        $queryOriginal = "
            SELECT 
                'SEMUA PROVINSI' as Provinsi,
                ? as Tahun,
                SUM(Volume_Ton) as Volume_Ton,
                SUM(Nilai_Juta) as Nilai_Juta,
                ROUND(AVG(Harga_Tertimbang_Kg), 2) as Harga_Tertimbang_Kg
            FROM produksi_bandeng_all
            WHERE Tahun = ?
        ";
        $stmt = $conn->prepare($queryOriginal);
        $stmt->bind_param("ii", $tahun, $tahun);
    } else {
        $queryOriginal = "SELECT * FROM produksi_bandeng_all WHERE Provinsi = ? AND Tahun = ?";
        $stmt = $conn->prepare($queryOriginal);
        $stmt->bind_param("si", $provinsi, $tahun);
    }
    
    $stmt->execute();
    $dataOriginal = $stmt->get_result()->fetch_assoc();
    
    if ($dataOriginal) {
        $nilaiBaru = ($volumeBaru * $hargaBaru * 1000) / 1000000;
        
        $perubahanVolume = $volumeBaru - $dataOriginal['Volume_Ton'];
        $perubahanNilai = $nilaiBaru - $dataOriginal['Nilai_Juta'];
        $perubahanHarga = $hargaBaru - $dataOriginal['Harga_Tertimbang_Kg'];
        
        $persenVolume = ($perubahanVolume / $dataOriginal['Volume_Ton']) * 100;
        $persenNilai = ($perubahanNilai / $dataOriginal['Nilai_Juta']) * 100;
        $persenHarga = ($perubahanHarga / $dataOriginal['Harga_Tertimbang_Kg']) * 100;
        
        $simulationResult = [
            'original' => $dataOriginal,
            'volume_baru' => $volumeBaru,
            'harga_baru' => $hargaBaru,
            'nilai_baru' => $nilaiBaru,
            'perubahan_volume' => $perubahanVolume,
            'perubahan_nilai' => $perubahanNilai,
            'perubahan_harga' => $perubahanHarga,
            'persen_volume' => $persenVolume,
            'persen_nilai' => $persenNilai,
            'persen_harga' => $persenHarga,
            'is_nasional' => ($provinsi == 'SEMUA')
        ];
        
        // Set variabel untuk tetap menampilkan data real
        $dataReal = $dataOriginal;
        $provinsiTerpilih = $provinsi;
        $tahunTerpilih = $tahun;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis What-If</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }
        
        .sidebar-header {
            padding: 30px 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h1 {
            font-size: 1.4em;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 0.85em;
            opacity: 0.8;
        }
        
        .nav-menu {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .nav-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #3498db;
        }
        
        .nav-menu a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: #3498db;
            font-weight: 600;
        }
        
        .sidebar-footer {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.8em;
            text-align: center;
            opacity: 0.7;
        }
        
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        
        .header {
            background: white;
            padding: 25px 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            border-bottom: 1px solid #e1e8ed;
        }
        
        .header h2 {
            color: #2c3e50;
            font-size: 1.8em;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 0.95em;
        }
        
        .content-wrapper {
            flex: 1;
            overflow-y: auto;
            padding: 30px 40px;
        }
        
        .section-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .section-title {
            color: #2c3e50;
            font-size: 1.3em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #3498db;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .step-badge {
            background: #3498db;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 15px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .data-real-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .data-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .data-item h4 {
            color: #7f8c8d;
            font-size: 0.85em;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .data-item .value {
            color: #2c3e50;
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .data-item .unit {
            color: #95a5a6;
            font-size: 0.9em;
        }
        
        .info-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 1.1em;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #e1e8ed;
        }
        
        .results {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .result-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 2px solid #e1e8ed;
        }
        
        .result-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
            font-size: 1.1em;
        }
        
        .result-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .result-row:last-child {
            border-bottom: none;
        }
        
        .result-label {
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .result-value {
            color: #2c3e50;
            font-weight: bold;
        }
        
        .positive {
            color: #27ae60;
        }
        
        .negative {
            color: #e74c3c;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .comparison-table thead {
            background: #34495e;
            color: white;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 15px;
            text-align: left;
        }
        
        .comparison-table th {
            font-size: 0.8em;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .comparison-table tbody tr {
            border-bottom: 1px solid #ecf0f1;
        }
        
        .comparison-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .help-text {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-top: 5px;
            font-style: italic;
        }
        
        .warning-box {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            border-radius: 5px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>SIE KKP</h1>
            <p>Sistem Informasi Eksekutif</p>
        </div>
        
        <nav class="nav-menu">
            <a href="index.php">
                <span>Dashboard</span>
            </a>
            <a href="top_ranking.php">
                <span>Top Ranking</span>
            </a>
            <a href="drill_down.php">
                <span>Drill Down</span>
            </a>
            <a href="what_if.php" class="active">
                <span>Analisis What-If</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            &copy; 2025 KKP Indonesia
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h2>Analisis What-If</h2>
            <p>Lihat data real terlebih dahulu, lalu simulasikan perubahan volume dan nilai produksi</p>
        </div>
        
        <div class="content-wrapper">
            <!-- STEP 1: PILIH DATA REAL -->
            <div class="section-card">
                <div class="section-title">
                    <span class="step-badge">1</span>
                    <span>Pilih Data Real yang Akan Dianalisis</span>
                </div>
                
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="provinsi">Provinsi:</label>
                            <select name="provinsi" id="provinsi" required>
                                <option value="">-- Pilih Provinsi --</option>
                                <option value="SEMUA" <?php echo ($provinsiTerpilih == 'SEMUA') ? 'selected' : ''; ?>>SEMUA PROVINSI (NASIONAL)</option>
                                <?php 
                                $resultProvinsi->data_seek(0);
                                while($row = $resultProvinsi->fetch_assoc()): 
                                ?>
                                <option value="<?php echo htmlspecialchars($row['Provinsi']); ?>" 
                                        <?php echo ($provinsiTerpilih == $row['Provinsi']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['Provinsi']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="help-text">Pilih "SEMUA PROVINSI" untuk data nasional</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="tahun">Tahun:</label>
                            <select name="tahun" id="tahun" required>
                                <option value="">-- Pilih Tahun --</option>
                                <option value="2019" <?php echo ($tahunTerpilih == '2019') ? 'selected' : ''; ?>>2019</option>
                                <option value="2020" <?php echo ($tahunTerpilih == '2020') ? 'selected' : ''; ?>>2020</option>
                                <option value="2021" <?php echo ($tahunTerpilih == '2021') ? 'selected' : ''; ?>>2021</option>
                                <option value="2022" <?php echo ($tahunTerpilih == '2022') ? 'selected' : ''; ?>>2022</option>
                                <option value="2023" <?php echo ($tahunTerpilih == '2023') ? 'selected' : ''; ?>>2023</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Tampilkan Data Real</button>
                </form>
            </div>
            
            <!-- STEP 2: DATA REAL -->
            <?php if ($dataReal): ?>
            <div class="section-card">
                <div class="section-title">
                    <span class="step-badge">2</span>
                    <span>Data Real Produksi Bandeng</span>
                </div>
                
                <?php if ($provinsiTerpilih == 'SEMUA'): ?>
                <div class="info-badge">
                    Data Nasional (Agregat dari semua provinsi) - Tahun <?php echo $tahunTerpilih; ?>
                </div>
                <?php endif; ?>
                
                <div class="data-real-grid">
                    <div class="data-item">
                        <h4>Wilayah</h4>
                        <div class="value" style="font-size: 1.3em;">
                            <?php echo htmlspecialchars($dataReal['Provinsi']); ?>
                        </div>
                        <div class="unit">Lokasi</div>
                    </div>
                    
                    <div class="data-item">
                        <h4>Tahun Data</h4>
                        <div class="value"><?php echo $dataReal['Tahun']; ?></div>
                        <div class="unit">Periode</div>
                    </div>
                    
                    <div class="data-item" style="border-left-color: #3498db;">
                        <h4>Volume Produksi</h4>
                        <div class="value"><?php echo formatNumber($dataReal['Volume_Ton']); ?></div>
                        <div class="unit">Ton</div>
                    </div>
                    
                    <div class="data-item" style="border-left-color: #27ae60;">
                        <h4>Nilai Produksi</h4>
                        <div class="value"><?php echo formatNumber($dataReal['Nilai_Juta']); ?></div>
                        <div class="unit">Juta Rupiah</div>
                    </div>
                    
                    <div class="data-item" style="border-left-color: #e74c3c;">
                        <h4>Harga Tertimbang</h4>
                        <div class="value"><?php echo formatNumber($dataReal['Harga_Tertimbang_Kg']); ?></div>
                        <div class="unit">Rupiah/Kg</div>
                    </div>
                </div>
                
                <div style="background: #e8f5e9; padding: 15px; border-radius: 6px; margin-top: 15px;">
                    <strong>Informasi:</strong> Data di atas adalah data real produksi bandeng. 
                    Gunakan form di bawah untuk melakukan simulasi perubahan.
                </div>
            </div>
            
            <!-- STEP 3: FORM SIMULASI -->
            <div class="section-card">
                <div class="section-title">
                    <span class="step-badge">3</span>
                    <span>Masukkan Parameter Simulasi What-If</span>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="provinsi_sim" value="<?php echo htmlspecialchars($provinsiTerpilih); ?>">
                    <input type="hidden" name="tahun_sim" value="<?php echo htmlspecialchars($tahunTerpilih); ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="volume_baru">Volume Baru (Ton):</label>
                            <input type="number" step="0.01" name="volume_baru" id="volume_baru" required 
                                   placeholder="Contoh: <?php echo number_format($dataReal['Volume_Ton'] * 1.1, 2, '.', ''); ?>"
                                   value="<?php echo isset($_POST['volume_baru']) ? $_POST['volume_baru'] : ''; ?>">
                            <div class="help-text">
                                Data saat ini: <?php echo formatNumber($dataReal['Volume_Ton']); ?> Ton
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="harga_baru">Harga Tertimbang Baru (Rp/Kg):</label>
                            <input type="number" step="1" name="harga_baru" id="harga_baru" required 
                                   placeholder="Contoh: <?php echo number_format($dataReal['Harga_Tertimbang_Kg'] * 1.05, 0, '.', ''); ?>"
                                   value="<?php echo isset($_POST['harga_baru']) ? $_POST['harga_baru'] : ''; ?>">
                            <div class="help-text">
                                Data saat ini: Rp <?php echo formatNumber($dataReal['Harga_Tertimbang_Kg']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="simulate" class="btn btn-success">Jalankan Simulasi What-If</button>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- STEP 4: HASIL SIMULASI -->
            <?php if ($simulationResult): ?>
            <div class="section-card" style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border: 2px solid #3498db;">
                <div class="section-title">
                    <span class="step-badge" style="background: #27ae60;">4</span>
                    <span>Hasil Analisis What-If</span>
                </div>
                
                <?php if ($simulationResult['is_nasional']): ?>
                <div class="info-badge">
                    Simulasi Data Nasional (Agregat dari semua provinsi)
                </div>
                <?php endif; ?>
                
                <div class="results">
                    <div class="result-card">
                        <h3>Data Original</h3>
                        <div class="result-row">
                            <span class="result-label">Wilayah:</span>
                            <span class="result-value"><?php echo htmlspecialchars($simulationResult['original']['Provinsi']); ?></span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Tahun:</span>
                            <span class="result-value"><?php echo $simulationResult['original']['Tahun']; ?></span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Volume:</span>
                            <span class="result-value"><?php echo formatNumber($simulationResult['original']['Volume_Ton']); ?> Ton</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Nilai:</span>
                            <span class="result-value"><?php echo formatNumber($simulationResult['original']['Nilai_Juta']); ?> Jt</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Harga/Kg:</span>
                            <span class="result-value">Rp <?php echo formatNumber($simulationResult['original']['Harga_Tertimbang_Kg']); ?></span>
                        </div>
                    </div>
                    
                    <div class="result-card" style="border-color: #27ae60;">
                        <h3>Hasil Simulasi</h3>
                        <div class="result-row">
                            <span class="result-label">Volume Baru:</span>
                            <span class="result-value"><?php echo formatNumber($simulationResult['volume_baru']); ?> Ton</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Nilai Baru:</span>
                            <span class="result-value"><?php echo formatNumber($simulationResult['nilai_baru']); ?> Jt</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Harga/Kg Baru:</span>
                            <span class="result-value">Rp <?php echo formatNumber($simulationResult['harga_baru']); ?></span>
                        </div>
                    </div>
                    
                    <div class="result-card" style="border-color: #9b59b6;">
                        <h3>Analisis Perubahan</h3>
                        <div class="result-row">
                            <span class="result-label">Δ Volume:</span>
                            <span class="result-value <?php echo $simulationResult['perubahan_volume'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $simulationResult['perubahan_volume'] >= 0 ? '↑' : '↓'; ?>
                                <?php echo formatNumber(abs($simulationResult['perubahan_volume'])); ?> Ton
                                (<?php echo number_format($simulationResult['persen_volume'], 2); ?>%)
                            </span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Δ Nilai:</span>
                            <span class="result-value <?php echo $simulationResult['perubahan_nilai'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $simulationResult['perubahan_nilai'] >= 0 ? '↑' : '↓'; ?>
                                <?php echo formatNumber(abs($simulationResult['perubahan_nilai'])); ?> Jt
                                (<?php echo number_format($simulationResult['persen_nilai'], 2); ?>%)
                            </span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Δ Harga/Kg:</span>
                            <span class="result-value <?php echo $simulationResult['perubahan_harga'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $simulationResult['perubahan_harga'] >= 0 ? '↑' : '↓'; ?>
                                Rp <?php echo formatNumber(abs($simulationResult['perubahan_harga'])); ?>
                                (<?php echo number_format($simulationResult['persen_harga'], 2); ?>%)
                            </span>
                        </div>
                    </div>
                </div>
                
                <h3 style="color: #2c3e50; margin-bottom: 20px; font-size: 1.2em;">Tabel Perbandingan Detail</h3>
                
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Indikator</th>
                            <th>Data Original</th>
                            <th>Data Simulasi</th>
                            <th>Perubahan Absolut</th>
                            <th>Perubahan (%)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Volume (Ton)</strong></td>
                            <td><?php echo formatNumber($simulationResult['original']['Volume_Ton']); ?></td>
                            <td><?php echo formatNumber($simulationResult['volume_baru']); ?></td>
                            <td class="<?php echo $simulationResult['perubahan_volume'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo formatNumber($simulationResult['perubahan_volume']); ?>
                            </td>
                            <td class="<?php echo $simulationResult['persen_volume'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo number_format($simulationResult['persen_volume'], 2); ?>%
                            </td>
                            <td>
                                <?php if ($simulationResult['perubahan_volume'] > 0): ?>
                                    <span style="color: #27ae60; font-weight: bold;">NAIK</span>
                                <?php elseif ($simulationResult['perubahan_volume'] < 0): ?>
                                    <span style="color: #e74c3c; font-weight: bold;">TURUN</span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; font-weight: bold;">TETAP</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Nilai (Juta Rp)</strong></td>
                            <td><?php echo formatNumber($simulationResult['original']['Nilai_Juta']); ?></td>
                            <td><?php echo formatNumber($simulationResult['nilai_baru']); ?></td>
                            <td class="<?php echo $simulationResult['perubahan_nilai'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo formatNumber($simulationResult['perubahan_nilai']); ?>
                            </td>
                            <td class="<?php echo $simulationResult['persen_nilai'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo number_format($simulationResult['persen_nilai'], 2); ?>%
                            </td>
                            <td>
                                <?php if ($simulationResult['perubahan_nilai'] > 0): ?>
                                    <span style="color: #27ae60; font-weight: bold;">NAIK</span>
                                <?php elseif ($simulationResult['perubahan_nilai'] < 0): ?>
                                    <span style="color: #e74c3c; font-weight: bold;">TURUN</span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; font-weight: bold;">TETAP</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Harga/Kg (Rp)</strong></td>
                            <td><?php echo formatNumber($simulationResult['original']['Harga_Tertimbang_Kg']); ?></td>
                            <td><?php echo formatNumber($simulationResult['harga_baru']); ?></td>
                            <td class="<?php echo $simulationResult['perubahan_harga'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo formatNumber($simulationResult['perubahan_harga']); ?>
                            </td>
                            <td class="<?php echo $simulationResult['persen_harga'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo number_format($simulationResult['persen_harga'], 2); ?>%
                            </td>
                            <td>
                                <?php if ($simulationResult['perubahan_harga'] > 0): ?>
                                    <span style="color: #27ae60; font-weight: bold;">NAIK</span>
                                <?php elseif ($simulationResult['perubahan_harga'] < 0): ?>
                                    <span style="color: #e74c3c; font-weight: bold;">TURUN</span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; font-weight: bold;">TETAP</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if ($simulationResult['is_nasional']): ?>
                <div class="warning-box">
                    <strong>Catatan:</strong> Simulasi ini menggunakan data agregat nasional dari semua provinsi. 
                    Perubahan yang ditampilkan merepresentasikan dampak terhadap total produksi nasional.
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Pesan jika belum memilih data -->
            <?php if (!$dataReal && !$simulationResult): ?>
            <div class="section-card">
                <div class="no-data">
                    <p><strong>Cara Menggunakan Analisis What-If:</strong></p>
                    <ol style="text-align: left; display: inline-block; margin-top: 15px;">
                        <li style="margin-bottom: 10px;">Pilih Provinsi dan Tahun di form atas</li>
                        <li style="margin-bottom: 10px;">Klik "Tampilkan Data Real" untuk melihat data aktual</li>
                        <li style="margin-bottom: 10px;">Masukkan parameter simulasi (volume dan harga baru)</li>
                        <li>Klik "Jalankan Simulasi What-If" untuk melihat hasil perbandingan</li>
                    </ol>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
