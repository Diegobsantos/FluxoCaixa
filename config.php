<?php

$host = 'SEU_SERVIDOR';        // ou IP do servidor, ex: '192.168.1.100'
$usuario = 'SEU_USUARIO';   // substitua pelo usuário do seu MySQL
$senha = 'SUA_SENHA';       // substitua pela senha do seu MySQL
$banco = 'SEU_BANCO';   // substitua pelo nome do banco criado

$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Define charset para evitar problemas com acentuação
$conn->set_charset("utf8");
?>
