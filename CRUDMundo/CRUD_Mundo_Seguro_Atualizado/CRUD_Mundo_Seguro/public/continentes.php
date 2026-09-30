<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';
require_login();

$pdo = db();
$errors = [];
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int)($_POST['id_continente'] ?? 0);
            $nome = trim((string)($_POST['nome'] ?? ''));
            $pop = (int)($_POST['populacao'] ?? 0);
            $area = normalize_decimal($_POST['area_km2'] ?? null);
            if ($nome === '') $errors[] = 'Nome é obrigatório.';
            if ($pop < 0) $errors[] = 'População não pode ser negativa.';
            if ($area !== null && $area < 0) $errors[] = 'Área não pode ser negativa.';
            if (!$errors) {
                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE continentes SET nome=?, populacao=?, area_km2=? WHERE id_continente=?');
                    $stmt->execute([$nome, $pop, $area, $id]);
                    audit("Continente #{$id} atualizado.");
                    flash('success', 'Continente atualizado.');
                } else {
                    $stmt = $pdo->prepare('INSERT INTO continentes (nome, populacao, area_km2, total_paises) VALUES (?,?,?,0)');
                    $stmt->execute([$nome, $pop, $area]);
                    $newId = (int)$pdo->lastInsertId();
                    audit("Continente #{$newId} cadastrado.");
                    flash('success', 'Continente cadastrado.');
                }
                redirect('continentes.php');
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id_continente'];
            $stmt = $pdo->prepare('DELETE FROM continentes WHERE id_continente=?');
            $stmt->execute([$id]);
            audit("Continente #{$id} excluído.");
            flash('success', 'Continente excluído.');
            redirect('continentes.php');
        }
    } catch (PDOException $e) {
        if ((int)$e->errorInfo[1] === 1451) $errors[] = 'Não é possível excluir: existem países vinculados a este continente.';
        elseif ((int)$e->errorInfo[1] === 1062) $errors[] = 'Já existe um continente com esse nome.';
        else $errors[] = 'Não foi possível concluir a operação.';
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM continentes WHERE id_continente=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
$list = $pdo->query('SELECT * FROM continentes ORDER BY nome')->fetchAll();
$title = 'Continentes';
require __DIR__ . '/../app/views/header.php';
?>
<div class="page-head"><div><h1>Continentes</h1><p class="muted">CRUD de continentes com integridade referencial.</p></div></div>
<?php if ($errors): ?><div class="errors"><ul><?php foreach($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="grid">
<div class="card">
<h2><?= $edit ? 'Editar continente' : 'Novo continente' ?></h2>
<form method="post" data-submit-once><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id_continente" value="<?= (int)($edit['id_continente'] ?? 0) ?>">
<div style="margin-bottom:12px"><label>Nome</label><input name="nome" maxlength="80" required value="<?= h($edit['nome'] ?? '') ?>"></div>
<div style="margin-bottom:12px"><label>População</label><input name="populacao" type="number" min="0" value="<?= h((string)($edit['populacao'] ?? 0)) ?>"></div>
<div style="margin-bottom:12px"><label>Área (km²)</label><input name="area_km2" type="number" min="0" step="0.01" value="<?= h((string)($edit['area_km2'] ?? '')) ?>"></div>
<div class="actions"><button type="submit">Salvar</button><?php if ($edit): ?><a class="btn btn-secondary" href="continentes.php">Cancelar</a><?php endif; ?></div>
</form></div>
<div class="card"><h2>Lista</h2><input class="search" data-search-target="#tabela-continentes" placeholder="Pesquisar continente..."><div class="table-wrap"><table id="tabela-continentes"><thead><tr><th>Nome</th><th>População</th><th>Área</th><th>Países</th><th>Ações</th></tr></thead><tbody>
<?php foreach($list as $item): ?><tr><td><?= h($item['nome']) ?></td><td><?= number_format((int)$item['populacao'],0,',','.') ?></td><td><?= h((string)$item['area_km2']) ?></td><td><?= (int)$item['total_paises'] ?></td><td class="actions"><a class="btn btn-small" href="?edit=<?= (int)$item['id_continente'] ?>">Editar</a><form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_continente" value="<?= (int)$item['id_continente'] ?>"><button class="btn-danger btn-small" data-confirm="Excluir este continente?">Excluir</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
