#!/bin/bash

###############################################################################
# Script de Setup Automático - MySQL para Produção
# Sistema de Registro de Ponto Eletrônico
#
# Este script automatiza completamente a instalação e configuração do MySQL
# para testes realistas em ambiente de produção.
#
# IMPORTANTE: Requer permissões de sudo
#
# Uso: sudo bash setup_mysql_production.sh
###############################################################################

set -e  # Exit on error

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
DB_NAME="ponto_eletronico"
DB_USER="ponto_user"
DB_ROOT_PASS=""
DB_USER_PASS=""
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}"
echo "============================================================"
echo "  SETUP AUTOMÁTICO - MySQL para Testes em Produção"
echo "  Sistema de Registro de Ponto Eletrônico"
echo "============================================================"
echo -e "${NC}"

###############################################################################
# Função: Verificar se está rodando como root/sudo
###############################################################################
check_root() {
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}❌ Este script precisa ser executado com sudo${NC}"
        echo -e "${YELLOW}Uso: sudo bash setup_mysql_production.sh${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ Executando com permissões adequadas${NC}"
}

###############################################################################
# Função: Verificar dependências
###############################################################################
check_dependencies() {
    echo -e "\n${BLUE}[1/9] Verificando dependências...${NC}"

    # PHP
    if ! command -v php &> /dev/null; then
        echo -e "${RED}❌ PHP não encontrado${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ PHP $(php -r 'echo PHP_VERSION;')${NC}"

    # Composer
    if ! command -v composer &> /dev/null; then
        echo -e "${RED}❌ Composer não encontrado${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ Composer $(composer --version --no-ansi | head -1 | awk '{print $3}')${NC}"
}

###############################################################################
# Função: Instalar MySQL
###############################################################################
install_mysql() {
    echo -e "\n${BLUE}[2/9] Instalando MySQL Server...${NC}"

    # Verificar se já está instalado
    if systemctl is-active --quiet mysql; then
        echo -e "${GREEN}✅ MySQL já está instalado e rodando${NC}"
        return 0
    fi

    if command -v mysql &> /dev/null; then
        echo -e "${YELLOW}⚠️  MySQL já está instalado mas não está rodando${NC}"
        echo -e "${YELLOW}   Tentando iniciar...${NC}"
        systemctl start mysql || true
        if systemctl is-active --quiet mysql; then
            echo -e "${GREEN}✅ MySQL iniciado com sucesso${NC}"
            return 0
        fi
    fi

    # Instalar MySQL
    echo -e "${YELLOW}📦 Atualizando repositórios...${NC}"
    apt-get update -qq

    echo -e "${YELLOW}📦 Instalando MySQL Server...${NC}"
    # Instalar sem prompt de senha (será configurada depois)
    DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server mysql-client > /dev/null 2>&1

    # Iniciar serviço
    systemctl start mysql
    systemctl enable mysql

    if systemctl is-active --quiet mysql; then
        echo -e "${GREEN}✅ MySQL instalado e iniciado com sucesso${NC}"
    else
        echo -e "${RED}❌ Falha ao iniciar MySQL${NC}"
        exit 1
    fi
}

###############################################################################
# Função: Gerar senhas fortes
###############################################################################
generate_passwords() {
    echo -e "\n${BLUE}[3/9] Gerando senhas seguras...${NC}"

    # Senha root do MySQL (16 caracteres)
    DB_ROOT_PASS=$(openssl rand -base64 16 | tr -d "=+/" | cut -c1-16)

    # Senha do usuário da aplicação (20 caracteres com caracteres especiais)
    DB_USER_PASS=$(openssl rand -base64 20 | tr -d "=/" | head -c 20)

    echo -e "${GREEN}✅ Senhas geradas com sucesso${NC}"
    echo -e "${YELLOW}   Root password: $DB_ROOT_PASS${NC}"
    echo -e "${YELLOW}   User password: $DB_USER_PASS${NC}"

    # Salvar senhas em arquivo seguro
    cat > "$APP_DIR/.mysql_credentials" <<EOF
# Credenciais MySQL - Geradas automaticamente
# IMPORTANTE: Mantenha este arquivo seguro e não commite no git!

DB_ROOT_PASSWORD=$DB_ROOT_PASS
DB_USER_PASSWORD=$DB_USER_PASS
DB_NAME=$DB_NAME
DB_USER=$DB_USER

# Para conectar:
# mysql -u root -p'$DB_ROOT_PASS'
# mysql -u $DB_USER -p'$DB_USER_PASS' $DB_NAME
EOF

    chmod 600 "$APP_DIR/.mysql_credentials"
    echo -e "${GREEN}✅ Credenciais salvas em: .mysql_credentials${NC}"
}

