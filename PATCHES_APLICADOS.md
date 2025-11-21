# Patches Aplicados ao Projeto

## 📋 Histórico de Correções

### 🔧 [21-Nov-2025] Correção: Undefined array key "args" - CodeIgniter 4

**Problema Identificado:**
- Erro fatal: `Undefined array key "args"` no sistema de tratamento de exceções do CodeIgniter 4
- Localização: `vendor/codeigniter4/framework/system/Debug/BaseExceptionHandler.php:102`
- Impacto: Sistema não conseguia tratar exceções corretamente, causando falhas em cascata

**Causa Raiz:**
O código do CodeIgniter 4 assumia que todas as entradas do stack trace teriam a chave 'args', mas em alguns casos (funções internas do PHP, certas situações de backtrace), essa chave pode não existir.

**Arquivos Corrigidos:**

1. **vendor/codeigniter4/framework/system/Debug/BaseExceptionHandler.php (linha 102)**
   - Método: `maskSensitiveData()`
   - Alteração: Adicionada verificação `isset($line['args'])` antes de acessar a chave

2. **vendor/codeigniter4/framework/system/Debug/Exceptions.php (linha 445)**
   - Método: `maskSensitiveData()` (deprecated)
   - Alteração: Adicionada verificação `isset($line['args'])` antes de acessar a chave

**Código Aplicado:**
```php
// Antes:
foreach ($trace as $i => $line) {
    $trace[$i]['args'] = $this->maskData($line['args'], $keysToMask);
}

// Depois:
foreach ($trace as $i => $line) {
    // Fix: Verificar se a chave 'args' existe antes de acessá-la
    if (isset($line['args'])) {
        $trace[$i]['args'] = $this->maskData($line['args'], $keysToMask);
    }
}
```

**Status:** ✅ Corrigido

**Versão CodeIgniter:** ^4.4

**Observações:**
- Esta é uma correção temporária aplicada diretamente no vendor
- Recomenda-se verificar atualizações do CodeIgniter que possam incluir esta correção oficialmente
- Se executar `composer update`, pode ser necessário reaplicar este patch
- Considerar criar um patch permanente usando composer-patches ou similar

---

## 📝 Notas para Manutenção

### Como Reaplicar os Patches Após Atualização do Composer

Se você executar `composer update` e o patch for perdido, siga estes passos:

1. **BaseExceptionHandler.php:**
   ```bash
   # Localizar o arquivo
   nano vendor/codeigniter4/framework/system/Debug/BaseExceptionHandler.php

   # Ir para a linha ~102 no método maskSensitiveData()
   # Adicionar verificação isset() antes de acessar $line['args']
   ```

2. **Exceptions.php:**
   ```bash
   # Localizar o arquivo
   nano vendor/codeigniter4/framework/system/Debug/Exceptions.php

   # Ir para a linha ~445 no método maskSensitiveData()
   # Adicionar verificação isset() antes de acessar $line['args']
   ```

### Alternativa: Usar composer-patches

Para aplicar automaticamente após cada `composer install/update`:

```bash
composer require cweagans/composer-patches
```

Adicione ao `composer.json`:
```json
{
    "extra": {
        "patches": {
            "codeigniter4/framework": {
                "Fix undefined array key args in exception handler": "patches/codeigniter4-fix-args-key.patch"
            }
        }
    }
}
```

---

## 🔍 Verificação de Status

Para verificar se os patches estão aplicados:

```bash
# Verificar BaseExceptionHandler.php
grep -n "isset(\$line\['args'\])" vendor/codeigniter4/framework/system/Debug/BaseExceptionHandler.php

# Verificar Exceptions.php
grep -n "isset(\$line\['args'\])" vendor/codeigniter4/framework/system/Debug/Exceptions.php
```

Se os comandos acima retornarem números de linha, os patches estão aplicados. ✅

---

*Documento gerado automaticamente em: 21-Nov-2025*
