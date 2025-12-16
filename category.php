<?php require 'auth_check.php'; require 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  if ($action === 'add_category') {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    if (empty($name) || !in_array($type, ['income','expense'])) {
      $error = 'Name and type are required.';
    } else {
      $stmt = $pdo->prepare('INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)');
      $stmt->execute([$_SESSION['user_id'], $name, $type]);
      header('Location: category.php'); exit;
    }
  } elseif ($action === 'edit_category') {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    if (empty($name) || !in_array($type, ['income','expense'])) {
      $error = 'Name and type are required for edit.';
    } else {
      $stmt = $pdo->prepare('UPDATE categories SET name = ?, type = ? WHERE id = ? AND user_id = ?');
      $stmt->execute([$name, $type, $id, $_SESSION['user_id']]);
      header('Location: category.php'); exit;
    }
  } elseif ($action === 'delete_category') {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    header('Location: category.php'); exit;
  }
}
$catStmt = $pdo->prepare('SELECT id, name, type FROM categories WHERE user_id = ? ORDER BY name');
$catStmt->execute([$_SESSION['user_id']]);
$categories = $catStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Category - Budget Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container mt-4">
  <h1>Categories</h1>
  <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post" class="row g-3"> 
    <input type="hidden" name="action" value="add_category">
    <div class="col-md-6">
      <input type="text" name="name" class="form-control" placeholder="Category name" required>
    </div>
    <div class="col-md-3">
      <select name="type" class="form-select">
        <option value="expense">Expense</option>
        <option value="income">Income</option>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-primary">Add Category</button>
    </div>
  </form>

  <hr>
  <h3>Your Categories</h3>
  <?php if (empty($categories)): ?>
    <p>No categories yet.</p>
  <?php else: ?>
    <ul class="list-group">
      <?php foreach ($categories as $cat): ?>
        <li class="list-group-item">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong><?=htmlspecialchars($cat['name'])?></strong>
              <span class="badge bg-secondary ms-2"><?=htmlspecialchars($cat['type'])?></span>
            </div>
            <div>
              <button class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="collapse" data-bs-target="#edit-<?= $cat['id'] ?>">Edit</button>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </div>
          </div>

          <div class="collapse mt-3" id="edit-<?= $cat['id'] ?>">
            <form method="post" class="row g-2">
              <input type="hidden" name="action" value="edit_category">
              <input type="hidden" name="id" value="<?= $cat['id'] ?>">
              <div class="col-md-6">
                <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($cat['name'])?>" required>
              </div>
              <div class="col-md-3">
                <select name="type" class="form-select">
                  <option value="expense" <?= $cat['type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
                  <option value="income" <?= $cat['type'] === 'income' ? 'selected' : '' ?>>Income</option>
                </select>
              </div>
              <div class="col-md-3">
                <button class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-secondary ms-2" data-bs-toggle="collapse" data-bs-target="#edit-<?= $cat['id'] ?>">Cancel</button>
              </div>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>