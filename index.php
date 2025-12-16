<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Personal Budget Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 text-center">
  <h1>Personal Budget Tracker</h1>
  <p class="lead">Manage income, expenses, categories and budgets.</p>
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    <a href="logout.php" class="btn btn-outline-secondary">Logout</a>
  <?php else: ?>
    <a href="register.php" class="btn btn-primary">Sign Up</a>
    <a href="login.php" class="btn btn-outline-secondary">Login</a>
  <?php endif; ?>
</div>
</body>
</html>
