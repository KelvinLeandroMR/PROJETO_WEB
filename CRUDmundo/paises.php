<?php
/**
 * API REST - Países
 * GET    api/paises.php                       -> lista todos (aceita ?busca=, ?id_continente=)
 * GET    api/paises.php?id=1                  -> busca um
 * POST   api/paises.php                       -> cria (JSON no corpo)
 * PUT    api/paises.php?id=1                  -> atualiza (JSON no corpo)
 * DELETE api/paises.php?id=1                  -> exclui
 * DELETE api/paises.php?id=1&cascata=1        -> exclui o país e todas as suas cidades
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
    SELECT p.*, c.nome AS nome_continente, g.nome AS nome_governante
      FROM paises p
      JOIN continentes c ON c.id_continente = p.id_continente
 LEFT JOIN governantes g ON g.id_governante = p.id_governante
';

switch ($metodo) {

    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $db->prepare(SELECT_BASE . ' WHERE p.id_pais = :id');
            $stmt->execute([':id' => $_GET['id']]);
            $pais = $stmt->fetch();

            if (!$pais) {
                enviarResposta(false, null, 'País não encontrado.', 404);
            }
            enviarResposta(true, $pais);
        }

        $sql = SELECT_BASE;
        $condicoes = [];
        $params = [];

        if (!empty($_GET['busca'])) {
            $condicoes[] = 'p.nome LIKE :busca';
            $params[':busca'] = '%' . $_GET['busca'] . '%';
        }
        if (!empty($_GET['id_continente'])) {
            $condicoes[] = 'p.id_continente = :id_continente';
            $params[':id_continente'] = $_GET['id_continente'];
        }
        if ($condicoes) {
            $sql .= ' WHERE ' . implode(' AND ', $condicoes);
        }
        $sql .= ' ORDER BY p.nome ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        enviarResposta(true, $stmt->fetchAll());
        break;

    case 'POST':
        $dados = lerCorpoRequisicao();
        $faltando = validarCamposObrigatorios($dados, ['nome', 'id_continente']);
        if ($faltando) {
            enviarResposta(false, null, 'Campos obrigatórios ausentes: ' . implode(', ', $faltando), 422);
        }

        try {
            $stmt = $db->prepare(
                'INSERT INTO paises (nome, id_continente, populacao, area_km2, idioma, id_governante, clima, regime_politico, moeda)
                 VALUES (:nome, :id_continente, :populacao, :area_km2, :idioma, :id_governante, :clima, :regime, :moeda)'
            );
            $stmt->execute([
                ':nome'          => $dados['nome'],
                ':id_continente' => $dados['id_continente'],
                ':populacao'     => $dados['populacao'] ?? 0,
                ':area_km2'      => $dados['area_km2'] ?? 0,
                ':idioma'        => $dados['idioma'] ?? null,
                ':id_governante' => $dados['id_governante'] ?: null,
                ':clima'         => $dados['clima'] ?? null,
                ':regime'        => $dados['regime_politico'] ?? null,
                ':moeda'         => $dados['moeda'] ?? null,
            ]);
            enviarResposta(true, ['id_pais' => (int) $db->lastInsertId()], 'País cadastrado com sucesso.', 201);
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao cadastrar país: ' . $e->getMessage(), 500);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            enviarResposta(false, null, 'Informe o id do país a ser atualizado.', 400);
        }
        $dados = lerCorpoRequisicao();
        $faltando = validarCamposObrigatorios($dados, ['nome', 'id_continente']);
        if ($faltando) {
            enviarResposta(false, null, 'Campos obrigatórios ausentes: ' . implode(', ', $faltando), 422);
        }

        try {
            $stmt = $db->prepare(
                'UPDATE paises SET nome = :nome, id_continente = :id_continente, populacao = :populacao,
                 area_km2 = :area_km2, idioma = :idioma, id_governante = :id_governante,
                 clima = :clima, regime_politico = :regime, moeda = :moeda
                 WHERE id_pais = :id'
            );
            $stmt->execute([
                ':nome'          => $dados['nome'],
                ':id_continente' => $dados['id_continente'],
                ':populacao'     => $dados['populacao'] ?? 0,
                ':area_km2'      => $dados['area_km2'] ?? 0,
                ':idioma'        => $dados['idioma'] ?? null,
                ':id_governante' => $dados['id_governante'] ?: null,
                ':clima'         => $dados['clima'] ?? null,
                ':regime'        => $dados['regime_politico'] ?? null,
                ':moeda'         => $dados['moeda'] ?? null,
                ':id'            => $_GET['id'],
            ]);

            if ($stmt->rowCount() === 0) {
                enviarResposta(false, null, 'País não encontrado ou nenhum dado alterado.', 404);
            }
            enviarResposta(true, null, 'País atualizado com sucesso.');
        } catch (PDOException $e) {
            enviarResposta(false, null, 'Erro ao atualizar país: ' . $e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            enviarResposta(false, null, 'Informe o id do país a ser excluído.', 400);
        }
        $idPais = $_GET['id'];

        try {
            $stmt = $db->prepare('SELECT COUNT(*) AS total FROM cidades WHERE id_pais = :id');
            $stmt->execute([':id' => $idPais]);
            $totalCidades = (int) $stmt->fetch()['total'];

            if ($totalCidades > 0 && empty($_GET['cascata'])) {
                enviarResposta(
                    false,
                    ['total_cidades' => $totalCidades],
                    "Não é possível excluir: existem {$totalCidades} cidade(s) vinculada(s) a este país. " .
                    "Repita a exclusão com o parâmetro cascata=1 para excluir o país e suas cidades.",
                    409
                );
            }

            $db->beginTransaction();
            if ($totalCidades > 0) {
                $db->prepare('DELETE FROM cidades WHERE id_pais = :id')->execute([':id' => $idPais]);
            }
            $stmt = $db->prepare('DELETE FROM paises WHERE id_pais = :id');
            $stmt->execute([':id' => $idPais]);

            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                enviarResposta(false, null, 'País não encontrado.', 404);
            }
            $db->commit();
            enviarResposta(true, null, 'País excluído com sucesso.');
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            enviarResposta(false, null, 'Erro ao excluir país: ' . $e->getMessage(), 500);
        }
        break;

    default:
        enviarResposta(false, null, 'Método não suportado.', 405);
}
