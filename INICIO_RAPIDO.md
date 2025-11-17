# 🚀 INÍCIO RÁPIDO - Sistema de Ponto Eletrônico

**Problema:** Sistema apresenta erro 500 em todas as páginas
**Causa:** MySQL não está rodando
**Solução:** Escolha uma das opções abaixo

---

## ⚡ SOLUÇÃO MAIS RÁPIDA (3 minutos)

```bash
# 1. Executar script automático
./instalar-mysql.sh

# 2. Executar migrations
php spark migrate

# 3. Criar usuário admin
php spark shield:user create

# 4. Iniciar sistema
php spark serve

# 5. Acessar
http://localhost:8080
```

---

## 📋 QUAL SCRIPT USAR?

| Situação | Script | Tempo |
|----------|--------|-------|
| **MySQL não instalado** | `./instalar-mysql.sh` | 5-10 min |
| **MySQL já instalado** | `./create-database.sh` | 2 min |
| **Erro 500 genérico** | `./FIX_ERRO_500.sh` | 3 min |
| **Só quer testar conexão** | `php public/test-db-connection.php` | 10 seg |

---

## 🆘 PRECISA DE AJUDA?

### Ler Guia Completo
```bash
cat INSTALAR_MYSQL.md          # Como instalar MySQL (3 opções)
cat DIAGNOSTICO_ERRO_500.md    # Análise completa do erro
```

### Executar Diagnóstico
```bash
php public/test-db-connection.php    # Testar conexão MySQL
php public/test-error-500.php        # Diagnóstico completo
```

---

## ✅ DEPOIS QUE MYSQL ESTIVER RODANDO

```bash
# 1. Criar estrutura do banco
php spark migrate

# 2. (Opcional) Popular dados de exemplo
php spark db:seed DatabaseSeeder

# 3. Criar primeiro usuário admin
php spark shield:user create
# Email: admin@empresa.com
# Password: (escolha senha forte)

# 4. Iniciar servidor
php spark serve

# 5. Acessar no navegador
http://localhost:8080
```

---

## 🎯 PRÓXIMOS PASSOS

1. Configurar email no `.env` (para recuperação de senha)
2. Configurar DeepFace API (reconhecimento facial)
3. Configurar Redis (cache e sessões)
4. Importar funcionários
5. Configurar backup automático

---

**Dúvidas?** Consulte `INSTALAR_MYSQL.md` para guia detalhado
