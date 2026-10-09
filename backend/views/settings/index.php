<?php
/**
 * @var array<string,string> $settings
 * @var list<string> $paymentMethods
 * @var list<array{id:int,filename:string}> $slides
 * @var list<array<string,mixed>> $users
 * @var string|null $success
 * @var string|null $error
 * @var string|null $temporaryPassword
 * @var string $passwordPattern
 */

use App\Core\Auth;

$selected = static fn(string $key, string $value): string => ($settings[$key] ?? '') === $value ? 'selected' : '';
?>
<div class="settings-page">
  <div class="settings-heading">
    <div>
      <div class="welcome-kicker">SYSTEM SETTINGS</div>
      <h1>Settings</h1>
      <p>Manage system preferences, login slideshow and users.</p>
    </div>
    <div class="settings-breadcrumb">Home <span>›</span> Settings</div>
  </div>

  <?php if ($error): ?><div class="alert danger settings-alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert success settings-alert"><?= e($success) ?></div><?php endif; ?>

  <div class="settings-four-grid">

    <!-- System Preferences -->
    <section class="settings-section system-preferences-section">
      <div class="settings-section-head">
        <div class="settings-section-icon system-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M19 12a7 7 0 0 0-.2-1.7l2-1.2-2-3.4-2.1 1a7.5 7.5 0 0 0-2.9-1.7L13.5 2h-3l-.3 3a7.5 7.5 0 0 0-2.9 1.7l-2.1-1-2 3.4 2 1.2A7 7 0 0 0 5 12c0 .6.1 1.2.2 1.7l-2 1.2 2 3.4 2.1-1a7.5 7.5 0 0 0 2.9 1.7l.3 3h3l.3-3a7.5 7.5 0 0 0 2.9-1.7l2.1 1 2-3.4-2-1.2c.1-.5.2-1.1.2-1.7z" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <h2>System Preferences</h2>
          <p>General settings for your food shop system.</p>
        </div>
      </div>

      <form method="post" action="/settings">
        <?= csrf_field() ?>
        <input type="hidden" name="user_action" value="save_system_settings">
        <div class="settings-fields">
          <div class="settings-field full">
            <label for="currency">Currency</label>
            <select id="currency" name="currency"><option value="TZS" selected>Tanzanian Shilling (TSh)</option></select>
          </div>
          <div class="settings-field">
            <label for="date_format">Date Format</label>
            <select id="date_format" name="date_format"><option value="DD MMM YYYY" selected>DD MMM YYYY (e.g. 11 Sep 2024)</option></select>
          </div>
          <div class="settings-field">
            <label for="time_format">Time Format</label>
            <select id="time_format" name="time_format"><option value="24 Hours" selected>24 Hours (e.g. 14:30)</option></select>
          </div>
          <div class="settings-field">
            <label for="low_stock_alert">Low Stock Alert</label>
            <select id="low_stock_alert" name="low_stock_alert">
              <option value="1" <?= $selected('low_stock_alert', '1') ?>>Enable</option>
              <option value="0" <?= $selected('low_stock_alert', '0') ?>>Disable</option>
            </select>
          </div>
          <div class="settings-field">
            <label for="default_payment">Default Payment Method (POS)</label>
            <select id="default_payment" name="default_payment">
              <?php foreach ($paymentMethods as $method): ?>
                <option value="<?= e($method) ?>" <?= $selected('default_payment', $method) ?>><?= e($method) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button class="settings-save" type="submit">
          <svg viewBox="0 0 24 24"><path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 20v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
          Save Changes
        </button>
      </form>
    </section>

    <!-- Login Slideshow -->
    <section class="settings-section settings-slideshow-section">
      <div class="settings-section-head">
        <div class="settings-section-icon slideshow-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="8" cy="10" r="1.6" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="m5 17 4.5-4 3 2.5 2.2-2 4.3 3.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <h2>Login Slideshow</h2>
          <p>Admin can add or remove the pictures shown on the login page.</p>
        </div>
      </div>

      <form method="post" action="/settings" enctype="multipart/form-data" class="slide-upload-form">
        <?= csrf_field() ?>
        <input type="hidden" name="user_action" value="upload_slide">
        <div class="settings-field">
          <label for="slide_image">Slide Image</label>
          <input type="file" id="slide_image" name="slide_image" accept="image/jpeg,image/png,image/webp,image/gif" required>
          <small>JPG, PNG or WEBP • maximum 6 MB</small>
        </div>
        <div class="slide-upload-submit"><button class="add-user-btn filled" type="submit">+ Add Slide</button></div>
      </form>

      <?php if ($slides): ?>
        <div class="login-slide-grid">
          <?php foreach ($slides as $i => $slide): ?>
            <div class="login-slide-admin-card">
              <div class="login-slide-admin-preview"><img src="<?= e(url('/slides/image', ['id' => (int)$slide['id']])) ?>" alt="<?= e($slide['filename']) ?>" loading="lazy" decoding="async"></div>
              <div class="login-slide-admin-meta"><strong>Slide <?= $i + 1 ?></strong><span><?= e($slide['filename']) ?></span></div>
              <form method="post" action="/settings" data-confirm="Remove this login slide?">
                <?= csrf_field() ?>
                <input type="hidden" name="user_action" value="delete_slide">
                <input type="hidden" name="slide_id" value="<?= (int)$slide['id'] ?>">
                <button class="user-action-btn delete" type="submit">Remove</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="slide-default-note">No custom slides uploaded yet. The built-in EDM login pictures are currently being used.</div>
      <?php endif; ?>
    </section>

    <!-- User Management -->
    <section class="settings-section settings-users-section">
      <div class="settings-section-head">
        <div class="settings-section-icon users-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 20c.5-3.4 2.3-5 5.5-5s5 1.6 5.5 5" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="17" cy="9" r="2.4" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M15 15c2.7-.2 4.6 1.4 5 4.5" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>
        </div>
        <div>
          <h2>User Management</h2>
          <p>Create Cashier/Admin accounts, reset passwords and remove users.</p>
        </div>
      </div>

      <?php if ($temporaryPassword): ?>
        <div class="temporary-password-box">
          <strong>Temporary password</strong>
          <span><?= e($temporaryPassword) ?></span>
          <small>Give this password to the user. The system will force a password change at the next login.</small>
        </div>
      <?php endif; ?>

      <div class="create-user-box">
        <div class="create-user-title">Create New User</div>
        <form method="post" action="/settings" class="create-user-form">
          <?= csrf_field() ?>
          <input type="hidden" name="user_action" value="create_user">
          <div class="settings-field"><label for="user_name">Full Name</label><input id="user_name" name="user_name" type="text" maxlength="100" required></div>
          <div class="settings-field"><label for="user_username">Username</label><input id="user_username" name="user_username" type="text" maxlength="50" pattern="[A-Za-z0-9._\-]+" title="Letters, numbers, dots, dashes and underscores only." required></div>
          <div class="settings-field"><label for="user_role">Role</label><select id="user_role" name="user_role"><option value="Cashier">Cashier</option><option value="Admin">Admin</option></select></div>
          <div class="settings-field">
            <label for="user_password">Temporary Password</label>
            <div class="password-rules">Password must contain: <b>8+ characters</b>, uppercase, lowercase, number, and special character.</div>
            <input id="user_password" name="user_password" type="password" minlength="8" pattern="<?= e($passwordPattern) ?>" title="Use at least 8 characters with uppercase, lowercase, a number, and a special character." autocomplete="new-password" required>
          </div>
          <div class="settings-field"><label for="user_password_confirm">Confirm Password</label><input id="user_password_confirm" name="user_password_confirm" type="password" minlength="8" autocomplete="new-password" required></div>
          <div class="create-user-submit"><button class="add-user-btn filled" type="submit">
            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Create User
          </button></div>
        </form>
      </div>

      <div class="user-table-wrap">
        <table class="settings-user-table">
          <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($users as $i => $u): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e($u['name']) ?></strong><small>@<?= e($u['username']) ?></small></td>
              <td><span class="role-badge"><?= e($u['role']) ?></span></td>
              <td><span class="active-status">Active</span><?php if (!empty($u['must_change_password'])): ?><small class="must-change-label">Password change required</small><?php endif; ?></td>
              <td>
                <div class="user-actions">
                  <form method="post" action="/settings" class="rename-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_action" value="rename_user">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <input class="rename-input" name="new_name" value="<?= e($u['name']) ?>" maxlength="100" aria-label="Rename user">
                    <button class="user-action-btn rename" type="submit">Rename</button>
                  </form>
                  <form method="post" action="/settings" data-confirm="Reset this user's password? They will need the new temporary password to sign in.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_action" value="reset_user">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button class="user-action-btn reset" type="submit">Reset Password</button>
                  </form>
                  <?php if ((int)$u['id'] !== Auth::id()): ?>
                  <form method="post" action="/settings" data-confirm="Delete this user? This cannot be undone.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button class="user-action-btn delete" type="submit">Delete</button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?><tr><td colspan="5" class="no-users">No users found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="user-management-footer">
        <span>New accounts and reset accounts must change their temporary password before entering the system.</span>
      </div>
    </section>

  </div>
</div>