###############################################################################
# Função: Configurar segurança do MySQL
###############################################################################
secure_mysql() {
    echo -e "\n${BLUE}[4/9] Configurando segurança do MySQL...${NC}"

    # Definir senha do root
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$DB_ROOT_PASS';" 2>/dev/null || \
        mysql -e "SET PASSWORD FOR 'root'@'localhost' = PASSWORD('$DB_ROOT_PASS');"

    # Remover usuários anônimos
    mysql -u root -p"$DB_ROOT_PASS" -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null

    # Remover banco de dados de teste
    mysql -u root -p"$DB_ROOT_PASS" -e "DROP DATABASE IF EXISTS test;" 2>/dev/null

    # Remover permissões em test
    mysql -u root -p"$DB_ROOT_PASS" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';" 2>/dev/null

    # Desabilitar login remoto do root
    mysql -u root -p"$DB_ROOT_PASS" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" 2>/dev/null

    # Aplicar mudanças
    mysql -u root -p"$DB_ROOT_PASS" -e "FLUSH PRIVILEGES;"

    echo -e "${GREEN}✅ MySQL configurado com segurança${NC}"
}

###############################################################################
# Função: Criar banco de dados e usuário
###############################################################################
create_database() {
    echo -e "\n${BLUE}[5/9] Criando banco de dados e usuário...${NC}"

    # Criar banco de dados
    mysql -u root -p"$DB_ROOT_PASS" <<EOF
-- Criar banco de dados
DROP DATABASE IF EXISTS $DB_NAME;
CREATE DATABASE $DB_NAME
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Criar usuário da aplicação
DROP USER IF EXISTS '$DB_USER'@'localhost';
CREATE USER '$DB_USER'@'localhost'
    IDENTIFIED WITH mysql_native_password
    BY '$DB_USER_PASS';

-- Conceder permissões (Least Privilege)
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER, DROP
    ON $DB_NAME.*
    TO '$DB_USER'@'localhost';

-- Aplicar permissões
FLUSH PRIVILEGES;

-- Verificar criação
SELECT
    'Database created' as status,
    SCHEMA_NAME as database_name,
    DEFAULT_CHARACTER_SET_NAME as charset,
    DEFAULT_COLLATION_NAME as collation
FROM information_schema.SCHEMATA
WHERE SCHEMA_NAME = '$DB_NAME';

SELECT
    'User created' as status,
    User,
    Host,
    plugin as auth_plugin
FROM mysql.user
WHERE User = '$DB_USER';
EOF

    echo -e "${GREEN}✅ Banco de dados '$DB_NAME' criado${NC}"
    echo -e "${GREEN}✅ Usuário '$DB_USER' criado com permissões adequadas${NC}"
}

###############################################################################
# Função: Atualizar arquivo .env
###############################################################################
update_env_file() {
    echo -e "\n${BLUE}[6/9] Atualizando configuração da aplicação (.env)...${NC}"

    if [ ! -f "$APP_DIR/.env" ]; then
        echo -e "${YELLOW}⚠️  Arquivo .env não encontrado, criando a partir do exemplo...${NC}"
        cp "$APP_DIR/.env.example" "$APP_DIR/.env" 2>/dev/null || true
    fi

    # Backup do .env atual
    cp "$APP_DIR/.env" "$APP_DIR/.env.backup.$(date +%Y%m%d_%H%M%S)"

    # Atualizar credenciais do banco de dados
    sed -i "s/database.default.hostname = .*/database.default.hostname = localhost/" "$APP_DIR/.env"
    sed -i "s/database.default.database = .*/database.default.database = $DB_NAME/" "$APP_DIR/.env"
    sed -i "s/database.default.username = .*/database.default.username = $DB_USER/" "$APP_DIR/.env"
    sed -i "s/database.default.password = .*/database.default.password = $DB_USER_PASS/" "$APP_DIR/.env"
    sed -i "s/database.default.DBDriver = .*/database.default.DBDriver = MySQLi/" "$APP_DIR/.env"
    sed -i "s/database.default.port = .*/database.default.port = 3306/" "$APP_DIR/.env"

    # Garantir que ambiente está em development para testes
    sed -i "s/CI_ENVIRONMENT = .*/CI_ENVIRONMENT = development/" "$APP_DIR/.env"

    echo -e "${GREEN}✅ Arquivo .env atualizado com as credenciais do banco${NC}"
}

###############################################################################
# Função: Executar migrations
###############################################################################
run_migrations() {
    echo -e "\n${BLUE}[7/9] Executando migrations do banco de dados...${NC}"

    cd "$APP_DIR"

    # Verificar status das migrations
    echo -e "${YELLOW}📊 Status das migrations:${NC}"
    php spark migrate:status 2>/dev/null || echo "Nenhuma migration encontrada ainda"

    # Executar migrations
    echo -e "${YELLOW}🔄 Executando migrations...${NC}"
    php spark migrate || {
        echo -e "${RED}❌ Erro ao executar migrations${NC}"
        echo -e "${YELLOW}Verifique os logs acima para mais detalhes${NC}"
        exit 1
    }

    echo -e "${GREEN}✅ Migrations executadas com sucesso${NC}"

    # Mostrar tabelas criadas
    echo -e "\n${YELLOW}📋 Tabelas criadas:${NC}"
    mysql -u $DB_USER -p"$DB_USER_PASS" $DB_NAME -e "SHOW TABLES;"
}

