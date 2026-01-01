<?php
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/lib/UserManagement.php';
require_once __DIR__ . '/lib/Files.php';
Application::init();
require_login();

$me = current_user();
$userId = (int)($_GET['user_id'] ?? $me['id']);

// Get the user profile
$user = UserManagement::findById($userId);
if (!$user) {
    http_response_code(404);
    exit('User not found');
}

// Only allow users to view their own profile (or admins can view anyone)
if ($userId !== (int)$me['id'] && empty($me['is_admin'])) {
    http_response_code(403);
    exit('Forbidden');
}

$displayName = trim($user['preferred_name'] ?? '') ?: $user['first_name'];
$fullName = $displayName . ' ' . $user['last_name'];

// Format address as one line
$addressParts = array_filter([
    $user['street1'] ?? null,
    $user['street2'] ?? null,
    $user['city'] ?? null,
    $user['state'] ?? null,
    $user['zip'] ?? null
]);
$address = !empty($addressParts) ? implode(', ', $addressParts) : null;

$phone = $user['phone'] ?? null;

// Attributes - only show if explicitly set to 1 (true)
$attributes = [];
if (!empty($user['has_owned_a_dog'])) $attributes[] = 'Has owned a dog';
if (!empty($user['has_children_at_home'])) $attributes[] = 'Has children at home';
if (!empty($user['has_outdoor_space'])) $attributes[] = 'Has outdoor space';

$msg = $_GET['msg'] ?? null;
$err = $_GET['err'] ?? null;

$userName = trim((string)($user['first_name'] ?? '').' '.(string)($user['last_name'] ?? ''));
$userInitials = strtoupper((string)substr((string)($user['first_name'] ?? ''),0,1).(string)substr((string)($user['last_name'] ?? ''),0,1));
$userPhotoUrl = Files::profilePhotoUrl($user['photo_public_file_id'] ?? null, 120);
$canEdit = ($userId === (int)$me['id'] || !empty($me['is_admin']));

header_html('Profile: ' . h($fullName));
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <h2><?=h($fullName)?></h2>
    <a href="/profile_edit.php?user_id=<?=(int)$userId?>" class="button">Edit</a>
</div>

<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card">
    <div style="display:flex;gap:2rem;flex-wrap:wrap-reverse;">
        <!-- Main content on left -->
        <div style="flex:1;min-width:250px;">
            <?php if ($address || $phone || !empty($attributes)): ?>
                <?php if ($address): ?>
                    <div style="margin-bottom:1rem;">
                        <strong>Address:</strong><br>
                        <?=h($address)?>
                    </div>
                <?php endif; ?>
                
                <?php if ($phone): ?>
                    <div style="margin-bottom:1rem;">
                        <strong>Phone:</strong><br>
                        <?=h(format_phone($phone))?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($attributes)): ?>
                    <div>
                        <strong>Attributes:</strong>
                        <ul style="margin-top:0.5rem;">
                            <?php foreach ($attributes as $attr): ?>
                                <li><?=h($attr)?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="color:#666;font-style:italic;">No profile information has been added yet.</p>
                <p><a href="/profile_edit.php?user_id=<?=(int)$userId?>">Add profile information</a></p>
            <?php endif; ?>
        </div>

        <!-- Photo section on right -->
        <div style="text-align:center;min-width:150px;margin:0 auto;">
            <?php if ($userPhotoUrl !== ''): ?>
                <img class="avatar" src="<?= h($userPhotoUrl) ?>" alt="<?= h($userName) ?>" style="width:120px;height:120px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">
            <?php else: ?>
                <div class="avatar avatar-initials" aria-hidden="true" style="width:120px;height:120px;border-radius:50%;background:#007bff;color:white;display:inline-flex;align-items:center;justify-content:center;font-size:32px;font-weight:500;margin-bottom:1rem;"><?= h($userInitials) ?></div>
            <?php endif; ?>
            
            <?php if ($canEdit): ?>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <button onclick="openPhotoModal()" class="button" style="width:100%;">Replace Photo</button>
                    <?php if ($userPhotoUrl !== ''): ?>
                        <form method="post" action="/upload_photo.php?user_id=<?= (int)$userId ?>&return_to=/profile.php?user_id=<?= (int)$userId ?>" onsubmit="return confirm('Remove this photo?');">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="button" style="width:100%;">Remove Photo</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Photo Upload Modal -->
<div id="photoUploadModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;padding:2rem;border-radius:8px;max-width:500px;width:90%;">
        <h3 style="margin-top:0;">Upload Profile Photo</h3>
        <form id="photoUploadForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <div style="margin-bottom:1rem;">
                <label style="display:block;margin-bottom:0.5rem;">Choose a photo:</label>
                <input type="file" name="photo" accept="image/*" required style="width:100%;">
            </div>
            <div id="uploadStatus" style="margin-bottom:1rem;"></div>
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                <button type="button" onclick="closePhotoModal()" class="button">Cancel</button>
                <button type="submit" class="button primary" id="uploadBtn">Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPhotoModal() {
    document.getElementById('photoUploadModal').style.display = 'flex';
}

function closePhotoModal() {
    document.getElementById('photoUploadModal').style.display = 'none';
    document.getElementById('photoUploadForm').reset();
    document.getElementById('uploadStatus').innerHTML = '';
}

document.getElementById('photoUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var form = this;
    var uploadBtn = document.getElementById('uploadBtn');
    var statusDiv = document.getElementById('uploadStatus');
    
    // Disable button during upload
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';
    statusDiv.innerHTML = '<p style="color:#666;">Uploading photo...</p>';
    
    // Create FormData
    var formData = new FormData(form);
    
    // AJAX upload
    fetch('/upload_photo.php?user_id=<?= (int)$userId ?>&return_to=/profile.php?user_id=<?= (int)$userId ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (response.ok && response.redirected) {
            // Success - reload the page
            window.location.href = response.url;
        } else {
            throw new Error('Upload failed');
        }
    })
    .catch(function(error) {
        statusDiv.innerHTML = '<p style="color:#d32f2f;">Upload failed. Please try again.</p>';
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload';
    });
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
    }
});

// Close modal on background click
document.getElementById('photoUploadModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePhotoModal();
    }
});
</script>

<?php footer_html(); ?>
