<?php

require_once 'config.php';
require_once 'proteger.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$registro = null;

if (!in_array($tipo, array('entrada', 'sangria', 'despesa')) || !$id) {
    die("Parâmetros inválidos.");
}

$tabela = ($tipo === 'entrada') ? 'entradas' : (($tipo === 'sangria') ? 'sangrias' : 'despesas');

// Buscar registro
$stmt = $conn->prepare("SELECT * FROM $tabela WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $registro = $result->fetch_assoc();
} else {
    die("Registro não encontrado.");
}
$stmt->close();

// Atualizar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = isset($_POST['data']) ? $_POST['data'] : null;
    $valor = isset($_POST['valor']) ? $_POST['valor'] : null;
    $descricao = isset($_POST['descricao']) ? $_POST['descricao'] : null;
    $caixa = isset($_POST['caixa']) ? $_POST['caixa'] : null;
    $destino = isset($_POST['destino']) ? $_POST['destino'] : null;

    if ($tipo === 'entrada') {
        $stmt = $conn->prepare("UPDATE entradas SET data = ?, valor = ?, descricao = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $data, $valor, $descricao, $id);
    } elseif ($tipo === 'sangria') {
        $stmt = $conn->prepare("UPDATE sangrias SET data = ?, valor = ?, caixa = ?, destino = ? WHERE id = ?");
        $stmt->bind_param("sdssi", $data, $valor, $caixa, $destino, $id);
    } else {
        $stmt = $conn->prepare("UPDATE despesas SET data = ?, valor = ?, descricao = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $data, $valor, $descricao, $id);
    }

    if ($stmt->execute()) {
        header('Location: index.php');
        exit;
    } else {
        echo "Erro ao atualizar: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar <?php echo htmlspecialchars($tipo); ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
    <h2>Editar <?php echo htmlspecialchars($tipo); ?></h2>
    <form method="POST" class="formulario">
        <label>Data:</label>
        <input type="date" name="data" value="<?php echo $registro['data']; ?>" required>

        <label>Valor:</label>
        <input type="number" step="0.01" name="valor" value="<?php echo $registro['valor']; ?>" required>

        <?php if ($tipo === 'entrada' || $tipo === 'despesa'): ?>
            <label>Descrição:</label>
            <input type="text" name="descricao" value="<?php echo $registro['descricao']; ?>">
        <?php elseif ($tipo === 'sangria'): ?>
            <label>Caixa:</label>
            <input type="text" name="caixa" value="<?php echo $registro['caixa']; ?>">

            <label>Origem:</label>
            <select name="destino">
                <option value="banco" <?php if($registro['destino'] === 'banco') echo 'selected'; ?>>Banco</option>
                <option value="caixa" <?php if($registro['destino'] === 'caixa') echo 'selected'; ?>>Caixa</option>
            </select>
        <?php endif; ?>

        <button type="submit">Salvar</button>
        <a href="index.php" class="botao-voltar">Cancelar</a>
    </form>
</div>

</body>
</html>