###############################################################################
# Função: Inserir dados de teste
###############################################################################
insert_test_data() {
    echo -e "\n${BLUE}[8/9] Inserindo dados de teste...${NC}"

    # Hash para senha: Admin@123456 (BCrypt cost 12)
    ADMIN_PASSWORD_HASH='$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewPAAa4pQRLfT4SG'

    mysql -u $DB_USER -p"$DB_USER_PASS" $DB_NAME <<EOF
-- Inserir usuário administrador
INSERT INTO employees (name, email, password, role, active, created_at, updated_at)
VALUES (
    'Administrador Teste',
    'admin@teste.com',
    '$ADMIN_PASSWORD_HASH',
    'admin',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE email=email;

-- Inserir gestor de teste
INSERT INTO employees (name, email, password, role, active, created_at, updated_at)
VALUES (
    'Gestor Teste',
    'gestor@teste.com',
    '$ADMIN_PASSWORD_HASH',
    'gestor',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE email=email;

-- Inserir funcionário de teste
INSERT INTO employees (name, email, password, role, active, created_at, updated_at)
VALUES (
    'Funcionário Teste',
    'funcionario@teste.com',
    '$ADMIN_PASSWORD_HASH',
    'funcionario',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE email=email;

-- Verificar inserção
SELECT id, name, email, role, active FROM employees;
EOF

    echo -e "${GREEN}✅ Dados de teste inseridos${NC}"
    echo -e "${YELLOW}"
    echo "   📧 Credenciais de teste:"
    echo "   Admin:       admin@teste.com / Admin@123456"
    echo "   Gestor:      gestor@teste.com / Admin@123456"
    echo "   Funcionário: funcionario@teste.com / Admin@123456"
    echo -e "${NC}"
}

###############################################################################
# Função: Testar conexão e sistema
###############################################################################
test_system() {
    echo -e "\n${BLUE}[9/9] Testando sistema completo...${NC}"

    cd "$APP_DIR"

    # Testar conexão básica
    echo -e "${YELLOW}🔌 Testando conexão com banco...${NC}"
    php test_basic.php | grep -A 2 "Banco de Dados"

    # Executar testes de segurança
    echo -e "\n${YELLOW}🔒 Executando testes de segurança...${NC}"
    php test_security_components.php

    echo -e "\n${GREEN}✅ Sistema testado e funcionando!${NC}"
}

###############################################################################
# Função: Mostrar resumo final
###############################################################################
show_summary() {
    echo -e "\n${GREEN}"
    echo "============================================================"
    echo "  ✅ SETUP COMPLETO - MySQL Configurado com Sucesso!"
    echo "============================================================"
    echo -e "${NC}"

    echo -e "${BLUE}📊 Informações do Sistema:${NC}"
    echo "   Banco de dados: $DB_NAME"
    echo "   Usuário: $DB_USER"
    echo "   Host: localhost:3306"
    echo ""

    echo -e "${BLUE}📝 Arquivos de Configuração:${NC}"
    echo "   .env - Configuração da aplicação"
    echo "   .mysql_credentials - Credenciais do MySQL (SEGURO!)"
    echo ""

    echo -e "${BLUE}🔐 Credenciais de Acesso:${NC}"
    echo "   MySQL Root: root / $DB_ROOT_PASS"
    echo "   MySQL User: $DB_USER / $DB_USER_PASS"
    echo ""

    echo -e "${BLUE}👤 Usuários de Teste (Senha: Admin@123456):${NC}"
    echo "   Admin:       admin@teste.com"
    echo "   Gestor:      gestor@teste.com"
    echo "   Funcionário: funcionario@teste.com"
    echo ""

    echo -e "${BLUE}🚀 Próximos Passos:${NC}"
    echo "   1. Iniciar servidor: php spark serve"
    echo "   2. Acessar: http://localhost:8080"
    echo "   3. Fazer login com credenciais acima"
    echo "   4. Executar testes: bash run_full_tests.sh"
    echo "   5. Consultar documentação: SECURITY_TESTING_GUIDE.md"
    echo ""

    echo -e "${YELLOW}⚠️  IMPORTANTE:${NC}"
    echo "   - Altere as senhas padrão após primeiro login"
    echo "   - Não commite o arquivo .mysql_credentials no git"
    echo "   - Revise o arquivo .env antes de produção"
    echo "   - Configure backup automático (ver MYSQL_INSTALLATION_GUIDE.md)"
    echo ""

    echo -e "${GREEN}✅ Sistema pronto para testes realistas!${NC}\n"
}

###############################################################################
# Função: Main
###############################################################################
main() {
    check_root
    check_dependencies
    install_mysql
    generate_passwords
    secure_mysql
    create_database
    update_env_file
    run_migrations
    insert_test_data
    test_system
    show_summary
}

# Executar script
main "$@"
