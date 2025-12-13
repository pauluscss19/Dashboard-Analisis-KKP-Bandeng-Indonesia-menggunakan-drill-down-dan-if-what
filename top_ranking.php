<?php
require_once 'config.php';
$conn = getConnection();

// Query Top 5 Tertinggi
$queryTop5 = "
    SELECT 
        Provinsi,
        SUM(Volume_Ton) as Total_Volume,
        SUM(Nilai_Juta) as Total_Nilai,
        AVG(Harga_Tertimbang_Kg) as Avg_Harga
    FROM produksi_bandeng_all
    WHERE Tahun BETWEEN 2019 AND 2023
    GROUP BY Provinsi
    ORDER BY Total_Volume DESC
    LIMIT 5
";

// Query Top 5 Terendah
$queryBottom5 = "
    SELECT 
        Provinsi,
        SUM(Volume_Ton) as Total_Volume,
        SUM(Nilai_Juta) as Total_Nilai,
        AVG(Harga_Tertimbang_Kg) as Avg_Harga
    FROM produksi_bandeng_all
    WHERE Tahun BETWEEN 2019 AND 2023
    GROUP BY Provinsi
    ORDER BY Total_Volume ASC
    LIMIT 5
";

$resultTop5 = $conn->query($queryTop5);
$resultBottom5 = $conn->query($queryBottom5);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Ranking Produksi Bandeng</title>
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
        
        .ranking-section {
            margin-bottom: 40px;
        }
        
        .ranking-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.3em;
            padding-bottom: 10px;
            border-bottom: 3px solid #3498db;
        }
        
        .ranking-cards {
            display: grid;
            gap: 20px;
        }
        
        .rank-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 25px;
            transition: transform 0.3s;
        }
        
        .rank-card:hover {
            transform: translateX(10px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .rank-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }
        
        .rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%); }
        .rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); }
        .rank-4 { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .rank-5 { background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); }
        
        .rank-info {
            flex-grow: 1;
        }
        
        .rank-info h4 {
            color: #2c3e50;
            font-size: 1.3em;
            margin-bottom: 15px;
        }
        
        .rank-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.85em;
            margin-bottom: 5px;
        }
        
        .stat-value {
            color: #2c3e50;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .bottom-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
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
            <a href="top_ranking.php" class="active">
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
            <h2>Top Ranking Produksi Bandeng</h2>
            <p>5 Provinsi dengan produksi tertinggi dan terendah periode 2019-2023</p>
        </div>
        
        <div class="content-wrapper">
            <div class="ranking-section">
                <h3>Top 5 Provinsi dengan Produksi Tertinggi</h3>
                <div class="ranking-cards">
                    <?php 
                    $rank = 1;
                    while($row = $resultTop5->fetch_assoc()): 
                    ?>
                    <div class="rank-card">
                        <div class="rank-number rank-<?php echo $rank; ?>">
                            <?php echo $rank; ?>
                        </div>
                        <div class="rank-info">
                            <h4><?php echo htmlspecialchars($row['Provinsi']); ?></h4>
                            <div class="rank-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Total Volume</span>
                                    <span class="stat-value"><?php echo formatNumber($row['Total_Volume']); ?> Ton</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total Nilai</span>
                                    <span class="stat-value"><?php echo formatNumber($row['Total_Nilai']); ?> Jt</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Rata-rata Harga/Kg</span>
                                    <span class="stat-value">Rp <?php echo formatNumber($row['Avg_Harga']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </div>
            </div>
            
            <div class="ranking-section bottom-section">
                <h3>Top 5 Provinsi dengan Produksi Terendah</h3>
                <div class="ranking-cards">
                    <?php 
                    $rank = 1;
                    while($row = $resultBottom5->fetch_assoc()): 
                    ?>
                    <div class="rank-card">
                        <div class="rank-number rank-<?php echo $rank; ?>">
                            <?php echo $rank; ?>
                        </div>
                        <div class="rank-info">
                            <h4><?php echo htmlspecialchars($row['Provinsi']); ?></h4>
                            <div class="rank-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Total Volume</span>
                                    <span class="stat-value"><?php echo formatNumber($row['Total_Volume']); ?> Ton</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total Nilai</span>
                                    <span class="stat-value"><?php echo formatNumber($row['Total_Nilai']); ?> Jt</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Rata-rata Harga/Kg</span>
                                    <span class="stat-value">Rp <?php echo formatNumber($row['Avg_Harga']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
