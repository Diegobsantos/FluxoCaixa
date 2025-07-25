<?php

require_once 'config.php';
require_once 'proteger.php';

// Set performance headers
header('Cache-Control: no-cache, must-revalidate');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT'); // 5 minutes cache for static content

$data_hoje = date('Y-m-d');
$data = isset($_GET['data']) ? $_GET['data'] : $data_hoje;

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    $data = $data_hoje;
}

// Optimize: Single query to get all data for the selected date
$sql = "SELECT 
    'entrada' as tipo,
    e.id,
    e.data,
    e.valor,
    e.descricao,
    u.usuario,
    '' as caixa,
    '' as destino
FROM entradas e 
LEFT JOIN usuarios u ON e.usuario_id = u.id 
WHERE e.data = ?

UNION ALL

SELECT 
    'sangria' as tipo,
    s.id,
    s.data,
    s.valor,
    '' as descricao,
    u.usuario,
    s.caixa,
    s.destino
FROM sangrias s 
LEFT JOIN usuarios u ON s.usuario_id = u.id 
WHERE s.data = ?

UNION ALL

SELECT 
    'despesa' as tipo,
    d.id,
    d.data,
    d.valor,
    d.descricao,
    u.usuario,
    '' as caixa,
    '' as destino
FROM despesas d 
LEFT JOIN usuarios u ON d.usuario_id = u.id 
WHERE d.data = ?

ORDER BY data DESC, tipo";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $data, $data, $data);
$stmt->execute();
$result = $stmt->get_result();

// Initialize arrays
$entradas = [];
$sangrias_banco = [];
$sangrias_caixas = [];
$despesas = [];
$total_recebimentos = 0.00;
$total_banco = 0.00;
$total_caixa = 0.00;
$total_despesas = 0.00;

// Process all results in a single loop
while ($row = $result->fetch_assoc()) {
    switch ($row['tipo']) {
        case 'entrada':
            $entradas[] = $row;
            $total_recebimentos += $row['valor'];
            break;
        case 'sangria':
            if ($row['destino'] === 'banco') {
                $sangrias_banco[] = $row;
                $total_banco += $row['valor'];
            } else {
                $sangrias_caixas[] = $row;
                $total_caixa += $row['valor'];
            }
            break;
        case 'despesa':
            $despesas[] = $row;
            $total_despesas += $row['valor'];
            break;
    }
}
$stmt->close();

// Optimized saldo calculation
$saldo_anterior = 0.00;
$sql_saldo = "SELECT 
    (
        COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) +
        COALESCE(SUM(CASE WHEN tipo = 'sangria' THEN valor ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END), 0)
    ) AS saldo_anterior
FROM (
    SELECT 'entrada' as tipo, valor FROM entradas WHERE data < ?
    UNION ALL
    SELECT 'sangria' as tipo, valor FROM sangrias WHERE data < ?
    UNION ALL
    SELECT 'despesa' as tipo, valor FROM despesas WHERE data < ?
) AS movimentacoes";

$stmt_saldo = $conn->prepare($sql_saldo);
$stmt_saldo->bind_param("sss", $data, $data, $data);
$stmt_saldo->execute();
$res_saldo = $stmt_saldo->get_result();
if ($res_saldo && $row_saldo = $res_saldo->fetch_assoc()) {
    $saldo_anterior = $row_saldo['saldo_anterior'] ?? 0.00;
}
$stmt_saldo->close();

function formatar_moeda($v) {
    return 'R$ ' . number_format($v, 2, ',', '.');
}

