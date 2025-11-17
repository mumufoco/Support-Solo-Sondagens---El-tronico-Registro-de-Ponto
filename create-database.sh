#!/bin/bash

###############################################################################
# Script para Criar Banco de Dados
# Sistema de Ponto Eletrônico Brasileiro
#
# Use este script se você JÁ TEM MySQL instalado
# e só precisa criar o banco de dados
###############################################################################

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

echo "========================================================================"
echo "🗄️  CRIAR BANCO DE DADOS - Ponto Eletrônico"
echo "========================================================================"
echo ""

# Verificar se MySQL está instalado
if ! command -v mysql &> /dev/null; then
    print_error "MySQL não está instalado!"
    echo ""
    print_info "Execute primeiro: ./instalar-mysql.sh"
    exit 1
fi

# Verificar se MySQL está rodando
if ! pgrep -x "mysqld" > /dev/null; then
    print_error "MySQL não está rodando!"
    echo ""
    print_info "Tente iniciar com: sudo systemctl start mysql"
    exit 1
fi

print_success "MySQL está rodando!"
echo ""

# Opções de criação
echo "Escolha como criar o banco de dados:"
echo ""
echo "1) Sem senha (MySQL instalado recentemente sem senha configurada)"
echo "2) Com senha root"
echo "3) Criar com novo usuário específico (mais seguro)"
echo ""
read -p "Escolha (1, 2 ou 3): " choice

case $choice in
    1)
        print_info "Criando banco sem senha..."
        mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
FLUSH PRIVILEGES;
SELECT 'Banco criado com sucesso!' AS Status;
EOF
        if [ $? -eq 0 ]; then
            print_success "Banco criado!"
        else
            print_error "Falha. Tente a opção 2 (com senha)"
        fi
        ;;

    2)
        print_info "Digite a senha do root do MySQL:"
        mysql -u root -p <<EOF
CREATE DATABASE IF NOT EXISTS ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
FLUSH PRIVILEGES;
SELECT 'Banco criado com sucesso!' AS Status;
EOF
        if [ $? -eq 0 ]; then
            print_success "Banco criado!"
        else
            print_error "Falha ao criar banco. Verifique a senha."
        fi
        ;;

    3)
        print_info "Criar novo usuário específico para o sistema"
        echo ""
        read -p "Nome do usuário (ex: ponto_user): " db_user
        read -sp "Senha para o usuário: " db_pass
        echo ""

        print_info "Digite a senha do root do MySQL:"
        mysql -u root -p <<EOF
CREATE DATABASE IF NOT EXISTS ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$db_user'@'localhost' IDENTIFIED BY '$db_pass';
GRANT ALL PRIVILEGES ON ponto_eletronico.* TO '$db_user'@'localhost';
FLUSH PRIVILEGES;
SELECT 'Banco e usuário criados!' AS Status;
EOF

        if [ $? -eq 0 ]; then
            print_success "Banco e usuário criados!"
            echo ""
            print_info "Atualize o arquivo .env com:"
            echo ""
            echo "database.default.username = $db_user"
            echo "database.default.password = $db_pass"
            echo ""
        else
            print_error "Falha ao criar."
        fi
        ;;

    *)
        print_error "Opção inválida"
        exit 1
        ;;
esac

echo ""
print_info "Testando conexão..."
php public/test-db-connection.php > /tmp/test-result.html 2>&1

if grep -q "CONEXÃO ESTABELECIDA" /tmp/test-result.html; then
    print_success "✅ Conexão funcionando!"
    echo ""
    print_info "Próximos passos:"
    echo "1. php spark migrate"
    echo "2. php spark shield:user create"
    echo "3. php spark serve"
else
    print_error "Problema na conexão"
    echo "Execute: php public/test-db-connection.php"
fi

echo ""
print_success "✅ Script finalizado"
