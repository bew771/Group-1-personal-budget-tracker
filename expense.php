<?php require 'auth_check.php'; require 'db.php';
$error = '';
// Default expense sources
$defaultSources = ['Transport', 'Food', 'Cloth', 'Bill', 'Entertainment', 'Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_tx') {
    $amount = floatval($_POST['amount']);
    $rawCategory = $_POST['category_id'] ?? '';
    $customSource = trim($_POST['custom_source'] ?? '');
    $date = $_POST['date'] ?: date('Y-m-d');
    $note = trim($_POST['note']);

    $category_id = null;
    if ($rawCategory !== '') {
        if (strpos($rawCategory, 'source:') === 0) {
            $sourceName = substr($rawCategory, strlen('source:'));
            if ($sourceName === 'Other' && $customSource !== '') {
                $sourceName = $customSource;
            }
            // Find or create category for this user
            $findStmt = $pdo->prepare('SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ? LIMIT 1');
            $findStmt->execute([$_SESSION['user_id'], $sourceName, 'expense']);
            $found = $findStmt->fetchColumn();
            if ($found) {
                $category_id = (int)$found;
            } else {
                $ins = $pdo->prepare('INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)');
                $ins->execute([$_SESSION['user_id'], $sourceName, 'expense']);
                $category_id = (int)$pdo->lastInsertId();
            }
        } elseif (is_numeric($rawCategory)) {
            $category_id = (int)$rawCategory;
        } else {
            $category_id = null;
        }
    }

    if ($amount <= 0) { $error = 'Amount must be greater than zero.'; }
    else {
        $stmt = $pdo->prepare('INSERT INTO transactions (user_id, category_id, amount, type, note, date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $category_id, $amount, 'expense', $note, $date]);
        header('Location: expense.php'); exit;
    }
}
$cats = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ? AND type = ? ORDER BY name');
$cats->execute([$_SESSION['user_id'], 'expense']);
$expenseCats = $cats->fetchAll();
$txStmt = $pdo->prepare('SELECT t.id, t.amount, t.date, t.note, c.name as category FROM transactions t LEFT JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.type = ? ORDER BY t.date DESC LIMIT 50');
$txStmt->execute([$_SESSION['user_id'], 'expense']);
$transactions = $txStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Expense - Budget Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container mt-4">
  <h1>Expense</h1>
  <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post" class="row g-3 mb-4">
    <input type="hidden" name="action" value="add_tx">
    <div class="col-md-3"><input type="date" name="date" class="form-control" value="<?=date('Y-m-d')?>"></div>
    <div class="col-md-3">
      <select name="category_id" id="category_id" class="form-select">
        <option value="">Uncategorized</option>
        <optgroup label="Default Sources">
          <?php foreach ($defaultSources as $ds): ?>
            <option value="source:<?=htmlspecialchars($ds)?>"><?=htmlspecialchars($ds)?></option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Your Categories">
        <?php foreach($expenseCats as $c): ?>
          <option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option>
        <?php endforeach; ?>
        </optgroup>
      </select>
    </div>
    <div id="customSourceDiv" class="col-md-3" style="display:none">
      <input type="text" name="custom_source" id="custom_source" class="form-control" placeholder="Custom source (if Other)">
    </div>
    <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount"></div>
    <div class="col-md-2"><input type="text" name="note" class="form-control" placeholder="Note"></div>
    <div class="col-md-2"><button class="btn btn-danger">Add Expense</button></div>
  </form>

  <h3>Recent Expense</h3>
  <table class="table">
    <thead><tr><th>Date</th><th>Category</th><th>Note</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
      <?php foreach($transactions as $t): ?>
        <tr>
          <td><?=htmlspecialchars($t['date'])?></td>
          <td><?=htmlspecialchars($t['category'] ?? 'Uncategorized')?></td>
          <td><?=htmlspecialchars($t['note'])?></td>
          <td class="text-end"><?=number_format($t['amount'], 2)?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
  (function () {
    const catSelect = document.getElementById('category_id');
    const customDiv = document.getElementById('customSourceDiv');
    if (!catSelect || !customDiv) return;
    function toggle() {
      customDiv.style.display = catSelect.value === 'source:Other' ? 'block' : 'none';
    }
    catSelect.addEventListener('change', toggle);
    toggle();
  })();
</script>
</body>
</html>