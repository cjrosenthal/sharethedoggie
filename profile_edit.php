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

// Only allow users to edit their own profile (or admins can edit anyone)
if ($userId !== (int)$me['id'] && empty($me['is_admin'])) {
    http_response_code(403);
    exit('Forbidden');
}

$displayName = trim($user['preferred_name'] ?? '') ?: $user['first_name'];
$fullName = $displayName . ' ' . $user['last_name'];

$err = $_GET['err'] ?? null;
$msg = null;

// Handle messages from upload_photo redirect
if (isset($_GET['uploaded'])) { $msg = 'Photo uploaded successfully.'; }
if (isset($_GET['deleted'])) { $msg = 'Photo removed successfully.'; }

$userName = trim((string)($user['first_name'] ?? '').' '.(string)($user['last_name'] ?? ''));
$userInitials = strtoupper((string)substr((string)($user['first_name'] ?? ''),0,1).(string)substr((string)($user['last_name'] ?? ''),0,1));
$userPhotoUrl = Files::profilePhotoUrl($user['photo_public_file_id'] ?? null, 120);

header_html('Edit Profile: ' . h($fullName));
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <h2><?=h($fullName)?></h2>
    <div style="display:flex;gap:0.5rem;">
        <button type="submit" form="profileEditForm" class="primary">Save</button>
        <a href="/profile.php?user_id=<?=(int)$userId?>" class="button">View</a>
    </div>
</div>

