<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/security.php';

$title = $title ?? APP_NAME;
$flashes = pull_flashes();
$user = current_user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
    <title><?= h($title) ?> | <?= h(APP_NAME) ?></title>
    <meta name="description" content="Sistema CRUD Mundo com PHP, MySQL e controles básicos de segurança.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="container topbar-inner">
        <a class="brand" href="dashboard.php">CRUD Mundo</a>
        <?php if ($user): ?>
            <nav class="nav" aria-label="Navegação principal">
                <a href="dashboard.php">Início</a>
                <a href="continentes.php">Continentes</a>
                <a href="paises.php">Países</a>
                <a href="cidades.php">Cidades</a>
                <a href="governantes.php">Governantes</a>
                <?php if ($user['tipo'] === 'A'): ?>
                    <a href="usuarios.php">Usuários</a>
                    <a href="logs.php">Logs</a>
                <?php endif; ?>
                <a href="trocar_senha.php">Trocar senha</a>
                <a href="logout.php" onclick="return confirm('Deseja realmente sair?')">Sair</a>
            </nav>
            <div class="user-chip">
                <?= h($user['nome']) ?> · <?= $user['tipo'] === 'A' ? 'Administrador' : 'Usuário' ?>
            </div>
        <?php endif; ?>
    </div>
</header>
<main class="container main-content">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>" role="alert">
            <?= h($flash['message']) ?>
        </div>
    <?php endforeach; ?>
