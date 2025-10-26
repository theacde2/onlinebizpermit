<?php
// Page-specific variables
$page_title = 'Reports';
$current_page = 'reports';

// Include Header
require_once __DIR__ . '/staff_header.php';

// --- Main KPI Counts ---
$kpi_sql = "SELECT
                COUNT(*) as total_applications,
                SUM(CASE WHEN status IN ('approved', 'complete') THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status IN ('pending', 'for review', 'review') THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
            FROM applications";
$kpi_result = $conn->query($kpi_sql);
$kpis = $kpi_result ? $kpi_result->fetch_assoc() : [];

// --- Data for Monthly Trend Chart (Last 12 Months) ---
$monthly_labels = [];
$monthly_counts = [];
$counts_by_month = [];
for ($i = 11; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i month"));
    $monthly_labels[] = date('M Y', strtotime($month_key));
    $counts_by_month[$month_key] = 0;
}

$monthly_sql = "SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS month, COUNT(id) AS count
                FROM applications
                WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY month
                ORDER BY month ASC";
$monthly_result = $conn->query($monthly_sql);
if ($monthly_result) {
    while ($row = $monthly_result->fetch_assoc()) {
        if (isset($counts_by_month[$row['month']])) {
            $counts_by_month[$row['month']] = (int)$row['count'];
        }
    }
}
$monthly_counts = array_values($counts_by_month);

// --- Data for Application Status Doughnut Chart ---
$status_distribution_sql = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
$status_result = $conn->query($status_distribution_sql);
$status_labels = [];
$status_counts = [];
$status_colors = [
    'approved' => '#28a745',
    'complete' => '#20c997',
    'pending' => '#ffc107',
    'rejected' => '#dc3545',
    'review' => '#17a2b8',
    'for review' => '#17a2b8',
    'default' => '#6c757d'
];
$doughnut_bg_colors = [];
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        $status_labels[] = ucfirst($row['status']);
        $status_counts[] = $row['count'];
        $doughnut_bg_colors[] = $status_colors[strtolower($row['status'])] ?? $status_colors['default'];
    }
}

// --- Fetch Recent Applications for Table ---
$recent_apps_sql = "SELECT a.id, a.business_name, u.name as applicant_name, a.status, a.submitted_at
                    FROM applications a
                    JOIN users u ON a.user_id = u.id
                    ORDER BY a.submitted_at DESC
                    LIMIT 7";
