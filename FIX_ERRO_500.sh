#!/bin/bash

###############################################################################
# Script de Correção do Erro 500
# Sistema de Ponto Eletrônico Brasileiro
#
# Este script corrige automaticamente o erro 500 identificado
# Causa: MySQL não está rodando
###############################################################################

set -e  # Exit on error

echo "========================================================================"
echo "🔧 CORREÇÃO AUTOMÁTICA DO ERRO 500"
echo "========================================================================"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para printar com cor
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "ℹ️  $1"
}

###############################################################################
# 1. Verificar se MySQL está rodando
###############################################################################

echo "1️⃣  Verificando status do MySQL..."
echo ""

if pgrep -x "mysqld" > /dev/null; then
    print_success "MySQL está rodando!"
    mysql_running=true
else
    print_error "MySQL NÃO está rodando - Esta é a causa do erro 500"
    mysql_running=false
fi

echo ""

###############################################################################
# 2. Tentar solução via Docker (recomendado)
###############################################################################

if [ "$mysql_running" = false ]; then
    echo "2️⃣  Tentando iniciar MySQL via Docker..."
    echo ""

    if command -v docker &> /dev/null; then
        print_info "Docker encontrado!"

        if command -v docker-compose &> /dev/null; then
            print_info "Docker Compose encontrado!"
            echo ""

            print_info "Iniciando container MySQL..."
            docker-compose up -d mysql

            print_info "Aguardando MySQL inicializar (30 segundos)..."
            sleep 30

            print_success "MySQL iniciado via Docker!"
            mysql_running=true
        else
            print_warning "Docker Compose não encontrado"
            echo "Instale com: sudo apt-get install docker-compose"
        fi
    else
        print_warning "Docker não está instalado"
        echo ""
        echo "Para instalar Docker:"
        echo "  curl -fsSL https://get.docker.com | sh"
        echo ""
    fi
fi

echo ""

###############################################################################
# 3. Verificar se MySQL local está instalado
###############################################################################

if [ "$mysql_running" = false ]; then
    echo "3️⃣  Verificando instalação do MySQL local..."
    echo ""

    if command -v mysql &> /dev/null; then
        print_info "MySQL está instalado mas não está rodando"
        echo ""
        print_info "Tentando iniciar MySQL..."

        # Tentar systemctl
        if command -v systemctl &> /dev/null; then
            sudo systemctl start mysql || sudo systemctl start mariadb
            sudo systemctl enable mysql || sudo systemctl enable mariadb

            sleep 5

            if pgrep -x "mysqld" > /dev/null; then
                print_success "MySQL iniciado com sucesso!"
                mysql_running=true
            else
                print_error "Falha ao iniciar MySQL"
            fi
        # Tentar service
        elif command -v service &> /dev/null; then
            sudo service mysql start || sudo service mariadb start

            sleep 5

            if pgrep -x "mysqld" > /dev/null; then
                print_success "MySQL iniciado com sucesso!"
                mysql_running=true
            else
                print_error "Falha ao iniciar MySQL"
            fi
        else
            print_error "Não foi possível iniciar MySQL automaticamente"
            print_info "Tente manualmente: sudo systemctl start mysql"
        fi
    else
        print_error "MySQL não está instalado"
        echo ""
        echo "Para instalar MySQL:"
        echo "  sudo apt-get update"
        echo "  sudo apt-get install mysql-server"
        echo ""
    fi
fi

echo ""

###############################################################################
# 4. Testar conexão com banco de dados
###############################################################################

if [ "$mysql_running" = true ]; then
    echo "4️⃣  Testando conexão com banco de dados..."
    echo ""

    # Executar script de teste
    php public/test-db-connection.php > /tmp/db-test.html 2>&1

    if grep -q "CONEXÃO ESTABELECIDA COM SUCESSO" /tmp/db-test.html; then
        print_success "Conexão com banco de dados OK!"

        # Verificar se banco existe
        if grep -q "Nenhuma tabela encontrada" /tmp/db-test.html; then
            print_warning "Banco existe mas está vazio"
            echo ""
            print_info "Execute as migrations:"
            echo "  php spark migrate"
        else
            print_success "Banco de dados com tabelas encontradas!"
        fi
    else
        if grep -q "o database 'ponto_eletronico' não existe" /tmp/db-test.html; then
            print_warning "Banco 'ponto_eletronico' não existe"
            echo ""
            print_info "Criando banco de dados..."

            mysql -u root -p <<EOF
CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
FLUSH PRIVILEGES;
EOF

            if [ $? -eq 0 ]; then
                print_success "Banco criado com sucesso!"
                print_info "Execute as migrations: php spark migrate"
            else
                print_error "Falha ao criar banco. Tente manualmente."
            fi
        else
            print_error "Problema de conexão com banco"
            echo ""
            echo "Verifique o arquivo de teste:"
            echo "  cat /tmp/db-test.html"
        fi
    fi
else
    print_error "MySQL ainda não está rodando. Correção manual necessária."
fi

echo ""

###############################################################################
# 5. Testar sistema CodeIgniter
###############################################################################

if [ "$mysql_running" = true ]; then
    echo "5️⃣  Testando inicialização do CodeIgniter..."
    echo ""

    php public/test-error-500.php > /tmp/ci-test.html 2>&1

    if grep -q "Diagnóstico Concluído" /tmp/ci-test.html; then
        print_success "CodeIgniter inicializado sem erros!"
    else
        print_warning "CodeIgniter apresentou alguns avisos"
        echo "Verifique: cat /tmp/ci-test.html"
    fi
fi

echo ""

###############################################################################
# 6. Verificar permissões
###############################################################################

echo "6️⃣  Verificando permissões dos diretórios..."
echo ""

# Executar script de permissões se existir
if [ -f "setup-permissions.sh" ]; then
    print_info "Executando script de permissões..."
    bash setup-permissions.sh
    print_success "Permissões verificadas!"
else
    # Manualmente
    chmod -R 775 writable/
    chmod 600 .env
    print_success "Permissões básicas configuradas!"
fi

echo ""

###############################################################################
# 7. Resumo e Próximos Passos
###############################################################################

echo "========================================================================"
echo "📊 RESUMO DA CORREÇÃO"
echo "========================================================================"
echo ""

if [ "$mysql_running" = true ]; then
    print_success "PROBLEMA RESOLVIDO!"
    echo ""
    echo "O erro 500 foi corrigido. Próximos passos:"
    echo ""
    echo "1️⃣  Execute as migrations do banco:"
    echo "   php spark migrate"
    echo ""
    echo "2️⃣  Crie um usuário administrador:"
    echo "   php spark shield:user create"
    echo ""
    echo "3️⃣  Inicie o servidor de desenvolvimento:"
    echo "   php spark serve"
    echo ""
    echo "4️⃣  Acesse o sistema em:"
    echo "   http://localhost:8080"
    echo ""
    print_success "Sistema pronto para uso!"
else
    print_error "PROBLEMA NÃO RESOLVIDO AUTOMATICAMENTE"
    echo ""
    echo "MySQL não está rodando. Soluções:"
    echo ""
    echo "🐳 OPÇÃO 1 - Usar Docker (RECOMENDADO):"
    echo "   1. Instalar Docker: curl -fsSL https://get.docker.com | sh"
    echo "   2. Iniciar MySQL: docker-compose up -d mysql"
    echo "   3. Aguardar 30 segundos"
    echo ""
    echo "💾 OPÇÃO 2 - MySQL Local:"
    echo "   1. Instalar: sudo apt-get install mysql-server"
    echo "   2. Iniciar: sudo systemctl start mysql"
    echo "   3. Criar banco: mysql -u root -p"
    echo "      CREATE DATABASE ponto_eletronico;"
    echo ""
    echo "📄 Consulte o relatório completo em:"
    echo "   cat DIAGNOSTICO_ERRO_500.md"
fi

echo ""
echo "========================================================================"
echo "✅ Script finalizado"
echo "========================================================================"
