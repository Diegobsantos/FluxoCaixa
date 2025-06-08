<?php

require_once 'config.php';
require_once 'proteger.php';

$data_hoje = date('Y-m-d');
$data = isset($_GET['data']) ? $_GET['data'] : $data_hoje;

$saldo_anterior = 0.00;

// Buscar última data com movimentação antes da data atual
$sql = "SELECT MAX(data) AS ultima_data FROM (
    SELECT data FROM entradas WHERE data < ?
    UNION
    SELECT data FROM sangrias WHERE data < ?
    UNION
    SELECT data FROM despesas WHERE data < ?
) AS movimentacoes";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $data, $data, $data);
$stmt->execute();
$res = $stmt->get_result();

if ($res) {
    $row = $res->fetch_assoc();
    if ($row && !empty($row['ultima_data'])) {
        $ultima_data = $row['ultima_data'];

        $sql_saldo = "SELECT 
            (
                IFNULL((SELECT SUM(valor) FROM entradas WHERE data <= ?), 0) +
                IFNULL((SELECT SUM(valor) FROM sangrias WHERE data <= ?), 0) -
                IFNULL((SELECT SUM(valor) FROM despesas WHERE data <= ?), 0)
            ) AS saldo_anterior";

        $stmt2 = $conn->prepare($sql_saldo);
        $stmt2->bind_param("sss", $ultima_data, $ultima_data, $ultima_data);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($res2) {
            $row2 = $res2->fetch_assoc();
            if ($row2 && isset($row2['saldo_anterior'])) {
                $saldo_anterior = $row2['saldo_anterior'];
            }
        }
        $stmt2->close();
    }
}
$stmt->close();

function formatar_moeda($v) {
    return 'R$ ' . number_format($v, 2, ',', '.');
}

// === RECEBIMENTOS ===
$entradas = [];
$total_recebimentos = 0.00;
$stmt = $conn->prepare("SELECT e.*, u.usuario FROM entradas e LEFT JOIN usuarios u ON e.usuario_id = u.id WHERE e.data = ?");
$stmt->bind_param("s", $data);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $entradas[] = $row;
    $total_recebimentos += $row['valor'];
}
$stmt->close();

// === SANGRIA BANCO ===
$sangrias_banco = [];
$total_banco = 0.00;
$stmt = $conn->prepare("SELECT s.*, u.usuario FROM sangrias s LEFT JOIN usuarios u ON s.usuario_id = u.id WHERE s.data = ? AND s.destino = 'banco'");
$stmt->bind_param("s", $data);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $sangrias_banco[] = $row;
    $total_banco += $row['valor'];
}
$stmt->close();

// === SANGRIA CAIXAS ===
$sangrias_caixas = [];
$total_caixa = 0.00;
$stmt = $conn->prepare("SELECT s.*, u.usuario FROM sangrias s LEFT JOIN usuarios u ON s.usuario_id = u.id WHERE s.data = ? AND s.destino = 'caixa'");
$stmt->bind_param("s", $data);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $sangrias_caixas[] = $row;
    $total_caixa += $row['valor'];
}
$stmt->close();

// === DESPESAS ===
$despesas = [];
$total_despesas = 0.00;
$stmt = $conn->prepare("SELECT d.*, u.usuario FROM despesas d LEFT JOIN usuarios u ON d.usuario_id = u.id WHERE d.data = ?");
$stmt->bind_param("s", $data);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $despesas[] = $row;
    $total_despesas += $row['valor'];
}
$stmt->close();

$total_entradas = $total_recebimentos + $total_banco + $total_caixa;
$saldo_final = $saldo_anterior + $total_entradas - $total_despesas;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Caixa</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
    <h1>Controle de Caixa - <?php echo date('d/m/Y', strtotime($data)); ?></h1>

    <div class="acoes">
        <a href="lancamento.php?tipo=entrada" class="botao-acoes verde">+ Recebimento</a>
        <a href="lancamento.php?tipo=sangria" class="botao-acoes azul">+ Sangria</a>
        <a href="lancamento.php?tipo=despesa" class="botao-acoes vermelho">- Despesa</a>
        <a href="logout.php" class="botao-acoes cinza">Sair</a>
    </div>

    <form method="GET" class="data-form">
        <label>Consultar data:</label>
        <input type="date" name="data" value="<?php echo $data; ?>">
        <button type="submit">Filtrar</button>
    </form>

    <h2>Saldo Anterior: <?php echo formatar_moeda($saldo_anterior); ?></h2>

    <h2>Sangrias Banco</h2>
    <table>
        <tr><th>Data</th><th>Valor</th><th>Usuário</th><th>Ações</th></tr>
        <?php foreach ($sangrias_banco as $s): ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($s['data'])); ?></td>
            <td><?php echo formatar_moeda($s['valor']); ?></td>
            <td><?php echo $s['usuario']; ?></td>
            <td><a href="editar.php?tipo=sangria&id=<?php echo $s['id']; ?>">Editar</a> | 
                <a href="excluir.php?tipo=sangria&id=<?php echo $s['id']; ?>" onclick="return confirm('Excluir este lançamento?');">Excluir</a></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Sangrias Caixas</h2>
    <table>
        <tr><th>Data</th><th>Valor</th><th>Caixa</th><th>Usuário</th><th>Ações</th></tr>
        <?php foreach ($sangrias_caixas as $s): ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($s['data'])); ?></td>
            <td><?php echo formatar_moeda($s['valor']); ?></td>
            <td><?php echo $s['caixa']; ?></td>
            <td><?php echo $s['usuario']; ?></td>
            <td><a href="editar.php?tipo=sangria&id=<?php echo $s['id']; ?>">Editar</a> | 
                <a href="excluir.php?tipo=sangria&id=<?php echo $s['id']; ?>" onclick="return confirm('Excluir este lançamento?');">Excluir</a></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Recebimentos Diversos</h2>
    <table>
        <tr><th>Data</th><th>Valor</th><th>Descrição</th><th>Usuário</th><th>Ações</th></tr>
        <?php foreach ($entradas as $e): ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($e['data'])); ?></td>
            <td><?php echo formatar_moeda($e['valor']); ?></td>
            <td><?php echo $e['descricao']; ?></td>
            <td><?php echo $e['usuario']; ?></td>
            <td><a href="editar.php?tipo=entrada&id=<?php echo $e['id']; ?>">Editar</a> | 
                <a href="excluir.php?tipo=entrada&id=<?php echo $e['id']; ?>" onclick="return confirm('Excluir este lançamento?');">Excluir</a></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Despesas</h2>
    <table>
        <tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Usuário</th><th>Ações</th></tr>
        <?php foreach ($despesas as $d): ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($d['data'])); ?></td>
            <td><?php echo $d['descricao']; ?></td>
            <td><?php echo formatar_moeda($d['valor']); ?></td>
            <td><?php echo $d['usuario']; ?></td>
            <td><a href="editar.php?tipo=despesa&id=<?php echo $d['id']; ?>">Editar</a> | 
                <a href="excluir.php?tipo=despesa&id=<?php echo $d['id']; ?>" onclick="return confirm('Excluir este lançamento?');">Excluir</a></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="resumo">
        <h2>Resumo do Dia</h2>
        <p><strong>Total de Entradas:</strong> <?php echo formatar_moeda($total_entradas); ?></p>
        <p><strong>Total de Despesas:</strong> <?php echo formatar_moeda($total_despesas); ?></p>
        <p><strong>Saldo Final:</strong> <?php echo formatar_moeda($saldo_final); ?></p>
    </div>
</div>

</body>
</html>
