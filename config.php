<?php

// Database configuration
$host = 'SEU_SERVIDOR';        // ou IP do servidor, ex: '192.168.1.100'
$usuario = 'SEU_USUARIO';   // substitua pelo usuário do seu MySQL
$senha = 'SUA_SENHA';       // substitua pela senha do seu MySQL
$banco = 'SEU_BANCO';   // substitua pelo nome do banco criado

// Performance optimizations
$options = array(
    MYSQLI_OPT_CONNECT_TIMEOUT => 5,
    MYSQLI_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'",
    MYSQLI_OPT_READ_TIMEOUT => 10,
    MYSQLI_OPT_LOCAL_INFILE => false
);

$conn = new mysqli($host, $usuario, $senha, $banco);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Erro na conexão com o banco de dados. Tente novamente mais tarde.");
}

// Set charset for better character handling and security
$conn->set_charset("utf8mb4");

// Optimize MySQL settings for performance
$conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
$conn->query("SET SESSION autocommit = 1");
$conn->query("SET SESSION query_cache_type = ON");

// Function to close connection properly
function close_db_connection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Register shutdown function to close connection
register_shutdown_function('close_db_connection');
?>
