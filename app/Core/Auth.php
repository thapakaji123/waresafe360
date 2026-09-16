<?php

declare(strict_types=1);

namespace Safe360\Core;

use PDO;

final class Auth
{
    /** @var array<string, mixed>|null|false */
    private static array|null|false $cachedUser = false;

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$cachedUser !== false) {
            return self::$cachedUser;
        }

        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) && !ctype_digit((string) $id)) {
            self::$cachedUser = null;
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT u.id, u.name, u.email, u.status, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1'
        );
        $statement->execute(['id' => (int) $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        self::$cachedUser = $user ?: null;
        return self::$cachedUser;
    }

    public static function attempt(string $email, string $password): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT u.id, u.password_hash, u.status FROM users u WHERE LOWER(u.email) = LOWER(:email) LIMIT 1'
        );
        $statement->execute(['email' => trim($email)]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active' || !password_verify($password, (string) $user['password_hash'])) {
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$cachedUser = false;

        $update = Database::connection()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute(['id' => (int) $user['id']]);
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$cachedUser = null;
    }

    public static function requireUser(): array
    {
        $user = self::user();
        if (!$user || $user['status'] !== 'active') {
            if (str_starts_with(Request::path(), '/api/')) {
                Response::json(['error' => 'unauthenticated', 'message' => 'Please sign in to continue.'], 401);
            }
            Response::redirect('/login');
        }
        return $user;
    }

    public static function requireRole(string $role): array
    {
        $user = self::requireUser();
        if (($user['role'] ?? null) !== $role) {
            if (str_starts_with(Request::path(), '/api/')) {
                Response::json(['error' => 'forbidden', 'message' => 'You are not authorised to perform this action.'], 403);
            }
            http_response_code(403);
            View::render('errors/403', ['title' => 'Access denied']);
            exit;
        }
        return $user;
    }
}

