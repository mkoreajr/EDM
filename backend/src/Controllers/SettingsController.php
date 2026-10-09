<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\Notifications;
use App\Services\Password;
use App\Services\Settings;
use DomainException;
use Throwable;

final class SettingsController
{
    private const MAX_SLIDE_BYTES = 6 * 1024 * 1024;
    private const SLIDE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function index(): void
    {
        view('settings/index', [
            'pageTitle'         => 'Settings',
            'active'            => 'settings',
            'settings'          => Settings::all(),
            'paymentMethods'    => Settings::PAYMENT_METHODS,
            // Only metadata here — previews are loaded from /slides/image, not inlined as base64.
            'slides'            => DB::all('SELECT id, filename FROM login_slides ORDER BY sort_order ASC, id ASC'),
            'users'             => DB::all('SELECT id, name, username, role, must_change_password FROM users ORDER BY id ASC'),
            'success'           => flash('success'),
            'error'             => flash('error'),
            'temporaryPassword' => flash('temporary_password'),
            'passwordPattern'   => Password::HTML_PATTERN,
        ]);
    }

    public function handle(): void
    {
        $actions = [
            'save_business_settings' => 'saveBusiness',
            'save_receipt_settings'  => 'saveReceipt',
            'save_system_settings'   => 'saveSystem',
            'upload_slide'           => 'uploadSlide',
            'delete_slide'           => 'deleteSlide',
            'create_user'            => 'createUser',
            'delete_user'            => 'deleteUser',
            'rename_user'            => 'renameUser',
            'reset_user'             => 'resetUser',
        ];
        $method = $actions[(string)input('user_action')] ?? null;

        if ($method !== null) {
            try {
                flash('success', $this->{$method}());
            } catch (DomainException $e) {
                flash('error', $e->getMessage());
            } catch (Throwable $e) {
                error_log((string)$e);
                flash('error', 'The change could not be saved. Please try again.');
            }
        }
        redirect('/settings');
    }

    /* ------------------------------------------------------------ settings */

    private function saveBusiness(): string
    {
        foreach (['shop_name', 'shop_phone', 'shop_address', 'shop_email'] as $key) {
            Settings::set($key, (string)input($key));
        }
        return 'Business information saved successfully.';
    }

    private function saveReceipt(): string
    {
        foreach (['shop_name', 'address', 'phone', 'number', 'datetime', 'cashier', 'thanks'] as $key) {
            Settings::set('receipt_show_' . $key, isset($_POST['receipt_show_' . $key]) ? '1' : '0');
        }
        Settings::set('receipt_footer', (string)input('receipt_footer'));
        return 'Receipt settings saved successfully.';
    }

    private function saveSystem(): string
    {
        $payment = (string)input('default_payment', 'Cash');
        Settings::set('currency', 'TZS');
        Settings::set('date_format', 'DD MMM YYYY');
        Settings::set('time_format', '24 Hours');
        Settings::set('low_stock_alert', input('low_stock_alert', '1') === '1' ? '1' : '0');
        Settings::set('default_payment', in_array($payment, Settings::PAYMENT_METHODS, true) ? $payment : 'Cash');
        return 'System preferences saved successfully.';
    }

    /* -------------------------------------------------------------- slides */

    private function uploadSlide(): string
    {
        $file = $_FILES['slide_image'] ?? null;
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'Image is too large for the server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'Image is too large for the upload form limit.',
            UPLOAD_ERR_PARTIAL    => 'The image upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE    => 'Please choose an image file.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary upload folder is unavailable.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not save the uploaded image.',
            UPLOAD_ERR_EXTENSION  => 'The server stopped the image upload.',
        ];

