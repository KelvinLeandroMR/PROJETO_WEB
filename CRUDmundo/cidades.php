<?php
/**
 * API REST - Cidades
 * GET    api/cidades.php               -> lista todas (aceita ?busca=, ?id_pais=)
 * GET    api/cidades.php?id=1          -> busca uma
 * POST   api/cidades.php               -> cria (JSON no corpo)
 * PUT    api/cidades.php?id=1          -> atualiza (JSON no corpo)
 * DELETE api/cidades.php?id=1          -> exclui
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

const SELECT_BASE = '
    SELECT ci.*, p.nome AS nome_pais, g.nome AS nome_governante
      FROM cidades ci
      JOIN paises p ON p.id_pais = ci.id_pais
 LEFT JOIN governantes g ON g.id_governante = ci.id_governante
';

switch ($metodo) {

    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $db->prepare(SELECT_BASE . ' WHERE ci.id_cidade = :id');
            $stmt->execute([':id' => $_GET['id']]);
            $cidade = $stmt->fetch();

            if (!$cidade) {
                enviarResposta(false, null, 'Cidade não encontrada.', 404);
            }
            enviarResposta(true, $cidade);
        }

        $sql = SELECT_BASE;
        $condicoes = [];
        $params = [];

        if (!empty($_GET['busca'])) {
            $condicoes[] = 'ci.nome LIKE :busca';
            $params[':busca'] = '%' . $_GET['busca'] . '%';
        }
        if (!empty($_GET['id_pais'])) {
            $condicoes[] = 'ci.id_pais = :id_pais';
            $params[':id_pais'] = $_GET['id_pais'];
        }
        if ($condicoes) {
            $sql .= ' WHERE ' . implode(' AND ', $condicoes);
        }
        $sql .= ' ORDER BY ci.nome ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        enviarResposta(true, $stmt->fetchAll());
        break;

    case 'POST':
        $dados = lerCorpoRequisicao();
        $faltando = validarCamposObrigatorios($dados, ['nome', 'id_pais']);
        if ($faltando) {
            enviarResposta(false, null, 'Campos obrigatórios ausentes: ' . implode(', ', $faltando), 422);
        }

        try {
            $stmt = $db->prepare(
                'INSERT INTO cidades (nome, id_pais, populacao, area_km2, clima, id_governante, data_fundacao)
                 VALUES (:nome, :id_pais, :populacao, :area_km2, :clima, :id_governante, :fundacao)'
            );
            $stmt->execute([
                ':nome'          => $dados['nome'],
                ':id_pais'       => $dados['id_pais'],
                ':populacao'     => $dados['populacao'] ?? 0,
                ':area_km2'      => $dados['area_km2'] ?? 0,
                ':clima'         => $dados['clima'] ?? null,
                ':id_governante' => $dados['id_governante'] ?: null,
                ':fundacao'      => $dados['data_fundacao'] ?: null,
            ]);
            enviarResposta(true, ['id_cidade' => (int) $db->lastInsertId()], 'Cidade cadastrada com sucesso.', 201);
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao cadastrar cidade: ' . $e->getMessage(), 500);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            enviarResposta(false, null, 'Informe o id da cidade a ser atualizada.', 400);
        }
        $dados = lerCorpoRequisicao();
        $faltando = validarCamposObrigatorios($dados, ['nome', 'id_pais']);
        if ($faltando) {
            enviarResposta(false, null, 'Campos obrigatórios ausentes: ' . implode(', ', $faltando), 422);
        }

        try {
            $stmt = $db->prepare(
                'UPDATE cidades SET nome = :nome, id_pais = :id_pais, populacao = :populacao,
                 area_km2 = :area_km2, clima = :clima, id_governante = :id_governante, data_fundacao = :fundacao
                 WHERE id_cidade = :id'
            );
            $stmt->execute([
                ':nome'          => $dados['nome'],
                ':id_pais'       => $dados['id_pais'],
                ':populacao'     => $dados['populacao'] ?? 0,
                ':area_km2'      => $dados['area_km2'] ?? 0,
                ':clima'         => $dados['clima'] ?? null,
                ':id_governante' => $dados['id_governante'] ?: null,
                ':fundacao'      => $dados['data_fundacao'] ?: null,
                ':id'            => $_GET['id'],
            ]);

            if ($stmt->rowCount() === 0) {
                enviarResposta(false, null, 'Cidade não encontrada ou nenhum dado alterado.', 404);
            }
            enviarResposta(true, null, 'Cidade atualizada com sucesso.');
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao atualizar cidade: ' . $e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            enviarResposta(false, null, 'Informe o id da cidade a ser excluída.', 400);
        }

        try {
            $stmt = $db->prepare('DELETE FROM cidades WHERE id_cidade = :id');
            $stmt->execute([':id' => $_GET['id']]);

            if ($stmt->rowCount() === 0) {
                enviarResposta(false, null, 'Cidade não encontrada.', 404);
            }
            enviarResposta(true, null, 'Cidade excluída com sucesso.');
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao excluir cidade: ' . $e->getMessage(), 500);
        }
        break;

    default:
        enviarResposta(false, null, 'Método não suportado.', 405);
}
