<?php

namespace App\Controllers;

use App\Core\DB;
use App\Services\LoginThrottle;
use App\Services\Password;
use Throwable;

/**
 * One-time admin password reset, enabled only when ADMIN_RECOVERY_CODE is set.
 * Never touches business data.
 */
final class AdminRecoveryController
{
    private const THROTTLE_KEY = '__admin_recovery__';

    public function show(): void
    {
        $this->ensureEnabled();
        view('auth/recovery', [
            'used'    => $this->alreadyUsed(),
            'message' => flash('success'),
            'error'   => flash('error'),
        ], null);
    }

    public function reset(): void
    {
        $code = $this->ensureEnabled();
        $entered = (string)input('recovery_code');
        $temporary = (string)($_POST['temporary_password'] ?? '');
        $ip = client_ip();

        if ($this->alreadyUsed()) {
            flash('error', 'Admin recovery has already been used. Set a new recovery code before using this page again.');
        } elseif (LoginThrottle::tooManyAttempts(self::THROTTLE_KEY, $ip)) {
            flash('error', 'Too many attempts. Please wait 15 minutes and try again.');
        } elseif (!hash_equals($code, $entered)) {
            LoginThrottle::recordFailure(self::THROTTLE_KEY, $ip);
            flash('error', 'Invalid recovery code.');
        } elseif (!Password::isStrong($temporary)) {
            flash('error', 'Temporary ' . lcfirst(Password::RULES));
        } else {
            try {
                DB::transaction(static function () use ($temporary): void {
                    DB::execute(
                        "UPDATE users SET password = ?, must_change_password = TRUE WHERE username = 'admin'",
                        [Password::hash($temporary)]
                    );
                    DB::execute('UPDATE system_recovery SET admin_recovery_used = TRUE WHERE id = 1');
                });
                flash('success', 'Admin password reset successfully. Sign in with username "admin" and the temporary password you just created. The system will require you to change it.');
            } catch (Throwable $e) {
                error_log((string)$e);
                flash('error', 'Could not reset the admin password.');
            }
        }

        redirect('/admin-recovery');
    }

    private function ensureEnabled(): string
    {
        $code = (string)env('ADMIN_RECOVERY_CODE', '');
        if ($code === '') {
            abort(404, 'Admin recovery is not enabled.');
        }
        return $code;
    }

    private function alreadyUsed(): bool
    {
        return (bool)DB::value('SELECT admin_recovery_used FROM system_recovery WHERE id = 1');
    }
}