$recent_apps_result = $conn->query($recent_apps_sql);
$recent_applications = $recent_apps_result ? $recent_apps_result->fetch_all(MYSQLI_ASSOC) : [];
require_once './staff_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
  <header class="header">
    <h1>Reports</h1>
    <div class="header-actions">
        <a href="export_reports.php" class="btn btn-primary">
            <i class="fas fa-file-csv"></i> Export as CSV
        </a>
    </div>
  </header>

  <style>
    /* Button and Dropdown Styles */
    .btn-primary {
        background-color: var(--primary-color);
        color: white;
        padding: 10px 20px;
        border-radius: var(--border-radius);
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-primary:hover {
        background-color: #3b5699; /* Darker shade of primary */
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .main { flex: 1; padding: 30px; overflow-y: auto; }
    .data-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; }
    .chart-box { background: var(--card-bg); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); }
    .chart-box h3 { margin: 0 0 1.5rem 0; font-size: 1.125rem; color: var(--text-primary); padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
    .chart-container { position: relative; height: 350px; }
    .header {
        display: flex;
        justify-content: space-between; 
        align-items: center;
        margin-bottom: 1.5rem;
        background: var(--card-bg);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
    }

    .header h1 {
        color: var(--text-primary);
        font-size: 1.75rem;
        margin: 0;
    }
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
    .stat-card { background: var(--card-bg); border-radius: var(--border-radius); padding: 1.5rem; display: flex; align-items: center; gap: 1.5rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); color: #fff; }
    .kpi-grid .stat-card:nth-child(1) { background: linear-gradient(45deg, #2980b9, #3498db); }
    .kpi-grid .stat-card:nth-child(2) { background: linear-gradient(45deg, #27ae60, #2ecc71); }
    .kpi-grid .stat-card:nth-child(3) { background: linear-gradient(45deg, #f39c12, #f1c40f); }
    .kpi-grid .stat-card:nth-child(4) { background: linear-gradient(45deg, #c0392b, #e74c3c); }
    .stat-card p { color: rgba(255, 255, 255, 0.8); }
    .stat-icon { width: 60px; height: 60px; font-size: 1.75rem; color: #fff; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.15); border-radius: 50%; }
    .stat-info span { font-size: 2.5rem; color: #fff; }
    .table-container { background: var(--card-bg); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); margin-top: 1.5rem; }
    .table-container h3 { margin: 0 0 1.5rem 0; font-size: 1.125rem; color: var(--text-primary); padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
    .recent-apps-table { width: 100%; border-collapse: collapse; }
    .recent-apps-table th, .recent-apps-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
    .recent-apps-table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: var(--text-secondary); }
    .status-badge { padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; text-align: center; display: inline-block; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-approved, .status-complete { background-color: #27ae60; }
    .status-pending, .status-review, .status-for-review { background-color: #f39c12; }
    .status-rejected { background-color: #c0392b; }
  </style>
  
  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
      <div class="stat-info">
        <p>Total Applications</p>
        <span><?= $kpis['total_applications'] ?? 0 ?></span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
      <div class="stat-info">
        <p>Approved / Completed</p>
        <span><?= $kpis['approved_count'] ?? 0 ?></span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-clock"></i></div>
      <div class="stat-info">
        <p>Pending / In Review</p>
        <span><?= $kpis['pending_count'] ?? 0 ?></span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
      <div class="stat-info">
        <p>Rejected</p>
        <span><?= $kpis['rejected_count'] ?? 0 ?></span>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="data-grid">
    <div class="chart-box">
      <h3>Monthly Application Trend</h3>
      <div class="chart-container">
        <canvas id="monthlyTrendChart"></canvas>
      </div>
    </div>
    <div class="chart-box">
      <h3>Application Status Distribution</h3>
      <div class="chart-container" style="max-height: 300px; margin: auto;">
        <canvas id="statusDoughnutChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Recent Applications Table -->
  <div class="table-container">
    <h3>Recent Applications</h3>
    <table class="recent-apps-table">
      <thead>
        <tr><th>Application ID</th><th>Business Name</th><th>Applicant</th><th>Status</th><th>Date Submitted</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recent_applications as $app): ?>
            <tr>
              <td>#<?= htmlspecialchars($app['id']) ?></td>
              <td><?= htmlspecialchars($app['business_name']) ?></td>
              <td><?= htmlspecialchars($app['applicant_name']) ?></td>
              <td><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $app['status'])) ?>"><?= htmlspecialchars(ucfirst($app['status'])) ?></span></td>
              <td><?= date('M d, Y', strtotime($app['submitted_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Bar Chart
    const monthlyChartCanvas = document.getElementById('monthlyTrendChart');
    if (monthlyChartCanvas) {
        new Chart(monthlyChartCanvas, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthly_labels) ?>,
                datasets: [{
                    label: 'Applications',
                    data: <?= json_encode($monthly_counts) ?>,
                    backgroundColor: 'rgba(74, 105, 189, 0.7)',
                    borderColor: 'rgba(74, 105, 189, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { precision: 0, color: '#666' } 
                    },
                    x: {
                        ticks: { color: '#666' }
                    }
                }
            }
        });
    }

    // Status Distribution Doughnut Chart
    const statusChartCanvas = document.getElementById('statusDoughnutChart');
    if (statusChartCanvas) {
        new Chart(statusChartCanvas, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($status_labels) ?>,
            datasets: [{
            data: <?= json_encode($status_counts) ?>,
            backgroundColor: <?= json_encode($doughnut_bg_colors) ?>,
            borderColor: '#fff',
            borderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
            legend: { position: 'bottom', labels: { color: '#333' } },
            title: { display: false }
            }
        }
        });
    }
});

<?php
// Include Footer
require_once __DIR__ . '/staff_footer.php';
?>