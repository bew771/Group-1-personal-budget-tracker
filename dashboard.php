<?php
require 'auth_check.php';
require 'db.php';

// user
$userId = $_SESSION['user_id'];

// Helper to get totals
function totalRange($pdo, $userId, $type, $start, $end) {
  $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = ? AND date BETWEEN ? AND ?');
  $stmt->execute([$userId, $type, $start, $end]);
  return (float)$stmt->fetchColumn();
}

// Monthly and all-time totals
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$incomeMonth = totalRange($pdo, $userId, 'income', $monthStart, $monthEnd);
$expenseMonth = totalRange($pdo, $userId, 'expense', $monthStart, $monthEnd);
$incomeTotalStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = ?');
$incomeTotalStmt->execute([$userId, 'income']); $incomeTotal = (float)$incomeTotalStmt->fetchColumn();
$expenseTotalStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = ?');
$expenseTotalStmt->execute([$userId, 'expense']); $expenseTotal = (float)$expenseTotalStmt->fetchColumn();

// Monthly trend (last 6 months)
function monthlyTrend($pdo, $userId, $type, $months = 6) {
  $labels = []; $data = [];
  $start = new DateTime(date('Y-m-01', strtotime('-'.($months-1).' months')));
  $end = new DateTime(date('Y-m-t'));
  $stmt = $pdo->prepare('SELECT DATE_FORMAT(date, "%Y-%m") as ym, COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id = ? AND type = ? AND date BETWEEN ? AND ? GROUP BY ym ORDER BY ym');
  $stmt->execute([$userId, $type, $start->format('Y-m-d'), $end->format('Y-m-d')]);
  $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
  $cur = clone $start;
  while ($cur <= $end) {
    $ym = $cur->format('Y-m');
    $labels[] = $cur->format('M Y');
    $data[] = isset($rows[$ym]) ? (float)$rows[$ym] : 0.0;
    $cur->modify('+1 month');
  }
  return ['labels'=>$labels,'data'=>$data];
}

$trendIncome = monthlyTrend($pdo, $userId, 'income', 6);
$trendExpense = monthlyTrend($pdo, $userId, 'expense', 6);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - Budget Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* Sidebar sticky */
    #sidebar { position: sticky; top: 1rem; }
    .menu-icon { width: 1.2rem; margin-right: 0.5rem; }
    .list-group-item-action.active { background: linear-gradient(90deg,#4facfe,#00f2fe); color: #fff; }
    .card .card-body h5.card-title { display:flex; align-items:center; gap:0.5rem; }
    .card .accent { background: linear-gradient(90deg,#6a11cb,#2575fc); color:white; }
    .table-hover tbody tr:hover { background: #04327bff; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container mt-4">
  <div class="row">
    <div class="col-md-3">
      <div class="collapse show" id="sidebar">
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Menu</h5>
          <div class="list-group">
            <a href="dashboard.php" class="list-group-item list-group-item-action active"><i class="bi bi-speedometer2 menu-icon"></i>Dashboard</a>
            <a href="income.php" class="list-group-item list-group-item-action"><i class="bi bi-wallet2 menu-icon"></i>Add Income</a>
            <a href="expense.php" class="list-group-item list-group-item-action"><i class="bi bi-currency-exchange menu-icon"></i>Add Expense</a>
            <a href="category.php" class="list-group-item list-group-item-action"><i class="bi bi-tags menu-icon"></i>Categories</a>
            <a href="budget.php" class="list-group-item list-group-item-action"><i class="bi bi-graph-up menu-icon"></i>Budgets</a>
            <a href="reports.php" class="list-group-item list-group-item-action"><i class="bi bi-bar-chart menu-icon"></i>Reports</a>
            <a href="logout.php" class="list-group-item list-group-item-action"><i class="bi bi-box-arrow-right menu-icon"></i>Logout</a>
          </div>
        </div>
      </div>
      <!-- Quick Actions removed per user request -->
    </div>
   
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('chartSummary').getContext('2d');
const months = <?=json_encode($trendIncome['labels'])?>;
const incomeTrend = <?=json_encode($trendIncome['data'])?>;
const expenseTrend = <?=json_encode($trendExpense['data'])?>;
new Chart(ctx, {
  type: 'bar',
  data: { labels: months, datasets: [{ label: 'Income', data: incomeTrend, backgroundColor: 'rgba(75,192,192,0.6)' }, { label: 'Expense', data: expenseTrend, backgroundColor: 'rgba(255,99,132,0.6)'}] },
  options: { responsive: true }
});
</script>
</body>
</html>
