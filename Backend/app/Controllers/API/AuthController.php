<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Libraries\ActivityLogger;
use App\Models\ActivityLogModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class AuthController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $sessionTracker;
    protected ActivityLogger $activityLogger;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->sessionTracker = service('sessiontracker');
        $this->activityLogger = new ActivityLogger();
    }

    public function register()
    {
        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'phone' => 'max_length[20]',
            'user_type' => 'required|in_list[customer,worker]',
            'address' => 'max_length[500]'
        ];

        if (!$this->validate($rules)) {
            $this->activityLogger->record('account', 'user_registration', 'validation_failed', null, null, [
                'email' => $this->request->getVar('email'),
                'errors' => $this->validator->getErrors(),
            ], 'api');
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'first_name' => $this->request->getVar('first_name'),
            'last_name' => $this->request->getVar('last_name'),
            'email' => $this->request->getVar('email'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'phone' => $this->request->getVar('phone'),
            'user_type' => $this->request->getVar('user_type'),
            'address' => $this->request->getVar('address'),
            'status' => 'active',
            'password_changed_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->request->getVar('user_type') === 'worker') {
            $data['skills'] = $this->request->getVar('skills');
            $data['experience_years'] = $this->request->getVar('experience_years');
            $data['commission_rate'] = 20.00; // Default commission rate
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $userId = $this->userModel->insert($data);
            if ($userId === false) {
                throw new \Exception('Failed to insert user record.');
            }

            $this->activityLogger->record('account', 'user_registered', 'success', (int) $userId, (int) $userId, [
                'created_fields' => $this->activityLogger->changedFields([], $data, array_keys($data)),
            ], 'api');

            $otp = $this->issueOtpForUser((int) $userId);

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed.');
            }

            $db->transCommit();

            if (! $this->sendOtpEmail($data['email'], $otp, 'register')) {
                return $this->failServerError('Account created, but we could not send the verification code email.');
            }

            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Registration successful. Please verify the OTP sent to your email.',
                'requires_otp' => true,
                'data' => [
                    'email' => $data['email'],
                ],
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->fail('Registration failed: ' . $e->getMessage());
        }
    }

    public function login()
    {
        $rules = [
            'email' => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $user = $this->userModel->where('email', $email)->first();

        if (!$user) {
            $this->activityLogger->record('auth', 'login_attempt', 'failed', null, null, [
                'email' => $email,
                'reason' => 'invalid_credentials',
            ], 'api');
            return $this->fail('Invalid credentials');
        }

        if ($this->userModel->isLocked($user)) {
            $this->activityLogger->record('auth', 'login_attempt', 'locked', null, (int) $user['id'], [
                'email' => $email,
                'locked_until' => $user['locked_until'] ?? null,
            ], 'api');
            return $this->fail('Account is temporarily locked due to multiple failed login attempts');
        }

        if (!password_verify($password, $user['password'])) {
            $this->userModel->recordFailedLogin((int) $user['id']);
            $this->activityLogger->record('auth', 'login_attempt', 'failed', null, (int) $user['id'], [
                'email' => $email,
                'reason' => 'invalid_credentials',
            ], 'api');
            return $this->fail('Invalid credentials');
        }

        if ($user['status'] !== 'active') {
            $this->activityLogger->record('auth', 'login_attempt', 'blocked', null, (int) $user['id'], [
                'email' => $email,
                'reason' => 'inactive_account',
                'status' => $user['status'],
            ], 'api');
            return $this->fail('Account is not active');
        }

        $this->userModel->clearFailedLogins((int) $user['id']);
        $otp = $this->issueOtpForUser((int) $user['id']);
        if (! $this->sendOtpEmail($user['email'], $otp, 'login')) {
            return $this->failServerError('We could not send the verification code email. Please try again.');
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Verification code sent. Please enter the OTP to complete login.',
            'requires_otp' => true,
            'data' => [
                'email' => $user['email'],
            ],
        ]);
    }

    public function verifyOtp()
    {
        $rules = [
            'email' => 'required|valid_email',
            'otp' => 'required|exact_length[6]|numeric',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = (string) $this->request->getVar('email');
        $otpInput = (string) $this->request->getVar('otp');
        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            $this->activityLogger->record('auth', 'otp_verification', 'failed', null, null, [
                'email' => $email,
                'reason' => 'missing_user',
            ], 'api');
            return $this->failNotFound('User not found');
        }

        if (($user['otp_attempts'] ?? 0) >= 3) {
            $this->activityLogger->record('auth', 'otp_verification', 'blocked', null, (int) $user['id'], [
                'email' => $email,
                'reason' => 'max_attempts',
            ], 'api');
            return $this->fail('Max OTP attempts reached. Please request a new code.');
        }

        if (!empty($user['otp_expire']) && strtotime((string) $user['otp_expire']) < time()) {
            $this->activityLogger->record('auth', 'otp_verification', 'failed', null, (int) $user['id'], [
                'email' => $email,
                'reason' => 'expired_otp',
            ], 'api');
            return $this->fail('OTP has expired. Please request a new code.');
        }

        if (($user['otp'] ?? '') !== $otpInput || empty($user['otp'])) {
            $newAttempts = ((int) ($user['otp_attempts'] ?? 0)) + 1;
            $this->userModel->update((int) $user['id'], ['otp_attempts' => $newAttempts]);
            $this->activityLogger->record('auth', 'otp_verification', 'failed', null, (int) $user['id'], [
                'email' => $email,
                'reason' => 'invalid_otp',
                'attempts' => $newAttempts,
            ], 'api');
            return $this->fail('Invalid OTP code. Tries left: ' . max(0, 3 - $newAttempts));
        }

        $this->userModel->update((int) $user['id'], [
            'otp' => null,
            'otp_expire' => null,
            'otp_attempts' => 0,
            'email_verified_at' => $user['email_verified_at'] ?: date('Y-m-d H:i:s'),
        ]);

        $freshUser = $this->userModel->find((int) $user['id']);
        unset($freshUser['password']);
        $sessionKey = $this->sessionTracker->startApiSession($freshUser);
        $this->activityLogger->record('auth', 'login_attempt', 'success', (int) $freshUser['id'], (int) $freshUser['id'], [
            'email' => $freshUser['email'],
            'session_type' => 'api',
        ], 'api', $sessionKey);

        return $this->respond([
            'status' => 'success',
            'message' => 'Verification successful',
            'data' => [
                'user' => $freshUser,
                'token' => $this->createApiToken((int) $freshUser['id'], (string) $freshUser['user_type'], $sessionKey),
            ],
        ]);
    }

    public function resendOtp()
    {
        $rules = [
            'email' => 'required|valid_email',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = (string) $this->request->getVar('email');
        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            return $this->failNotFound('User not found');
        }

        $otp = $this->issueOtpForUser((int) $user['id']);

        try {
            if (! $this->sendOtpEmail($email, $otp, 'resend')) {
                return $this->failServerError('Failed to resend verification code.');
            }
        } catch (Exception $e) {
            return $this->failServerError('Failed to resend verification code.');
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'A new verification code has been sent to your email.',
            'requires_otp' => true,
            'data' => [
                'email' => $email,
            ],
        ]);
    }

    public function requestPasswordReset()
    {
        $rules = [
            'email' => 'required|valid_email',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = (string) $this->request->getVar('email');
        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            $this->activityLogger->record('account', 'password_reset_request', 'ignored', null, null, [
                'email' => $email,
                'reason' => 'missing_user',
            ], 'api');

            return $this->respond([
                'status' => 'success',
                'message' => 'If that email exists, a password reset code has been sent.',
                'data' => [
                    'email' => $email,
                ],
            ]);
        }

        $otp = $this->issueOtpForUser((int) $user['id']);

        try {
            if (! $this->sendOtpEmail($email, $otp, 'forgot_password')) {
                return $this->failServerError('We could not send the password reset code. Please try again.');
            }
        } catch (Exception) {
            return $this->failServerError('We could not send the password reset code. Please try again.');
        }

        $this->activityLogger->record('account', 'password_reset_request', 'success', (int) $user['id'], (int) $user['id'], [
            'email' => $email,
            'delivery' => 'email_otp',
        ], 'api');

        return $this->respond([
            'status' => 'success',
            'message' => 'A password reset code has been sent to your email.',
            'data' => [
                'email' => $email,
            ],
        ]);
    }

    public function resetPasswordWithOtp()
    {
        $rules = [
            'email' => 'required|valid_email',
            'otp' => 'required|exact_length[6]|numeric',
            'new_password' => 'required|min_length[8]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = (string) $this->request->getVar('email');
        $otpInput = (string) $this->request->getVar('otp');
        $newPassword = (string) $this->request->getVar('new_password');
        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            $this->activityLogger->record('account', 'password_reset', 'failed', null, null, [
                'email' => $email,
                'reason' => 'missing_user',
            ], 'api');
            return $this->failNotFound('User not found');
        }

        if (($user['otp_attempts'] ?? 0) >= 3) {
            $this->activityLogger->record('account', 'password_reset', 'blocked', null, (int) $user['id'], [
                'email' => $email,
                'reason' => 'max_attempts',
            ], 'api');
            return $this->fail('Max OTP attempts reached. Please request a new code.');
        }

        if (!empty($user['otp_expire']) && strtotime((string) $user['otp_expire']) < time()) {
            $this->activityLogger->record('account', 'password_reset', 'failed', null, (int) $user['id'], [
                'email' => $email,
                'reason' => 'expired_otp',
            ], 'api');
            return $this->fail('OTP has expired. Please request a new code.');
        }

        if (($user['otp'] ?? '') !== $otpInput || empty($user['otp'])) {
            $newAttempts = ((int) ($user['otp_attempts'] ?? 0)) + 1;
            $this->userModel->update((int) $user['id'], ['otp_attempts' => $newAttempts]);
            $this->activityLogger->record('account', 'password_reset', 'failed', null, (int) $user['id'], [
                'email' => $email,
                'reason' => 'invalid_otp',
                'attempts' => $newAttempts,
            ], 'api');
            return $this->fail('Invalid OTP code. Tries left: ' . max(0, 3 - $newAttempts));
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            if ($this->userModel->changePassword((int) $user['id'], $newPassword) === false) {
                throw new \Exception('Failed to update password.');
            }

            $this->userModel->update((int) $user['id'], [
                'otp' => null,
                'otp_expire' => null,
                'otp_attempts' => 0,
                'email_verified_at' => $user['email_verified_at'] ?: date('Y-m-d H:i:s'),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            $this->activityLogger->record('account', 'password_reset', 'success', (int) $user['id'], (int) $user['id'], [
                'email' => $email,
                'method' => 'email_otp',
            ], 'api');

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed.');
            }

            $db->transCommit();

            return $this->respond([
                'status' => 'success',
                'message' => 'Password reset successfully. You can now log in with your new password.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->fail('Password reset failed: ' . $e->getMessage());
        }
    }

    public function profile()
    {
        $userId = $this->request->authUserId;
        $user   = $this->request->authUser;

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        unset($user['password']);

        // Add additional data based on user type only for admin callers
        $callerRole = $this->request->authUserRole ?? null;
        if ($user['user_type'] === 'worker' && ($callerRole === 'admin' || $callerRole === 'super_admin')) {
            $user['average_rating'] = $this->userModel->getWorkerRating($userId);
            $user['total_earnings'] = $this->userModel->getWorkerEarnings($userId);
        }

        return $this->respond([
            'status' => 'success',
            'data'   => $user,
        ]);
    }

    public function updateProfile()
    {
        $userId = $this->request->authUserId;

        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
            'phone'      => 'max_length[20]',
            'address'    => 'max_length[500]',
        ];

        if (($this->request->authUser['user_type'] ?? '') === 'worker') {
            $rules['service_city'] = 'permit_empty|max_length[120]';
            $rules['service_radius_km'] = 'permit_empty|numeric|greater_than[0]|less_than_equal_to[500]';
            $rules['work_latitude'] = 'permit_empty|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]';
            $rules['work_longitude'] = 'permit_empty|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]';
        }

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'first_name' => $this->request->getVar('first_name'),
            'last_name'  => $this->request->getVar('last_name'),
            'phone'      => $this->request->getVar('phone'),
            'address'    => $this->request->getVar('address'),
        ];

        if (($this->request->authUser['user_type'] ?? '') === 'worker') {
            $data['skills']           = $this->request->getVar('skills');
            $data['experience_years'] = $this->request->getVar('experience_years');
            $data['service_city'] = trim((string) ($this->request->getVar('service_city') ?? ''));
            $data['service_radius_km'] = (float) ($this->request->getVar('service_radius_km') ?: 20);
            $data['work_latitude'] = $this->request->getVar('work_latitude') ?: null;
            $data['work_longitude'] = $this->request->getVar('work_longitude') ?: null;
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            if ($this->userModel->update($userId, $data) === false) {
                throw new \Exception('Failed to update user profile.');
            }

            $user = $this->userModel->find($userId);
            unset($user['password']);

            $this->activityLogger->record('account', 'profile_updated', 'success', (int) $userId, (int) $userId, [
                'changed_fields' => $this->activityLogger->changedFields($this->request->authUser ?? [], $data, array_keys($data)),
            ], 'api', $this->request->authSessionKey ?? null);

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed.');
            }

            $db->transCommit();

            return $this->respond([
                'status'  => 'success',
                'message' => 'Profile updated successfully',
                'data'    => $user,
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->fail('Profile update failed: ' . $e->getMessage());
        }
    }

    public function changePassword()
    {
        $userId = $this->request->authUserId;

        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        // Re-fetch from DB to get the hashed password for verification
        $user = $this->userModel->find($userId);
        if (!password_verify($this->request->getVar('current_password'), $user['password'])) {
            return $this->fail('Current password is incorrect');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            if ($this->userModel->changePassword($userId, $this->request->getVar('new_password')) === false) {
                throw new \Exception('Failed to update password.');
            }

            $this->activityLogger->record('account', 'password_changed', 'success', (int) $userId, (int) $userId, [
                'method' => 'self_service',
            ], 'api', $this->request->authSessionKey ?? null);

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed.');
            }

            $db->transCommit();

            return $this->respond([
                'status'  => 'success',
                'message' => 'Password changed successfully',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->fail('Password change failed: ' . $e->getMessage());
        }
    }

    public function requestEmailChange()
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $currentUser = $this->userModel->find($userId);

        if (! $currentUser) {
            return $this->failNotFound('User not found');
        }

        $rules = [
            'email' => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $newEmail = strtolower(trim((string) $this->request->getVar('email')));
        $currentEmail = strtolower(trim((string) ($currentUser['email'] ?? '')));

        if ($newEmail === $currentEmail) {
            return $this->respond([
                'status' => 'success',
                'message' => 'That email is already your current email address.',
                'data' => [
                    'email' => $currentEmail,
                ],
            ]);
        }

        $existingUser = $this->userModel->where('email', $newEmail)->where('id !=', $userId)->first();
        if ($existingUser) {
            return $this->fail('That email address is already in use.');
        }

        $otp = $this->issueEmailChangeOtpForUser($userId, $newEmail);

        try {
            if (! $this->sendOtpEmail($newEmail, $otp, 'change_email')) {
                return $this->failServerError('We could not send the verification code to the new email address.');
            }
        } catch (Exception) {
            return $this->failServerError('We could not send the verification code to the new email address.');
        }

        $this->activityLogger->record('account', 'email_change_requested', 'success', $userId, $userId, [
            'pending_email' => $newEmail,
            'delivery' => 'email_otp',
        ], 'api', $this->request->authSessionKey ?? null);

        return $this->respond([
            'status' => 'success',
            'message' => 'A verification code has been sent to your new email address.',
            'data' => [
                'email' => $newEmail,
            ],
        ]);
    }

    public function confirmEmailChange()
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $currentUser = $this->userModel->find($userId);

        if (! $currentUser) {
            return $this->failNotFound('User not found');
        }

        $rules = [
            'email' => 'required|valid_email',
            'otp' => 'required|exact_length[6]|numeric',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $pendingEmail = strtolower(trim((string) $this->request->getVar('email')));
        $otpInput = trim((string) $this->request->getVar('otp'));

        if (strtolower(trim((string) ($currentUser['pending_email'] ?? ''))) !== $pendingEmail) {
            return $this->fail('No pending email change matches that email address. Please request a new code.');
        }

        if (((int) ($currentUser['email_change_otp_attempts'] ?? 0)) >= 3) {
            return $this->fail('Max OTP attempts reached. Please request a new code.');
        }

        if (! empty($currentUser['email_change_otp_expire']) && strtotime((string) $currentUser['email_change_otp_expire']) < time()) {
            return $this->fail('OTP has expired. Please request a new code.');
        }

        if (($currentUser['email_change_otp'] ?? '') !== $otpInput || empty($currentUser['email_change_otp'])) {
            $newAttempts = ((int) ($currentUser['email_change_otp_attempts'] ?? 0)) + 1;
            $this->userModel->update($userId, ['email_change_otp_attempts' => $newAttempts]);
            return $this->fail('Invalid OTP code. Tries left: ' . max(0, 3 - $newAttempts));
        }

        $existingUser = $this->userModel->where('email', $pendingEmail)->where('id !=', $userId)->first();
        if ($existingUser) {
            return $this->fail('That email address is already in use.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            if ($this->userModel->update($userId, [
                'email' => $pendingEmail,
                'pending_email' => null,
                'email_change_otp' => null,
                'email_change_otp_expire' => null,
                'email_change_otp_attempts' => 0,
                'email_verified_at' => date('Y-m-d H:i:s'),
            ]) === false) {
                throw new \Exception('Failed to update email address.');
            }

            $updatedUser = $this->userModel->find($userId);
            unset($updatedUser['password']);

            $this->activityLogger->record('account', 'email_changed', 'success', $userId, $userId, [
                'email' => $pendingEmail,
                'method' => 'email_otp',
            ], 'api', $this->request->authSessionKey ?? null);

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed.');
            }

            $db->transCommit();

            return $this->respond([
                'status' => 'success',
                'message' => 'Email updated successfully.',
                'data' => $updatedUser,
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->fail('Email change failed: ' . $e->getMessage());
        }
    }

    public function settings()
    {
        $user = $this->request->authUser;
        $userId = (int) ($this->request->authUserId ?? 0);
        $role = (string) ($this->request->authUserRole ?? ($user['user_type'] ?? ''));
        $isAdmin = in_array($role, ['super_admin', 'admin'], true);
        $activityLogModel = new ActivityLogModel();
        $sessionKey = (string) ($this->request->authSessionKey ?? '');

        return $this->respond([
            'status' => 'success',
            'data' => [
                'role' => $role,
                'baseUrl' => base_url(),
                'environment' => ENVIRONMENT,
                'currentSession' => $sessionKey !== '' ? $this->sessionTracker->validateTrackedSession($sessionKey) : null,
                'myActiveSessions' => $this->sessionTracker->getActiveSessionsForUser($userId),
                'activeSessionCount' => $isAdmin ? $this->sessionTracker->getActiveSessionCount() : null,
                'recentActiveSessions' => $isAdmin ? $this->sessionTracker->getActiveSessions(10) : [],
                'myActivityLogs' => $activityLogModel->getRecentForUser($userId, 15),
                'recentActivityLogs' => $isAdmin ? $activityLogModel->getRecentWithUsers(25) : [],
            ],
        ]);
    }

    public function logout()
    {
        $userId = $this->request->authUserId ?? null;
        $this->activityLogger->record('auth', 'logout', 'success', is_numeric($userId) ? (int) $userId : null, is_numeric($userId) ? (int) $userId : null, [
            'session_type' => 'api',
        ], 'api', $this->request->authSessionKey ?? null);
        $this->sessionTracker->endApiSession($this->request->authSessionKey ?? null);
        return $this->respond([
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    }

    private function issueOtpForUser(int $userId): string
    {
        $otp = sprintf('%06d', mt_rand(0, 999999));
        $expire = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $this->userModel->update($userId, [
            'otp' => $otp,
            'otp_expire' => $expire,
            'otp_attempts' => 0,
        ]);

        return $otp;
    }

    private function issueEmailChangeOtpForUser(int $userId, string $pendingEmail): string
    {
        $otp = sprintf('%06d', mt_rand(0, 999999));
        $expire = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $this->userModel->update($userId, [
            'pending_email' => $pendingEmail,
            'email_change_otp' => $otp,
            'email_change_otp_expire' => $expire,
            'email_change_otp_attempts' => 0,
        ]);

        return $otp;
    }

    private function sendOtpEmail(string $recipientEmail, string $otp, string $context): bool
    {
        if (!class_exists(PHPMailer::class)) {
            log_message('error', 'PHPMailer is not installed.');
            return false;
        }

        $smtpUser = $this->getEnvValue('SMTP_USER', 'email.SMTPUser');
        $smtpPass = $this->normalizeSmtpPassword($this->getEnvValue('SMTP_PASS', 'email.SMTPPass'));
        $fromName = $this->getEnvValue('SMTP_FROM_NAME', 'email.fromName') ?: 'SkillLink Services';

        if (!$smtpUser || !$smtpPass) {
            log_message('error', 'SMTP credentials are missing for OTP email sending.');
            return false;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom($smtpUser, $fromName);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = match ($context) {
            'register' => 'Verify your SkillLink account',
            'login' => 'SkillLink login verification code',
            'forgot_password' => 'SkillLink password reset code',
            'change_email' => 'Verify your new SkillLink email',
            default => 'Your new SkillLink verification code',
        };
        $mail->Body = $this->generateOtpEmailMessage($otp, $recipientEmail, $context);
        $mail->AltBody = "Your SkillLink verification code is {$otp}. It expires in 5 minutes.";

        return $mail->send();
    }

    private function getEnvValue(string $primaryKey, ?string $fallbackKey = null): ?string
    {
        $value = getenv($primaryKey);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if ($fallbackKey !== null) {
            $fallbackValue = getenv($fallbackKey);
            if (is_string($fallbackValue) && trim($fallbackValue) !== '') {
                return trim($fallbackValue);
            }
        }

        return null;
    }

    private function normalizeSmtpPassword(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', $value);

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    private function generateOtpEmailMessage(string $otp, string $email, string $context): string
    {
        $apiKey = getenv('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return $this->buildDefaultOtpEmail($otp, $context);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
        $action = match ($context) {
            'register' => 'verifying a newly created SkillLink account',
            'forgot_password' => 'resetting a SkillLink account password',
            'change_email' => 'confirming a new email address for SkillLink Services',
            default => 'logging into SkillLink Services',
        };
        $prompt = "Write a very short, professional and welcoming security email for a user with email $email who is $action.
                   The 6-digit verification code is $otp.
                   Mention it expires in 5 minutes.
                   Keep it very concise (max 3 sentences).
                   Use HTML with a short greeting and the <b> tag for the code.";

        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                return $this->buildDefaultOtpEmail($otp, $context);
            }
            curl_close($ch);

            $result = json_decode($response, true);
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? $this->buildDefaultOtpEmail($otp, $context);

            if (strpos($text, $otp) === false) {
                return $this->buildDefaultOtpEmail($otp, $context);
            }

            return $text;
        } catch (\Exception) {
            return $this->buildDefaultOtpEmail($otp, $context);
        }
    }

    private function buildDefaultOtpEmail(string $otp, string $context): string
    {
        $intro = $context === 'register'
            ? 'Thanks for creating your SkillLink account.'
            : ($context === 'forgot_password'
                ? 'We received a request to reset your SkillLink password.'
                : ($context === 'change_email'
                    ? 'We received a request to change the email address on your SkillLink account.'
                    : 'We received a request to verify your SkillLink sign-in.'));

        return "<p>{$intro}</p><p>Your verification code is <b>{$otp}</b>.</p><p>This code expires in 5 minutes.</p>";
    }

    private function createApiToken(int $userId, string $userType, ?string $sessionKey = null): ?string
    {
        $key = getenv('JWT_SECRET');
        if (!$key) {
            return null;
        }

        $payload = [
            'iss' => 'skilllink_backend',
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24),
            'user_type' => $userType,
            'sid' => $sessionKey,
        ];

        return JWT::encode($payload, $key, 'HS256');
    }
}
