<?php
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/lib/UserManagement.php';
Application::init();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

require_csrf();

$me = current_user();
$userId = (int)($_GET['user_id'] ?? $me['id']);

// Verify user exists
$user = UserManagement::findById($userId);
if (!$user) {
    http_response_code(404);
    exit('User not found');
}

// Only allow users to edit their own profile (or admins can edit anyone)
if ($userId !== (int)$me['id'] && empty($me['is_admin'])) {
    http_response_code(403);
    exit('Forbidden');
}

// Collect form data
$fields = [
    'preferred_name' => trim($_POST['preferred_name'] ?? ''),
    'street1' => trim($_POST['street1'] ?? ''),
    'street2' => trim($_POST['street2'] ?? ''),
    'city' => trim($_POST['city'] ?? ''),
    'state' => trim($_POST['state'] ?? ''),
    'zip' => trim($_POST['zip'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'has_owned_a_dog' => $_POST['has_owned_a_dog'] ?? '',
    'has_children_at_home' => $_POST['has_children_at_home'] ?? '',
    'has_outdoor_space' => $_POST['has_outdoor_space'] ?? '',
];

try {
    $ctx = UserContext::getLoggedInUserContext();
    $ok = UserManagement::updateUserProfileFields($ctx, $userId, $fields);
    
    if ($ok) {
        header('Location: /profile.php?user_id=' . $userId . '&msg=' . urlencode('Profile updated successfully.'));
        exit;
    } else {
        header('Location: /profile_edit.php?user_id=' . $userId . '&err=' . urlencode('Failed to update profile.'));
        exit;
    }
} catch (Throwable $e) {
    header('Location: /profile_edit.php?user_id=' . $userId . '&err=' . urlencode('Error updating profile: ' . $e->getMessage()));
    exit;
}
