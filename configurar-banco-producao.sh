#!/bin/bash

###############################################################################
# Script de Configuração do Banco de Dados em Produção
# Sistema de Ponto Eletrônico Brasileiro
#
# Use este script quando você JÁ TEM um servidor MySQL disponível
# (fornecido pela hospedagem, outro servidor, etc.)
###############################################################################

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
print_step() { echo -e "${CYAN}$1${NC}"; }

echo "========================================================================"
echo "🗄️  CONFIGURAÇÃO DO BANCO DE DADOS EM PRODUÇÃO"
echo "========================================================================"
echo ""

print_warning "Este script configura a conexão com um banco MySQL EXISTENTE"
print_info "Se você ainda não tem o banco, crie primeiro no painel da hospedagem"
echo ""

read -p "Você já criou o banco de dados MySQL? (s/n): " has_db

if [[ "$has_db" != "s" && "$has_db" != "S" ]]; then
    print_error "Você precisa criar o banco primeiro!"
    echo ""
    print_info "Passos para criar:"
    echo "1. Acesse o painel de controle (cPanel/Plesk/etc)"
    echo "2. Vá em 'MySQL Databases' ou 'Bancos de Dados'"
    echo "3. Crie um banco chamado 'ponto_eletronico'"
    echo "4. Crie um usuário MySQL"
    echo "5. Adicione o usuário ao banco com TODAS as permissões"
    echo "6. Execute este script novamente"
    echo ""
    exit 1
fi

echo ""
print_success "Ótimo! Vamos configurar a conexão."
echo ""

###############################################################################
# Coletar Credenciais
###############################################################################

print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
print_step "📝 CREDENCIAIS DO BANCO DE DADOS"
print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

print_info "Digite as credenciais do seu banco MySQL:"
echo ""

# Hostname
read -p "Hostname (geralmente 'localhost'): " db_host
db_host=${db_host:-localhost}

# Database
read -p "Nome do banco (ex: ponto_eletronico): " db_name
db_name=${db_name:-ponto_eletronico}

# Username
read -p "Usuário MySQL: " db_user

# Password
read -sp "Senha MySQL: " db_pass
echo ""

# Port
read -p "Porta (padrão 3306): " db_port
db_port=${db_port:-3306}

echo ""
print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

###############################################################################
# Mostrar Resumo
###############################################################################

print_info "Resumo das configurações:"
echo ""
echo "  Hostname: $db_host"
echo "  Database: $db_name"
echo "  Username: $db_user"
echo "  Password: $(echo "$db_pass" | sed 's/./*/g')"
echo "  Port: $db_port"
echo ""

read -p "Confirma essas informações? (s/n): " confirm

if [[ "$confirm" != "s" && "$confirm" != "S" ]]; then
    print_error "Configuração cancelada"
    exit 1
fi

echo ""
print_info "Atualizando arquivo .env..."

###############################################################################
# Backup do .env
###############################################################################

if [ -f .env ]; then
    backup_file=".env.backup.$(date +%Y%m%d_%H%M%S)"
    cp .env "$backup_file"
    print_success "Backup criado: $backup_file"
fi

###############################################################################
# Atualizar .env
###############################################################################

# Criar arquivo .env se não existir
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        print_info "Arquivo .env criado a partir do .env.example"
    else
        print_error ".env não encontrado!"
        exit 1
    fi
fi

# Atualizar configurações do banco
sed -i "s|^database\.default\.hostname = .*|database.default.hostname = $db_host|" .env
sed -i "s|^database\.default\.database = .*|database.default.database = $db_name|" .env
sed -i "s|^database\.default\.username = .*|database.default.username = $db_user|" .env
sed -i "s|^database\.default\.password = .*|database.default.password = $db_pass|" .env
sed -i "s|^database\.default\.port = .*|database.default.port = $db_port|" .env

# Atualizar variáveis de compatibilidade
sed -i "s|^DB_HOST = .*|DB_HOST = $db_host|" .env
sed -i "s|^DB_DATABASE = .*|DB_DATABASE = $db_name|" .env
sed -i "s|^DB_USERNAME = .*|DB_USERNAME = $db_user|" .env
sed -i "s|^DB_PASSWORD = .*|DB_PASSWORD = $db_pass|" .env

print_success "Arquivo .env atualizado!"

echo ""
print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
print_step "🔍 TESTANDO CONEXÃO"
print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

