<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Services\Security\TwoFactorAuthService;
use App\Services\Security\EncryptionService;
use App\Models\EmployeeModel;

/**
 * Two-Factor Authentication Controller
 *
 * Handles 2FA setup, verification, and management
 */
class TwoFactorAuthController extends BaseController
{
    protected TwoFactorAuthService $twoFactorService;
    protected EncryptionService $encryptionService;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->twoFactorService = new TwoFactorAuthService();
        $this->encryptionService = new EncryptionService();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Show 2FA setup page
     */
    public function setup()
    {
        $employeeId = session()->get('user_id');

        if (!$employeeId) {
            return redirect()->to('/auth/login')->with('error', 'Sessão expirada.');
        }

        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return redirect()->to('/dashboard')->with('error', 'Funcionário não encontrado.');
        }

        // Check if already enabled
        if ($employee->two_factor_enabled) {
            return redirect()->to('/auth/2fa/manage')
                ->with('info', '2FA já está habilitado. Você pode gerenciá-lo aqui.');
        }

        // Generate new secret
        $secret = $this->twoFactorService->generateSecret();

        // Store secret in session (temporary until verified)
        session()->set('2fa_setup_secret', $secret);

        // Get QR code data
        $qrCodeData = $this->twoFactorService->getQRCodeDataUri($secret, $employee->email);
        $otpauthUrl = $this->twoFactorService->getOTPAuthURL($secret, $employee->email);

