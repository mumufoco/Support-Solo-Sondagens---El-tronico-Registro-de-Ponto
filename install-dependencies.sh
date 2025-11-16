#!/bin/bash

###############################################################################
# Script de Instalação - Sistema de Ponto Eletrônico Brasileiro
# Versão 2.0 - Com DeepFace (sem Docker)
# Cria toda a estrutura de diretórios do projeto
###############################################################################

set -e  # Sair em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para imprimir mensagens
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Banner
echo "=============================================================="
echo "  Sistema de Ponto Eletrônico Brasileiro - Instalação"
echo "  Versão 2.0 com DeepFace"
echo "=============================================================="
echo ""

# Verificar se estamos no diretório correto
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

print_info "Diretório atual: $SCRIPT_DIR"
print_info "Criando estrutura de diretórios do projeto..."
echo ""

# Criar diretórios principais
print_info "Criando diretórios principais..."

mkdir -p public/assets/{css,js,images/icons}
mkdir -p app/{Config,Controllers,Models,Services,Filters,Libraries,Views,Database}
mkdir -p storage/{cache,logs,uploads,faces,keys}
mkdir -p vendor
mkdir -p deepface-api
mkdir -p scripts
mkdir -p tests/{unit,integration,e2e}

print_success "Diretórios principais criados"

# Criar subdiretórios de Controllers
print_info "Criando estrutura de Controllers..."

mkdir -p app/Controllers/{Auth,Dashboard,Timesheet,Employee,Biometric,Geolocation,Report,Chat,Warning,Setting,Api}

print_success "Controllers estruturados"

# Criar estrutura de Services
print_info "Criando estrutura de Services..."

mkdir -p app/Services/{Auth,Biometric,Geolocation,ICP,Notification,Report,Queue,WebSocket,LGPD}

print_success "Services estruturados"

# Criar estrutura de Libraries
print_info "Criando estrutura de Libraries..."

mkdir -p app/Libraries/{QRCode,Encryption,Validation}

print_success "Libraries estruturadas"

# Criar estrutura de Views
print_info "Criando estrutura de Views..."

mkdir -p app/Views/{layouts,auth,dashboard,timesheet,employee,biometric,report,chat,warning,settings}

print_success "Views estruturadas"

# Criar estrutura de Database
print_info "Criando estrutura de Database..."

mkdir -p app/Database/{Migrations,Seeds}

print_success "Database estruturada"

# Criar estrutura de Storage
print_info "Criando estrutura de Storage..."

mkdir -p storage/uploads/{justifications,warnings,temp}
mkdir -p storage/keys/icp

print_success "Storage estruturado"

# Criar diretório para ambiente virtual Python (DeepFace)
print_info "Criando estrutura para DeepFace API..."

mkdir -p deepface-api/venv

print_success "DeepFace API estruturada"

# Configurar permissões
print_info "Configurando permissões de diretórios..."

# Permissões de escrita para storage
chmod -R 775 storage/
chmod -R 775 public/assets/

# Permissões para scripts
chmod -R 755 scripts/

print_success "Permissões configuradas"

# Criar arquivo .gitignore se não existir
if [ ! -f .gitignore ]; then
    print_info "Criando arquivo .gitignore..."
    cat > .gitignore << 'EOF'
# Environment
.env
.env.*
!.env.example

# Vendor
/vendor/
/node_modules/

# Storage
/storage/cache/*
!/storage/cache/.gitkeep
/storage/logs/*
!/storage/logs/.gitkeep
/storage/uploads/*
!/storage/uploads/.gitkeep
/storage/faces/*
!/storage/faces/.gitkeep
/storage/keys/*
!/storage/keys/.gitkeep

# DeepFace
/deepface-api/venv/
/deepface-api/__pycache__/
/deepface-api/*.pyc
/deepface-api/.pytest_cache/

# IDE
.vscode/
.idea/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# Composer
composer.phar
composer.lock

# Testing
/tests/_output/
/tests/_support/_generated/
EOF
    print_success "Arquivo .gitignore criado"
fi

# Criar arquivos .gitkeep para manter estrutura de diretórios vazios
print_info "Criando arquivos .gitkeep..."

touch storage/cache/.gitkeep
touch storage/logs/.gitkeep
touch storage/uploads/.gitkeep
touch storage/uploads/justifications/.gitkeep
touch storage/uploads/warnings/.gitkeep
touch storage/uploads/temp/.gitkeep
touch storage/faces/.gitkeep
touch storage/keys/.gitkeep
touch storage/keys/icp/.gitkeep

print_success "Arquivos .gitkeep criados"

# Resumo da estrutura criada
echo ""
echo "=============================================================="
print_success "Estrutura de diretórios criada com sucesso!"
echo "=============================================================="
echo ""

print_info "Estrutura criada:"
echo ""
echo "  📁 public/          - Document root (assets, index.php)"
echo "  📁 app/             - Código da aplicação"
echo "    📁 Controllers/   - Controladores MVC"
echo "    📁 Models/        - Modelos de dados"
echo "    📁 Services/      - Serviços de negócio"
echo "    📁 Libraries/     - Bibliotecas customizadas"
echo "    📁 Views/         - Templates de visualização"
echo "    📁 Database/      - Migrations e Seeds"
echo "  📁 storage/         - Arquivos e cache"
echo "  📁 deepface-api/    - Microserviço Python"
echo "  📁 scripts/         - Scripts de automação"
echo "  📁 tests/           - Testes automatizados"
echo ""

print_warning "Próximos passos:"
echo ""
echo "  1. Configure o arquivo .env com suas credenciais"
echo "  2. Instale as dependências PHP: composer install"
echo "  3. Configure o ambiente Python para DeepFace"
echo "  4. Execute as migrations do banco de dados"
echo "  5. Inicie o servidor de desenvolvimento"
echo ""

print_info "Script finalizado em: $(date)"
echo ""

exit 0
