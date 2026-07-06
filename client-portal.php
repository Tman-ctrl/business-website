<?php
require_once __DIR__ . '/portal_auth.php';

$loginError = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $loginError = 'Please enter both your email and password.';
    } elseif (!loginUser($email, $password)) {
        $loginError = 'Invalid email or password. Please try again.';
    } else {
        header('Location: client-portal.php');
        exit;
    }
}

$user = getCurrentUser();
$loggedIn = isLoggedIn();

$dashboardData = [
    'cleaningStatus' => 'On schedule',
    'nextVisit'      => '14 June 2026',
    'statuses'       => [
        ['label' => 'Current Building Clean', 'detail' => 'Completed 12 June 2026', 'status' => 'Done'],
        ['label' => 'Façade Inspection', 'detail' => 'Scheduled 14 June 2026', 'status' => 'Pending'],
        ['label' => 'Ongoing Maintenance Plan', 'detail' => 'Next check 30 June 2026', 'status' => 'Scheduled'],
    ],
    'reports' => [
        ['name' => 'Cleaning Report - 12 Jun 2026', 'href' => '#'],
        ['name' => 'Façade Inspection Summary', 'href' => '#'],
        ['name' => 'Completion Certificate', 'href' => '#'],
    ],
    'plan' => [
        'frequency' => 'Monthly',
        'type'      => 'Standard Maintenance',
        'notes'     => 'Includes external window cleaning, drone inspection, and service confirmation every 30 days.',
    ],
    'invoices' => [
        ['id' => 'INV-2026-014', 'date' => '12 Jun 2026', 'amount' => 'R 18 450', 'status' => 'Paid'],
        ['id' => 'INV-2026-009', 'date' => '10 May 2026', 'amount' => 'R 16 200', 'status' => 'Paid'],
        ['id' => 'INV-2026-003', 'date' => '05 Apr 2026', 'amount' => 'R 19 000', 'status' => 'Paid'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Client Portal – Dash Drones Window Cleaning</title>
  <style>
    :root {
      --bg: #06131f;
      --surface: rgba(255,255,255,0.08);
      --border: rgba(0,180,216,0.25);
      --text: #e6f5ff;
      --muted: #a3c7e6;
      --accent: #00d1ff;
      --radius: 22px;
      --shadow: 0 32px 80px rgba(0,0,0,0.35);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', Arial, sans-serif;
      background: linear-gradient(180deg, #0b1b2f 0%, #06131f 100%);
      color: var(--text);
    }
    a { color: inherit; text-decoration: none; }
    body::before {
      content: '';
      position: fixed; inset: 0;
      background: radial-gradient(circle at top, rgba(0,209,255,0.12), transparent 25%),
                  radial-gradient(circle at bottom right, rgba(0,209,255,0.08), transparent 20%);
      pointer-events: none;
    }
    nav {
      display: flex; align-items: center; justify-content: space-between;
      padding: 18px 32px; position: sticky; top: 0; z-index: 10;
      background: rgba(6,19,31,0.92); border-bottom: 1px solid rgba(255,255,255,0.05);
      backdrop-filter: blur(14px);
    }
    .nav-logo {
      font-size: 1.4rem; font-weight: 700;
      letter-spacing: -0.02em; color: #fff;
    }
    .nav-logo span { color: var(--accent); }
    .nav-links { display: flex; gap: 24px; align-items: center; }
    .nav-links a { color: var(--muted); font-size: 0.92rem; }
    .nav-links a.active,
    .nav-links a:hover { color: #fff; }
    .page-shell { max-width: 1180px; margin: 0 auto; padding: 40px 32px 80px; }
    .hero {
      display: grid; gap: 24px; padding: 32px 36px;
      background: rgba(255,255,255,0.04); border: 1px solid var(--border);
      border-radius: var(--radius); box-shadow: var(--shadow);
    }
    .hero .eyebrow { text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.76rem; color: var(--accent); margin-bottom: 14px; }
    .hero h1 { font-size: clamp(2rem, 3.5vw, 3.4rem); line-height: 1.05; margin: 0; }
    .hero p { color: var(--muted); max-width: 760px; font-size: 1rem; line-height: 1.7; }
    .hero-buttons { display: flex; flex-wrap: wrap; gap: 14px; }
    .btn-primary, .btn-secondary {
      display: inline-flex; align-items: center; justify-content: center;
      padding: 14px 28px; border-radius: 999px; border: 1px solid transparent;
      font-weight: 700; letter-spacing: 0.03em; transition: transform 0.2s, background 0.2s;
    }
    .btn-primary {
      background: linear-gradient(135deg, #00d1ff, #149ef8);
      color: #071827;
    }
    .btn-secondary {
      color: #fff; background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15);
    }
    .btn-primary:hover, .btn-secondary:hover { transform: translateY(-2px); }
    .content-grid {
      display: grid; grid-template-columns: 1.7fr 1fr; gap: 32px; margin-top: 32px;
    }
    .info-cards { display: grid; gap: 20px; }
    .card {
      padding: 26px 28px; border-radius: var(--radius);
      background: rgba(255,255,255,0.05); border: 1px solid var(--border);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
    }
    .card h3 { margin-top: 0; margin-bottom: 12px; font-size: 1.1rem; }
    .card p { color: var(--muted); line-height: 1.7; font-size: 0.96rem; }
    .portal-panel {
      position: sticky; top: 110px;
      padding: 32px; border-radius: var(--radius);
      background: rgba(255,255,255,0.05); border: 1px solid var(--border);
      box-shadow: var(--shadow);
    }
    .portal-panel h2 { margin-top: 0; margin-bottom: 10px; }
    .portal-panel .panel-sub { color: var(--muted); margin-bottom: 24px; line-height: 1.75; }
    .portal-dashboard { display: <?= $loggedIn ? 'block' : 'none' ?>; }
    .dashboard-header { margin-bottom: 22px; }
    .dashboard-welcome { font-size: 1.2rem; margin: 0 0 8px; }
    .dashboard-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .summary-card {
      background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);
      border-radius: 18px; padding: 16px 18px;
    }
    .summary-card strong { display: block; margin-bottom: 8px; color: var(--accent); }
    .dashboard-section { margin-top: 18px; }
    .dashboard-section h3 { margin-bottom: 12px; }
    .status-list, .report-list { list-style: none; padding: 0; margin: 0; }
    .status-list li, .report-list li { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 14px 16px; margin-bottom: 12px; }
    .status-label { display: block; font-weight: 700; margin-bottom: 6px; }
    .status-meta { color: var(--muted); font-size: 0.95rem; }
    .report-list a { color: var(--accent); font-weight: 600; }
    .plan-card { padding: 18px 20px; border-radius: 18px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); }
    .invoice-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    .invoice-table th, .invoice-table td { text-align: left; padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.08); }
    .invoice-table th { color: var(--accent); font-weight: 700; }
    .status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: rgba(0,209,255,0.12); color: #d7f7ff; font-size: 0.85rem; }
    .sign-out { margin-top: 22px; width: 100%; padding: 14px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.18); background: transparent; color: var(--text); cursor: pointer; }
    .form-group { margin-bottom: 18px; }
    label { display: block; color: var(--muted); font-size: 0.8rem; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 8px; }
    input {
      width: 100%; padding: 14px 16px; border: 1px solid rgba(255,255,255,0.15);
      border-radius: 14px; background: rgba(255,255,255,0.05); color: #fff;
      font-size: 0.98rem;
    }
    input::placeholder { color: rgba(255,255,255,0.5); }
    .form-submit {
      width: 100%; padding: 14px 16px; border: none;
      border-radius: 14px; background: linear-gradient(135deg, #00d1ff, #149ef8);
      color: #071827; font-weight: 700; cursor: pointer;
    }
    .form-submit:hover { opacity: 0.96; }
    .form-msg { display: none; margin-top: 20px; padding: 14px 16px; border-radius: 14px; font-size: 0.95rem; }
    .form-msg.success { background: rgba(0,201,112,0.12); border: 1px solid rgba(0,201,112,0.3); color: #c8ffde; }
    .form-msg.error { background: rgba(255,103,103,0.12); border: 1px solid rgba(255,103,103,0.3); color: #ffd1d1; }
    .note { margin-top: 22px; color: var(--muted); font-size: 0.92rem; line-height: 1.7; }
    .highlight-section { margin-top: 24px; }
    .highlight-section h2 { margin-bottom: 18px; }
    .highlight-grid { display: grid; gap: 18px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .highlight-card { padding: 22px 24px; border-radius: var(--radius); background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); }
    .highlight-card h4 { margin-top: 0; color: var(--accent); }
    .highlight-card p { color: var(--muted); line-height: 1.7; }
    footer { margin-top: 40px; padding: 24px 0; text-align: center; color: var(--muted); }
    footer nav { display: flex; justify-content: center; flex-wrap: wrap; gap: 18px; margin-top: 14px; }
    footer a { color: var(--muted); font-size: 0.9rem; }
    @media (max-width: 900px) {
      nav { flex-wrap: wrap; gap: 14px; }
      .content-grid { grid-template-columns: 1fr; }
      .portal-dashboard { display: block; }
      .highlight-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 620px) {
      .page-shell { padding: 24px 18px 60px; }
      .hero, .portal-panel, .card, .highlight-card { padding: 22px; }
      .nav-links { display: none; }
      .hero h1 { font-size: 2.1rem; }
    }
  </style>
</head>
<body>
  <nav>
    <a class="nav-logo" href="index.html">Dash<span>.</span>Drones</a>
    <div class="nav-links">
      <a href="index.html">Home</a>
      <a href="about.html">About</a>
      <a href="services.html">Services</a>
      <a href="contact.html">Contact</a>
      <a href="client-portal.php" class="active">Client Portal</a>
    </div>
    <a class="btn-secondary" href="contact.html">Book a Clean</a>
  </nav>

  <div class="page-shell">
    <section class="hero">
      <span class="eyebrow">Client Portal</span>
      <h1>Secure portal access for property managers, clients, and facility teams.</h1>
      <p>Login to review cleaning schedules, invoices, fa&ccedil;ade inspection files, and service updates for your site.</p>
      <div class="hero-buttons">
        <a class="btn-primary" href="#portal-login">Sign In</a>
        <a class="btn-secondary" href="contact.html">Request Support</a>
      </div>
    </section>

    <div class="content-grid">
      <div class="info-cards">
        <div class="card">
          <h3>Project Overview</h3>
          <p>Track the status of ongoing cleans, see the last completed service, and view any alerts from your maintenance team.</p>
        </div>
        <div class="card">
          <h3>Invoice History</h3>
          <p>Download invoices, payment records, and quote approvals in one secure location.</p>
        </div>
        <div class="card">
          <h3>Inspection Reports</h3>
          <p>Access fa&ccedil;ade inspection reports, drone imagery, and compliance documents whenever you need them.</p>
        </div>
      </div>

      <aside class="portal-panel" id="portal-login">
        <?php if (!$loggedIn): ?>
          <div id="portalFormWrapper">
            <h2>Client Login</h2>
            <p class="panel-sub">Enter your email and password to access the client portal.</p>
            <?php if ($loginError): ?>
              <div class="form-msg error" style="display: block; margin-bottom: 18px;"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form id="clientPortalForm" method="post">
              <div class="form-group">
                <label for="clientEmail">Email address</label>
                <input id="clientEmail" type="email" name="email" placeholder="client@company.co.za" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
              <div class="form-group">
                <label for="clientPassword">Password</label>
                <input id="clientPassword" type="password" name="password" placeholder="Enter your password" required>
              </div>
              <button type="submit" class="form-submit">Sign In</button>
            </form>
            <p class="note"><strong>Test accounts:</strong> client@company.co.za / Client2026! or estate.manager@example.com / Estate2026!</p>
          </div>
        <?php endif; ?>

        <?php if ($loggedIn): ?>
          <div id="portalDashboard" class="portal-dashboard">
            <div class="dashboard-header">
              <p class="eyebrow">Dashboard</p>
              <h2 class="dashboard-welcome">Hello, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h2>
              <p class="panel-sub">Your service summary is displayed below.</p>
            </div>
            <div class="dashboard-summary">
              <div class="summary-card">
                <strong>Cleaning status</strong>
                <div><?= htmlspecialchars($dashboardData['cleaningStatus'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="summary-card">
                <strong>Next visit</strong>
                <div><?= htmlspecialchars($dashboardData['nextVisit'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
            <div class="dashboard-section">
              <h3>Cleaning Status</h3>
              <ul class="status-list">
                <?php foreach ($dashboardData['statuses'] as $status): ?>
                  <li>
                    <span class="status-label"><?= htmlspecialchars($status['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="status-meta"><?= htmlspecialchars($status['detail'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="status-pill"><?= htmlspecialchars($status['status'], ENT_QUOTES, 'UTF-8') ?></div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="dashboard-section">
              <h3>Documents & Reports</h3>
              <ul class="report-list">
                <?php foreach ($dashboardData['reports'] as $report): ?>
                  <li><a href="<?= htmlspecialchars($report['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($report['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="dashboard-section">
              <h3>Recurring Plan Details</h3>
              <div class="plan-card">
                <strong>Plan type:</strong> <?= htmlspecialchars($dashboardData['plan']['type'], ENT_QUOTES, 'UTF-8') ?><br>
                <strong>Frequency:</strong> <?= htmlspecialchars($dashboardData['plan']['frequency'], ENT_QUOTES, 'UTF-8') ?><br>
                <strong>Notes:</strong> <?= htmlspecialchars($dashboardData['plan']['notes'], ENT_QUOTES, 'UTF-8') ?>
              </div>
            </div>
            <div class="dashboard-section">
              <h3>Invoice History</h3>
              <table class="invoice-table">
                <thead>
                  <tr>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($dashboardData['invoices'] as $invoice): ?>
                    <tr>
                      <td><?= htmlspecialchars($invoice['id'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars($invoice['date'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars($invoice['amount'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars($invoice['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <a href="logout.php" class="sign-out">Sign Out</a>
          </div>
        <?php endif; ?>
      </aside>
    </div>

    <section class="highlight-section">
      <h2>Client Portal features</h2>
      <div class="highlight-grid">
        <div class="highlight-card">
          <h4>Cleaning status</h4>
          <p>See when each cleaning is scheduled, completed, and whether any follow-up work is required.</p>
        </div>
        <div class="highlight-card">
          <h4>Documents & reports</h4>
          <p>Download inspection reports, certificates, and before/after job summaries for every visit.</p>
        </div>
        <div class="highlight-card">
          <h4>Recurring plan details</h4>
          <p>View your service plan, next visit date, and recommended maintenance cycle for your building.</p>
        </div>
      </div>
    </section>
  </div>

  <footer>
    <div>&copy; 2025 Dash Drones (Pty) Ltd | Kempton Park, Gauteng</div>
    <nav>
      <a href="index.html">Home</a>
      <a href="about.html">About</a>
      <a href="services.html">Services</a>
      <a href="contact.html">Contact</a>
      <a href="client-portal.php">Client Portal</a>
      <a href="terms-of-service.html">Terms of Service</a>
      <a href="privacy-policy.html">Privacy Policy</a>
    </nav>
  </footer>
</body>
</html>