$total_entradas = $total_recebimentos + $total_banco + $total_caixa;
$saldo_final = $saldo_anterior + $total_entradas - $total_despesas;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Controle de Caixa">
    <title>Painel de Caixa</title>
    <link rel="stylesheet" href="assets/style.css?v=1.1">
    <link rel="preload" href="assets/style.css?v=1.1" as="style">
    <style>
        /* Critical CSS inline for faster rendering */
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .loading { display: none; }
    </style>
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
        <label for="data">Consultar data:</label>
        <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($data); ?>" required>
        <button type="submit">Filtrar</button>
    </form>

    <div class="resumo-header">
        <h2>Saldo Anterior: <?php echo formatar_moeda($saldo_anterior); ?></h2>
    </div>

    <?php if (!empty($sangrias_banco)): ?>
    <section>
        <h2>Sangrias Banco</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Data</th><th>Valor</th><th>Usuário</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sangrias_banco as $s): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($s['data'])); ?></td>
                        <td><?php echo formatar_moeda($s['valor']); ?></td>
                        <td><?php echo htmlspecialchars($s['usuario']); ?></td>
                        <td>
                            <a href="editar.php?tipo=sangria&id=<?php echo $s['id']; ?>">Editar</a> | 
                            <a href="excluir.php?tipo=sangria&id=<?php echo $s['id']; ?>" onclick="return confirm('Excluir este lançamento?');">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($sangrias_caixas)): ?>
    <section>
        <h2>Sangrias Caixas</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Data</th><th>Valor</th><th>Caixa</th><th>Usuário</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sangrias_caixas as $s): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($s['data'])); ?></td>
                        <td><?php echo formatar_moeda($s['valor']); ?></td>
                        <td><?php echo htmlspecialchars($s['caixa']); ?></td>
                        <td><?php echo htmlspecialchars($s['usuario']); ?></td>
                        <td>
                            <a href="editar.php?tipo=sangria&id=<?php echo $s['id']; ?>">Editar</a> | 
                            <a href="excluir.php?tipo=sangria&id=<?php echo $s['id']; ?>" onclick="return confirm('Excluir este lançamento?');">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($entradas)): ?>
    <section>
        <h2>Recebimentos Diversos</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Data</th><th>Valor</th><th>Descrição</th><th>Usuário</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($entradas as $e): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($e['data'])); ?></td>
                        <td><?php echo formatar_moeda($e['valor']); ?></td>
                        <td><?php echo htmlspecialchars($e['descricao']); ?></td>
                        <td><?php echo htmlspecialchars($e['usuario']); ?></td>
                        <td>
                            <a href="editar.php?tipo=entrada&id=<?php echo $e['id']; ?>">Editar</a> | 
                            <a href="excluir.php?tipo=entrada&id=<?php echo $e['id']; ?>" onclick="return confirm('Excluir este lançamento?');">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($despesas)): ?>
    <section>
        <h2>Despesas</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Usuário</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($despesas as $d): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($d['data'])); ?></td>
                        <td><?php echo htmlspecialchars($d['descricao']); ?></td>
                        <td><?php echo formatar_moeda($d['valor']); ?></td>
                        <td><?php echo htmlspecialchars($d['usuario']); ?></td>
                        <td>
                            <a href="editar.php?tipo=despesa&id=<?php echo $d['id']; ?>">Editar</a> | 
                            <a href="excluir.php?tipo=despesa&id=<?php echo $d['id']; ?>" onclick="return confirm('Excluir este lançamento?');">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <section class="resumo">
        <h2>Resumo do Dia</h2>
        <div class="resumo-grid">
            <div class="resumo-item positivo">
                <strong>Total de Entradas:</strong> <?php echo formatar_moeda($total_entradas); ?>
            </div>
            <div class="resumo-item negativo">
                <strong>Total de Despesas:</strong> <?php echo formatar_moeda($total_despesas); ?>
            </div>
            <div class="resumo-item <?php echo $saldo_final >= 0 ? 'positivo' : 'negativo'; ?>">
                <strong>Saldo Final:</strong> <?php echo formatar_moeda($saldo_final); ?>
            </div>
        </div>
    </section>
</div>

<script>
// Lazy load non-critical JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on date change for better UX
    const dateInput = document.getElementById('data');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            this.form.submit();
        });
    }
});
</script>

</body>
</html>
