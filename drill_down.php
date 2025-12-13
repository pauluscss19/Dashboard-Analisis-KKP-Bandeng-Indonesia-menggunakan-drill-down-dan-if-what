<?php
require_once 'config.php';
$conn = getConnection();

$provinsi = isset($_GET['provinsi']) ? $_GET['provinsi'] : '';

// Query list provinsi untuk dropdown
$queryProvinsi = "SELECT DISTINCT Provinsi FROM produksi_bandeng_all ORDER BY Provinsi";
$resultProvinsi = $conn->query($queryProvinsi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drill Down Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .filter-group {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 15px;
            min-width: 300px;
        }
        
        .filter-group button {
            padding: 10px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .filter-group button:hover {
            background: #2980b9;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        
        .chart-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .chart-box h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.1em;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 40px;
            color: #7f8c8d;
            font-size: 1.2em;
            background: white;
            border-radius: 8px;
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
        
        @media (max-width: 1200px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
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
            <a href="drill_down.php" class="active">
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
            <h2>Drill Down Analysis</h2>
            <p>Visualisasi grafik produksi bandeng per provinsi atau nasional</p>
        </div>
        
        <div class="content-wrapper">
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label for="provinsi">Pilih Provinsi:</label>
                        <select name="provinsi" id="provinsi" required>
                            <option value="">-- Pilih Provinsi --</option>
                            <option value="SEMUA" <?php echo ($provinsi == 'SEMUA') ? 'selected' : ''; ?>>SEMUA PROVINSI (NASIONAL)</option>
                            <?php while($row = $resultProvinsi->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['Provinsi']); ?>" 
                                    <?php echo ($provinsi == $row['Provinsi']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['Provinsi']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit">Tampilkan Grafik</button>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($provinsi)): 
                $dataPoints = [];
                
                if ($provinsi == 'SEMUA') {
                    // Query untuk semua data (agregat nasional)
                    $queryChart = "
                        SELECT 
                            Tahun,
                            SUM(Volume_Ton) as Volume_Ton,
                            SUM(Nilai_Juta) as Nilai_Juta,
                            ROUND(AVG(Harga_Tertimbang_Kg), 2) as Harga_Tertimbang_Kg
                        FROM produksi_bandeng_all
                        GROUP BY Tahun
                        ORDER BY Tahun ASC
                    ";
                    $resultChart = $conn->query($queryChart);
                    while($row = $resultChart->fetch_assoc()) {
                        $dataPoints[] = $row;
                    }
                    $judulGrafik = "NASIONAL (Semua Provinsi)";
                } else {
                    // Query data untuk provinsi tertentu
                    $queryChart = "
                        SELECT Tahun, Volume_Ton, Nilai_Juta, Harga_Tertimbang_Kg
                        FROM produksi_bandeng_all
                        WHERE Provinsi = ?
                        ORDER BY Tahun ASC
                    ";
                    $stmt = $conn->prepare($queryChart);
                    $stmt->bind_param("s", $provinsi);
                    $stmt->execute();
                    $resultChart = $stmt->get_result();
                    
                    while($row = $resultChart->fetch_assoc()) {
                        $dataPoints[] = $row;
                    }
                    $stmt->close();
                    $judulGrafik = htmlspecialchars($provinsi);
                }
            ?>
            
            <?php if ($provinsi == 'SEMUA'): ?>
            <div class="info-badge">
                Menampilkan data agregat nasional (total dari semua provinsi)
            </div>
            <?php endif; ?>
            
            <h3 style="color: #2c3e50; margin-bottom: 20px; font-size: 1.3em;">
                Grafik Produksi: <?php echo $judulGrafik; ?>
            </h3>
            
            <div class="charts-container">
                <div class="chart-box">
                    <h3>Tren Volume Produksi (Ton)</h3>
                    <canvas id="chartVolume"></canvas>
                </div>
                
                <div class="chart-box">
                    <h3>Tren Nilai Produksi (Juta Rp)</h3>
                    <canvas id="chartNilai"></canvas>
                </div>
                
                <div class="chart-box">
                    <h3>Tren Harga per Kg (Rp)</h3>
                    <canvas id="chartHarga"></canvas>
                </div>
                
                <div class="chart-box">
                    <h3>Perbandingan Volume vs Nilai</h3>
                    <canvas id="chartKombinasi"></canvas>
                </div>
            </div>
            
            <script>
                const dataPoints = <?php echo json_encode($dataPoints); ?>;
                const tahun = dataPoints.map(d => d.Tahun);
                const volume = dataPoints.map(d => parseFloat(d.Volume_Ton));
                const nilai = dataPoints.map(d => parseFloat(d.Nilai_Juta));
                const harga = dataPoints.map(d => parseFloat(d.Harga_Tertimbang_Kg));
                
                // Chart Volume
                new Chart(document.getElementById('chartVolume'), {
                    type: 'line',
                    data: {
                        labels: tahun,
                        datasets: [{
                            label: 'Volume (Ton)',
                            data: volume,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.2)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { 
                                display: true, 
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += new Intl.NumberFormat('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }).format(context.parsed.y);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('id-ID').format(value);
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Chart Nilai
                new Chart(document.getElementById('chartNilai'), {
                    type: 'bar',
                    data: {
                        labels: tahun,
                        datasets: [{
                            label: 'Nilai (Juta Rp)',
                            data: nilai,
                            backgroundColor: '#27ae60',
                            borderColor: '#229954',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { 
                                display: true, 
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += new Intl.NumberFormat('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }).format(context.parsed.y);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('id-ID').format(value);
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Chart Harga
                new Chart(document.getElementById('chartHarga'), {
                    type: 'line',
                    data: {
                        labels: tahun,
                        datasets: [{
                            label: 'Harga/Kg (Rp)',
                            data: harga,
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.2)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { 
                                display: true, 
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': Rp ';
                                        }
                                        label += new Intl.NumberFormat('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }).format(context.parsed.y);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Chart Kombinasi
                new Chart(document.getElementById('chartKombinasi'), {
                    type: 'line',
                    data: {
                        labels: tahun,
                        datasets: [
                            {
                                label: 'Volume (Ton)',
                                data: volume,
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                                yAxisID: 'y',
                                borderWidth: 3,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            },
                            {
                                label: 'Nilai (Juta Rp)',
                                data: nilai,
                                borderColor: '#27ae60',
                                backgroundColor: 'rgba(39, 174, 96, 0.2)',
                                yAxisID: 'y1',
                                borderWidth: 3,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: { 
                                display: true, 
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += new Intl.NumberFormat('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }).format(context.parsed.y);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Volume (Ton)',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('id-ID').format(value);
                                    }
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Nilai (Juta Rp)',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('id-ID').format(value);
                                    }
                                }
                            }
                        }
                    }
                });
            </script>
            
            <?php else: ?>
            <div class="no-data">
                <p>Silakan pilih provinsi atau "SEMUA PROVINSI" untuk menampilkan grafik analisis</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
