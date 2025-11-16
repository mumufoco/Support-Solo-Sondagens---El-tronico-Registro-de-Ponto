#!/bin/bash

###############################################################################
# Testes de Carga - Sistema de Ponto Eletrônico
# Usa Apache Bench (ab) para simular carga
###############################################################################

set -e

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== Testes de Carga - Sistema de Ponto Eletrônico ===${NC}"
echo ""

BASE_URL="http://localhost:8080"
RESULTS_DIR="tests/_output/load_tests"

# Criar diretório de resultados
mkdir -p $RESULTS_DIR

# Token JWT para autenticação (obter via login)
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

###############################################################################
# 1. Teste: Endpoint /api/punch (100 funcionários batendo ponto simultaneamente)
###############################################################################
echo -e "${YELLOW}[1/4]${NC} Testando endpoint /api/punch..."

# Criar payload JSON
cat > /tmp/punch_payload.json <<EOF
{
    "employee_id": 1,
    "punch_type": "entrada",
    "latitude": -23.550520,
    "longitude": -46.633309
}
EOF

# Executar teste
# -n 1000 = 1000 requisições totais
# -c 50   = 50 requisições concorrentes
# -p      = payload (POST)
# -T      = Content-Type
# -H      = Header de autorização

ab -n 1000 -c 50 \
   -p /tmp/punch_payload.json \
   -T "application/json" \
   -H "Authorization: Bearer $TOKEN" \
   "$BASE_URL/api/punch" \
   > "$RESULTS_DIR/punch_test.txt" 2>&1

# Validar resultados
echo -e "${GREEN}✓${NC} Teste concluído: /api/punch"
echo "  Target: 95% requests <500ms, 0% falhas"
grep "Time per request" "$RESULTS_DIR/punch_test.txt" | head -1
grep "Failed requests" "$RESULTS_DIR/punch_test.txt"
echo ""

###############################################################################
# 2. Teste: Endpoint /recognize (reconhecimento facial)
###############################################################################
echo -e "${YELLOW}[2/4]${NC} Testando endpoint /recognize (DeepFace)..."

cat > /tmp/recognize_payload.json <<EOF
{
    "image": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD...",
    "threshold": 0.40
}
EOF

# Menos requisições pois reconhecimento facial é lento
ab -n 100 -c 10 \
   -p /tmp/recognize_payload.json \
   -T "application/json" \
   "$BASE_URL:5000/recognize" \
   > "$RESULTS_DIR/recognize_test.txt" 2>&1

echo -e "${GREEN}✓${NC} Teste concluído: /recognize"
echo "  Target: 95% requests <2s (processar foto é lento)"
grep "Time per request" "$RESULTS_DIR/recognize_test.txt" | head -1
echo ""

###############################################################################
# 3. Teste: GET /api/employees (listagem)
###############################################################################
echo -e "${YELLOW}[3/4]${NC} Testando endpoint /api/employees..."

ab -n 500 -c 25 \
   -H "Authorization: Bearer $TOKEN" \
   "$BASE_URL/api/employees" \
   > "$RESULTS_DIR/employees_test.txt" 2>&1

echo -e "${GREEN}✓${NC} Teste concluído: /api/employees"
grep "Requests per second" "$RESULTS_DIR/employees_test.txt"
echo ""

###############################################################################
# 4. Teste: Geração de relatório grande (10k linhas)
###############################################################################
echo -e "${YELLOW}[4/4]${NC} Testando geração de relatório grande..."

ab -n 20 -c 5 \
   -H "Authorization: Bearer $TOKEN" \
   "$BASE_URL/api/reports/timesheet?start=2024-01-01&end=2024-12-31" \
   > "$RESULTS_DIR/report_test.txt" 2>&1

echo -e "${GREEN}✓${NC} Teste concluído: relatório"
echo "  Target: Não causar timeout ou memory limit"
grep "Complete requests" "$RESULTS_DIR/report_test.txt"
echo ""

###############################################################################
# Gerar Relatório Consolidado
###############################################################################
echo -e "${BLUE}=== Relatório Consolidado ===${NC}"
echo ""

echo "📊 Resumo dos Testes:"
echo ""

echo "1. /api/punch:"
grep "Requests per second" "$RESULTS_DIR/punch_test.txt" | awk '{print "   - " $0}'
grep "Time per request" "$RESULTS_DIR/punch_test.txt" | head -1 | awk '{print "   - " $0}'
grep "Failed requests" "$RESULTS_DIR/punch_test.txt" | awk '{print "   - " $0}'

echo ""
echo "2. /recognize:"
grep "Requests per second" "$RESULTS_DIR/recognize_test.txt" | awk '{print "   - " $0}'
grep "Time per request" "$RESULTS_DIR/recognize_test.txt" | head -1 | awk '{print "   - " $0}'

echo ""
echo "3. /api/employees:"
grep "Requests per second" "$RESULTS_DIR/employees_test.txt" | awk '{print "   - " $0}'

echo ""
echo "4. Relatórios:"
grep "Complete requests" "$RESULTS_DIR/report_test.txt" | awk '{print "   - " $0}'

echo ""
echo -e "${GREEN}✓ Testes de carga concluídos!${NC}"
echo -e "Resultados salvos em: ${BLUE}$RESULTS_DIR${NC}"
echo ""

# Limpar payloads temporários
rm -f /tmp/punch_payload.json /tmp/recognize_payload.json

exit 0
