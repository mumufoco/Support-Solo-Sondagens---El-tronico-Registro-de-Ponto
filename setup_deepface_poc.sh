#!/bin/bash

# Setup DeepFace POC Environment
# Sistema de Ponto Eletrônico

echo "========================================="
echo "DeepFace POC - Setup"
echo "Sistema de Ponto Eletrônico"
echo "========================================="
echo ""

# Verificar se Python 3 está instalado
if ! command -v python3 &> /dev/null; then
    echo "ERRO: Python 3 não encontrado"
    echo "Instale Python 3.8+ primeiro:"
    echo "  Ubuntu/Debian: sudo apt install python3 python3-venv python3-pip"
    echo "  CentOS/RHEL: sudo yum install python3 python3-pip"
    exit 1
fi

PYTHON_VERSION=$(python3 --version | cut -d' ' -f2 | cut -d'.' -f1,2)
echo "✓ Python encontrado: $(python3 --version)"

# Criar ambiente virtual
VENV_DIR="venv_deepface"

if [ -d "$VENV_DIR" ]; then
    echo "⚠ Ambiente virtual já existe: $VENV_DIR"
    read -p "Deseja recriar? (s/N): " RECREATE
    if [ "$RECREATE" = "s" ] || [ "$RECREATE" = "S" ]; then
        echo "Removendo ambiente antigo..."
        rm -rf "$VENV_DIR"
    else
        echo "Usando ambiente existente"
    fi
fi

if [ ! -d "$VENV_DIR" ]; then
    echo ""
    echo "Criando ambiente virtual..."
    python3 -m venv "$VENV_DIR"
    echo "✓ Ambiente virtual criado: $VENV_DIR"
fi

# Ativar ambiente virtual
echo ""
echo "Ativando ambiente virtual..."
source "$VENV_DIR/bin/activate"

# Atualizar pip
echo ""
echo "Atualizando pip..."
pip install --upgrade pip

# Instalar dependências
echo ""
echo "Instalando dependências do DeepFace..."
echo "(Isso pode levar alguns minutos...)"
pip install -r requirements_deepface.txt

echo ""
echo "========================================="
echo "SETUP CONCLUÍDO"
echo "========================================="
echo ""
echo "Para executar o POC:"
echo "  1. Ative o ambiente virtual:"
echo "     source venv_deepface/bin/activate"
echo ""
echo "  2. Execute o teste:"
echo "     python test_deepface.py"
echo ""
echo "  3. Para sair do ambiente virtual:"
echo "     deactivate"
echo ""
echo "========================================="
