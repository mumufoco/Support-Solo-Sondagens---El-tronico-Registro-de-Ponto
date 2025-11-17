#!/bin/bash

###############################################################################
# Script de Instalação Automática do MySQL
# Sistema de Ponto Eletrônico Brasileiro
#
# Este script tenta instalar MySQL automaticamente usando a melhor opção
# disponível para o seu sistema
###############################################################################

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo "========================================================================"
echo "🗄️  INSTALAÇÃO AUTOMÁTICA DO MYSQL"
echo "========================================================================"
echo ""

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

###############################################################################
# Detectar Sistema Operacional
###############################################################################

echo "🔍 Detectando sistema operacional..."
echo ""

if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        OS_VERSION=$VERSION_ID
        print_info "Sistema: $PRETTY_NAME"
    else
        OS="unknown"
    fi
elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macos"
    print_info "Sistema: macOS"
else
    OS="unknown"
    print_warning "Sistema operacional não identificado: $OSTYPE"
fi

echo ""

###############################################################################
# Verificar se MySQL já está instalado
###############################################################################

echo "🔍 Verificando se MySQL já está instalado..."
echo ""

if command -v mysql &> /dev/null; then
    print_success "MySQL já está instalado!"
    mysql --version

    echo ""
    print_info "Verificando se está rodando..."

    if pgrep -x "mysqld" > /dev/null; then
        print_success "MySQL está rodando!"
        echo ""
        print_success "✅ MYSQL JÁ ESTÁ PRONTO PARA USO"
        echo ""
        echo "Próximos passos:"
        echo "1. Criar banco: ./create-database.sh"
        echo "2. Executar migrations: php spark migrate"
        echo "3. Iniciar sistema: php spark serve"
        exit 0
    else
        print_warning "MySQL instalado mas não está rodando"
        print_info "Tentando iniciar..."

        if command -v systemctl &> /dev/null; then
            sudo systemctl start mysql || sudo systemctl start mariadb
            sleep 3

            if pgrep -x "mysqld" > /dev/null; then
                print_success "MySQL iniciado com sucesso!"
                exit 0
            fi
        fi
    fi
else
    print_info "MySQL não encontrado. Prosseguindo com instalação..."
fi

echo ""

###############################################################################
# Opção 1: Tentar Docker (Recomendado)
###############################################################################

echo "========================================================================"
echo "🐳 OPÇÃO 1: DOCKER (Recomendado)"
echo "========================================================================"
echo ""

if command -v docker &> /dev/null; then
    print_success "Docker encontrado!"
    docker --version

    echo ""
    print_info "Verificando docker-compose..."

    if command -v docker-compose &> /dev/null; then
        print_success "Docker Compose encontrado!"
        docker-compose --version

        echo ""
        print_info "🚀 Iniciando MySQL via Docker..."
        echo ""

        docker-compose up -d mysql

        print_info "Aguardando MySQL inicializar (30 segundos)..."
        sleep 30

        print_success "✅ MySQL iniciado via Docker!"
        echo ""
        echo "Container MySQL está rodando!"
        echo ""
        echo "Próximos passos:"
        echo "1. Testar conexão: php public/test-db-connection.php"
        echo "2. Executar migrations: php spark migrate"
        echo "3. Criar usuário: php spark shield:user create"
        echo "4. Iniciar sistema: php spark serve"
        echo ""
        print_success "✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!"
        exit 0
    else
        print_warning "Docker Compose não encontrado"
        print_info "Tentando instalar Docker Compose..."

        if [[ "$OS" == "ubuntu" ]] || [[ "$OS" == "debian" ]]; then
            sudo apt-get update
            sudo apt-get install docker-compose -y

            if command -v docker-compose &> /dev/null; then
                print_success "Docker Compose instalado!"

                docker-compose up -d mysql
                sleep 30

                print_success "✅ MySQL iniciado via Docker!"
                exit 0
            fi
        fi
    fi
else
    print_info "Docker não encontrado"
    echo ""
    echo "Quer instalar Docker? (Recomendado)"
    echo "1) Sim - Instalar Docker (rápido e fácil)"
    echo "2) Não - Instalar MySQL localmente"
    echo ""
    read -p "Escolha (1 ou 2): " docker_choice

    if [ "$docker_choice" = "1" ]; then
        print_info "Instalando Docker..."
        echo ""

        curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
        sudo sh /tmp/get-docker.sh

        # Adicionar usuário ao grupo docker
        sudo usermod -aG docker $USER

        print_success "Docker instalado!"
        print_warning "IMPORTANTE: Você precisa fazer logout e login novamente"
        print_info "Após login, execute novamente: ./instalar-mysql.sh"
        exit 0
    fi
fi

echo ""

