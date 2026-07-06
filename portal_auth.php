<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getUsers(): array
{
    return require __DIR__ . '/portal_users.php';
}

function loginUser(string $email, string $password): bool
{
    foreach (getUsers() as $user) {
        if (strcasecmp($user['email'], $email) === 0 && password_verify($password, $user['password'])) {
            $_SESSION['client_user'] = [
                'email'   => $user['email'],
                'name'    => $user['name'],
                'company' => $user['company'],
            ];
            session_regenerate_id(true);
            return true;
        }
    }
    return false;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['client_user']);
}

function getCurrentUser(): ?array
{
    return $_SESSION['client_user'] ?? null;
}

function logoutUser(): void
{
    unset($_SESSION['client_user']);
    if (session_status() !== PHP_SESSION_NONE) {
        session_regenerate_id(true);
    }
}