###############################################################################
# Testar Conexão
###############################################################################

print_info "Testando conexão com o banco de dados..."
echo ""

# Executar teste de conexão
php public/test-db-connection.php > /tmp/db-test-result.html 2>&1

# Verificar resultado
if grep -q "CONEXÃO ESTABELECIDA COM SUCESSO" /tmp/db-test-result.html; then
    print_success "✅ CONEXÃO ESTABELECIDA COM SUCESSO!"
    echo ""

    # Verificar se tem tabelas
    if grep -q "Nenhuma tabela encontrada" /tmp/db-test-result.html; then
        print_warning "Banco existe mas está vazio (nenhuma tabela)"
        needs_migration=true
    else
        print_success "Banco tem tabelas!"
        needs_migration=false
    fi
else
    print_error "FALHA NA CONEXÃO!"
    echo ""
    print_info "Detalhes do erro:"
    grep -A 5 "ERRO DE CONEXÃO" /tmp/db-test-result.html | grep -v "^<" || echo "Verifique: cat /tmp/db-test-result.html"
    echo ""
    print_warning "Possíveis causas:"
    echo "  - Credenciais incorretas"
    echo "  - Hostname errado (pode não ser 'localhost')"
    echo "  - Usuário sem permissões no banco"
    echo "  - Firewall bloqueando conexão"
    echo ""
    print_info "Você pode:"
    echo "  1. Editar manualmente o .env e testar: php public/test-db-connection.php"
    echo "  2. Executar este script novamente"
    echo ""
    exit 1
fi

echo ""
print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

###############################################################################
# Executar Migrations
###############################################################################

if [ "$needs_migration" = true ]; then
    echo ""
    print_info "O banco está vazio. Precisa criar as tabelas."
    echo ""
    read -p "Executar migrations agora? (s/n): " run_migrations

    if [[ "$run_migrations" == "s" || "$run_migrations" == "S" ]]; then
        print_info "Executando migrations..."
        echo ""

        php spark migrate

        if [ $? -eq 0 ]; then
            print_success "Migrations executadas com sucesso!"
        else
            print_error "Erro ao executar migrations"
            exit 1
        fi
    else
        print_warning "Migrations não executadas"
        print_info "Execute depois com: php spark migrate"
    fi
fi

echo ""
print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

###############################################################################
# Criar Usuário Admin
###############################################################################

echo ""
print_info "Criar usuário administrador?"
echo ""
read -p "Criar usuário admin agora? (s/n): " create_admin

if [[ "$create_admin" == "s" || "$create_admin" == "S" ]]; then
    print_info "Criando usuário administrador..."
    echo ""

    php spark shield:user create

    if [ $? -eq 0 ]; then
        print_success "Usuário criado com sucesso!"
    else
        print_warning "Não foi possível criar usuário automaticamente"
        print_info "Crie depois com: php spark shield:user create"
    fi
else
    print_info "Você pode criar depois com: php spark shield:user create"
fi

echo ""
print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
print_step "✅ CONFIGURAÇÃO CONCLUÍDA!"
print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

print_success "🎉 Banco de dados configurado com sucesso!"
echo ""

###############################################################################
# Próximos Passos
###############################################################################

echo "📋 PRÓXIMOS PASSOS:"
echo ""

if [ "$needs_migration" = true ] && [[ "$run_migrations" != "s" && "$run_migrations" != "S" ]]; then
    echo "1️⃣  Executar migrations:"
    echo "   php spark migrate"
    echo ""
fi

if [[ "$create_admin" != "s" && "$create_admin" != "S" ]]; then
    echo "2️⃣  Criar usuário administrador:"
    echo "   php spark shield:user create"
    echo ""
fi

echo "3️⃣  Configurar permissões dos diretórios:"
echo "   ./setup-permissions.sh"
echo ""

echo "4️⃣  Acessar o sistema:"
if [ -f "public/index.php" ]; then
    echo "   - Via servidor web: https://seudominio.com.br"
    echo "   - Via PHP interno: php spark serve"
fi
echo ""

echo "5️⃣  Testar funcionalidades principais"
echo ""

print_step "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

print_info "Logs salvos em: /tmp/db-test-result.html"
print_info "Backup do .env anterior: $backup_file"

echo ""
print_success "Sistema pronto para uso! 🚀"
