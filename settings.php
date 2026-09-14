<?php
require "auth.php";
$pageTitle="Settings";
$active="settings";
require "partials/header.php";

$users = [];
try {
    $result = $db->query("SELECT id,name,username,role FROM users ORDER BY id ASC");
    while ($row = $result->fetch_assoc()) { $users[] = $row; }
} catch (Throwable $e) {
    $users = [];
}
?>
<div class="settings-page">
  <div class="settings-heading">
    <div>
      <div class="welcome-kicker">SYSTEM SETTINGS</div>
      <h1>Settings</h1>
      <p>Customize your shop information, receipts, system preferences and users.</p>
    </div>
    <div class="settings-breadcrumb">Home <span>›</span> Settings</div>
  </div>

  <div class="settings-four-grid">

    <!-- Business Information -->
    <section class="settings-section">
      <div class="settings-section-head">
        <div class="settings-section-icon business-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 10h16M5 10v9h14v-9M3 10l2-5h14l2 5M8 14h3v5H8zM14 14h3v5h-3z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            <path d="M4 19h16" fill="none" stroke="currentColor" stroke-width="1.7"/>
          </svg>
        </div>
        <div>
          <h2>Business Information</h2>
          <p>Update your shop details shown on receipts.</p>
        </div>
      </div>
      <div class="settings-fields">
        <div class="settings-field full">
          <label>Shop Name <b>*</b></label>
          <input type="text" value="EDM Kienyeji Egg Shop">
        </div>
        <div class="settings-field">
          <label>Phone Number</label>
          <input type="text" value="0712 345 678">
        </div>
        <div class="settings-field">
          <label>Address</label>
          <input type="text" value="Kibaha, Pwani">
        </div>
        <div class="settings-field full">
          <label>Email (Optional)</label>
          <input type="email" value="info@edmkenyeji.co.tz">
        </div>
      </div>
      <button class="settings-save" type="button">
        <svg viewBox="0 0 24 24"><path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 20v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        Save Changes
      </button>
    </section>

    <!-- Receipt Setting -->
    <section class="settings-section">
      <div class="settings-section-head">
        <div class="settings-section-icon receipt-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            <path d="M9 8h6M9 12h6M9 16h4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <h2>Receipt Setting</h2>
          <p>Customize what appears on printed receipts.</p>
        </div>
      </div>

      <div class="receipt-options">
        <label><input type="checkbox" checked><span>Show shop name</span></label>
        <label><input type="checkbox" checked><span>Show address</span></label>
        <label><input type="checkbox" checked><span>Show phone number</span></label>
        <label><input type="checkbox" checked><span>Show receipt number</span></label>
        <label><input type="checkbox" checked><span>Show date &amp; time</span></label>
        <label><input type="checkbox" checked><span>Show cashier name</span></label>
        <label><input type="checkbox" checked><span>Show thank you message</span></label>
      </div>

      <div class="settings-field full receipt-message">
        <label>Receipt Footer Message</label>
        <textarea>Thank you for supporting local farmers!</textarea>
      </div>

      <button class="settings-save" type="button">
        <svg viewBox="0 0 24 24"><path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 20v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        Save Changes
      </button>
    </section>

    <!-- System Preferences -->
    <section class="settings-section">
      <div class="settings-section-head">
        <div class="settings-section-icon system-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.7"/>
            <path d="M19 12a7 7 0 0 0-.2-1.7l2-1.2-2-3.4-2.1 1a7.5 7.5 0 0 0-2.9-1.7L13.5 2h-3l-.3 3a7.5 7.5 0 0 0-2.9 1.7l-2.1-1-2 3.4 2 1.2A7 7 0 0 0 5 12c0 .6.1 1.2.2 1.7l-2 1.2 2 3.4 2.1-1a7.5 7.5 0 0 0 2.9 1.7l.3 3h3l.3-3a7.5 7.5 0 0 0 2.9-1.7l2.1 1 2-3.4-2-1.2c.1-.5.2-1.1.2-1.7z" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linejoin="round"/>
          </svg>
        </div>
        <div>
          <h2>System Preferences</h2>
          <p>General settings for your egg shop system.</p>
        </div>
      </div>

      <div class="settings-fields">
        <div class="settings-field full">
          <label>Currency</label>
          <select><option selected>Tanzanian Shilling (TSh)</option></select>
        </div>
        <div class="settings-field">
          <label>Date Format</label>
          <select><option selected>DD MMM YYYY (e.g. 11 Sep 2024)</option></select>
        </div>
        <div class="settings-field">
          <label>Time Format</label>
          <select><option selected>24 Hours (e.g. 14:30)</option></select>
        </div>
        <div class="settings-field">
          <label>Low Stock Alert</label>
          <select><option selected>Enable</option><option>Disable</option></select>
        </div>
        <div class="settings-field">
          <label>Default Payment Method (POS)</label>
          <select>
            <option selected>Cash</option>
            <option>Mobile Money</option>
            <option>Bank</option>
          </select>
        </div>
      </div>

      <button class="settings-save" type="button">
        <svg viewBox="0 0 24 24"><path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 20v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        Save Changes
      </button>
    </section>

    <!-- User Management -->
    <section class="settings-section settings-users-section">
      <div class="settings-section-head">
        <div class="settings-section-icon users-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="9" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.7"/>
            <path d="M3.5 20c.5-3.4 2.3-5 5.5-5s5 1.6 5.5 5" fill="none" stroke="currentColor" stroke-width="1.7"/>
            <circle cx="17" cy="9" r="2.4" fill="none" stroke="currentColor" stroke-width="1.7"/>
            <path d="M15 15c2.7-.2 4.6 1.4 5 4.5" fill="none" stroke="currentColor" stroke-width="1.7"/>
          </svg>
        </div>
        <div>
          <h2>User Management</h2>
          <p>Manage system users and their access.</p>
        </div>
      </div>

      <div class="user-table-wrap">
        <table class="settings-user-table">
          <thead>
            <tr><th>#</th><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php if ($users): ?>
            <?php foreach ($users as $i => $u): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><strong><?=e($u['name'])?></strong><small>@<?=e($u['username'])?></small></td>
                <td><?=e($u['role'])?></td>
                <td><span class="active-status">Active</span></td>
                <td><a class="user-edit-btn" href="change_password.php">
                  <svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16zM13 7l4 4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg> Edit
                </a></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" class="no-users">No users found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="user-management-footer">
        <span>Administrator access is protected by your account password.</span>
        <a href="change_password.php" class="add-user-btn">
          <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Add New User
        </a>
      </div>
    </section>

  </div>
</div>
<?php require "partials/footer.php"; ?>
