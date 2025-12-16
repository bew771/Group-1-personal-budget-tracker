<?php
require 'auth_check.php';
require 'db.php';

// Minimal report page with totals & two charts.

function getTotalForRange($pdo, $userId, $type, $start, $end) {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = ? AND date BETWEEN ? AND ?');
    $stmt->execute([$userId, $type, $start, $end]);
    return (float)$stmt->fetchColumn();
}

function getTrendByRange($pdo, $userId, $type, $startDate, $endDate) {
    $labels = [];
    $data = [];
    $stmt = $pdo->prepare('SELECT DATE_FORMAT(date, "%Y-%m") as ym, COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id = ? AND type = ? AND date BETWEEN ? AND ? GROUP BY ym ORDER BY ym');
    $stmt->execute([$userId, $type, $startDate, $endDate]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[$r['ym']] = (float)$r['total'];
    $start = new DateTime($startDate);
    $start->modify('first day of this month');
    $end = new DateTime($endDate);
    $end->modify('first day of this month');
    while ($start <= $end) {
        $ym = $start->format('Y-m');
        $labels[] = $start->format('M Y');
        $data[] = $map[$ym] ?? 0;
        $start->modify('+1 month');
    }
    return ['labels'=>$labels,'data'=>$data];
}

$userId = $_SESSION['user_id'];

// Ranges
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$yearStart = date('Y-01-01');
$yearEnd = date('Y-12-31');

$incomeWeek = getTotalForRange($pdo, $userId, 'income', $weekStart, $weekEnd);
$expenseWeek = getTotalForRange($pdo, $userId, 'expense', $weekStart, $weekEnd);
$incomeMonth = getTotalForRange($pdo, $userId, 'income', $monthStart, $monthEnd);
$expenseMonth = getTotalForRange($pdo, $userId, 'expense', $monthStart, $monthEnd);
$incomeYear = getTotalForRange($pdo, $userId, 'income', $yearStart, $yearEnd);
$expenseYear = getTotalForRange($pdo, $userId, 'expense', $yearStart, $yearEnd);

// Totals
$incomeTotalStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = ?');
$incomeTotalStmt->execute([$userId, 'income']);
$incomeTotal = (float)$incomeTotalStmt->fetchColumn();
$expenseTotalStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = ?');
$expenseTotalStmt->execute([$userId, 'expense']);
$expenseTotal = (float)$expenseTotalStmt->fetchColumn();

// Trend data: last 12 months
$trendStart = date('Y-m-01', strtotime('-11 months'));
$trendEnd = date('Y-m-t');
$trendIncome = getTrendByRange($pdo, $userId, 'income', $trendStart, $trendEnd);
$trendExpense = getTrendByRange($pdo, $userId, 'expense', $trendStart, $trendEnd);

// Expense breakdown for current month
$catStmt = $pdo->prepare('SELECT c.name, COALESCE(SUM(t.amount),0) AS total FROM categories c LEFT JOIN transactions t ON c.id = t.category_id AND t.user_id = ? AND t.type = ? AND t.date BETWEEN ? AND ? WHERE c.user_id = ? AND c.type = ? GROUP BY c.id ORDER BY total DESC');
$catStmt->execute([$userId, 'expense', $monthStart, $monthEnd, $userId, 'expense']);
$catBreakdown = $catStmt->fetchAll();
$catLabels = array_map(fn($r)=>$r['name'], $catBreakdown);
$catData = array_map(fn($r)=>(float)$r['total'], $catBreakdown);

// PDF export (current month)
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><head><title>Report PDF</title><style>body{font-family:sans-serif;}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:8px}</style></head><body>';
    echo '<h2>Report: '.htmlspecialchars($monthStart).' to '.htmlspecialchars($monthEnd).'</h2>';
    echo '<p>Income: '.number_format($incomeMonth,2).' — Expense: '.number_format($expenseMonth,2).' — Balance: '.number_format($incomeMonth-$expenseMonth,2).'</p>';
    echo '<table><thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Amount</th></tr></thead><tbody>';
    $stmt = $pdo->prepare('SELECT t.date, t.type, c.name AS category, t.amount FROM transactions t LEFT JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.date BETWEEN ? AND ? ORDER BY t.date');
    $stmt->execute([$userId, $monthStart, $monthEnd]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr><td>'.htmlspecialchars($row['date']).'</td><td>'.htmlspecialchars($row['type']).'</td><td>'.htmlspecialchars($row['category'] ?? 'Uncategorized').'</td><td>'.number_format($row['amount'],2).'</td></tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reports - Budget Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Compact report layout */
    .report-container { max-width: 980px; margin: 0 auto; }
    .report-container h1 { font-size: 1.25rem; margin-bottom: .5rem; }
    .report-container .card { margin-bottom: .2rem; }
    .report-container .card .card-body { padding: .6rem .9rem; }
    .report-container .card-title { font-size: 1rem; margin-bottom: .3rem; }
    .report-container p { margin-bottom: .35rem; }
    /* Expense breakdown (current month) styles */
    .cat-card { max-width: 630px; margin: 0 auto; }
    .cat-card .card-body { padding: .5rem .6rem; height: 220px; display:flex; align-items:top; justify-content:center; }
    .small-chart { max-width: 400px; height: 80px; margin: 0 auto; display:block; }
  </style>
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container mt-4 report-container">
  <h1>Reports</h1>
  <div class="mb-3">
    <a class="btn btn-outline-secondary" href="?export=pdf" target="_blank">Export PDF (Current Month)</a>
  </div>
  <div class="row">
    <div class="col-md-4">
      <div class="card mb-3"><div class="card-body"><h5 class="card-title">This Week</h5><p>Income <strong><?=number_format($incomeWeek,2)?></strong></p><p>Expense <strong><?=number_format($expenseWeek,2)?></strong></p><p>Balance <strong><?=number_format($incomeWeek-$expenseWeek,2)?></strong></p></div></div>
      <div class="card mb-3"><div class="card-body"><h5 class="card-title">This Month</h5><p>Income <strong><?=number_format($incomeMonth,2)?></strong></p><p>Expense <strong><?=number_format($expenseMonth,2)?></strong></p><p>Balance <strong><?=number_format($incomeMonth-$expenseMonth,2)?></strong></p></div></div>
      <div class="card mb-3"><div class="card-body"><h5 class="card-title">This Year</h5><p>Income <strong><?=number_format($incomeYear,2)?></strong></p><p>Expense <strong><?=number_format($expenseYear,2)?></strong></p><p>Balance <strong><?=number_format($incomeYear-$expenseYear,2)?></strong></p></div></div>
      <div class="card mb-3"><div class="card-body"><h5 class="card-title">All Time</h5><p>Income <strong><?=number_format($incomeTotal,2)?></strong></p><p>Expense <strong><?=number_format($expenseTotal,2)?></strong></p><p>Balance <strong><?=number_format($incomeTotal-$expenseTotal,2)?></strong></p></div></div>
    </div>
    <div class="col-md-8">
      <div class="card mb-3"><div class="card-body"><h5 class="card-title">Monthly Trend (Last 12 months)</h5><canvas id="trendChart"></canvas></div></div>
      <div class="card mb-3 cat-card"><div class="card-body"><h5 class="card-title">Expense Breakdown (Current Month)</h5><canvas id="catChart" class="small-chart"></canvas></div></div>
    </div>
  </div>
</div>

<script>
  const months = <?=json_encode($trendIncome['labels'])?>;
  const incomeTrend = <?=json_encode($trendIncome['data'])?>;
  const expenseTrend = <?=json_encode($trendExpense['data'])?>;
  const catLabels = <?=json_encode($catLabels)?>;
  const catData = <?=json_encode($catData)?>;
  const ctx = document.getElementById('trendChart').getContext('2d');
  new Chart(ctx, { type: 'line', data: { labels: months, datasets: [ { label: 'Income', data: incomeTrend, borderColor: 'rgba(75,192,192,1)', backgroundColor: 'rgba(75,192,192,0.2)', tension: 0.3 }, { label: 'Expense', data: expenseTrend, borderColor: 'rgba(255,99,132,1)', backgroundColor: 'rgba(255,99,132,0.2)', tension: 0.3 } ] }, options: { responsive: true } });
  const ctx2 = document.getElementById('catChart').getContext('2d');
  new Chart(ctx2, { type: 'pie', data: { labels: catLabels, datasets: [{ data: catData, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#AA65CC', '#6CC199', '#FF9F40'] }] }, options: { responsive: true, maintainAspectRatio: false } });
</script>
</body>
</html>
