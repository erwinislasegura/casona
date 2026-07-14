<?php

final class AdminAuthController
{
    public function __construct(private AdminAuthRepository $users) {}

    public function login(array $input, string $ip, string $userAgent): string
    {
        $email = (string)($input['email'] ?? '');
        $password = (string)($input['password'] ?? '');
        $redirectTo = $this->safeRedirect((string)($input['redirect_to'] ?? '/admin/'));
        $user = $this->users->findActiveUserByEmail($email);

        $isLocked = $user && !empty($user['locked_until']) && strtotime((string)$user['locked_until']) > time();

        if (!$user || $isLocked || !password_verify($password, $user['password_hash'])) {
            $this->recordInvalidLogin($email, $user['id'] ?? null, $ip, $userAgent);
            return '/admin/login/?error=invalid';
        }

        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int)$user['id'];
        $_SESSION['admin_user_name'] = (string)($user['name'] ?? 'Administrador');
        $_SESSION['admin_last_activity'] = time();
        $this->recordSuccessfulLogin((int)$user['id'], $email, $ip, $userAgent);

        return $redirectTo;
    }

    private function recordInvalidLogin(string $email, ?int $userId, string $ip, string $userAgent): void
    {
        try {
            if ($userId !== null) {
                $this->users->registerFailedLogin($userId);
            }
            $this->users->recordLoginAttempt($email, $userId, $ip, $userAgent, false, 'invalid_credentials');
        } catch (Throwable) {
            // El registro de auditoría no debe reemplazar el mensaje real de credenciales inválidas.
        }
    }

    private function recordSuccessfulLogin(int $userId, string $email, string $ip, string $userAgent): void
    {
        try {
            $this->users->markSuccessfulLogin($userId, $ip);
            $this->users->recordLoginAttempt($email, $userId, $ip, $userAgent, true);
        } catch (Throwable) {
            // Si una instalación antigua no tiene tablas/columnas de auditoría, el acceso válido debe continuar.
        }
    }

    private function safeRedirect(string $path): string
    {
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/admin/';
        }
        return $path;
    }
}
