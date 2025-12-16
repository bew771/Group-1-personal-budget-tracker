<?php // Small nav partial used by interior pages
require 'auth_check.php'; ?>
<style>
  /* Page background: blend of white, light blue and light green */
  html, body { height: 100%; }
  body {
    background: linear-gradient(135deg, #ffffff 0%, #d6edff 45%, #dff6e9 100%);
    background-attachment: fixed;
  }
  /* Nav button styles: green-white mixed for action buttons, red for logout */
  .navbar-nav .nav-link.action-btn,
  .navbar-nav a.nav-link[href="income.php"],
  .navbar-nav a.nav-link[href="expense.php"],
  .navbar-nav a.nav-link[href="category.php"],
  .navbar-nav a.nav-link[href="reports.php"],
  .navbar-nav a.nav-link[href="budget.php"] {
    background: linear-gradient(180deg, #f6fff6 0%, #e6fff0 60%);
    color: #0b5d2d !important;
    border-radius: .35rem;
    padding: .375rem .6rem;
    margin-left: .35rem;
    font-weight: 600;
  }
  .navbar-nav .nav-link.action-btn:hover,
  .navbar-nav a.nav-link[href="income.php"]:hover,
  .navbar-nav a.nav-link[href="expense.php"]:hover,
  .navbar-nav a.nav-link[href="category.php"]:hover,
  .navbar-nav a.nav-link[href="reports.php"]:hover,
  .navbar-nav a.nav-link[href="budget.php"]:hover {
    background: linear-gradient(180deg, #e9fff0 0%, #d8ffdf 60%);
    color: #063a20 !important;
    text-decoration: none;
  }
  .navbar-nav .nav-link.logout-btn {
    background: #dc3545;
    color: #fff !important;
    border-radius: .35rem;
    padding: .375rem .6rem;
    margin-left: .5rem;
    font-weight: 600;
  }
  .navbar-nav .nav-link.logout-btn:hover { background:#c82333; color:#fff !important; }
</style>
<nav class="navbar navbar-expand-md navbar-light bg-light">
  <div class="container">
    <button class="btn btn-outline-secondary me-2 d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="true" aria-label="Toggle sidebar">
      <i class="bi bi-list"></i>
    </button>
    <a class="navbar-brand" href="dashboard.php">Budget Tracker</a>
    <div class="collapse navbar-collapse" id="navbars">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link action-btn" href="income.php">Income</a></li>
        <li class="nav-item"><a class="nav-link action-btn" href="expense.php">Expense</a></li>
        <li class="nav-item"><a class="nav-link action-btn" href="category.php">Category</a></li>
        <li class="nav-item"><a class="nav-link action-btn" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link action-btn" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link logout-btn" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
