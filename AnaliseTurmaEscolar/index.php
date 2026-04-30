<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Sistema Escolar</title>

<link rel="stylesheet" href="css/css.css">
<script>
function gerarCampos() {
    let qtd = document.getElementById("qtd").value;
    let div = document.getElementById("alunos");
    div.innerHTML = "";

    if (qtd <= 0) return;

    for (let i = 0; i < qtd; i++) {
        div.innerHTML += `
        <div class="aluno-box">
            <h3>Aluno ${i+1}</h3>

            <div class="grid">
                <input name="nome[]" placeholder="Nome" required>

                <input name="n1[]" type="number" step="0.1" min="0" max="10" placeholder="Nota 1" required>

                <input name="n2[]" type="number" step="0.1" min="0" max="10" placeholder="Nota 2" required>

                <input name="nt[]" type="number" step="0.1" min="0" max="10" placeholder="Trabalho" required>
            </div>
        </div>
        `;
    }
}

/* segurança extra (caso tentem burlar) */
function validarNotas() {
    let notas = document.querySelectorAll("input[type='number']");
    
    for (let nota of notas) {
        let valor = parseFloat(nota.value);

        if (valor < 0 || valor > 10) {
            alert("As notas devem estar entre 0 e 10.");
            nota.focus();
            return false;
        }
    }
    return true;
}
</script>

</head>
<body>

<div class="container">

<h1>Cadastro da Turma</h1>

<form method="POST" action="resultado.php" onsubmit="return validarNotas()">

    <input name="turma" placeholder="Nome da Turma" required><br><br>

    <input 
        type="number" 
        id="qtd" 
        min="1" 
        placeholder="Quantidade de alunos"
        onchange="gerarCampos()" 
        required
    ><br><br>

    <div id="alunos"></div>

    <button type="submit">Calcular</button>

</form>

</div>

</body>
</html>