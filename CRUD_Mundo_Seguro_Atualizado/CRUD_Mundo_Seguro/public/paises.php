<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';
require_login();

$pdo=db(); $errors=[]; $edit=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $action=$_POST['action']??'';
    try {
        if ($action==='save') {
            $id=(int)($_POST['id_pais']??0); $nome=trim((string)($_POST['nome']??'')); $continente=(int)($_POST['continente_id']??0);
            $pop=(int)($_POST['populacao']??0); $area=normalize_decimal($_POST['area_km2']??null); $idioma=trim((string)($_POST['idioma']??'')); $clima=trim((string)($_POST['clima']??'')); $regime=trim((string)($_POST['regime_politico']??'')); $moeda=trim((string)($_POST['moeda']??''));
            if($nome==='')$errors[]='Nome é obrigatório.'; if($continente<=0)$errors[]='Selecione um continente.'; if($pop<0)$errors[]='População inválida.'; if($area!==null&&$area<0)$errors[]='Área inválida.';
            if(!$errors){
                if($id>0){$s=$pdo->prepare('UPDATE paises SET nome=?, continente_id=?, populacao=?, area_km2=?, idioma=?, clima=?, regime_politico=?, moeda=? WHERE id_pais=?');$s->execute([$nome,$continente,$pop,$area,$idioma,$clima,$regime,$moeda,$id]);audit("País #{$id} atualizado.");flash('success','País atualizado.');}
                else{$s=$pdo->prepare('INSERT INTO paises(nome,continente_id,populacao,area_km2,idioma,clima,regime_politico,moeda) VALUES(?,?,?,?,?,?,?,?)');$s->execute([$nome,$continente,$pop,$area,$idioma,$clima,$regime,$moeda]);$new=(int)$pdo->lastInsertId();audit("País #{$new} cadastrado.");flash('success','País cadastrado.');}
                redirect('paises.php');
            }
        } elseif($action==='delete'){
            $id=(int)$_POST['id_pais'];$s=$pdo->prepare('DELETE FROM paises WHERE id_pais=?');$s->execute([$id]);audit("País #{$id} excluído.");flash('success','País excluído.');redirect('paises.php');
        }
    } catch(PDOException $e){if((int)($e->errorInfo[1]??0)===1451)$errors[]='Não é possível excluir: existem cidades ou governantes vinculados a este país.';elseif((int)($e->errorInfo[1]??0)===1062)$errors[]='Já existe um país com esse nome no continente selecionado.';else$errors[]='Não foi possível concluir a operação.';}
}
if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM paises WHERE id_pais=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch()?:null;}
$continentes=$pdo->query('SELECT id_continente,nome FROM continentes ORDER BY nome')->fetchAll();
$list=$pdo->query('SELECT p.*, c.nome continente, (SELECT GROUP_CONCAT(g.nome ORDER BY g.nome SEPARATOR ", ") FROM governantes g WHERE g.pais_id=p.id_pais) governantes FROM paises p JOIN continentes c ON c.id_continente=p.continente_id ORDER BY p.nome')->fetchAll();
$title='Países'; require __DIR__.'/../app/views/header.php';
?>
<div class="page-head"><div><h1>Países</h1><p class="muted">Cadastre e mantenha países associados a um continente.</p></div></div>
<?php if($errors):?><div class="errors"><ul><?php foreach($errors as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="grid"><div class="card"><h2><?= $edit?'Editar país':'Novo país' ?></h2><form method="post" data-submit-once><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id_pais" value="<?= (int)($edit['id_pais']??0) ?>">
<div style="margin-bottom:12px"><label>Nome</label><input name="nome" maxlength="100" required value="<?=h($edit['nome']??'')?>"></div>
<div style="margin-bottom:12px"><label>Continente</label><select name="continente_id" required><option value="">Selecione</option><?php foreach($continentes as $c):?><option value="<?=$c['id_continente']?>" <?=((int)($edit['continente_id']??0)===(int)$c['id_continente'])?'selected':''?>><?=h($c['nome'])?></option><?php endforeach;?></select></div>
<div class="form-grid"><div><label>População</label><input name="populacao" type="number" min="0" value="<?=h((string)($edit['populacao']??0))?>"></div><div><label>Área (km²)</label><input name="area_km2" type="number" min="0" step="0.01" value="<?=h((string)($edit['area_km2']??''))?>"></div></div>
<div style="margin:12px 0"><label>Idioma</label><input name="idioma" maxlength="100" value="<?=h($edit['idioma']??'')?>"></div>
<div style="margin-bottom:12px"><label>Clima</label><input name="clima" maxlength="120" value="<?=h($edit['clima']??'')?>"></div>
<div style="margin-bottom:12px"><label>Regime político</label><input name="regime_politico" maxlength="120" value="<?=h($edit['regime_politico']??'')?>"></div>
<div style="margin-bottom:12px"><label>Moeda</label><input name="moeda" maxlength="80" value="<?=h($edit['moeda']??'')?>"></div>
<div class="actions"><button type="submit">Salvar</button><?php if($edit):?><a class="btn btn-secondary" href="paises.php">Cancelar</a><?php endif;?></div></form></div>
<div class="card"><h2>Lista</h2><input class="search" data-search-target="#tabela-paises" placeholder="Pesquisar país..."><div class="table-wrap"><table id="tabela-paises"><thead><tr><th>País</th><th>Continente</th><th>População</th><th>Idioma</th><th>Governante(s)</th><th>Ações</th></tr></thead><tbody><?php foreach($list as $p):?><tr><td><?=h($p['nome'])?></td><td><?=h($p['continente'])?></td><td><?=number_format((int)$p['populacao'],0,',','.')?></td><td><?=h($p['idioma'])?></td><td><?=h($p['governantes']??'—')?></td><td class="actions"><a class="btn btn-small" href="?edit=<?=$p['id_pais']?>">Editar</a><form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_pais" value="<?=$p['id_pais']?>"><button class="btn-danger btn-small" data-confirm="Excluir este país?">Excluir</button></form></td></tr><?php endforeach;?></tbody></table></div></div></div>
<?php require __DIR__.'/../app/views/footer.php'; ?>
