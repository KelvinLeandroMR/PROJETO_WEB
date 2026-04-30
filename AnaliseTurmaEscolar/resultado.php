<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Resultado</title>

<link rel="stylesheet" href="css/css.css">
</head>
<body>

<div class="container">

<?php

$turma = $_POST["turma"];   
$nomes = $_POST["nome"];
$n1 = $_POST["n1"];
$n2 = $_POST["n2"];
$nt = $_POST["nt"];

$totalAlunos = count($nomes);

$somaMedias = 0;
$maiorMedia = 0;
$menorMedia = 10;

$aprovados = 0;
$rec = 0;
$rep = 0;

$somaNotas = 0;

echo "<h1>Relatório da Turma</h1>";
echo "<h2>$turma</h2>";

echo "<table>
<tr>
<th>Nome</th>
<th>Média</th>
<th>Raiz</th>
<th>Diferença</th>
<th>Situação</th>
</tr>";

for ($i = 0; $i < $totalAlunos; $i++) {

    $media = ($n1[$i] + $n2[$i] + $nt[$i]) / 3;
    $raiz = sqrt($n1[$i] + $n2[$i] + $nt[$i]);

    $maiorNota = max($n1[$i], $n2[$i], $nt[$i]);
    $menorNota = min($n1[$i], $n2[$i], $nt[$i]);
    $dif = abs($maiorNota - $menorNota);

    $somaMedias += $media;
    $somaNotas += ($n1[$i] + $n2[$i] + $nt[$i]);

    if ($media > $maiorMedia) $maiorMedia = $media;
    if ($media < $menorMedia) $menorMedia = $media;

    if ($media >= 7) {
        $situacao = "Aprovado";
        $classe = "aprovado";
        $aprovados++;
    } elseif ($media >= 5) {
        $situacao = "Recuperação";
        $classe = "recuperacao";
        $rec++;
    } else {
        $situacao = "Reprovado";
        $classe = "reprovado";
        $rep++;
    }

    echo "<tr>
    <td>{$nomes[$i]}</td>
    <td>" . number_format($media,2) . "</td>
    <td>" . number_format($raiz,2) . "</td>
    <td>" . number_format($dif,2) . "</td>
    <td class='$classe'>$situacao</td>
    </tr>";
}

echo "</table>";

$mediaGeral = $somaMedias / $totalAlunos;
$percentual = ($aprovados / $totalAlunos) * 100;

echo "<h3>Relatório Estatístico</h3>";
echo "Média Geral: " . number_format($mediaGeral,2) . "<br>";
echo "Maior Média: " . number_format($maiorMedia,2) . "<br>";
echo "Menor Média: " . number_format($menorMedia,2) . "<br>";
echo "Aprovados: $aprovados<br>";
echo "Recuperação: $rec<br>";
echo "Reprovados: $rep<br>";
echo "Percentual de aprovação: " . number_format($percentual,2) . "%<br>";
echo "Soma total das notas: " . number_format($somaNotas,2);

?>

</div>

</body>
</html>