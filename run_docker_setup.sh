#!/bin/bash

###############################################################################
# Script de Setup com Docker Compose
# Sistema de Registro de Ponto Eletrônico
#
# Este script configura o sistema completo usando Docker
#
# Requisitos: Docker e Docker Compose instalados
# Uso: bash run_docker_setup.sh
###############################################################################

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================================"
echo "  SETUP COM DOCKER - Sistema de Ponto Eletrônico"
echo "============================================================"
echo -e "${NC}"

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker não está instalado${NC}"
    echo -e "${YELLOW}Instale com: curl -fsSL https://get.docker.com | sh${NC}"
    exit 1
fi

# Verificar se Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose não está instalado${NC}"
    echo -e "${YELLOW}Instale com: sudo apt-get install docker-compose${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker e Docker Compose disponíveis${NC}\n"

# Parar containers existentes
echo -e "${BLUE}[1/7] Parando containers existentes...${NC}"
docker-compose down 2>/dev/null || true

# Construir imagens
echo -e "\n${BLUE}[2/7] Construindo imagens Docker...${NC}"
docker-compose build

# Iniciar serviços
echo -e "\n${BLUE}[3/7] Iniciando serviços (MySQL + App + PHPMyAdmin)...${NC}"
docker-compose up -d

# Aguardar MySQL ficar pronto
echo -e "\n${BLUE}[4/7] Aguardando MySQL inicializar...${NC}"
echo -e "${YELLOW}Isso pode levar 15-30 segundos...${NC}"
sleep 20

# Verificar se MySQL está pronto
for i in {1..10}; do
    if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -proot_password_CHANGE_ME_in_production &>/dev/null; then
        echo -e "${GREEN}✅ MySQL pronto!${NC}"
        break
    fi
    echo -e "${YELLOW}⏳ Aguardando MySQL... ($i/10)${NC}"
    sleep 3
done

# Executar migrations
echo -e "\n${BLUE}[5/7] Executando migrations...${NC}"
docker-compose exec -T app php spark migrate

# Inserir dados de teste
echo -e "\n${BLUE}[6/7] Inserindo dados de teste...${NC}"
docker-compose exec -T mysql mysql -u ponto_user -pponto_pass_CHANGE_ME_in_production ponto_eletronico <<'SQL'
-- Hash para senha: Admin@123456
INSERT INTO employees (name, email, password, role, active, created_at, updated_at)
VALUES
    ('Administrador Teste', 'admin@teste.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewPAAa4pQRLfT4SG', 'admin', 1, NOW(), NOW()),
    ('Gestor Teste', 'gestor@teste.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewPAAa4pQRLfT4SG', 'gestor', 1, NOW(), NOW()),
    ('Funcionário Teste', 'funcionario@teste.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewPAAa4pQRLfT4SG', 'funcionario', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE email=email;
SQL

echo -e "${GREEN}✅ Dados de teste inseridos${NC}"

# Executar testes
echo -e "\n${BLUE}[7/7] Executando testes de segurança...${NC}"
docker-compose exec -T app php test_security_components.php

# Mostrar resumo
echo -e "\n${GREEN}"
echo "============================================================"
echo "  ✅ SETUP COMPLETO - Sistema Rodando com Docker!"
echo "============================================================"
echo -e "${NC}"

echo -e "${BLUE}🌐 Serviços Disponíveis:${NC}"
echo "   Aplicação:   http://localhost:8080"
echo "   PHPMyAdmin:  http://localhost:8081"
echo "   MySQL:       localhost:3306"
echo ""

echo -e "${BLUE}🔐 Credenciais de Acesso:${NC}"
echo "   MySQL Root:  root / root_password_CHANGE_ME_in_production"
echo "   MySQL User:  ponto_user / ponto_pass_CHANGE_ME_in_production"
echo ""

echo -e "${BLUE}👤 Usuários de Teste (Senha: Admin@123456):${NC}"
echo "   Admin:       admin@teste.com"
echo "   Gestor:      gestor@teste.com"
echo "   Funcionário: funcionario@teste.com"
echo ""

echo -e "${BLUE}📋 Comandos Úteis:${NC}"
echo "   Ver logs:           docker-compose logs -f"
echo "   Parar serviços:     docker-compose stop"
echo "   Reiniciar:          docker-compose restart"
echo "   Remover tudo:       docker-compose down -v"
echo "   Entrar no MySQL:    docker-compose exec mysql mysql -u root -p"
echo "   Entrar na app:      docker-compose exec app bash"
echo ""

echo -e "${GREEN}✅ Acesse http://localhost:8080 e faça login!${NC}\n"
