<?php
// Vicidial Secure Access: the page a user opens to whitelist their IP address.
// Shows whether this IP is allowed (red / green), the login form, and the result.
// The login itself is handled by inc/code.php.

require __DIR__ . '/inc/lib.php';
ld_session_start();
ld_security_headers();

$config = ld_config();
$now    = time();
$ip     = ld_client_ip();
$state  = $ip !== '' ? ld_allow_state($ip) : array('allowed' => false, 'expires' => 0);
$tries  = $ip !== '' ? ld_attempts($ip) : array('count' => 0, 'locked_for' => 0);
$locked = $tries['locked_for'] > 0;

$flash = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : '';
unset($_SESSION['flash']);

// Set by inc/code.php right after a correct login; shown once.
$granted = null;
if (isset($_SESSION['granted']) && is_array($_SESSION['granted'])) {
    if ($now - (int) $_SESSION['granted']['at'] <= 30 && $state['allowed']) {
        $granted = $_SESSION['granted'];
    }
    unset($_SESSION['granted']);
}

function ld_when($ts)
{
    return date('j M Y, H:i', $ts);
}

// Status line under the IP.
if ($ip === '') {
    $status = 'Could not read your IP address';
} elseif (!$state['allowed']) {
    $status = 'Not allowed yet';
} elseif ($state['expires'] === 0) {
    $status = 'Allowed';
} else {
    $status = 'Allowed until ' . ld_when($state['expires']);
    if (!$granted) {
        $days = (int) ceil(($state['expires'] - $now) / 86400);
        $status .= ' (' . $days . ($days === 1 ? ' day left)' : ' days left)');
    }
}

// Message above the form.
$message = '';
if ($flash === 'bad_login') {
    $left = max(0, (int) $config['max_tries'] - $tries['count']);
    $message = 'Wrong username or password. ' . $left . ($left === 1 ? ' try left.' : ' tries left.');
} elseif ($flash === 'expired') {
    $message = 'This page was open too long. Please sign in again.';
} elseif ($flash === 'unavailable') {
    $message = 'The login check is not working right now. Tell your admin.';
} elseif ($flash === 'firewall') {
    $message = 'Your login was correct, but the firewall could not be updated. Tell your admin.';
} elseif ($flash === 'noip') {
    $message = 'Could not read your IP address. Tell your admin.';
}

$redirect = ld_redirect_url();
$renew    = $state['allowed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0c111d">
<meta name="robots" content="noindex, nofollow">
<?php if ($granted): ?>
<meta http-equiv="refresh" content="3;url=<?php echo ld_e($redirect); ?>">
<?php endif; ?>
<title>Vicidial Secure Access</title>
<link rel="stylesheet" href="css/access.css">
</head>
<body class="<?php echo $state['allowed'] ? 'on' : 'off'; ?>">

<header class="hero">
  <h1 class="h">Vicidial Secure Access</h1>
  <span class="ip num"><?php echo $ip !== '' ? ld_e($ip) : 'Unknown'; ?></span>
  <div class="status" role="status"><?php echo ld_e($status); ?></div>
</header>

<main class="stage">
  <div class="card">
<?php if ($granted): ?>
    <div class="ok">
      <div class="tick" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
      </div>
      <h2 class="h">Access granted</h2>
      <div class="facts">
        <div><span>User</span><span><?php echo ld_e($granted['user']); ?></span></div>
        <div><span>Allowed until</span><span class="num"><?php echo (int) $granted['expires'] > 0 ? ld_e(ld_when((int) $granted['expires'])) : 'No expiry'; ?></span></div>
      </div>
      <p class="redir">Opening Vicidial in <span class="num" id="redirect" data-seconds="3" data-url="<?php echo ld_e($redirect); ?>">3</span> seconds</p>
      <div class="bar"><span></span></div>
    </div>
<?php else: ?>
    <h2 class="t h"><?php echo $renew ? 'Log in again to extend your access' : 'Log in to whitelist your IP address'; ?></h2>
<?php if ($locked): ?>
    <p class="msg warn" role="alert">Too many failed tries from this IP. Try again in <span class="num" id="lock" data-seconds="<?php echo (int) $tries['locked_for']; ?>"><?php echo sprintf('%d:%02d', floor($tries['locked_for'] / 60), $tries['locked_for'] % 60); ?></span>.</p>
<?php elseif ($message !== ''): ?>
    <p class="msg err" role="alert"><?php echo ld_e($message); ?></p>
<?php endif; ?>
    <form action="inc/code.php" method="post" id="login">
      <input type="hidden" name="csrf" value="<?php echo ld_e(ld_csrf_token()); ?>">
      <div class="field">
        <label for="user">Username</label>
        <input id="user" name="user" type="text" maxlength="20" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" enterkeyhint="next" required>
      </div>
      <div class="field pw">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" maxlength="100" autocomplete="current-password" enterkeyhint="go" required>
        <button type="button" class="reveal" id="reveal" aria-label="Show password">Show</button>
      </div>
      <button class="btn" type="submit"<?php echo ($locked || $ip === '') ? ' disabled' : ''; ?>><?php echo $renew ? 'Renew for ' . (int) $config['allow_days'] . ' days' : 'Sign in and allow'; ?></button>
    </form>
    <p class="note">Access ends after <?php echo (int) $config['allow_days']; ?> days. Sign in here again to renew it.</p>
<?php endif; ?>
  </div>
</main>

<footer class="foot"><span>Authorised users only</span><span>All sign-ins are logged</span></footer>

<script src="js/access.js"></script>
</body>
</html>
