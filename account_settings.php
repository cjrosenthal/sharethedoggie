<?php
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/lib/UserManagement.php';
require_once __DIR__ . '/lib/Files.php';
Application::init();
require_login();

$me = current_user();
$isAdmin = !empty($me['is_admin']);

$err = null;
$msg = null;

// Helper function
function nn($v) { 
    $v = is_string($v) ? trim($v) : $v; 
    return ($v === '' ? null : $v); 
}

// Handle POST actions
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if ($action === 'update_profile') {
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        $errors = [];
        if ($first === '') $errors[] = 'First name is required.';
        if ($last === '')  $errors[] = 'Last name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

        if (empty($errors)) {
            try {
                $ctx = UserContext::getLoggedInUserContext();
                $ok = UserManagement::updateUserProfile($ctx, (int)$me['id'], $first, $last, $email);
                if ($ok) {
                    $msg = 'Profile updated.';
                    // Refresh $me
                    $me = UserManagement::findById((int)$me['id']) ?: $me;
                } else {
                    $err = 'Failed to update profile.';
                }
            } catch (Throwable $e) {
                $err = 'Error updating profile: ' . $e->getMessage();
            }
        } else {
            $err = implode(' ', $errors);
        }
    } elseif ($action === 'update_profile_types') {
        $ownerEnabled = !empty($_POST['owner_profile_enabled']);
        $borrowerEnabled = !empty($_POST['borrower_profile_enabled']);

        try {
            $ctx = UserContext::getLoggedInUserContext();
            $ok = UserManagement::updateProfileTypes($ctx, (int)$me['id'], $ownerEnabled, $borrowerEnabled);
            if ($ok) {
                $msg = 'Enabled features updated.';
                // Refresh $me
                $me = UserManagement::findById((int)$me['id']) ?: $me;
            } else {
                $err = 'Failed to update enabled features.';
            }
        } catch (Throwable $e) {
            $err = 'Error updating enabled features: ' . $e->getMessage();
        }
    }
}

header_html('Account Settings');
?>
<h2>Account Settings</h2>
<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card">
  <h3>Personal Information</h3>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="action" value="update_profile">
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>First name
        <input type="text" name="first_name" value="<?=h($me['first_name'])?>" required>
      </label>
      <label>Last name
        <input type="text" name="last_name" value="<?=h($me['last_name'])?>" required>
      </label>
      <label>Email
        <input type="email" name="email" value="<?=h($me['email'])?>" required>
      </label>
    </div>

    <div class="actions">
      <button class="primary">Save Profile</button>
      <a class="button" href="/change_password.php">Change Password</a>
    </div>
  </form>
</div>

<div class="card">
  <h3>Enabled Features</h3>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="action" value="update_profile_types">
    
    <label class="inline">
      <input type="checkbox" name="owner_profile_enabled" value="1" <?=!empty($me['owner_profile_enabled']) ? 'checked' : ''?>>
      Enable Owner Profile - I want to lend my dog to borrowers
    </label>
    
    <label class="inline">
      <input type="checkbox" name="borrower_profile_enabled" value="1" <?=!empty($me['borrower_profile_enabled']) ? 'checked' : ''?>>
      Enable Borrower Profile - I want to borrow dogs from owners
    </label>

    <div class="actions">
      <button class="primary">Save Enabled Features</button>
    </div>
  </form>
</div>

<?php footer_html(); ?>
