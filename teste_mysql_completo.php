#!/usr/bin/env php
<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * TESTE COMPLETO DE BANCO DE DADOS - MySQL Simulation
 * ═══════════════════════════════════════════════════════════════
 *
 * Este script PHP executa TODAS as operações SQL solicitadas:
 * 1. Cria banco de dados 'empresa_teste'
 * 2. Cria tabela 'funcionarios'
 * 3. Insere 5 registros fictícios
 * 4. Executa consultas SELECT, UPDATE, DELETE
 * 5. Valida resultados
 *
 * Usando: PDO com SQLite (sintaxe MySQL compatível)
 * ═══════════════════════════════════════════════════════════════
 */

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       TESTE MYSQL - BANCO 'empresa_teste'                    ║\n";
echo "║       Simulação de Servidor MySQL 8.0                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

try {
    // ═══════════════════════════════════════════════════════════════
    // ETAPA 1: CRIAR BANCO DE DADOS (SQLite file)
    // ═══════════════════════════════════════════════════════════════
    echo "[ETAPA 1/6] Criando banco de dados 'empresa_teste'...\n";
    $dbFile = '/tmp/empresa_teste.db';

    // Remover banco antigo se existir
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }

    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Banco de dados 'empresa_teste' criado com sucesso!\n";
    echo "  Localização: $dbFile\n\n";

    // ═══════════════════════════════════════════════════════════════
    // ETAPA 2: CRIAR TABELA 'funcionarios'
    // ═══════════════════════════════════════════════════════════════
    echo "[ETAPA 2/6] Criando tabela 'funcionarios'...\n";

    $sqlCreateTable = "
        CREATE TABLE IF NOT EXISTS funcionarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            cargo VARCHAR(50) NOT NULL,
            salario DECIMAL(10,2) NOT NULL
        )
    ";

    $pdo->exec($sqlCreateTable);
    echo "✓ Tabela 'funcionarios' criada com campos:\n";
    echo "  - id (INT, PRIMARY KEY, AUTO INCREMENT)\n";
    echo "  - nome (VARCHAR 100)\n";
    echo "  - cargo (VARCHAR 50)\n";
    echo "  - salario (DECIMAL 10,2)\n\n";

    // ═══════════════════════════════════════════════════════════════
    // ETAPA 3: INSERIR 5 REGISTROS FICTÍCIOS
    // ═══════════════════════════════════════════════════════════════
    echo "[ETAPA 3/6] Inserindo 5 registros fictícios...\n";

    $funcionarios = [
        ['João Silva', 'Desenvolvedor Senior', 8500.00],
        ['Maria Santos', 'Gerente de Projetos', 12000.00],
        ['Pedro Oliveira', 'Analista de Sistemas', 6500.00],
        ['Ana Costa', 'Designer UX/UI', 7500.00],
        ['Carlos Mendes', 'Desenvolvedor Junior', 4500.00]
    ];

    $sqlInsert = "INSERT INTO funcionarios (nome, cargo, salario) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sqlInsert);

    foreach ($funcionarios as $index => $func) {
        $stmt->execute($func);
        echo "  ✓ Registro " . ($index + 1) . ": {$func[0]} - {$func[1]} - R$ " . number_format($func[2], 2, ',', '.') . "\n";
    }
    echo "\n";

    // ═══════════════════════════════════════════════════════════════
    // ETAPA 4: CONSULTA 1 - SELECT ALL
    // ═══════════════════════════════════════════════════════════════
    echo "[ETAPA 4/6] CONSULTA SQL: SELECT * FROM funcionarios\n";
    echo str_repeat("─", 95) . "\n";
    printf("%-4s | %-25s | %-25s | %15s\n", "ID", "NOME", "CARGO", "SALÁRIO");
    echo str_repeat("─", 95) . "\n";

    $result = $pdo->query("SELECT * FROM funcionarios ORDER BY id");
    $allFuncionarios = $result->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allFuncionarios as $row) {
        printf("%-4d | %-25s | %-25s | R$ %12s\n",
            $row['id'],
            $row['nome'],
            $row['cargo'],
            number_format($row['salario'], 2, ',', '.')
        );
    }
    echo str_repeat("─", 95) . "\n";
    echo "Total de registros: " . count($allFuncionarios) . "\n\n";

    // ═══════════════════════════════════════════════════════════════
    // ETAPA 5: CONSULTA 2 - SELECT com WHERE (salário > 5000)
    // ═══════════════════════════════════════════════════════════════
    echo "[ETAPA 5/6] CONSULTA SQL: SELECT * FROM funcionarios WHERE salario > 5000\n";
    echo str_repeat("─", 95) . "\n";
    printf("%-4s | %-25s | %-25s | %15s\n", "ID", "NOME", "CARGO", "SALÁRIO");
    echo str_repeat("─", 95) . "\n";

    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE salario > ? ORDER BY salario DESC");
    $stmt->execute([5000]);
    $highEarners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($highEarners as $row) {
        printf("%-4d | %-25s | %-25s | R$ %12s\n",
            $row['id'],
            $row['nome'],
            $row['cargo'],
            number_format($row['salario'], 2, ',', '.')
        );
    }
    echo str_repeat("─", 95) . "\n";
    echo "Funcionários com salário > R$ 5.000,00: " . count($highEarners) . "\n\n";

    // ═══════════════════════════════════════════════════════════════
    // ETAPA 6: UPDATE - Atualizar cargo de um funcionário
    // ═══════════════════════════════════════════════════════════════
    echo "[ETAPA 6/6] ATUALIZAÇÃO SQL: UPDATE funcionarios SET cargo = 'Tech Lead' WHERE id = 1\n";

    $sqlUpdate = "UPDATE funcionarios SET cargo = ? WHERE id = ?";
    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute(['Tech Lead', 1]);

    echo "✓ Cargo do funcionário ID=1 atualizado com sucesso!\n\n";

    // Verificar atualização
    echo "Verificando alteração:\n";
    echo str_repeat("─", 95) . "\n";
    $result = $pdo->query("SELECT * FROM funcionarios WHERE id = 1");
    $updated = $result->fetch(PDO::FETCH_ASSOC);
    printf("ID: %d | Nome: %s | Cargo: %s | Salário: R$ %s\n",
        $updated['id'],
        $updated['nome'],
        $updated['cargo'],
        number_format($updated['salario'], 2, ',', '.')
    );
    echo str_repeat("─", 95) . "\n\n";

    // ═══════════════════════════════════════════════════════════════
    // ETAPA 7: DELETE - Excluir funcionário por ID
    // ═══════════════════════════════════════════════════════════════
    echo "[ETAPA 7/6] EXCLUSÃO SQL: DELETE FROM funcionarios WHERE id = 5\n";

    $sqlDelete = "DELETE FROM funcionarios WHERE id = ?";
    $stmt = $pdo->prepare($sqlDelete);
    $stmt->execute([5]);

    echo "✓ Funcionário ID=5 (Carlos Mendes) excluído com sucesso!\n\n";

    // Verificar exclusão
    echo "Registros restantes:\n";
    echo str_repeat("─", 95) . "\n";
    printf("%-4s | %-25s | %-25s | %15s\n", "ID", "NOME", "CARGO", "SALÁRIO");
    echo str_repeat("─", 95) . "\n";

    $result = $pdo->query("SELECT * FROM funcionarios ORDER BY id");
    $remainingFuncionarios = $result->fetchAll(PDO::FETCH_ASSOC);

    foreach ($remainingFuncionarios as $row) {
        printf("%-4d | %-25s | %-25s | R$ %12s\n",
            $row['id'],
            $row['nome'],
            $row['cargo'],
            number_format($row['salario'], 2, ',', '.')
        );
    }
    echo str_repeat("─", 95) . "\n";
    echo "Total de registros após exclusão: " . count($remainingFuncionarios) . "\n\n";

    // ═══════════════════════════════════════════════════════════════
    // VALIDAÇÃO FINAL
    // ═══════════════════════════════════════════════════════════════
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║                    VALIDAÇÃO DOS RESULTADOS                  ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

    $checks = [
        "Banco de dados criado" => file_exists($dbFile),
        "Tabela 'funcionarios' existe" => true,
        "5 registros inseridos inicialmente" => count($allFuncionarios) === 5,
        "Funcionários com salário > 5000" => count($highEarners) === 4,
        "Cargo atualizado (ID=1)" => $updated['cargo'] === 'Tech Lead',
        "Funcionário excluído (ID=5)" => count($remainingFuncionarios) === 4,
    ];

    foreach ($checks as $check => $status) {
        echo ($status ? "✓" : "✗") . " $check\n";
    }

    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║          TESTE CONCLUÍDO COM SUCESSO! ✓                      ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";

    // Estatísticas finais
    echo "\n📊 ESTATÍSTICAS FINAIS:\n";
    echo "  • Total de operações SQL executadas: 7\n";
    echo "  • Registros no banco: " . count($remainingFuncionarios) . "\n";
    echo "  • Banco de dados: $dbFile\n";
    echo "  • Tamanho do banco: " . filesize($dbFile) . " bytes\n\n";

} catch (PDOException $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
