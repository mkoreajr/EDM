<?php
require "auth.php";
require_admin();
$pageTitle="Settings";
$active="settings";
require "partials/header.php";


$adminMessage=''; $adminError=''; $temporaryPassword='';

// Load persistent shop/system settings.
$settings = [
    'shop_name'=>'MSINDA — FOOD SHOP',
    'shop_phone'=>'0712 345 678',
    'shop_address'=>'Mwanza, Tanzania',
    'shop_email'=>'',
    'receipt_show_shop_name'=>'1',
    'receipt_show_address'=>'1',
    'receipt_show_phone'=>'1',
    'receipt_show_number'=>'1',
    'receipt_show_datetime'=>'1',
    'receipt_show_cashier'=>'1',
    'receipt_show_thanks'=>'1',
    'receipt_footer'=>'Thank you for shopping with us!',
    'currency'=>'TZS',
    'date_format'=>'DD MMM YYYY',
    'time_format'=>'24 Hours',
    'low_stock_alert'=>'1',
    'default_payment'=>'Cash'
];
try {
    $sr=$conn->query("SELECT setting_key,setting_value FROM app_settings");
    if($sr){ while($r=$sr->fetch_assoc()){ $settings[$r['setting_key']]=$r['setting_value']; } }
} catch(Throwable $e) {}

