<?php
include 'config.php'; // conexão com o banco

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($usuario) || empty($senha)) {
        $mensagem = "Preencha todos os campos.";
    } else {
        // Criptografa a senha com MD5
        $senhaCriptografada = md5($senha);

        // Verifica se o usuário já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $mensagem = "Usuário já existe.";
        } else {
            // Insere novo usuário
            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, senha) VALUES (?, ?)");
            $stmt->bind_param("ss", $usuario, $senhaCriptografada);

            if ($stmt->execute()) {
                $mensagem = "Usuário cadastrado com sucesso!";
            } else {
                $mensagem = "Erro ao cadastrar: " . $stmt->error;
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Usuário</title>
    <style>
        body { font-family: Arial; background-color: #f2f2f2; padding: 30px; }
        form { background: #fff; padding: 20px; border-radius: 6px; width: 300px; margin: auto; }
        label, input { display: block; width: 100%; margin-bottom: 10px; }
        input[type="submit"] { background: #28a745; color: #fff; border: none; cursor: pointer; }
        .mensagem { text-align: center; margin-bottom: 15px; color: #333; }
    </style>
</head>
<body>

<form method="POST">
    <h2>Cadastrar Usuário</h2>
    <?php if ($mensagem): ?>
        <div class="mensagem"><?= $mensagem ?></div>
    <?php endif; ?>
    <label for="usuario">Usuário:</label>
    <input type="text" name="usuario" required>
    
    <label for="senha">Senha:</label>
    <input type="password" name="senha" required>
    
    <input type="submit" value="Cadastrar">
</form>

</body>
</html>
