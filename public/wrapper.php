<?php
/**
 * Wrapper extremo - Mostra output ANTES, DURANTE e DEPOIS do CodeIgniter
 */

// ANTES - Isso DEVE aparecer
echo "ANTES: Script iniciado\n";
flush();

// Output buffer para capturar tudo
ob_start();

// Incluir index.php
try {
    require __DIR__ . '/index.php';
} catch (Throwable $e) {
    echo "\nEXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

$output = ob_get_clean();

// DEPOIS - Isso também DEVE aparecer
echo "\n\nDEPOIS: index.php terminou\n";
echo "Output capturado: " . strlen($output) . " bytes\n";

if (!empty($output)) {
    echo "\n--- INÍCIO DO OUTPUT ---\n";
    echo $output;
    echo "\n--- FIM DO OUTPUT ---\n";
} else {
    echo "\n❌ NENHUM OUTPUT foi produzido pelo index.php!\n";
}
