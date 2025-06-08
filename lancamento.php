<?php

require_once 'config.php';
require_once 'proteger.php';

// Corrigindo para PHP 5.x
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

if (!in_array($tipo, array('entrada', 'sangria', 'despesa'))) {
    die('Tipo inválido.');
}

// Definir título da página
$titulo = ucfirst($tipo);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = isset($_POST['data']) ? $_POST['data'] : null;
    $valor = isset($_POST['valor']) ? $_POST['valor'] : null;
    $descricao = isset($_POST['descricao']) ? $_POST['descricao'] : null;
    $caixa = isset($_POST['caixa']) ? $_POST['caixa'] : null;
    $destino = isset($_POST['destino']) ? $_POST['destino'] : null;
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

    if ($tipo === 'entrada') {
        $stmt = $conn->prepare("INSERT INTO entradas (data, valor, descricao, usuario_id) VALUES (?, ?, ?, ?)");
        if (!$stmt) die("Erro no prepare: " . $conn->error);
        $stmt->bind_param("sdsi", $data, $valor, $descricao, $usuario_id);
    } elseif ($tipo === 'sangria') {
        $stmt = $conn->prepare("INSERT INTO sangrias (data, valor, caixa, destino, usuario_id) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) die("Erro no prepare: " . $conn->error);
        $stmt->bind_param("sdssi", $data, $valor, $caixa, $destino, $usuario_id);
    } else { // despesa
        $stmt = $conn->prepare("INSERT INTO despesas (data, valor, descricao, usuario_id) VALUES (?, ?, ?, ?)");
        if (!$stmt) die("Erro no prepare: " . $conn->error);
        $stmt->bind_param("sdsi", $data, $valor, $descricao, $usuario_id);
    }

    if ($stmt->execute()) {
        header('Location: index.php');
        exit;
    } else {
        echo "Erro ao salvar: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançar <?php echo htmlspecialchars($titulo); ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
    <h2>Lançar <?php echo htmlspecialchars($titulo); ?></h2>

    <form method="POST" class="formulario">
        <label>Data:</label>
        <input type="date" name="data" required>

        <label>Valor:</label>
        <input type="number" step="0.01" name="valor" required>

        <?php if ($tipo === 'entrada' || $tipo === 'despesa'): ?>
            <label>Descrição:</label>
            <input type="text" name="descricao">
        <?php elseif ($tipo === 'sangria'): ?>
            <label>Caixa:</label>
            <input type="text" name="caixa" required>

            <label>Origem:</label>
            <select name="destino" required>
                <option value="banco">Banco</option>
                <option value="caixa">Caixa</option>
            </select>
        <?php endif; ?>

        <button type="submit">Salvar</button>
        <a href="index.php" class="botao-voltar">Cancelar</a>
    </form>

</div>

</body>
</html>
