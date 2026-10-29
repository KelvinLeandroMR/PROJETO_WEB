<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';
require_admin();

$logs=db()->query('SELECT l.*,COALESCE(u.nome,l.username) nome_usuario FROM logs l LEFT JOIN usuarios u ON u.username=l.username ORDER BY l.id_log DESC LIMIT 300')->fetchAll();
$title='Logs de auditoria';require __DIR__.'/../app/views/header.php';
?>
<div class="page-head"><div><h1>Logs de auditoria</h1><p class="muted">Registros de autenticação e operações do sistema.</p></div></div>
<div class="card"><input class="search" data-search-target="#tabela-logs" placeholder="Pesquisar por usuário ou descrição..."><div class="table-wrap"><table id="tabela-logs"><thead><tr><th>ID</th><th>Data</th><th>Usuário</th><th>Descrição</th></tr></thead><tbody><?php foreach($logs as $log):?><tr><td><?=h((string)$log['id_log'])?></td><td><?=h($log['data_acesso'])?></td><td><?=h($log['nome_usuario']??'N/A')?></td><td><?=h($log['descricao'])?></td></tr><?php endforeach;?></tbody></table></div></div>
<?php require __DIR__.'/../app/views/footer.php'; ?>
