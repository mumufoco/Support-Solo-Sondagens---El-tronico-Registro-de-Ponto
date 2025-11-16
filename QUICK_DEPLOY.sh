#!/bin/bash

#############################################################################
# QUICK DEPLOY SCRIPT - Sistema de Ponto Eletrônico
#
# Este script automatiza o deploy em produção.
# IMPORTANTE: Leia DEPLOY_PRODUCTION.md antes de executar!
#
# Uso:
#   chmod +x QUICK_DEPLOY.sh
#   ./QUICK_DEPLOY.sh
#############################################################################

set -e  # Parar em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Banner
echo -e "${BLUE}"
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   Sistema de Ponto Eletrônico - Deploy Automatizado      ║"
echo "║   Support Solo Sondagens 🇧🇷                              ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Função para verificar se comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Função para print com cor
print_step() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

#############################################################################
# ETAPA 1: Verificações Pré-Deploy
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 1: Verificações Pré-Deploy ═══${NC}\n"

# Verificar Docker
if command_exists docker; then
    DOCKER_VERSION=$(docker --version | cut -d ' ' -f3 | cut -d ',' -f1)
    print_step "Docker instalado (versão: $DOCKER_VERSION)"
else
    print_error "Docker não está instalado!"
    print_info "Execute: curl -fsSL https://get.docker.com | sh"
    exit 1
fi

# Verificar Docker Compose V2
if docker compose version >/dev/null 2>&1; then
    COMPOSE_VERSION=$(docker compose version | cut -d ' ' -f4 | cut -d 'v' -f2)
    print_step "Docker Compose V2 instalado (versão: $COMPOSE_VERSION)"
else
    print_error "Docker Compose V2 não está instalado!"
    print_info "Execute: sudo apt-get install docker-compose-plugin"
    exit 1
fi

# Verificar se está no diretório correto
if [ ! -f "docker-compose.yml" ]; then
    print_error "Arquivo docker-compose.yml não encontrado!"
    print_info "Execute este script a partir do diretório do projeto."
    exit 1
fi
print_step "Diretório do projeto verificado"

# Verificar .env
if [ ! -f ".env" ]; then
    print_warning "Arquivo .env não encontrado!"

    if [ -f ".env.example" ]; then
        echo -e "${YELLOW}Deseja copiar .env.example para .env? (s/n)${NC}"
        read -r response
        if [[ "$response" =~ ^[Ss]$ ]]; then
            cp .env.example .env
            print_step ".env criado a partir do .env.example"
            print_warning "IMPORTANTE: Edite o .env e configure senhas antes de continuar!"
            print_info "Execute: nano .env"
            exit 0
        else
            print_error "Deploy cancelado. Crie o arquivo .env primeiro."
            exit 1
        fi
    else
        print_error ".env.example também não encontrado!"
        exit 1
    fi
else
    print_step "Arquivo .env encontrado"
fi

# Verificar se .env tem valores padrão perigosos
if grep -q "SuaSenhaMySQLForte123!" .env 2>/dev/null; then
    print_error "SENHAS PADRÃO DETECTADAS NO .env!"
    print_warning "NUNCA use senhas de exemplo em produção!"
    print_info "Edite o .env e altere todas as senhas antes de continuar."
    echo -e "${YELLOW}Continuar mesmo assim? (NÃO RECOMENDADO) (s/n)${NC}"
    read -r response
    if [[ ! "$response" =~ ^[Ss]$ ]]; then
        exit 1
    fi
fi

#############################################################################
# ETAPA 2: Configuração do Ambiente
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 2: Configuração do Ambiente ═══${NC}\n"

# Perguntar ambiente
echo -e "${YELLOW}Selecione o ambiente:${NC}"
echo "  1) Produção (apenas serviços essenciais)"
echo "  2) Desenvolvimento (inclui PHPMyAdmin, Mailhog, etc.)"
read -p "Opção (1 ou 2): " ENV_OPTION

case $ENV_OPTION in
    1)
        PROFILE=""
        print_step "Ambiente: PRODUÇÃO"
        ;;
    2)
        PROFILE="--profile development"
        print_step "Ambiente: DESENVOLVIMENTO"
        ;;
    *)
        print_error "Opção inválida!"
        exit 1
        ;;
esac

#############################################################################
# ETAPA 3: Build das Imagens
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 3: Build das Imagens Docker ═══${NC}\n"

