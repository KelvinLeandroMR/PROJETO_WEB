<?php
/**
 * API REST - Governantes
 * GET    api/governantes.php            -> lista todos (aceita ?busca=)
 * GET    api/governantes.php?id=1       -> busca um
 * POST   api/governantes.php            -> cria (JSON no corpo)
 * PUT    api/governantes.php?id=1       -> atualiza (JSON no corpo)
 * DELETE api/governantes.php?id=1       -> exclui
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
            $stmt = $db->prepare('SELECT * FROM governantes WHERE id_governante = :id');
            $stmt->execute([':id' => $_GET['id']]);
            $governante = $stmt->fetch();

            if (!$governante) {
                enviarResposta(false, null, 'Governante não encontrado.', 404);
            }
            enviarResposta(true, $governante);
        }

        $sql = 'SELECT * FROM governantes';
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
        $faltando = validarCamposObrigatorios($dados, ['nome', 'data_nascimento', 'data_inicio_mandato']);
        if ($faltando) {
            enviarResposta(false, null, 'Campos obrigatórios ausentes: ' . implode(', ', $faltando), 422);
        }

        try {
            $stmt = $db->prepare(
                'INSERT INTO governantes (nome, partido_politico, data_nascimento, idade, data_inicio_mandato, data_fim_mandato)
                 VALUES (:nome, :partido, :nascimento, :idade, :inicio, :fim)'
            );
            $stmt->execute([
                ':nome'       => $dados['nome'],
                ':partido'    => $dados['partido_politico'] ?? null,
                ':nascimento' => $dados['data_nascimento'],
                ':idade'      => calcularIdade($dados['data_nascimento']),
                ':inicio'     => $dados['data_inicio_mandato'],
                ':fim'        => $dados['data_fim_mandato'] ?? null,
            ]);
            enviarResposta(true, ['id_governante' => (int) $db->lastInsertId()], 'Governante cadastrado com sucesso.', 201);
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao cadastrar governante: ' . $e->getMessage(), 500);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            enviarResposta(false, null, 'Informe o id do governante a ser atualizado.', 400);
        }
        $dados = lerCorpoRequisicao();
        $faltando = validarCamposObrigatorios($dados, ['nome', 'data_nascimento', 'data_inicio_mandato']);
        if ($faltando) {
            enviarResposta(false, null, 'Campos obrigatórios ausentes: ' . implode(', ', $faltando), 422);
        }

        try {
            $stmt = $db->prepare(
                'UPDATE governantes SET nome = :nome, partido_politico = :partido, data_nascimento = :nascimento,
                 idade = :idade, data_inicio_mandato = :inicio, data_fim_mandato = :fim
                 WHERE id_governante = :id'
            );
            $stmt->execute([
                ':nome'       => $dados['nome'],
                ':partido'    => $dados['partido_politico'] ?? null,
                ':nascimento' => $dados['data_nascimento'],
                ':idade'      => calcularIdade($dados['data_nascimento']),
                ':inicio'     => $dados['data_inicio_mandato'],
                ':fim'        => $dados['data_fim_mandato'] ?? null,
                ':id'         => $_GET['id'],
            ]);

            if ($stmt->rowCount() === 0) {
                enviarResposta(false, null, 'Governante não encontrado ou nenhum dado alterado.', 404);
            }
            enviarResposta(true, null, 'Governante atualizado com sucesso.');
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao atualizar governante: ' . $e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            enviarResposta(false, null, 'Informe o id do governante a ser excluído.', 400);
        }

        try {
            $stmt = $db->prepare('DELETE FROM governantes WHERE id_governante = :id');
            $stmt->execute([':id' => $_GET['id']]);

            if ($stmt->rowCount() === 0) {
                enviarResposta(false, null, 'Governante não encontrado.', 404);
            }
            // FK configurada como ON DELETE SET NULL: países/cidades que tinham este
            // governante passam a ficar sem governante vinculado automaticamente.
            enviarResposta(true, null, 'Governante excluído com sucesso.');
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao excluir governante: ' . $e->getMessage(), 500);
        }
        break;

    default:
        enviarResposta(false, null, 'Método não suportado.', 405);
}