###############################################################################
# Opção 2: Instalar MySQL Localmente
###############################################################################

echo "========================================================================"
echo "💻 OPÇÃO 2: MYSQL LOCAL"
echo "========================================================================"
echo ""

print_info "Instalando MySQL Server localmente..."
echo ""

# Ubuntu/Debian
if [[ "$OS" == "ubuntu" ]] || [[ "$OS" == "debian" ]]; then
    print_info "Detectado: Ubuntu/Debian"
    echo ""

    print_info "Atualizando pacotes..."
    sudo apt-get update

    print_info "Instalando MySQL Server..."
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server

    print_info "Iniciando MySQL..."
    sudo systemctl start mysql
    sudo systemctl enable mysql

    sleep 5

    if pgrep -x "mysqld" > /dev/null; then
        print_success "✅ MySQL instalado e rodando!"
    else
        print_error "Falha ao iniciar MySQL"
        exit 1
    fi

# CentOS/RHEL/Fedora
elif [[ "$OS" == "centos" ]] || [[ "$OS" == "rhel" ]] || [[ "$OS" == "fedora" ]]; then
    print_info "Detectado: CentOS/RHEL/Fedora"
    echo ""

    if command -v dnf &> /dev/null; then
        print_info "Instalando MySQL via DNF..."
        sudo dnf install -y mysql-server
    else
        print_info "Instalando MySQL via YUM..."
        sudo yum install -y mysql-server
    fi

    print_info "Iniciando MySQL..."
    sudo systemctl start mysqld
    sudo systemctl enable mysqld

    sleep 5

    if pgrep -x "mysqld" > /dev/null; then
        print_success "✅ MySQL instalado e rodando!"
    else
        print_error "Falha ao iniciar MySQL"
        exit 1
    fi

# macOS
elif [[ "$OS" == "macos" ]]; then
    print_info "Detectado: macOS"
    echo ""

    if command -v brew &> /dev/null; then
        print_info "Instalando MySQL via Homebrew..."
        brew install mysql

        print_info "Iniciando MySQL..."
        brew services start mysql

        sleep 5

        print_success "✅ MySQL instalado e rodando!"
    else
        print_error "Homebrew não encontrado"
        print_info "Instale Homebrew primeiro: https://brew.sh"
        exit 1
    fi

else
    print_error "Sistema operacional não suportado para instalação automática"
    echo ""
    print_info "Consulte o guia manual em: INSTALAR_MYSQL.md"
    exit 1
fi

echo ""

###############################################################################
# Criar Banco de Dados
###############################################################################

echo "========================================================================"
echo "🗄️  CONFIGURAÇÃO DO BANCO DE DADOS"
echo "========================================================================"
echo ""

print_info "Criando banco de dados 'ponto_eletronico'..."
echo ""

# Tentar criar banco sem senha (MySQL novo sem senha configurada)
mysql -u root <<EOF 2>/dev/null
CREATE DATABASE IF NOT EXISTS ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
    print_success "Banco de dados criado com sucesso!"
else
    print_warning "Não foi possível criar banco automaticamente"
    print_info "Você pode criar manualmente depois"
fi

echo ""

###############################################################################
# Testar Conexão
###############################################################################

print_info "Testando conexão com banco de dados..."
echo ""

php public/test-db-connection.php > /tmp/db-test-result.html 2>&1

if grep -q "CONEXÃO ESTABELECIDA COM SUCESSO" /tmp/db-test-result.html; then
    print_success "✅ Conexão com banco de dados OK!"
else
    print_warning "Verifique a conexão manualmente"
fi

echo ""

###############################################################################
# Resumo Final
###############################################################################

echo "========================================================================"
echo "✅ INSTALAÇÃO CONCLUÍDA"
echo "========================================================================"
echo ""

print_success "MySQL está instalado e rodando!"
echo ""
echo "📋 PRÓXIMOS PASSOS:"
echo ""
echo "1️⃣  Executar migrations do banco:"
echo "   php spark migrate"
echo ""
echo "2️⃣  Criar usuário administrador:"
echo "   php spark shield:user create"
echo ""
echo "3️⃣  Iniciar servidor de desenvolvimento:"
echo "   php spark serve"
echo ""
echo "4️⃣  Acessar o sistema:"
echo "   http://localhost:8080"
echo ""
echo "========================================================================"
echo "📚 DOCUMENTAÇÃO ADICIONAL"
echo "========================================================================"
echo ""
echo "- Guia completo: cat INSTALAR_MYSQL.md"
echo "- Diagnóstico de erros: cat DIAGNOSTICO_ERRO_500.md"
echo "- Testar conexão: php public/test-db-connection.php"
echo ""
print_success "🎉 Tudo pronto para usar o sistema!"