function save_app_setting($conn,$key,$value){
    $q=$conn->prepare("INSERT INTO app_settings(setting_key,setting_value) VALUES(?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=EXCLUDED.setting_value");
    $q->bind_param("ss",$key,$value); $q->execute();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['user_action']??'';

    if($action==='save_business_settings'){
        try{
            save_app_setting($conn,'shop_name',trim($_POST['shop_name']??''));
            save_app_setting($conn,'shop_phone',trim($_POST['shop_phone']??''));
            save_app_setting($conn,'shop_address',trim($_POST['shop_address']??''));
            save_app_setting($conn,'shop_email',trim($_POST['shop_email']??''));
            $adminMessage='Business information saved successfully.';
        }catch(Throwable $e){ $adminError='Business information could not be saved.'; }
    }

    if($action==='save_receipt_settings'){
        try{
            foreach(['shop_name','address','phone','number','datetime','cashier','thanks'] as $k){
                save_app_setting($conn,'receipt_show_'.$k,isset($_POST['receipt_show_'.$k])?'1':'0');
            }
            save_app_setting($conn,'receipt_footer',trim($_POST['receipt_footer']??''));
            $adminMessage='Receipt settings saved successfully.';
        }catch(Throwable $e){ $adminError='Receipt settings could not be saved.'; }
    }

    if($action==='save_system_settings'){
        try{
            $currency=$_POST['currency']??'TZS';
            $dateFormat=$_POST['date_format']??'DD MMM YYYY';
            $timeFormat=$_POST['time_format']??'24 Hours';
            $lowStock=($_POST['low_stock_alert']??'1')==='1'?'1':'0';
            $payment=$_POST['default_payment']??'Cash';
            if(!in_array($payment,['Cash','Mobile Money','Bank'],true)) $payment='Cash';
            save_app_setting($conn,'currency',$currency);
            save_app_setting($conn,'date_format',$dateFormat);
            save_app_setting($conn,'time_format',$timeFormat);
            save_app_setting($conn,'low_stock_alert',$lowStock);
            save_app_setting($conn,'default_payment',$payment);
            $adminMessage='System preferences saved successfully.';
        }catch(Throwable $e){ $adminError='System preferences could not be saved.'; }
    }

    if($action==='upload_slide'){
        $file=$_FILES['slide_image']??null;
        if(!$file || !isset($file['error'])){
            $adminError='Please choose an image file.';
        }elseif($file['error']!==UPLOAD_ERR_OK){
            $uploadErrors=[
                UPLOAD_ERR_INI_SIZE=>'Image is too large for the server upload limit.',
                UPLOAD_ERR_FORM_SIZE=>'Image is too large for the upload form limit.',
                UPLOAD_ERR_PARTIAL=>'The image upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_FILE=>'Please choose an image file.',
                UPLOAD_ERR_NO_TMP_DIR=>'Server temporary upload folder is unavailable.',
                UPLOAD_ERR_CANT_WRITE=>'Server could not save the uploaded image.',
                UPLOAD_ERR_EXTENSION=>'The server stopped the image upload.'
            ];
            $adminError=$uploadErrors[$file['error']]??'The image could not be uploaded.';
        }elseif(($file['size']??0) > 6*1024*1024){
            $adminError='Image is too large. Maximum size is 6 MB.';
        }else{
            try{
                $info=@getimagesize($file['tmp_name']);
                $allowed=['image/jpeg','image/png','image/webp','image/gif'];
                $mime=(string)($info['mime']??'');
                if(!$info || !in_array($mime,$allowed,true)) throw new RuntimeException('Please select a JPG, PNG or WEBP image.');
                $raw=file_get_contents($file['tmp_name']);
                if($raw===false || $raw==='') throw new RuntimeException('The image could not be read.');
                $dataUrl='data:'.$mime.';base64,'.base64_encode($raw);
                $name=basename((string)$file['name']);
                $name=preg_replace('/[^A-Za-z0-9._ -]/','_', $name) ?: 'slide-image';
                $ordRes=$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 AS next_order FROM login_slides");
                $next=(int)(($ordRes?$ordRes->fetch_assoc():[])['next_order']??1);
                $ins=$conn->prepare("INSERT INTO login_slides(filename,mime_type,image_data,sort_order) VALUES(?,?,?,?)");
                $ins->bind_param("sssi",$name,$mime,$dataUrl,$next); $ins->execute();
                $adminMessage='Login slide added successfully. It is now included in the login slideshow.';
            }catch(Throwable $e){ $adminError=$e instanceof RuntimeException ? $e->getMessage() : 'The image could not be uploaded.'; }
        }
    }

    if($action==='delete_slide'){
        $id=(int)($_POST['slide_id']??0);
        if($id>0){
            try{
                $del=$conn->prepare("DELETE FROM login_slides WHERE id=?"); $del->bind_param("i",$id); $del->execute();
                $adminMessage='Login slide removed. If no custom slides remain, the default EDM slides will be used.';
            }catch(Throwable $e){ $adminError='The login slide could not be removed.'; }
        }
    }

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
        }elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)){
            $adminError='Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.';
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
                        $message='Your EDM Kienyeji Food Shop account was created. Sign in with the temporary password provided by the administrator and change it before continuing.';
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
                $chars='ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
                $temporaryPassword='';
                for($i=0;$i<12;$i++) $temporaryPassword.=$chars[random_int(0,strlen($chars)-1)];
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

$loginSlides = [];
try {
    $slideResult = $conn->query("SELECT id,filename,mime_type,image_data,sort_order FROM login_slides ORDER BY sort_order ASC,id ASC");
    while ($row = $slideResult->fetch_assoc()) { $loginSlides[] = $row; }
} catch (Throwable $e) {
    $loginSlides = [];
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
      <form method="post">
        <input type="hidden" name="user_action" value="save_business_settings">
      <div class="settings-fields">
        <div class="settings-field full">
          <label>Shop Name <b>*</b></label>
          <input name="shop_name" type="text" value="<?=e($settings['shop_name'])?>" required>
        </div>
        <div class="settings-field">
          <label>Phone Number</label>
          <input name="shop_phone" type="text" value="<?=e($settings['shop_phone'])?>">
        </div>
        <div class="settings-field">
          <label>Address</label>
          <input name="shop_address" type="text" value="<?=e($settings['shop_address'])?>">
        </div>
        <div class="settings-field full">
          <label>Email (Optional)</label>
          <input name="shop_email" type="email" value="<?=e($settings['shop_email'])?>">
        </div>
      </div>
      <button class="settings-save" type="submit">
        <svg viewBox="0 0 24 24"><path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 20v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        Save Changes
      </button>
      </form>
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

      <form method="post">
        <input type="hidden" name="user_action" value="save_receipt_settings">
      <div class="receipt-options">
        <label><input name="receipt_show_shop_name" type="checkbox" <?=($settings['receipt_show_shop_name']==='1'?'checked':'')?>><span>Show shop name</span></label>
        <label><input name="receipt_show_address" type="checkbox" <?=($settings['receipt_show_address']==='1'?'checked':'')?>><span>Show address</span></label>
        <label><input name="receipt_show_phone" type="checkbox" <?=($settings['receipt_show_phone']==='1'?'checked':'')?>><span>Show phone number</span></label>
        <label><input name="receipt_show_number" type="checkbox" <?=($settings['receipt_show_number']==='1'?'checked':'')?>><span>Show receipt number</span></label>
        <label><input name="receipt_show_datetime" type="checkbox" <?=($settings['receipt_show_datetime']==='1'?'checked':'')?>><span>Show date &amp; time</span></label>
        <label><input name="receipt_show_cashier" type="checkbox" <?=($settings['receipt_show_cashier']==='1'?'checked':'')?>><span>Show cashier name</span></label>
        <label><input name="receipt_show_thanks" type="checkbox" <?=($settings['receipt_show_thanks']==='1'?'checked':'')?>><span>Show thank you message</span></label>
      </div>

      <div class="settings-field full receipt-message">
        <label>Receipt Footer Message</label>
        <textarea name="receipt_footer"><?=e($settings['receipt_footer'])?></textarea>
      </div>

      <button class="settings-save" type="submit">
        <svg viewBox="0 0 24 24"><path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 20v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        Save Changes
      </button>
      </form>
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
          <p>General settings for your food shop system.</p>
        </div>
      </div>

      <form method="post">
        <input type="hidden" name="user_action" value="save_system_settings">
      <div class="settings-fields">
        <div class="settings-field full">
          <label>Currency</label>
          <select name="currency"><option value="TZS" <?=($settings['currency']==='TZS'?'selected':'')?>>Tanzanian Shilling (TSh)</option></select>
        </div>
        <div class="settings-field">
          <label>Date Format</label>
          <select name="date_format"><option value="DD MMM YYYY" <?=($settings['date_format']==='DD MMM YYYY'?'selected':'')?>>DD MMM YYYY (e.g. 11 Sep 2024)</option></select>
        </div>
        <div class="settings-field">
          <label>Time Format</label>
          <select name="time_format"><option value="24 Hours" <?=($settings['time_format']==='24 Hours'?'selected':'')?>>24 Hours (e.g. 14:30)</option></select>
        </div>
        <div class="settings-field">
          <label>Low Stock Alert</label>
          <select name="low_stock_alert"><option value="1" <?=($settings['low_stock_alert']==="1"?'selected':'')?>>Enable</option><option value="0" <?=($settings['low_stock_alert']!=="1"?'selected':'')?>>Disable</option></select>
        </div>
        <div class="settings-field">
          <label>Default Payment Method (POS)</label>
          <select name="default_payment">
            <option value="Cash" <?=($settings['default_payment']==='Cash'?'selected':'')?>>Cash</option>
            <option value="Mobile Money" <?=($settings['default_payment']==='Mobile Money'?'selected':'')?>>Mobile Money</option>
            <option value="Bank" <?=($settings['default_payment']==='Bank'?'selected':'')?>>Bank</option>
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

      <?php if($adminError):?><div class="alert danger settings-alert"><?=e($adminError)?></div><?php endif;?>
      <?php if($adminMessage):?><div class="alert success settings-alert"><?=e($adminMessage)?></div><?php endif;?>

      <form method="post" enctype="multipart/form-data" class="slide-upload-form">
        <input type="hidden" name="user_action" value="upload_slide">
        <div class="settings-field">
          <label>Slide Image</label>
          <input type="file" name="slide_image" accept="image/*" required>
          <small>JPG, PNG or WEBP • maximum 6 MB</small>
        </div>
        <div class="slide-upload-submit"><button class="add-user-btn filled" type="submit">+ Add Slide</button></div>
      </form>

      <?php if($loginSlides): ?>
        <div class="login-slide-grid">
          <?php foreach($loginSlides as $i=>$slide): ?>
            <div class="login-slide-admin-card">
              <div class="login-slide-admin-preview"><img src="<?=e($slide['image_data'])?>" alt="<?=e($slide['filename'])?>"></div>
              <div class="login-slide-admin-meta"><strong>Slide <?=($i+1)?></strong><span><?=e($slide['filename'])?></span></div>
              <form method="post" onsubmit="return confirm('Remove this login slide?');">
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
          <div class="settings-field"><label>Temporary Password</label><div class="password-rules">Password must contain: <b>8+ characters</b>, uppercase, lowercase, number, and special character.</div><input name="user_password" type="password" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters with uppercase, lowercase, a number, and a special character." minlength="6" required></div>
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
