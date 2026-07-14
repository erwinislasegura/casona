<?php

final class AdminAuthController
{
    public function __construct(private AdminAuthRepository $users) {}

    public function login(array $input, string $ip, string $userAgent): string
    {
        $email = (string)($input['email'] ?? '');
        $password = (string)($input['password'] ?? '');
        $redirectTo = $this->safeRedirect((string)($input['redirect_to'] ?? '/admin'));
        $user = $this->users->findActiveUserByEmail($email);

        $isLocked = $user && !empty($user['locked_until']) && strtotime((string)$user['locked_until']) > time();

        if (!$user || $isLocked || !password_verify($password, $user['password_hash'])) {
            if ($user) {
                $this->users->registerFailedLogin((int)$user['id']);
            }
            $this->users->recordLoginAttempt($email, $user['id'] ?? null, $ip, $userAgent, false, 'invalid_credentials');
            return '/admin/login?error=invalid';
        }

        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int)$user['id'];
        $_SESSION['admin_last_activity'] = time();
        $this->users->markSuccessfulLogin((int)$user['id'], $ip);
        $this->users->recordLoginAttempt($email, (int)$user['id'], $ip, $userAgent, true);

        return $redirectTo;
    }

    private function safeRedirect(string $path): string
    {
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/admin';
        }
        return $path;
    }
}
