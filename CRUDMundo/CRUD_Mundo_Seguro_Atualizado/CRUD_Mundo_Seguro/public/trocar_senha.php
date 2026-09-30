<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/lib/security.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
    $novaSenha = (string) ($_POST['nova_senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmar_senha'] ?? '');
    $username = (string) (current_user()['username'] ?? '');

    if ($senhaAtual === '') {
        $errors[] = 'Informe a senha atual.';
    }

    if (strlen($novaSenha) < 8) {
        $errors[] = 'A nova senha deve ter pelo menos 8 caracteres.';
    }

    if ($novaSenha !== $confirmacao) {
        $errors[] = 'A confirmação da nova senha não confere.';
    }

    if (!$errors && $senhaAtual === $novaSenha) {
        $errors[] = 'A nova senha deve ser diferente da senha atual.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'SELECT senha FROM usuarios WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $hashAtual = $stmt->fetchColumn();

        if (!$hashAtual || !password_verify($senhaAtual, (string) $hashAtual)) {
            $errors[] = 'Senha atual incorreta.';
        } else {
            $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);

            $update = db()->prepare(
                'UPDATE usuarios
                 SET senha = ?, primeiro_acesso = 0, tentativas_falhas = 0,
                     bloqueado_ate = NULL, dt_acesso = NOW()
                 WHERE username = ?'
            );
            $update->execute([$novoHash, $username]);

            $_SESSION['user']['primeiro_acesso'] = 0;

            audit('Senha alterada com sucesso.');
            flash('success', 'Senha alterada com sucesso.');
            redirect('dashboard.php');
        }
    }
}

$title = 'Trocar senha';
require __DIR__ . '/../app/views/header.php';
?>

<div class="card" style="max-width: 560px">
    <h1>Trocar senha</h1>
    <p class="muted">
        Informe sua senha atual e cadastre uma nova senha com pelo menos 8 caracteres.
        A senha é armazenada somente como hash.
    </p>

    <?php if ($errors): ?>
        <div class="errors" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" data-submit-once>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

        <div style="margin-bottom: 12px">
            <label for="senha_atual">Senha atual</label>
            <input
                id="senha_atual"
                type="password"
                name="senha_atual"
                autocomplete="current-password"
                required
            >
        </div>

        <div style="margin-bottom: 12px">
            <label for="nova_senha">Nova senha</label>
            <input
                id="nova_senha"
                type="password"
                name="nova_senha"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div style="margin-bottom: 12px">
            <label for="confirmar_senha">Confirmar nova senha</label>
            <input
                id="confirmar_senha"
                type="password"
                name="confirmar_senha"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit">Alterar senha</button>
    </form>
</div>

<?php require __DIR__ . '/../app/views/footer.php'; ?>
