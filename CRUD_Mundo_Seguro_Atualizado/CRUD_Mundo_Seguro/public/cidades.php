<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';
require_login();

$pdo=db();$errors=[];$edit=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);$action=$_POST['action']??'';
    try{
        if($action==='save'){
            $id=(int)($_POST['id_cidade']??0);$nome=trim((string)($_POST['nome']??''));$pais=(int)($_POST['pais_id']??0);$pop=(int)($_POST['populacao']??0);$area=normalize_decimal($_POST['area_km2']??null);$clima=trim((string)($_POST['clima']??''));$fund=trim((string)($_POST['data_fundacao']??''));
            if($nome==='')$errors[]='Nome é obrigatório.';if($pais<=0)$errors[]='Selecione um país.';if($pop<0)$errors[]='População inválida.';if($area!==null&&$area<0)$errors[]='Área inválida.';
            if(!$errors){if($id>0){$s=$pdo->prepare('UPDATE cidades SET nome=?, pais_id=?, populacao=?, area_km2=?, clima=?, data_fundacao=? WHERE id_cidade=?');$s->execute([$nome,$pais,$pop,$area,$clima,$fund?:null,$id]);audit("Cidade #{$id} atualizada.");flash('success','Cidade atualizada.');}else{$s=$pdo->prepare('INSERT INTO cidades(nome,pais_id,populacao,area_km2,clima,data_fundacao) VALUES(?,?,?,?,?,?)');$s->execute([$nome,$pais,$pop,$area,$clima,$fund?:null]);$new=(int)$pdo->lastInsertId();audit("Cidade #{$new} cadastrada.");flash('success','Cidade cadastrada.');}redirect('cidades.php');}
        }elseif($action==='delete'){$id=(int)$_POST['id_cidade'];$s=$pdo->prepare('DELETE FROM cidades WHERE id_cidade=?');$s->execute([$id]);audit("Cidade #{$id} excluída.");flash('success','Cidade excluída.');redirect('cidades.php');}
    }catch(PDOException $e){if((int)($e->errorInfo[1]??0)===1451)$errors[]='Não é possível excluir: existem governantes vinculados a esta cidade.';elseif((int)($e->errorInfo[1]??0)===1062)$errors[]='Já existe uma cidade com esse nome no país selecionado.';else$errors[]='Não foi possível concluir a operação.';}
}
if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM cidades WHERE id_cidade=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch()?:null;}
$paises=$pdo->query('SELECT id_pais,nome FROM paises ORDER BY nome')->fetchAll();
$list=$pdo->query('SELECT c.*,p.nome pais,(SELECT GROUP_CONCAT(g.nome ORDER BY g.nome SEPARATOR ", ") FROM governantes g WHERE g.cidade_id=c.id_cidade) governantes FROM cidades c JOIN paises p ON p.id_pais=c.pais_id ORDER BY c.nome')->fetchAll();
$title='Cidades';require __DIR__.'/../app/views/header.php';
?>
<div class="page-head"><div><h1>Cidades</h1><p class="muted">Cada cidade deve estar vinculada a um país existente.</p></div></div>
<?php if($errors):?><div class="errors"><ul><?php foreach($errors as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="grid"><div class="card"><h2><?= $edit?'Editar cidade':'Nova cidade' ?></h2><form method="post" data-submit-once><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id_cidade" value="<?= (int)($edit['id_cidade']??0) ?>">
<div style="margin-bottom:12px"><label>Nome</label><input name="nome" maxlength="120" required value="<?=h($edit['nome']??'')?>"></div><div style="margin-bottom:12px"><label>País</label><select name="pais_id" required><option value="">Selecione</option><?php foreach($paises as $p):?><option value="<?=$p['id_pais']?>" <?=((int)($edit['pais_id']??0)===(int)$p['id_pais'])?'selected':''?>><?=h($p['nome'])?></option><?php endforeach;?></select></div>
<div class="form-grid"><div><label>População</label><input name="populacao" type="number" min="0" value="<?=h((string)($edit['populacao']??0))?>"></div><div><label>Área (km²)</label><input name="area_km2" type="number" min="0" step="0.01" value="<?=h((string)($edit['area_km2']??''))?>"></div></div><div style="margin:12px 0"><label>Clima</label><input name="clima" maxlength="120" value="<?=h($edit['clima']??'')?>"></div><div style="margin-bottom:12px"><label>Data de fundação</label><input name="data_fundacao" type="date" value="<?=h($edit['data_fundacao']??'')?>"></div>
<div class="actions"><button type="submit">Salvar</button><?php if($edit):?><a class="btn btn-secondary" href="cidades.php">Cancelar</a><?php endif;?></div></form></div>
<div class="card"><h2>Lista</h2><input class="search" data-search-target="#tabela-cidades" placeholder="Pesquisar cidade..."><div class="table-wrap"><table id="tabela-cidades"><thead><tr><th>Cidade</th><th>País</th><th>População</th><th>Clima</th><th>Fundação</th><th>Governante(s)</th><th>Ações</th></tr></thead><tbody><?php foreach($list as $c):?><tr><td><?=h($c['nome'])?></td><td><?=h($c['pais'])?></td><td><?=number_format((int)$c['populacao'],0,',','.')?></td><td><?=h($c['clima'])?></td><td><?=h($c['data_fundacao']??'—')?></td><td><?=h($c['governantes']??'—')?></td><td class="actions"><a class="btn btn-small" href="?edit=<?=$c['id_cidade']?>">Editar</a><form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_cidade" value="<?=$c['id_cidade']?>"><button class="btn-danger btn-small" data-confirm="Excluir esta cidade?">Excluir</button></form></td></tr><?php endforeach;?></tbody></table></div></div></div>
<?php require __DIR__.'/../app/views/footer.php'; ?>
