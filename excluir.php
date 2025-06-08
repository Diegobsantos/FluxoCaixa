<?php

require_once 'config.php';
require_once 'proteger.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!in_array($tipo, array('entrada', 'sangria', 'despesa')) || !$id) {
    die("Parâmetros inválidos.");
}

$tabela = ($tipo === 'entrada') ? 'entradas' : (($tipo === 'sangria') ? 'sangrias' : 'despesas');

// Excluir registro
$stmt = $conn->prepare("DELETE FROM $tabela WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $mensagem = "Registro excluído com sucesso.";
} else {
    $mensagem = "Erro ao excluir: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Excluir Registro</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
    <h2>Excluir Registro</h2>
    <p><?php echo htmlspecialchars($mensagem); ?></p>
    <a href="index.php" class="botao-voltar">Voltar</a>
</div>

</body>
</html>
