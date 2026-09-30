<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): void
{
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(419);
        exit('Token CSRF inválido ou expirado. Volte e tente novamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'username' => $user['username'],
        'nome'     => $user['nome'],
        'status'   => $user['status'],
        'tipo'     => $user['tipo'],
        'primeiro_acesso' => (int)$user['primeiro_acesso'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return is_logged_in() && current_user()['tipo'] === 'A';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Faça login para continuar.');
        redirect('index.php');
    }

    // Primeiro acesso: o usuário não pode entrar no restante do sistema
    // enquanto não trocar a senha provisória.
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
    if ((int)(current_user()['primeiro_acesso'] ?? 0) === 1 && $currentPage !== 'trocar_senha.php' && $currentPage !== 'logout.php') {
        flash('warning', 'Por segurança, troque sua senha antes de continuar.');
        redirect('trocar_senha.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Acesso negado. Área exclusiva para administradores.');
    }
}

function audit(string $description, ?string $username = null): void
{
    $username = $username ?? (current_user()['username'] ?? null);
    try {
        $stmt = db()->prepare('INSERT INTO logs (data_acesso, descricao, username) VALUES (NOW(), ?, ?)');
        $stmt->execute([$description, $username]);
    } catch (Throwable $e) {
        // Falha no log não deve exibir detalhes internos ao usuário.
        error_log('Falha ao gravar log: ' . $e->getMessage());
    }
}

function validate_required(array $data, array $fields): array
{
    $errors = [];
    foreach ($fields as $field => $label) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $errors[] = "O campo {$label} é obrigatório.";
        }
    }
    return $errors;
}

function normalize_decimal(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    $value = str_replace(',', '.', trim((string)$value));
    return is_numeric($value) ? (float)$value : null;
}