        if (!is_array($file) || !isset($file['error'])) {
            throw new DomainException('Please choose an image file.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new DomainException($uploadErrors[$file['error']] ?? 'The image could not be uploaded.');
        }
        if (($file['size'] ?? 0) > self::MAX_SLIDE_BYTES) {
            throw new DomainException('Image is too large. Maximum size is 6 MB.');
        }

        $info = @getimagesize($file['tmp_name']);
        $mime = (string)($info['mime'] ?? '');
        if (!$info || !in_array($mime, self::SLIDE_TYPES, true)) {
            throw new DomainException('Please select a JPG, PNG or WEBP image.');
        }
        $raw = file_get_contents($file['tmp_name']);
        if ($raw === false || $raw === '') {
            throw new DomainException('The image could not be read.');
        }

        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename((string)$file['name'])) ?: 'slide-image';
        DB::execute(
            'INSERT INTO login_slides (filename, mime_type, image_data, sort_order)
             VALUES (?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM login_slides))',
            [mb_substr($name, 0, 255), $mime, base64_encode($raw)]
        );
        return 'Login slide added successfully. It is now included in the login slideshow.';
    }

    private function deleteSlide(): string
    {
        DB::execute('DELETE FROM login_slides WHERE id = ?', [(int)input('slide_id', 0)]);
        return 'Login slide removed. If no custom slides remain, the default EDM slides will be used.';
    }

    /* --------------------------------------------------------------- users */

    private function createUser(): string
    {
        $name = (string)input('user_name');
        $username = (string)input('user_username');
        $role = (string)input('user_role', 'Cashier');
        $password = (string)($_POST['user_password'] ?? '');
        $confirm = (string)($_POST['user_password_confirm'] ?? '');

        if ($name === '' || $username === '' || $password === '') {
            throw new DomainException('Name, username and password are required.');
        }
        if (mb_strlen($name) > 100 || mb_strlen($username) > 50 || !preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
            throw new DomainException('Use a name up to 100 characters and a username up to 50 letters, numbers, dots, dashes or underscores.');
        }
        if (!in_array($role, ['Cashier', 'Admin'], true)) {
            throw new DomainException('Invalid user role.');
        }
        if (!Password::isStrong($password)) {
            throw new DomainException(Password::RULES);
        }
        if ($password !== $confirm) {
            throw new DomainException('Password confirmation does not match.');
        }
        if (DB::value('SELECT 1 FROM users WHERE LOWER(username) = LOWER(?)', [$username])) {
            throw new DomainException('That username already exists.');
        }

        $newId = (int)DB::value(
            'INSERT INTO users (name, username, password, role, must_change_password) VALUES (?, ?, ?, ?, TRUE) RETURNING id',
            [$name, $username, Password::hash($password), $role]
        );
        Notifications::send(
            $newId,
            'New account created',
            'Your EDM Kienyeji Food Shop account was created. Sign in with the temporary password provided by the administrator and change it before continuing.'
        );
        return "User \"{$name}\" created successfully. The user must change the temporary password at first login.";
    }

    private function deleteUser(): string
    {
        $id = (int)input('user_id', 0);
        if ($id === Auth::id()) {
            throw new DomainException('You cannot delete the account you are currently using.');
        }
        $user = DB::one('SELECT role FROM users WHERE id = ?', [$id]);
        if ($user === null) {
            throw new DomainException('User not found.');
        }
        if (strtolower((string)$user['role']) === 'admin'
            && (int)DB::value("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'admin'") <= 1) {
            throw new DomainException('You cannot delete the last administrator.');
        }

        try {
            DB::execute('DELETE FROM users WHERE id = ?', [$id]);
        } catch (Throwable $e) {
            if (DB::isForeignKeyViolation($e)) {
                throw new DomainException('This user has recorded sales, purchases or expenses and cannot be deleted. Reset their password instead to block access.');
            }
            throw $e;
        }
        return 'User deleted successfully.';
    }

    private function renameUser(): string
    {
        $id = (int)input('user_id', 0);
        $name = (string)input('new_name');
        if ($id <= 0 || $name === '') {
            throw new DomainException('A valid name is required.');
        }
        if (mb_strlen($name) > 100) {
            throw new DomainException('Name is too long.');
        }
        DB::execute('UPDATE users SET name = ? WHERE id = ?', [$name, $id]);
        return 'User name updated successfully. The role remains unchanged.';
    }

    private function resetUser(): string
    {
        $id = (int)input('user_id', 0);
        if ($id <= 0 || !DB::value('SELECT 1 FROM users WHERE id = ?', [$id])) {
            throw new DomainException('User not found.');
        }

        $temporary = Password::temporary();
        DB::execute('UPDATE users SET password = ?, must_change_password = TRUE WHERE id = ?', [Password::hash($temporary), $id]);
        Notifications::send(
            $id,
            'Password reset',
            'Your password was reset by the administrator. Use the temporary password provided to you, then create a new password before continuing.'
        );
        flash('temporary_password', $temporary);
        return 'Password reset successfully. Give the temporary password below to the user.';
    }
}