echo -e "${YELLOW}Deseja fazer build das imagens? (s/n)${NC}"
echo "(Obrigatório na primeira vez ou após mudanças no código)"
read -r BUILD_RESPONSE

if [[ "$BUILD_RESPONSE" =~ ^[Ss]$ ]]; then
    print_info "Iniciando build... (pode levar 5-10 minutos)"

    if docker compose build --no-cache; then
        print_step "Build concluído com sucesso!"
    else
        print_error "Erro durante o build!"
        exit 1
    fi
else
    print_warning "Build ignorado. Usando imagens existentes."
fi

#############################################################################
# ETAPA 4: Iniciar Serviços
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 4: Iniciar Serviços ═══${NC}\n"

print_info "Iniciando containers..."

if docker compose $PROFILE up -d; then
    print_step "Containers iniciados!"
else
    print_error "Erro ao iniciar containers!"
    exit 1
fi

# Aguardar inicialização
print_info "Aguardando inicialização dos serviços (30 segundos)..."
sleep 30

#############################################################################
# ETAPA 5: Verificações Pós-Deploy
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 5: Verificações Pós-Deploy ═══${NC}\n"

# Verificar status dos containers
print_info "Verificando status dos containers..."
docker compose ps

# Verificar health dos containers
UNHEALTHY=$(docker compose ps --format json | grep -c '"Health":"unhealthy"' || true)
if [ "$UNHEALTHY" -gt 0 ]; then
    print_warning "$UNHEALTHY container(s) não estão saudáveis!"
    print_info "Execute: docker compose logs [nome-do-serviço]"
else
    print_step "Todos os containers estão saudáveis!"
fi

#############################################################################
# ETAPA 6: Configuração do Banco de Dados
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 6: Configuração do Banco de Dados ═══${NC}\n"

echo -e "${YELLOW}Executar migrations? (s/n)${NC}"
read -r MIGRATE_RESPONSE

if [[ "$MIGRATE_RESPONSE" =~ ^[Ss]$ ]]; then
    print_info "Executando migrations..."

    if docker compose exec app php spark migrate; then
        print_step "Migrations executadas com sucesso!"
    else
        print_warning "Erro ao executar migrations (pode ser normal se já foram executadas)"
    fi
fi

echo -e "${YELLOW}Executar seeder AdminSeeder? (s/n)${NC}"
read -r SEED_RESPONSE

if [[ "$SEED_RESPONSE" =~ ^[Ss]$ ]]; then
    print_info "Executando AdminSeeder..."

    if docker compose exec app php spark db:seed AdminSeeder; then
        print_step "AdminSeeder executado com sucesso!"
    else
        print_warning "Erro ao executar seeder (pode já existir)"
    fi
fi

#############################################################################
# ETAPA 7: Testes Finais
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 7: Testes Finais ═══${NC}\n"

# Testar endpoint HTTP
print_info "Testando endpoint HTTP..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|302"; then
    print_step "Aplicação respondendo na porta 80!"
else
    print_warning "Aplicação pode não estar respondendo corretamente"
fi

#############################################################################
# FINALIZAÇÃO
#############################################################################

echo -e "\n${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                   DEPLOY CONCLUÍDO! ✓                     ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}\n"

print_info "Acessos:"
echo "  • Aplicação Web: http://localhost"

if [[ "$PROFILE" == *"development"* ]]; then
    echo "  • PHPMyAdmin: http://localhost:8080"
    echo "  • Mailhog: http://localhost:8025"
    echo "  • Redis Commander: http://localhost:8081"
fi

echo ""
print_info "Comandos úteis:"
echo "  • Ver logs: docker compose logs -f"
echo "  • Parar: docker compose stop"
echo "  • Reiniciar: docker compose restart"
echo "  • Status: docker compose ps"

echo ""
print_warning "Próximos passos:"
echo "  1. Acesse a aplicação no navegador"
echo "  2. Teste o login com usuário admin"
echo "  3. Verifique logs: docker compose logs -f app"
echo "  4. Configure SSL/HTTPS (ver DEPLOY_PRODUCTION.md)"
echo "  5. Configure backups (ver DEPLOY_PRODUCTION.md)"

echo ""
print_info "Documentação completa: DEPLOY_PRODUCTION.md"
echo ""
