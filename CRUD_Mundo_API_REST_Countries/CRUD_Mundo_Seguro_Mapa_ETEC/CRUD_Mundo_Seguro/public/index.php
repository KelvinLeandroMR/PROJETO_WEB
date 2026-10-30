<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['senha'] ?? '');
    $errors = validate_required(['username' => $username, 'senha' => $password], [
        'username' => 'Usuário',
        'senha' => 'Senha',
    ]);

    if (!$errors) {
        $stmt = db()->prepare('SELECT username, senha, nome, status, tipo, tentativas_falhas, bloqueado_ate, primeiro_acesso FROM usuarios WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        $locked = $user && $user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time();
        if ($locked) {
            audit('Tentativa de login em conta temporariamente bloqueada.', $username);
            $errors[] = 'Usuário bloqueado temporariamente por excesso de tentativas. Tente novamente mais tarde.';
        } elseif (!$user || !password_verify($password, $user['senha'])) {
            if ($user) {
                $attempts = (int)$user['tentativas_falhas'] + 1;
                if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                    $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
                    $update = db()->prepare('UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = ? WHERE username = ?');
                    $update->execute([$lockUntil, $username]);
                    audit('Conta bloqueada temporariamente após 3 tentativas inválidas.', $username);
                    $errors[] = 'Conta temporariamente bloqueada após 3 tentativas de senha inválida.';
                } else {
                    $update = db()->prepare('UPDATE usuarios SET tentativas_falhas = ? WHERE username = ?');
                    $update->execute([$attempts, $username]);
                    audit('Falha de autenticação.', $username);
                    $errors[] = 'Usuário ou senha inválidos.';
                }
            } else {
                audit('Tentativa de login com usuário inexistente.', null);
                $errors[] = 'Usuário ou senha inválidos.';
            }
        } elseif ($user['status'] !== 'A') {
            audit('Tentativa de login em usuário inativo/bloqueado.', $username);
            $errors[] = 'Este usuário está inativo ou bloqueado. Procure o administrador.';
        } else {
            $update = db()->prepare('UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL, dt_acesso = NOW() WHERE username = ?');
            $update->execute([$username]);
            login_user($user);
            audit('Login realizado com sucesso.', $username);

            if ((int)$user['primeiro_acesso'] === 1) {
                flash('warning', 'Este é seu primeiro acesso. É obrigatório trocar a senha provisória.');
                redirect('trocar_senha.php');
            }

            redirect('dashboard.php');
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container login-shell">
    <div class="card login-card">
        <h1>CRUD Mundo</h1>
        <p class="muted">Acesso seguro ao sistema de países, cidades, continentes e governantes.</p>
        <?php if ($errors): ?>
            <div class="errors"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" data-submit-once>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div style="margin-bottom:14px">
                <label for="username">Username</label>
                <input id="username" name="username" maxlength="50" autocomplete="username" required>
            </div>
            <div style="margin-bottom:14px">
                <label for="senha">Senha</label>
                <input id="senha" type="password" name="senha" autocomplete="current-password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
        <p><small>Após 3 tentativas de senha inválida, o acesso é bloqueado temporariamente.</small></p>
    </div>
</div>
</body>
</html>
