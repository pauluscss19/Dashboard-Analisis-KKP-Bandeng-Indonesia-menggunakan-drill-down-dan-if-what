<?php
require_once 'config.php';
$conn = getConnection();

$provinsi = isset($_GET['provinsi']) ? $_GET['provinsi'] : '';

if (empty($provinsi)) {
    header('Location: index.php');
    exit;
}

// Query data 5 tahun provinsi terpilih
$queryDetail = "
    SELECT 
        Tahun,
        Provinsi,
        Volume_Ton,
        Nilai_Juta,
        Harga_Tertimbang_Kg
    FROM produksi_bandeng_all
    WHERE Provinsi = ?
    ORDER BY Tahun ASC
";

$stmt = $conn->prepare($queryDetail);
$stmt->bind_param("s", $provinsi);
$stmt->execute();
$resultDetail = $stmt->get_result();

// Query untuk statistik
$queryStats = "
    SELECT 
        MIN(Volume_Ton) as Min_Volume,
        MAX(Volume_Ton) as Max_Volume,
        AVG(Volume_Ton) as Avg_Volume,
        MIN(Nilai_Juta) as Min_Nilai,
        MAX(Nilai_Juta) as Max_Nilai,
        AVG(Nilai_Juta) as Avg_Nilai
    FROM produksi_bandeng_all
    WHERE Provinsi = ?
";

$stmtStats = $conn->prepare($queryStats);
$stmtStats->bind_param("s", $provinsi);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Provinsi - <?php echo htmlspecialchars($provinsi); ?></title>
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
        
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .back-button:hover {
            background: #2980b9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.85em;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .stat-card .value {
            font-size: 1.8em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card .unit {
            color: #95a5a6;
            font-size: 0.85em;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #34495e;
            color: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
        }
        
        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8em;
        }
        
        tbody tr {
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.3s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .trend-up {
            color: #27ae60;
            font-weight: bold;
        }
        
        .trend-down {
            color: #e74c3c;
            font-weight: bold;
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
            <a href="what_if.php">
                <span>Analisis What-If</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            &copy; 2025 KKP Indonesia
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h2>Detail Produksi: <?php echo htmlspecialchars($provinsi); ?></h2>
            <p>Data produksi bandeng tahun 2019-2023</p>
        </div>
        
        <div class="content-wrapper">
            <a href="index.php" class="back-button">Kembali ke Dashboard</a>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Volume Minimum</h3>
                    <div class="value"><?php echo formatNumber($stats['Min_Volume']); ?></div>
                    <div class="unit">Ton</div>
                </div>
                <div class="stat-card">
                    <h3>Volume Maksimum</h3>
                    <div class="value"><?php echo formatNumber($stats['Max_Volume']); ?></div>
                    <div class="unit">Ton</div>
                </div>
                <div class="stat-card">
                    <h3>Rata-rata Volume</h3>
                    <div class="value"><?php echo formatNumber($stats['Avg_Volume']); ?></div>
                    <div class="unit">Ton</div>
                </div>
                <div class="stat-card">
                    <h3>Nilai Minimum</h3>
                    <div class="value"><?php echo formatNumber($stats['Min_Nilai']); ?></div>
                    <div class="unit">Juta Rp</div>
                </div>
                <div class="stat-card">
                    <h3>Nilai Maksimum</h3>
                    <div class="value"><?php echo formatNumber($stats['Max_Nilai']); ?></div>
                    <div class="unit">Juta Rp</div>
                </div>
                <div class="stat-card">
                    <h3>Rata-rata Nilai</h3>
                    <div class="value"><?php echo formatNumber($stats['Avg_Nilai']); ?></div>
                    <div class="unit">Juta Rp</div>
                </div>
            </div>
            
            <h3 style="margin-bottom: 20px; color: #2c3e50; font-size: 1.3em;">Data Tahunan (2019-2023)</h3>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tahun</th>
                            <th>Volume (Ton)</th>
                            <th>Nilai (Juta Rp)</th>
                            <th>Harga/Kg (Rp)</th>
                            <th>Perubahan Volume</th>
                            <th>Perubahan Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $prevVolume = null;
                        $prevNilai = null;
                        while($row = $resultDetail->fetch_assoc()): 
                            $volumeChange = '';
                            $nilaiChange = '';
                            
                            if ($prevVolume !== null) {
                                $volDiff = $row['Volume_Ton'] - $prevVolume;
                                $volPercent = ($volDiff / $prevVolume) * 100;
                                $volumeChange = $volDiff >= 0 
                                    ? '<span class="trend-up">↑ ' . number_format($volPercent, 2) . '%</span>'
                                    : '<span class="trend-down">↓ ' . number_format(abs($volPercent), 2) . '%</span>';
                                    
                                $nilaiDiff = $row['Nilai_Juta'] - $prevNilai;
                                $nilaiPercent = ($nilaiDiff / $prevNilai) * 100;
                                $nilaiChange = $nilaiDiff >= 0 
                                    ? '<span class="trend-up">↑ ' . number_format($nilaiPercent, 2) . '%</span>'
                                    : '<span class="trend-down">↓ ' . number_format(abs($nilaiPercent), 2) . '%</span>';
                            }
                            
                            $prevVolume = $row['Volume_Ton'];
                            $prevNilai = $row['Nilai_Juta'];
                        ?>
                        <tr>
                            <td><strong><?php echo $row['Tahun']; ?></strong></td>
                            <td><?php echo formatNumber($row['Volume_Ton']); ?></td>
                            <td><?php echo formatNumber($row['Nilai_Juta']); ?></td>
                            <td><?php echo formatNumber($row['Harga_Tertimbang_Kg']); ?></td>
                            <td><?php echo $volumeChange ?: '-'; ?></td>
                            <td><?php echo $nilaiChange ?: '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

<?php 
$stmt->close();
$stmtStats->close();
$conn->close(); 
?>
