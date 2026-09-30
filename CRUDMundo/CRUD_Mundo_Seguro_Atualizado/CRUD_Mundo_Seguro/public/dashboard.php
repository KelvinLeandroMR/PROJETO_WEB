<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';
require_login();

$counts = [];
foreach (['continentes' => 'Continentes', 'paises' => 'Países', 'cidades' => 'Cidades', 'governantes' => 'Governantes'] as $table => $label) {
    $counts[$table] = (int)db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}
$recent = db()->query('SELECT l.*, COALESCE(u.nome, l.username) AS nome_usuario FROM logs l LEFT JOIN usuarios u ON u.username = l.username ORDER BY l.id_log DESC LIMIT 8')->fetchAll();
$title = 'Dashboard';
require __DIR__ . '/../app/views/header.php';
?>
<div class="page-head"><div><h1>Dashboard</h1><p class="muted">Visão geral do CRUD Mundo.</p></div></div>
<div class="grid" style="margin-bottom:18px">
<?php foreach ($counts as $table => $count): ?>
    <div class="card"><div class="muted"><?= h(ucfirst($table)) ?></div><div class="stat"><?= $count ?></div></div>
<?php endforeach; ?>
</div>
<div class="card">
    <h2>Atividade recente</h2>
    <div class="table-wrap"><table><thead><tr><th>Data</th><th>Usuário</th><th>Descrição</th></tr></thead><tbody>
    <?php foreach ($recent as $log): ?>
        <tr><td><?= h($log['data_acesso']) ?></td><td><?= h($log['nome_usuario'] ?? 'N/A') ?></td><td><?= h($log['descricao']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
