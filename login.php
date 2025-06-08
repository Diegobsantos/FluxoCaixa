<?php
date_default_timezone_set('America/Recife');

require_once 'config.php';
session_start();

// Verificar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    $stmt = $conn->prepare("SELECT id, senha FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $dados = $res->fetch_assoc();
        if (md5($senha) === $dados['senha']) { // Se quiser usar password_hash depois, mudamos aqui
            $_SESSION['usuario_id'] = $dados['id'];
            header('Location: index.php');
            exit;
        }
    }
    $erro = "Usuário ou senha inválidos!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="login-container">
    <h2>Login do Sistema</h2>

    <?php if (isset($erro)): ?>
        <div class="erro-login"><?php echo $erro; ?></div>
    <?php endif; ?>

    <form method="POST" class="formulario">
        <label>Usuário:</label>
        <input type="text" name="usuario" required>
		<br>
        <label>Senha:</label>
        <input type="password" name="senha" required>
		<br>
        <button type="submit">Entrar</button>
    </form>
</div>

</body>
</html>
