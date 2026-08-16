<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';
require_login();

$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $atual=(string)($_POST['senha_atual']??'');$nova=(string)($_POST['nova_senha']??'');$confirm=(string)($_POST['confirmar_senha']??'');
    if(strlen($nova)<8)$errors[]='A nova senha deve ter pelo menos 8 caracteres.';if($nova!==$confirm)$errors[]='As novas senhas não conferem.';
    if(!$errors){$s=db()->prepare('SELECT senha FROM usuarios WHERE username=?');$s->execute([current_user()['username']]);$hash=$s->fetchColumn();if(!$hash||!password_verify($atual,(string)$hash))$errors[]='Senha atual incorreta';else{$u=db()->prepare('UPDATE usuarios SET senha=?,dt_acesso=NOW() WHERE username=?');$u->execute([password_hash($nova,PASSWORD_DEFAULT),current_user()['username']]);audit('Senha alterada com sucesso.');flash('success','Senha alterada com sucesso.');redirect('dashboard.php');}}
}
$title='Trocar senha';require __DIR__.'/../app/views/header.php';
?>
<div class="card" style="max-width:560px"><h1>Trocar senha</h1><p class="muted">A senha é armazenada somente como hash; ela nunca é exibida em texto puro.</p><?php if($errors):?><div class="errors"><ul><?php foreach($errors as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif;?><form method="post" data-submit-once><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><div style="margin-bottom:12px"><label>Senha atual</label><input type="password" name="senha_atual" required></div><div style="margin-bottom:12px"><label>Nova senha</label><input type="password" name="nova_senha" minlength="8" required></div><div style="margin-bottom:12px"><label>Confirmar nova senha</label><input type="password" name="confirmar_senha" minlength="8" required></div><button type="submit">Alterar senha</button></form></div>
<?php require __DIR__.'/../app/views/footer.php'; ?>
