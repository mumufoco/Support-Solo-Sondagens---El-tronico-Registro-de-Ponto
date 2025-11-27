<?php
/**
 * Teste PURO PHP - Sem CodeIgniter
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Teste PHP Puro</title></head><body>";
echo "<h1 style='color: green;'>✅ PHP ESTÁ FUNCIONANDO!</h1>";
echo "<p>Se você está vendo isso, o PHP no servidor está OK.</p>";
echo "<p>Versão PHP: " . phpversion() . "</p>";
echo "<p>Hora: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";
echo "<h2>Próximo passo:</h2>";
echo "<p>Se este teste funciona mas o CodeIgniter não, o problema está em como o CodeIgniter envia a resposta.</p>";
echo "</body></html>";
