<?php
session_start();

const USER_FILE = __DIR__ . '/users.txt';

function sanitize_input(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function load_users(): array
{
    if (!file_exists(USER_FILE)) {
        return [];
    }

    $lines = file(USER_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = [];

    foreach ($lines as $line) {
        $parts = explode('|', $line, 3);
        if (count($parts) !== 3) {
            continue;
        }

        [$name, $email, $hash] = $parts;
        $users[strtolower(trim($email))] = [
            'name' => trim($name),
            'email' => trim($email),
            'hash' => trim($hash),
        ];
    }

    return $users;
}

function find_user_by_email(string $email): ?array
{
    $users = load_users();
    $key = strtolower($email);
    return $users[$key] ?? null;
}

function save_user(string $name, string $email, string $passwordHash): bool
{
    $line = sprintf("%s|%s|%s\n", $name, $email, $passwordHash);
    return (bool) file_put_contents(USER_FILE, $line, FILE_APPEND | LOCK_EX);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['member_email']) && !empty($_SESSION['member_name']);
}

function login_member(string $name, string $email): void
{
    $_SESSION['member_name'] = $name;
    $_SESSION['member_email'] = $email;
}

function logout_member(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_member_name(): string
{
    return $_SESSION['member_name'] ?? '';
}
