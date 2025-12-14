<?php
require_once 'config.php';
$conn = getConnection();

// Query untuk rata-rata nasional per provinsi (2019-2023)
$queryDashboard = "
    SELECT 
        Provinsi,
        ROUND(AVG(Volume_Ton), 2) as Rata_Volume,
        ROUND(AVG(Nilai_Juta), 2) as Rata_Nilai,
        ROUND(AVG(Harga_Tertimbang_Kg), 2) as Rata_Harga,
        COUNT(DISTINCT Tahun) as Jumlah_Tahun
    FROM produksi_bandeng_all
    GROUP BY Provinsi
    ORDER BY Rata_Volume DESC
";

$resultDashboard = $conn->query($queryDashboard);

// Simpan data untuk chart
$chartData = [];
while($row = $resultDashboard->fetch_assoc()) {
    $chartData[] = $row;
}

// Reset pointer untuk tabel
$resultDashboard->data_seek(0);

// Query untuk total nasional
$queryTotal = "
    SELECT 
        ROUND(AVG(Volume_Ton), 2) as Total_Volume,
        ROUND(AVG(Nilai_Juta), 2) as Total_Nilai,
        COUNT(DISTINCT Provinsi) as Jumlah_Provinsi
    FROM produksi_bandeng_all
";
$resultTotal = $conn->query($queryTotal);
$dataTotal = $resultTotal->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Produksi Bandeng KKP</title>
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
        
        /* Sidebar Navigation */
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
        
        /* Main Content */
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
        
        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #3498db;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .card h3 {
            color: #7f8c8d;
            font-size: 0.85em;
            text-transform: uppercase;
            margin-bottom: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .card .value {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .card .unit {
            color: #95a5a6;
            font-size: 0.9em;
        }
        
        /* Chart Section */
        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .chart-section h3 {
            color: #2c3e50;
            font-size: 1.3em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #3498db;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .chart-box h4 {
            color: #2c3e50;
            font-size: 1.1em;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .chart-info {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 0.9em;
            color: #1976d2;
            text-align: center;
        }
        
        /* Search Box */
        .search-box {
            margin-bottom: 25px;
        }
        
        .search-box input {
            width: 100%;
            max-width: 500px;
            padding: 12px 20px;
            font-size: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            transition: border 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        /* Table */
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
        
        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8em;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #ecf0f1;
            color: #2c3e50;
        }
        
        tbody tr {
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
            transition: all 0.3s;
            font-weight: 500;
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
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        /* Badge */
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-high {
            background: #e74c3c;
            color: white;
        }
        
        .badge-medium {
            background: #f39c12;
            color: white;
        }
        
        .badge-low {
            background: #95a5a6;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 1400px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }
            .main-content {
                margin-left: 220px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .main-content {
                margin-left: 70px;
            }
            .sidebar-header h1,
            .sidebar-header p,
            .nav-menu a span {
                display: none;
            }
            .sidebar-footer {
                font-size: 0.6em;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>SIE KKP</h1>
            <p>Sistem Informasi Eksekutif</p>
        </div>
        
        <nav class="nav-menu">
            <a href="index.php" class="active">
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
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Dashboard Produksi Bandeng</h2>
            <p>Ringkasan data produksi bandeng nasional tahun 2019-2023</p>
        </div>
        
        <div class="content-wrapper">
            <div class="summary-cards">
                <div class="card">
                    <h3>Rata-rata Volume Nasional</h3>
                    <div class="value"><?php echo formatNumber($dataTotal['Total_Volume']); ?></div>
                    <div class="unit">Ton per Provinsi</div>
                </div>
                <div class="card">
                    <h3>Rata-rata Nilai Nasional</h3>
                    <div class="value"><?php echo formatNumber($dataTotal['Total_Nilai']); ?></div>
                    <div class="unit">Juta Rupiah</div>
                </div>
                <div class="card">
                    <h3>Jumlah Provinsi</h3>
                    <div class="value"><?php echo $dataTotal['Jumlah_Provinsi']; ?></div>
                    <div class="unit">Provinsi Produsen</div>
                </div>
            </div>
            
            <!-- Chart Section -->
            <div class="chart-section">
                <h3>Visualisasi Data Produksi per Provinsi (Klik untuk Drill Down)</h3>
                
                <div class="charts-grid">
                    <div class="chart-box">
                        <h4>Rata-rata Volume Produksi per Provinsi (Ton)</h4>
                        <canvas id="chartVolume"></canvas>
                        <div class="chart-info">
                            Klik pada bar untuk melihat detail drill down provinsi
                        </div>
                    </div>
                    
                    <div class="chart-box">
                        <h4>Rata-rata Nilai Produksi per Provinsi (Juta Rp)</h4>
                        <canvas id="chartNilai"></canvas>
                        <div class="chart-info">
                            Klik pada bar untuk melihat detail drill down provinsi
                        </div>
                    </div>
                </div>
            </div>
            
            <h3 style="margin-bottom: 20px; color: #2c3e50; font-size: 1.3em;">Rata-rata Produksi per Provinsi (2019-2023)</h3>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Cari provinsi..." onkeyup="filterTable()">
            </div>
            
            <div class="table-container">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Provinsi</th>
                            <th>Rata-rata Volume (Ton)</th>
                            <th>Rata-rata Nilai (Juta Rp)</th>
                            <th>Rata-rata Harga/Kg (Rp)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while($row = $resultDashboard->fetch_assoc()): 
                            $volume = $row['Rata_Volume'];
                            if ($volume > 100000) {
                                $status = '<span class="badge badge-high">Sangat Tinggi</span>';
                            } elseif ($volume > 20000) {
                                $status = '<span class="badge badge-medium">Tinggi</span>';
                            } else {
                                $status = '<span class="badge badge-low">Rendah</span>';
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['Provinsi']); ?></strong></td>
                            <td><?php echo formatNumber($row['Rata_Volume']); ?></td>
                            <td><?php echo formatNumber($row['Rata_Nilai']); ?></td>
                            <td><?php echo formatNumber($row['Rata_Harga']); ?></td>
                            <td><?php echo $status; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="provinsi_detail.php?provinsi=<?php echo urlencode($row['Provinsi']); ?>" 
                                       class="btn btn-primary">Detail</a>
                                    <a href="drill_down.php?provinsi=<?php echo urlencode($row['Provinsi']); ?>" 
                                       class="btn btn-success">Grafik</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Data dari PHP
        const chartData = <?php echo json_encode($chartData); ?>;
        
        // Ambil top 10 provinsi untuk chart
        const top10Data = chartData.slice(0, 10);
        
        const provinsiLabels = top10Data.map(d => d.Provinsi);
        const volumeData = top10Data.map(d => parseFloat(d.Rata_Volume));
        const nilaiData = top10Data.map(d => parseFloat(d.Rata_Nilai));
        
        // Fungsi untuk redirect ke drill down
        function redirectToDrillDown(provinsi) {
            window.location.href = 'drill_down.php?provinsi=' + encodeURIComponent(provinsi);
        }
        
        // Chart Volume
        const ctxVolume = document.getElementById('chartVolume').getContext('2d');
        const chartVolume = new Chart(ctxVolume, {
            type: 'bar',
            data: {
                labels: provinsiLabels,
                datasets: [{
                    label: 'Rata-rata Volume (Ton)',
                    data: volumeData,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 2,
                    hoverBackgroundColor: 'rgba(52, 152, 219, 0.9)',
                    hoverBorderColor: 'rgba(41, 128, 185, 1)',
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                onClick: (event, activeElements) => {
                    if (activeElements.length > 0) {
                        const index = activeElements[0].index;
                        const provinsi = provinsiLabels[index];
                        redirectToDrillDown(provinsi);
                    }
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
                                label += ' Ton';
                                return label;
                            },
                            footer: function() {
                                return 'Klik untuk drill down';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                },
                onHover: (event, activeElements) => {
                    event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                }
            }
        });
        
        // Chart Nilai
        const ctxNilai = document.getElementById('chartNilai').getContext('2d');
        const chartNilai = new Chart(ctxNilai, {
            type: 'bar',
            data: {
                labels: provinsiLabels,
                datasets: [{
                    label: 'Rata-rata Nilai (Juta Rp)',
                    data: nilaiData,
                    backgroundColor: 'rgba(39, 174, 96, 0.7)',
                    borderColor: 'rgba(39, 174, 96, 1)',
                    borderWidth: 2,
                    hoverBackgroundColor: 'rgba(39, 174, 96, 0.9)',
                    hoverBorderColor: 'rgba(34, 153, 84, 1)',
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                onClick: (event, activeElements) => {
                    if (activeElements.length > 0) {
                        const index = activeElements[0].index;
                        const provinsi = provinsiLabels[index];
                        redirectToDrillDown(provinsi);
                    }
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
                                label += ' Juta Rp';
                                return label;
                            },
                            footer: function() {
                                return 'Klik untuk drill down';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                },
                onHover: (event, activeElements) => {
                    event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                }
            }
        });
        
        // Filter table function
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('dataTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[1];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