        return view('auth/2fa/setup', [
            'secret' => $secret,
            'qr_code_data' => $qrCodeData,
            'otpauth_url' => $otpauthUrl,
            'employee' => $employee,
        ]);
    }

    /**
     * Verify and enable 2FA
     */
    public function enable()
    {
        $employeeId = session()->get('user_id');
        $secret = session()->get('2fa_setup_secret');

        if (!$employeeId || !$secret) {
            return redirect()->to('/auth/2fa/setup')
                ->with('error', 'Sessão expirada. Por favor, inicie a configuração novamente.');
        }

        // Get verification code from request
        $code = $this->request->getPost('code');

        if (!$code) {
            return redirect()->back()
                ->with('error', 'Por favor, digite o código de verificação.');
        }

        // Verify code
        if (!$this->twoFactorService->verifyCode($secret, $code)) {
            return redirect()->back()
                ->with('error', 'Código inválido. Por favor, tente novamente.');
        }

        // Generate backup codes
        $backupCodes = $this->twoFactorService->generateBackupCodes(10);

        // Hash backup codes for storage
        $hashedBackupCodes = array_map(
            fn($code) => $this->twoFactorService->hashBackupCode($code),
            $backupCodes
        );

        // Encrypt secret before storing
        $encryptedSecret = $this->encryptionService->encrypt($secret);
        $encryptedBackupCodes = $this->encryptionService->encryptJson($hashedBackupCodes);

        // Update employee record
        $this->employeeModel->update($employeeId, [
            'two_factor_enabled' => true,
            'two_factor_secret' => $encryptedSecret,
            'two_factor_backup_codes' => $encryptedBackupCodes,
            'two_factor_verified_at' => date('Y-m-d H:i:s'),
        ]);

        // Clear setup secret from session
        session()->remove('2fa_setup_secret');

        // Store backup codes in session to display once
        session()->setFlashdata('backup_codes', $backupCodes);

        log_message('info', "2FA enabled for employee ID: {$employeeId}");

        return redirect()->to('/auth/2fa/backup-codes')
            ->with('success', '2FA habilitado com sucesso!');
    }

    /**
     * Show backup codes (one-time display)
     */
    public function showBackupCodes()
    {
        $backupCodes = session()->getFlashdata('backup_codes');

        if (!$backupCodes) {
            return redirect()->to('/auth/2fa/manage')
                ->with('error', 'Códigos de backup não disponíveis.');
        }

        return view('auth/2fa/backup_codes', [
            'backup_codes' => $backupCodes,
        ]);
    }

    /**
     * Verify 2FA code during login
     */
    public function verify()
    {
        $employeeId = session()->get('2fa_pending_user_id');

        if (!$employeeId) {
            return redirect()->to('/auth/login')
                ->with('error', 'Sessão expirada. Por favor, faça login novamente.');
        }

        $employee = $this->employeeModel->find($employeeId);

        if (!$employee || !$employee->two_factor_enabled) {
            return redirect()->to('/auth/login')
                ->with('error', 'Configuração de 2FA inválida.');
        }

        // Get code from request
        $code = $this->request->getPost('code');
        $useBackupCode = $this->request->getPost('use_backup_code');

        if (!$code) {
            return redirect()->back()
                ->with('error', 'Por favor, digite o código de verificação.');
        }

        $verified = false;

        if ($useBackupCode) {
            // Verify backup code
            $verified = $this->verifyAndConsumeBackupCode($employee, $code);
        } else {
            // Verify TOTP code
            $decryptedSecret = $this->encryptionService->decrypt($employee->two_factor_secret);
            $verified = $this->twoFactorService->verifyCode($decryptedSecret, $code);
        }

        if (!$verified) {
            log_message('warning', "Failed 2FA verification for employee ID: {$employeeId}");

            return redirect()->back()
                ->with('error', 'Código inválido. Por favor, tente novamente.');
        }

        // Complete login
        session()->remove('2fa_pending_user_id');
        session()->set('user_id', $employeeId);
        session()->set('2fa_verified', true);

        log_message('info', "2FA verified successfully for employee ID: {$employeeId}");

        return redirect()->to('/dashboard')
            ->with('success', 'Login realizado com sucesso!');
    }

    /**
     * Verify and consume backup code
     */
    protected function verifyAndConsumeBackupCode($employee, string $code): bool
    {
        $hashedBackupCodes = $this->encryptionService->decryptJson($employee->two_factor_backup_codes);

        if (!$hashedBackupCodes || empty($hashedBackupCodes)) {
            return false;
        }

        foreach ($hashedBackupCodes as $index => $hashedCode) {
            if ($this->twoFactorService->verifyBackupCode($code, $hashedCode)) {
                // Remove used backup code
                unset($hashedBackupCodes[$index]);
                $hashedBackupCodes = array_values($hashedBackupCodes); // Reindex array

                // Update employee record
                $encryptedBackupCodes = $this->encryptionService->encryptJson($hashedBackupCodes);
                $this->employeeModel->update($employee->id, [
                    'two_factor_backup_codes' => $encryptedBackupCodes,
                ]);

                log_message('info', "Backup code used for employee ID: {$employee->id}. Remaining: " . count($hashedBackupCodes));

                return true;
            }
        }

        return false;
    }

    /**
     * Manage 2FA settings
     */
    public function manage()
    {
        $employeeId = session()->get('user_id');

        if (!$employeeId) {
            return redirect()->to('/auth/login')->with('error', 'Sessão expirada.');
        }

        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return redirect()->to('/dashboard')->with('error', 'Funcionário não encontrado.');
        }

        // Count remaining backup codes
        $remainingBackupCodes = 0;
        if ($employee->two_factor_enabled && $employee->two_factor_backup_codes) {
            $backupCodes = $this->encryptionService->decryptJson($employee->two_factor_backup_codes);
            $remainingBackupCodes = count($backupCodes);
        }

        return view('auth/2fa/manage', [
            'employee' => $employee,
            'remaining_backup_codes' => $remainingBackupCodes,
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable()
    {
        $employeeId = session()->get('user_id');

        if (!$employeeId) {
            return redirect()->to('/auth/login')->with('error', 'Sessão expirada.');
        }

        // Verify password for security
        $password = $this->request->getPost('password');

        if (!$password) {
            return redirect()->back()
                ->with('error', 'Por favor, digite sua senha para confirmar.');
        }

        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return redirect()->to('/dashboard')->with('error', 'Funcionário não encontrado.');
        }

        // Verify password
        if (!password_verify($password, $employee->password)) {
            return redirect()->back()
                ->with('error', 'Senha incorreta.');
        }

        // Disable 2FA
        $this->employeeModel->update($employeeId, [
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
            'two_factor_verified_at' => null,
        ]);

        log_message('info', "2FA disabled for employee ID: {$employeeId}");

        return redirect()->to('/dashboard')
            ->with('success', '2FA desabilitado com sucesso.');
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes()
    {
        $employeeId = session()->get('user_id');

        if (!$employeeId) {
            return redirect()->to('/auth/login')->with('error', 'Sessão expirada.');
        }

        $employee = $this->employeeModel->find($employeeId);

        if (!$employee || !$employee->two_factor_enabled) {
            return redirect()->to('/dashboard')
                ->with('error', '2FA não está habilitado.');
        }

        // Verify password for security
        $password = $this->request->getPost('password');

        if (!$password) {
            return redirect()->back()
                ->with('error', 'Por favor, digite sua senha para confirmar.');
        }

        if (!password_verify($password, $employee->password)) {
            return redirect()->back()
                ->with('error', 'Senha incorreta.');
        }

        // Generate new backup codes
        $backupCodes = $this->twoFactorService->generateBackupCodes(10);

        // Hash backup codes
        $hashedBackupCodes = array_map(
            fn($code) => $this->twoFactorService->hashBackupCode($code),
            $backupCodes
        );

        // Encrypt and update
        $encryptedBackupCodes = $this->encryptionService->encryptJson($hashedBackupCodes);

        $this->employeeModel->update($employeeId, [
            'two_factor_backup_codes' => $encryptedBackupCodes,
        ]);

        // Store in session for one-time display
        session()->setFlashdata('backup_codes', $backupCodes);

        log_message('info', "Backup codes regenerated for employee ID: {$employeeId}");

        return redirect()->to('/auth/2fa/backup-codes')
            ->with('success', 'Novos códigos de backup gerados!');
    }
}
