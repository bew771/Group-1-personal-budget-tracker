<?php require 'auth_check.php'; require 'db.php';

$userId = $_SESSION['user_id'];

// Handle deletion
if (isset($_GET['delete_budget_id'])) {
  $stmt = $pdo->prepare('DELETE FROM budgets WHERE id = ? AND user_id = ?');
  $stmt->execute([$_GET['delete_budget_id'], $userId]);
  header('Location: budget.php');
  exit;
}

// Handle add budget form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_budget'])) {
  $month = $_POST['month'] ?? date('Y-m');
  $categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '0' ? intval($_POST['category_id']) : null;
  $amount = floatval($_POST['amount'] ?? 0);
  $stmt = $pdo->prepare('INSERT INTO budgets (user_id, category_id, month, amount) VALUES (?, ?, ?, ?)');
  $stmt->execute([$userId, $categoryId, $month, $amount]);
  header('Location: budget.php');
  exit;
}

// Load categories (expense types)
$catStmt = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ? AND type = ? ORDER BY name');
$catStmt->execute([$userId, 'expense']);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Load budgets for user
$budStmt = $pdo->prepare('SELECT b.*, c.name AS category_name FROM budgets b LEFT JOIN categories c ON b.category_id = c.id WHERE b.user_id = ? ORDER BY b.month DESC, c.name');
$budStmt->execute([$userId]);
$budgets = $budStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare chart data for budgets vs actual
$labels = [];
$budgetAmounts = [];
$actualAmounts = [];
foreach ($budgets as $b) {
  $labels[] = $b['category_name'] ? $b['category_name'] . ' (' . $b['month'] . ')' : 'All (' . $b['month'] . ')';
  $budgetAmounts[] = (float)$b['amount'];
  // compute actual expense for that month and category
  $monthStart = $b['month'] . '-01';
  $monthEnd = date('Y-m-t', strtotime($monthStart));
  if ($b['category_id']) {
    $actStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = ? AND category_id = ? AND date BETWEEN ? AND ?');
    $actStmt->execute([$userId, 'expense', $b['category_id'], $monthStart, $monthEnd]);
  } else {
    $actStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = ? AND date BETWEEN ? AND ?');
    $actStmt->execute([$userId, 'expense', $monthStart, $monthEnd]);
  }
  $actualAmounts[] = (float)$actStmt->fetchColumn();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Budget Planning - Budget Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container mt-4">
  <h1>Budget Planning</h1>
  <div class="row">
    <div class="col-md-5">
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Set Budget</h5>
          <form method="post">
            <div class="mb-3">
              <label class="form-label">Month</label>
              <input type="month" name="month" value="<?=date('Y-m')?>" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Category (optional)</label>
              <select name="category_id" class="form-select">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Amount</label>
              <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>
            <button class="btn btn-primary" name="add_budget">Save Budget</button>
          </form>
        </div>
      </div>
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Budget Warnings</h5>
          <ul class="list-group">
            <?php
            $warnings = [];
            foreach ($budgets as $b) {
              $budgetAmt = (float)$b['amount'];
              $actual = 0;
              // find corresponding actual using arrays above
              foreach ($budgets as $k=>$v) {
                if ($v['id'] == $b['id']) {
                  $actual = $actualAmounts[$k];
                }
              }
              $pct = $budgetAmt > 0 ? ($actual / $budgetAmt) * 100 : 0;
              if ($budgetAmt > 0 && $pct >= 90) {
                $warnings[] = ['budget' => $b, 'actual' => $actual, 'pct' => $pct];
              }
            }
            if (count($warnings) === 0) {
              echo '<li class="list-group-item">No warnings — all budgets are within limits.</li>';
            } else {
              foreach ($warnings as $w) {
                echo '<li class="list-group-item d-flex justify-content-between align-items-center">' . htmlspecialchars($w['budget']['category_name'] ?? 'All') . ' ' . htmlspecialchars($w['budget']['month']) . '<span class="badge bg-danger">' . round($w['pct']) . '%</span></li>';
              }
            }
            ?>
          </ul>
        </div>
      </div>
    </div>
    <div class="col-md-7">
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Budgets & Progress</h5>
          <table class="table table-sm">
            <thead><tr><th>Month</th><th>Category</th><th>Budget</th><th>Actual</th><th>Progress</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($budgets as $i => $b):
                $budgetAmt = (float)$b['amount'];
                $actual = $actualAmounts[$i] ?? 0;
                $pct = $budgetAmt > 0 ? min(100, ($actual / $budgetAmt) * 100) : 0;
              ?>
              <tr>
                <td><?=htmlspecialchars($b['month'])?></td>
                <td><?=htmlspecialchars($b['category_name'] ?? 'All')?></td>
                <td><?=number_format($budgetAmt,2)?></td>
                <td><?=number_format($actual,2)?></td>
                <td style="width: 30%">
                  <div class="progress" style="height: 16px">
                    <div class="progress-bar" role="progressbar" style="width: <?=$pct?>%" aria-valuenow="<?=$pct?>" aria-valuemin="0" aria-valuemax="100"><?=round($pct)?>%</div>
                  </div>
                </td>
                <td><a href="?delete_budget_id=<?=$b['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete budget?')">Delete</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Budget vs Actual</h5>
          <canvas id="budgetChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const budgetLabels = <?=json_encode($labels)?>;
  const budgetVals = <?=json_encode($budgetAmounts)?>;
  const actualVals = <?=json_encode($actualAmounts)?>;
  const ctx = document.getElementById('budgetChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: budgetLabels,
      datasets: [
        { label: 'Budget', data: budgetVals, backgroundColor: 'rgba(54,162,235,0.5)' },
        { label: 'Actual', data: actualVals, backgroundColor: 'rgba(255,99,132,0.5)' }
      ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });
</script>
</body>
</html>