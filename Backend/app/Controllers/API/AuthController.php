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

        if ($this->request->getVar('user_type') === 'worker') {
            $rules['skills'] = 'required';
            $rules['experience_years'] = 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[99]';
        }

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
            'password_changed_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->request->getVar('user_type') === 'worker') {
            $skills = UserModel::normalizeWorkerSkills($this->request->getVar('skills'));
            if (empty($skills)) {
                return $this->fail('Please select at least one skill.');
            }

            $data['skills'] = json_encode($skills);
            $data['experience_years'] = $this->request->getVar('experience_years');
            $data['commission_rate'] = 20.00; // Default commission rate
            $data['status'] = 'pending';
        } else {
            $data['status'] = 'active';
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
            return $this->fail($this->getApprovalStateMessage((string) ($user['status'] ?? '')));
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

        if (($user['status'] ?? '') !== 'active') {
            return $this->respond([
                'status' => 'success',
                'message' => $this->getApprovalStateMessage((string) ($user['status'] ?? '')),
                'approval_required' => true,
            ]);
        }

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

        $email = strtolower(trim((string) $this->request->getVar('email')));
        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            return $this->failNotFound('User not found');
        }

        $otp = $this->issueOtpForUser((int) $user['id']);
        if (! $this->sendOtpEmail($email, $otp, 'password_reset')) {
            return $this->failServerError('We could not send the password reset code. Please try again.');
        }

        $this->activityLogger->record('auth', 'password_reset_requested', 'success', null, (int) $user['id'], [
            'email' => $email,
        ], 'api');

        return $this->respond([
            'status' => 'success',
            'message' => 'Password reset code sent. Enter the code to continue.',
            'requires_otp' => true,
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

        $email = strtolower(trim((string) $this->request->getVar('email')));
        $otpInput = trim((string) $this->request->getVar('otp'));
        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            return $this->failNotFound('User not found');
        }

        if (($user['otp_attempts'] ?? 0) >= 3) {
            return $this->fail('Max OTP attempts reached. Please request a new code.');
        }

        if (empty($user['otp']) || ! hash_equals((string) $user['otp'], $otpInput)) {
            $attempts = ((int) ($user['otp_attempts'] ?? 0)) + 1;
            $this->userModel->update((int) $user['id'], ['otp_attempts' => $attempts]);
            return $this->fail('Invalid OTP code. Tries left: ' . max(0, 3 - $attempts));
        }

        if (!empty($user['otp_expire']) && strtotime((string) $user['otp_expire']) < time()) {
            return $this->fail('OTP has expired. Please request a new code.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            if ($this->userModel->changePassword((int) $user['id'], (string) $this->request->getVar('new_password')) === false) {
                throw new \RuntimeException('Failed to update password.');
            }

            $this->userModel->update((int) $user['id'], [
                'otp' => null,
                'otp_expire' => null,
                'otp_attempts' => 0,
            ]);

            $this->activityLogger->record('account', 'password_reset_completed', 'success', (int) $user['id'], (int) $user['id'], [
                'method' => 'otp_reset',
            ], 'api');

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();

            return $this->respond([
                'status' => 'success',
                'message' => 'Password reset successful. You can now sign in.',
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->fail('Password reset failed: ' . $e->getMessage());
        }
    }

    private function getApprovalStateMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Registration submitted successfully. Please wait for admin approval of your worker application. The admin will contact you through your email once reviewed. You cannot log in until approval is completed.',
            'rejected' => 'Your worker application was rejected. Please check your email for the reason and any next steps, or contact support.',
            default => 'Account is not active',
        };
    }

    private function getRegistrationPendingMessage(): string
    {
        return 'Registration submitted successfully. Please wait for admin approval of your worker application. The admin will contact you through your email once reviewed. You cannot log in until approval is completed.';
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
        $isWorker = (($this->request->authUser['user_type'] ?? '') === 'worker');

        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
            'phone'      => 'max_length[20]',
            'address'    => 'max_length[500]',
        ];

        if ($isWorker) {
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

        if ($isWorker) {
            $skills = $this->request->getVar('skills');
            if (is_string($skills)) {
                $skills = array_values(array_filter(array_map('trim', explode(',', $skills))));
            }

            if (!is_array($skills)) {
                $skills = [];
            }

            $data['skills'] = json_encode(UserModel::normalizeWorkerSkills($skills));
            $data['experience_years'] = (int) ($this->request->getVar('experience_years') ?? 0);
            $data['service_city'] = trim((string) ($this->request->getVar('service_city') ?? ''));
            $data['service_radius_km'] = (float) ($this->request->getVar('service_radius_km') ?: 20);
            $data['work_latitude'] = $this->nullIfEmpty($this->request->getVar('work_latitude'));
            $data['work_longitude'] = $this->nullIfEmpty($this->request->getVar('work_longitude'));
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

    public function settings()
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $role = (string) ($this->request->authUserRole ?? '');
        if ($userId <= 0) {
            return $this->failUnauthorized('User not authenticated');
        }

        $sessionTracker = service('sessiontracker');
        $activityLogModel = new ActivityLogModel();
        $currentSession = $sessionTracker->getCurrentSessionSummary();
        $myActiveSessions = $sessionTracker->getActiveSessionsForUser($userId);
        $myActivityLogs = $activityLogModel->getRecentForUser($userId, 20);
        $isAdmin = in_array($role, ['admin', 'super_admin'], true);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'environment' => ENVIRONMENT,
                'baseUrl' => (string) base_url('/'),
                'role' => $role,
                'currentSession' => $currentSession,
                'myActiveSessions' => $myActiveSessions,
                'myActivityLogs' => $myActivityLogs,
                'activeSessionCount' => $isAdmin ? $sessionTracker->getActiveSessionCount() : 0,
                'recentActiveSessions' => $isAdmin ? $sessionTracker->getActiveSessions(20) : [],
                'recentActivityLogs' => $isAdmin ? $activityLogModel->getRecentWithUsers(30) : [],
            ],
        ]);
    }

    public function requestEmailChange()
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $currentUser = $this->request->authUser ?? null;
        if ($userId <= 0 || !is_array($currentUser)) {
            return $this->failUnauthorized('User not authenticated');
        }

        $rules = [
            'email' => 'required|valid_email|regex_match[/^[A-Za-z0-9]+([._][A-Za-z0-9]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)+$/]|is_unique[users.email]',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = strtolower(trim((string) $this->request->getVar('email')));
        if ($email === strtolower(trim((string) ($currentUser['email'] ?? '')))) {
            return $this->fail('Your new email must be different from the current one.');
        }

        $otp = $this->issueEmailChangeOtpForUser($userId, $email);
        if (! $this->sendOtpEmail($email, $otp, 'email_change')) {
            return $this->failServerError('We could not send the verification code to your new email address.');
        }

        $this->activityLogger->record('account', 'email_change_requested', 'success', $userId, $userId, [
            'pending_email' => $email,
        ], 'api', $this->request->authSessionKey ?? null);

        return $this->respond([
            'status' => 'success',
            'message' => 'Verification code sent to your new email address.',
            'data' => [
                'email' => $email,
            ],
        ]);
    }

    public function confirmEmailChange()
    {
        $userId = (int) ($this->request->authUserId ?? 0);
        $currentUser = $this->request->authUser ?? null;
        if ($userId <= 0 || !is_array($currentUser)) {
            return $this->failUnauthorized('User not authenticated');
        }

        $rules = [
            'email' => 'required|valid_email',
            'otp' => 'required|exact_length[6]|numeric',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = strtolower(trim((string) $this->request->getVar('email')));
        $otpInput = trim((string) $this->request->getVar('otp'));
        $user = $this->userModel->find($userId);

        if (! $user) {
            return $this->failNotFound('User not found');
        }

        if (($user['email_change_otp_attempts'] ?? 0) >= 3) {
            return $this->fail('Max OTP attempts reached. Please request a new code.');
        }

        if ($email !== strtolower(trim((string) ($user['pending_email'] ?? '')))) {
            return $this->fail('This email change request is no longer valid.');
        }

        if (!empty($user['email_change_otp_expire']) && strtotime((string) $user['email_change_otp_expire']) < time()) {
            return $this->fail('OTP has expired. Please request a new code.');
        }

        if (empty($user['email_change_otp']) || ! hash_equals((string) $user['email_change_otp'], $otpInput)) {
            $attempts = ((int) ($user['email_change_otp_attempts'] ?? 0)) + 1;
            $this->userModel->update($userId, ['email_change_otp_attempts' => $attempts]);
            return $this->fail('Invalid OTP code. Tries left: ' . max(0, 3 - $attempts));
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            if ($this->userModel->update($userId, [
                'email' => $email,
                'pending_email' => null,
                'email_change_otp' => null,
                'email_change_otp_expire' => null,
                'email_change_otp_attempts' => 0,
                'email_verified_at' => date('Y-m-d H:i:s'),
            ]) === false) {
                throw new \RuntimeException('Failed to update email.');
            }

            $updatedUser = $this->userModel->find($userId);
            unset($updatedUser['password']);

            $this->activityLogger->record('account', 'email_changed', 'success', $userId, $userId, [
                'new_email' => $email,
            ], 'api', $this->request->authSessionKey ?? null);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();

            return $this->respond([
                'status' => 'success',
                'message' => 'Profile and email updated successfully.',
                'data' => $updatedUser,
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->fail('Email update failed: ' . $e->getMessage());
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
            'password_reset' => 'SkillLink password reset code',
            'email_change' => 'Confirm your new SkillLink email',
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

    private function nullIfEmpty($value)
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function generateOtpEmailMessage(string $otp, string $email, string $context): string
    {
        $apiKey = getenv('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return $this->buildDefaultOtpEmail($otp, $context);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
        $action = $context === 'register' ? 'verifying a newly created SkillLink account' : 'logging into SkillLink Services';
        if ($context === 'password_reset') {
            $action = 'resetting a SkillLink Services password';
        } elseif ($context === 'email_change') {
            $action = 'confirming a new email address for SkillLink Services';
        }
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
            : ($context === 'password_reset'
                ? 'We received a request to reset your SkillLink password.'
                : ($context === 'email_change'
                    ? 'We received a request to confirm your new SkillLink email address.'
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