<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<form id="profileEditForm" method="post" action="/profile_edit_eval.php?user_id=<?=(int)$userId?>" class="stack">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    
    <div class="card">
        <div style="display:flex;gap:2rem;flex-wrap:wrap-reverse;">
            <!-- Basic Information on left -->
            <div style="flex:1;min-width:250px;">
                <h3 style="margin-top:0;">Basic Information</h3>
                <div class="stack">
                    <label>Preferred Name
                        <input type="text" name="preferred_name" value="<?=h($user['preferred_name'] ?? '')?>" placeholder="Leave blank to use first name">
                    </label>
                    <label>Phone Number
                        <input type="tel" name="phone" value="<?=h($user['phone'] ?? '')?>">
                    </label>
                    <label>Description
                        <textarea name="description" rows="4" placeholder="Tell us about yourself..."><?=h($user['description'] ?? '')?></textarea>
                    </label>
                </div>
            </div>

            <!-- Photo section on right -->
            <div style="text-align:center;min-width:150px;margin:0 auto;">
                <?php if ($userPhotoUrl !== ''): ?>
                    <img class="avatar" src="<?= h($userPhotoUrl) ?>" alt="<?= h($userName) ?>" style="width:120px;height:120px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">
                <?php else: ?>
                    <div class="avatar avatar-initials" aria-hidden="true" style="width:120px;height:120px;border-radius:50%;background:#007bff;color:white;display:inline-flex;align-items:center;justify-content:center;font-size:32px;font-weight:500;margin-bottom:1rem;"><?= h($userInitials) ?></div>
                <?php endif; ?>
                
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <button type="button" onclick="openPhotoModal()" class="button" style="width:100%;">Replace Photo</button>
                    <?php if ($userPhotoUrl !== ''): ?>
                        <button type="button" onclick="removePhoto()" class="button" style="width:100%;">Remove Photo</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Address</h3>
        <div class="stack">
            <label>Street Address Line 1
                <input type="text" name="street1" value="<?=h($user['street1'] ?? '')?>">
            </label>
            <label>Street Address Line 2
                <input type="text" name="street2" value="<?=h($user['street2'] ?? '')?>">
            </label>
            <div class="grid" style="grid-template-columns:2fr 1fr 1fr;gap:12px;">
                <label>City
                    <input type="text" name="city" value="<?=h($user['city'] ?? '')?>">
                </label>
                <label>State
                    <input type="text" name="state" value="<?=h($user['state'] ?? '')?>">
                </label>
                <label>ZIP
                    <input type="text" name="zip" value="<?=h($user['zip'] ?? '')?>">
                </label>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Attributes</h3>
        <div class="stack">
            <fieldset style="border:none;padding:0;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                <legend style="font-weight:500;float:left;width:auto;">Have you owned a dog?</legend>
                <label style="display:inline-flex;align-items:center;margin-right:1rem;">
                    <input type="radio" name="has_owned_a_dog" value="1" <?=(!empty($user['has_owned_a_dog'])) ? 'checked' : ''?> style="margin-right:0.5rem;">
                    Yes
                </label>
                <label style="display:inline-flex;align-items:center;margin-right:1rem;">
                    <input type="radio" name="has_owned_a_dog" value="0" <?=(isset($user['has_owned_a_dog']) && $user['has_owned_a_dog'] === 0) ? 'checked' : ''?> style="margin-right:0.5rem;">
                    No
                </label>
                <a href="javascript:void(0)" onclick="clearRadio('has_owned_a_dog')" style="color:#666;text-decoration:underline;cursor:pointer;font-size:0.9em;">clear</a>
            </fieldset>

            <fieldset style="border:none;padding:0;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                <legend style="font-weight:500;float:left;width:auto;">Do you have children at home?</legend>
                <label style="display:inline-flex;align-items:center;margin-right:1rem;">
                    <input type="radio" name="has_children_at_home" value="1" <?=(!empty($user['has_children_at_home'])) ? 'checked' : ''?> style="margin-right:0.5rem;">
                    Yes
                </label>
                <label style="display:inline-flex;align-items:center;margin-right:1rem;">
                    <input type="radio" name="has_children_at_home" value="0" <?=(isset($user['has_children_at_home']) && $user['has_children_at_home'] === 0) ? 'checked' : ''?> style="margin-right:0.5rem;">
                    No
                </label>
                <a href="javascript:void(0)" onclick="clearRadio('has_children_at_home')" style="color:#666;text-decoration:underline;cursor:pointer;font-size:0.9em;">clear</a>
            </fieldset>

            <fieldset style="border:none;padding:0;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                <legend style="font-weight:500;float:left;width:auto;">Do you have outdoor space?</legend>
                <label style="display:inline-flex;align-items:center;margin-right:1rem;">
                    <input type="radio" name="has_outdoor_space" value="1" <?=(!empty($user['has_outdoor_space'])) ? 'checked' : ''?> style="margin-right:0.5rem;">
                    Yes
                </label>
                <label style="display:inline-flex;align-items:center;margin-right:1rem;">
                    <input type="radio" name="has_outdoor_space" value="0" <?=(isset($user['has_outdoor_space']) && $user['has_outdoor_space'] === 0) ? 'checked' : ''?> style="margin-right:0.5rem;">
                    No
                </label>
                <a href="javascript:void(0)" onclick="clearRadio('has_outdoor_space')" style="color:#666;text-decoration:underline;cursor:pointer;font-size:0.9em;">clear</a>
            </fieldset>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="primary">Save Profile</button>
        <a href="/profile.php?user_id=<?=(int)$userId?>" class="button">Cancel</a>
    </div>
</form>

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
    fetch('/upload_photo.php?user_id=<?= (int)$userId ?>&return_to=/profile_edit.php?user_id=<?= (int)$userId ?>', {
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

// Remove photo function
function removePhoto() {
    if (!confirm('Remove this photo?')) {
        return;
    }
    
    var formData = new FormData();
    formData.append('csrf', '<?= h(csrf_token()) ?>');
    formData.append('action', 'delete');
    
    fetch('/upload_photo.php?user_id=<?= (int)$userId ?>&return_to=/profile_edit.php?user_id=<?= (int)$userId ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (response.ok && response.redirected) {
            // Success - reload the page
            window.location.href = response.url;
        } else {
            throw new Error('Remove failed');
        }
    })
    .catch(function(error) {
        alert('Failed to remove photo. Please try again.');
    });
}

// Clear radio button function
function clearRadio(name) {
    var radios = document.getElementsByName(name);
    for (var i = 0; i < radios.length; i++) {
        radios[i].checked = false;
    }
}
</script>

<?php footer_html(); ?>
