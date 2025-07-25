<?php
date_default_timezone_set('America/Recife');

require_once 'config.php';

// Start session with security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_only_cookies', 1);
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting (simple implementation)
$max_attempts = 5;
$lockout_time = 300; // 5 minutes

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}

// Check if user is locked out
if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_remaining = $lockout_time - (time() - $_SESSION['last_attempt']);
    if ($time_remaining > 0) {
        $erro = "Muitas tentativas de login. Tente novamente em " . ceil($time_remaining / 60) . " minutos.";
    } else {
        // Reset attempts after lockout period
        $_SESSION['login_attempts'] = 0;
    }
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($erro)) {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $erro = "Token de segurança inválido.";
    } else {
        $usuario = trim($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (empty($usuario) || empty($senha)) {
            $erro = "Usuário e senha são obrigatórios.";
        } else {
            // Use prepared statement for security
            $stmt = $conn->prepare("SELECT id, senha FROM usuarios WHERE usuario = ? LIMIT 1");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $dados = $res->fetch_assoc();
                
                // Check if password is using old MD5 (for backward compatibility)
                // In production, you should migrate all passwords to password_hash
                $senha_hash = $dados['senha'];
                $senha_valida = false;
                
                if (strlen($senha_hash) === 32) {
                    // Old MD5 hash - check and migrate
                    if (md5($senha) === $senha_hash) {
                        $senha_valida = true;
                        
                        // Migrate to secure hash
                        $novo_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $novo_hash, $dados['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                } else {
                    // New password_hash format
                    $senha_valida = password_verify($senha, $senha_hash);
                }
                
                if ($senha_valida) {
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    $_SESSION['usuario_id'] = $dados['id'];
                    $_SESSION['usuario_nome'] = $usuario;
                    $_SESSION['login_time'] = time();
                    $_SESSION['login_attempts'] = 0; // Reset attempts on success
                    
                    header('Location: index.php');
                    exit;
                }
            }
            
            // Invalid login
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $erro = "Usuário ou senha inválidos!";
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login do Sistema de Controle de Caixa">
    <title>Login - Sistema de Caixa</title>
    <link rel="stylesheet" href="assets/style.css?v=1.1">
    <style>
        /* Critical CSS inline */
        .login-container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); text-align: center; }
        .erro-login { background-color: #dc3545; color: white; padding: 12px; margin-bottom: 20px; border-radius: 6px; font-weight: 600; }
    </style>
</head>
<body>

<div class="login-container">
    <h1>Login do Sistema</h1>

    <?php if (isset($erro)): ?>
        <div class="erro-login" role="alert"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="POST" class="formulario" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        
        <label for="usuario">Usuário:</label>
        <input type="text" 
               id="usuario" 
               name="usuario" 
               required 
               autocomplete="username"
               value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
               maxlength="50">

        <label for="senha">Senha:</label>
        <input type="password" 
               id="senha" 
               name="senha" 
               required 
               autocomplete="current-password"
               maxlength="255">

        <button type="submit" 
                <?php echo isset($erro) && strpos($erro, 'tentativas') !== false ? 'disabled' : ''; ?>>
            Entrar
        </button>
    </form>

    <div style="margin-top: 20px; font-size: 12px; color: #666;">
        <p>Tentativas restantes: <?php echo max(0, $max_attempts - $_SESSION['login_attempts']); ?></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Focus on first empty field
    const usuarioField = document.getElementById('usuario');
    const senhaField = document.getElementById('senha');
    
    if (!usuarioField.value) {
        usuarioField.focus();
    } else {
        senhaField.focus();
    }
    
    // Auto-clear password field on error
    <?php if (isset($erro)): ?>
    senhaField.value = '';
    <?php endif; ?>
});
</script>

</body>
</html>
