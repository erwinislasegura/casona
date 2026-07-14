<?php

final class AdminAuthRepository
{
    public function __construct(private PDO $db) {}

    public function findActiveUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function recordLoginAttempt(string $email, ?int $userId, string $ip, string $userAgent, bool $success, ?string $reason = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO admin_login_attempts (email, user_id, ip_address, user_agent, was_successful, failure_reason) VALUES (:email, :user_id, INET6_ATON(:ip), :user_agent, :success, :reason)');
        $stmt->execute([
            'email' => mb_strtolower(trim($email)),
            'user_id' => $userId,
            'ip' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'success' => $success ? 1 : 0,
            'reason' => $reason,
        ]);
    }

    public function markSuccessfulLogin(int $userId, string $ip): void
    {
        $stmt = $this->db->prepare('UPDATE admin_users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW(), last_login_ip = INET6_ATON(:ip) WHERE id = :id');
        $stmt->execute(['id' => $userId, 'ip' => $ip]);
    }

    public function registerFailedLogin(int $userId, int $maxAttempts = 5, int $lockMinutes = 15): void
    {
        $stmt = $this->db->prepare('UPDATE admin_users SET failed_login_count = failed_login_count + 1, locked_until = CASE WHEN failed_login_count + 1 >= :max_attempts THEN DATE_ADD(NOW(), INTERVAL :lock_minutes MINUTE) ELSE locked_until END WHERE id = :id');
        $stmt->execute(['id' => $userId, 'max_attempts' => $maxAttempts, 'lock_minutes' => $lockMinutes]);
    }

    public function storeRememberToken(int $userId, string $selector, string $plainToken, DateTimeInterface $expiresAt): void
    {
        $stmt = $this->db->prepare('INSERT INTO admin_remember_tokens (user_id, selector, token_hash, expires_at) VALUES (:user_id, :selector, :token_hash, :expires_at)');
        $stmt->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }
}
