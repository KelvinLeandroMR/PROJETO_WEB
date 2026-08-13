<?php
/**
 * API REST - Continentes
 * GET    api/continentes.php            -> lista todos
 * GET    api/continentes.php?id=1       -> busca um
 * POST   api/continentes.php            -> cria (JSON no corpo)
 * PUT    api/continentes.php?id=1       -> atualiza (JSON no corpo)
 * DELETE api/continentes.php?id=1       -> exclui
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

iniciarSessao();
exigirLogin(); // qualquer usuário autenticado pode consultar (GET)

$db = (new Database())->getConnection();
$metodo = $_SERVER['REQUEST_METHOD'];

// Apenas o Administrador pode criar, editar ou excluir registros
if (in_array($metodo, ['POST', 'PUT', 'DELETE'], true)) {
    exigirAdmin();
}

switch ($metodo) {

    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $db->prepare('SELECT * FROM continentes WHERE id_continente = :id');
            $stmt->execute([':id' => $_GET['id']]);
            $continente = $stmt->fetch();

            if (!$continente) {
                enviarResposta(false, null, 'Continente não encontrado.', 404);
            }
            enviarResposta(true, $continente);
        }

        $sql = 'SELECT * FROM continentes';
        $params = [];
        if (!empty($_GET['busca'])) {
            $sql .= ' WHERE nome LIKE :busca';
            $params[':busca'] = '%' . $_GET['busca'] . '%';
        }
        $sql .= ' ORDER BY nome ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        enviarResposta(true, $stmt->fetchAll());
        break;

    case 'POST':
        $dados = lerCorpoRequisicao();
        $faltando = validarCamposObrigatorios($dados, ['nome']);
        if ($faltando) {
            enviarResposta(false, null, 'Campos obrigatórios ausentes: ' . implode(', ', $faltando), 422);
        }

        try {
            $stmt = $db->prepare(
                'INSERT INTO continentes (nome, populacao, area_km2) VALUES (:nome, :populacao, :area_km2)'
            );
            $stmt->execute([
                ':nome'      => $dados['nome'],
                ':populacao' => $dados['populacao'] ?? 0,
                ':area_km2'  => $dados['area_km2'] ?? 0,
            ]);
            enviarResposta(true, ['id_continente' => (int) $db->lastInsertId()], 'Continente cadastrado com sucesso.', 201);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                enviarResposta(false, null, 'Já existe um continente com esse nome.', 409);
            }
            enviarResposta(false, null, 'Erro ao cadastrar continente: ' . $e->getMessage(), 500);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            enviarResposta(false, null, 'Informe o id do continente a ser atualizado.', 400);
        }
        $dados = lerCorpoRequisicao();
        $faltando = validarCamposObrigatorios($dados, ['nome']);
        if ($faltando) {
            enviarResposta(false, null, 'Campos obrigatórios ausentes: ' . implode(', ', $faltando), 422);
        }

        try {
            $stmt = $db->prepare(
                'UPDATE continentes SET nome = :nome, populacao = :populacao, area_km2 = :area_km2 WHERE id_continente = :id'
            );
            $stmt->execute([
                ':nome'      => $dados['nome'],
                ':populacao' => $dados['populacao'] ?? 0,
                ':area_km2'  => $dados['area_km2'] ?? 0,
                ':id'        => $_GET['id'],
            ]);

            if ($stmt->rowCount() === 0) {
                enviarResposta(false, null, 'Continente não encontrado.', 404);
            }
            enviarResposta(true, null, 'Continente atualizado com sucesso.');
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao atualizar continente: ' . $e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            enviarResposta(false, null, 'Informe o id do continente a ser excluído.', 400);
        }

        try {
            $stmt = $db->prepare('DELETE FROM continentes WHERE id_continente = :id');
            $stmt->execute([':id' => $_GET['id']]);

            if ($stmt->rowCount() === 0) {
                enviarResposta(false, null, 'Continente não encontrado.', 404);
            }
            enviarResposta(true, null, 'Continente excluído com sucesso.');
        } catch (PDOException $e) {
            // Erro 1451 = violação de chave estrangeira (existem países associados)
            if ($e->getCode() === '23000') {
                enviarResposta(false, null, 'Não é possível excluir: existem países associados a este continente.', 409);
            }
            enviarResposta(false, null, 'Erro ao excluir continente: ' . $e->getMessage(), 500);
        }
        break;

    default:
        enviarResposta(false, null, 'Método não suportado.', 405);
}
