<?php
require "auth.php";
require_admin();
$pageTitle="Settings";
$active="settings";
require "partials/header.php";


$adminMessage=''; $adminError=''; $temporaryPassword='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['user_action']??'';

    if($action==='create_user'){
        $name=trim($_POST['user_name']??'');
        $username=trim($_POST['user_username']??'');
        $role=$_POST['user_role']??'Cashier';
        $password=$_POST['user_password']??'';
        $confirm=$_POST['user_password_confirm']??'';

        if($name==='' || $username==='' || $password===''){
            $adminError='Name, username and password are required.';
        }elseif(!in_array($role,['Cashier','Admin'],true)){
            $adminError='Invalid user role.';
        }elseif(strlen($password)<6){
            $adminError='Password must be at least 6 characters.';
        }elseif($password!==$confirm){
            $adminError='Password confirmation does not match.';
        }else{
            try{
                $check=$conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
                $check->bind_param("s",$username); $check->execute();
                if($check->get_result()->fetch_assoc()){
                    $adminError='That username already exists.';
                }else{
                    $hash=hash('sha256',$password);
                    $ins=$conn->prepare("INSERT INTO users(name,username,password,role,must_change_password) VALUES(?,?,?,?,TRUE) RETURNING id");
                    $ins->bind_param("ssss",$name,$username,$hash,$role); $ins->execute();
                    $newRow=$ins->get_result()->fetch_assoc();
                    $newId=(int)($newRow['id']??0);
                    if($newId){
                        $msg=$conn->prepare("INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)");
                        $title='New account created';
                        $message='Your EDM Kienyeji Egg Shop account was created. Sign in with the temporary password provided by the administrator and change it before continuing.';
                        $msg->bind_param("iss",$newId,$title,$message); $msg->execute();
                    }
                    $adminMessage="User \"$name\" created successfully. The user must change the temporary password at first login.";
                }
            }catch(Throwable $e){
                $adminError='Could not create user. Please check the username and database.';
            }
        }
    }

    if($action==='delete_user'){
        $id=(int)($_POST['user_id']??0);
        if($id===(int)$_SESSION['user_id']){
            $adminError='You cannot delete the account you are currently using.';
        }elseif($id>0){
            try{
                $del=$conn->prepare("DELETE FROM users WHERE id=?");
                $del->bind_param("i",$id); $del->execute();
                $adminMessage=$del ? 'User deleted successfully.' : 'User could not be deleted.';
            }catch(Throwable $e){ $adminError='User could not be deleted.'; }
        }
    }

    if($action==='rename_user'){
        $id=(int)($_POST['user_id']??0); $name=trim($_POST['new_name']??'');
        if($id<=0 || $name==='') $adminError='A valid name is required.';
        elseif(mb_strlen($name)>100) $adminError='Name is too long.';
        else{
          $up=$conn->prepare("UPDATE users SET name=? WHERE id=?"); $up->bind_param("si",$name,$id); $up->execute();
          if($id===(int)$_SESSION['user_id']) $_SESSION['name']=$name;
          $adminMessage='User name updated successfully. The role remains unchanged.';
        }
    }

    if($action==='reset_user'){
        $id=(int)($_POST['user_id']??0);
        if($id>0){
            try{
                $temporaryPassword='EDM'.random_int(100000,999999);
                $hash=hash('sha256',$temporaryPassword);
                $up=$conn->prepare("UPDATE users SET password=?,must_change_password=TRUE WHERE id=?");
                $up->bind_param("si",$hash,$id); $up->execute();

                $msg=$conn->prepare("INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)");
                $title='Password reset';
                $message='Your password was reset by the administrator. Use the temporary password provided to you, then create a new password before continuing.';
                $msg->bind_param("iss",$id,$title,$message); $msg->execute();

                $adminMessage='Password reset successfully. Give the temporary password below to the user.';
            }catch(Throwable $e){ $adminError='Password could not be reset.'; }
        }
    }
}

$users = [];
try {
    $result = $conn->query("SELECT id,name,username,role,must_change_password FROM users ORDER BY id ASC");
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
          <p>Create Cashier/Admin accounts, reset passwords and remove users.</p>
        </div>
      </div>

      <?php if($adminError):?><div class="alert danger settings-alert"><?=e($adminError)?></div><?php endif;?>
      <?php if($adminMessage):?><div class="alert success settings-alert"><?=e($adminMessage)?></div><?php endif;?>
      <?php if($temporaryPassword):?>
        <div class="temporary-password-box">
          <strong>Temporary password</strong>
          <span><?=e($temporaryPassword)?></span>
          <small>Give this password to the user. The system will force a password change at the next login.</small>
        </div>
      <?php endif;?>

      <div class="create-user-box">
        <div class="create-user-title">Create New User</div>
        <form method="post" class="create-user-form">
          <input type="hidden" name="user_action" value="create_user">
          <div class="settings-field"><label>Full Name</label><input name="user_name" type="text" required></div>
          <div class="settings-field"><label>Username</label><input name="user_username" type="text" required></div>
          <div class="settings-field"><label>Role</label><select name="user_role"><option value="Cashier">Cashier</option><option value="Admin">Admin</option></select></div>
          <div class="settings-field"><label>Temporary Password</label><input name="user_password" type="password" minlength="6" required></div>
          <div class="settings-field"><label>Confirm Password</label><input name="user_password_confirm" type="password" minlength="6" required></div>
          <div class="create-user-submit"><button class="add-user-btn filled" type="submit">
            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Create User
          </button></div>
        </form>
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
                <td><span class="role-badge"><?=e($u['role'])?></span></td>
                <td><span class="active-status">Active</span><?php if(!empty($u['must_change_password'])):?><small class="must-change-label">Password change required</small><?php endif;?></td>
                <td>
                  <div class="user-actions">
                    <form method="post" class="rename-inline">
                      <input type="hidden" name="user_action" value="rename_user">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <input class="rename-input" name="new_name" value="<?=e($u['name'])?>" aria-label="Rename user">
                      <button class="user-action-btn rename" type="submit">Rename</button>
                    </form>
                    <form method="post">
                      <input type="hidden" name="user_action" value="reset_user">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <button class="user-action-btn reset" type="submit">Reset Password</button>
                    </form>
                    <?php if((int)$u['id']!==(int)$_SESSION['user_id']):?>
                    <form method="post" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                      <input type="hidden" name="user_action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <button class="user-action-btn delete" type="submit">Delete</button>
                    </form>
                    <?php endif;?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" class="no-users">No users found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="user-management-footer">
        <span>New accounts and reset accounts must change their temporary password before entering the system.</span>
      </div>
    </section>

  </div>
</div>
<?php require "partials/footer.php"; ?>
