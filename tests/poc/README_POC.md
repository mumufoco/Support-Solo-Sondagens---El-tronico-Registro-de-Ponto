# POC - Reconhecimento Facial em Produção

## Objetivo

Validar taxa de acerto do DeepFace com funcionários reais **antes do deploy em produção**.

**Target:** Taxa de acerto >90%

## Preparação

### 1. Coletar Fotos Reais

Solicitar a **5 funcionários voluntários** que tirem fotos em diferentes condições:

```
tests/poc/photos/
├── employee_1_base.jpg           # Foto base para cadastro
├── employee_1_morning.jpg         # Manhã (luz natural)
├── employee_1_afternoon.jpg       # Tarde
├── employee_1_night.jpg           # Noite (luz artificial)
├── employee_1_with_glasses.jpg    # Com óculos
├── employee_1_without_glasses.jpg # Sem óculos
├── employee_1_distance_30cm.jpg   # 30cm da câmera
├── employee_1_distance_50cm.jpg   # 50cm da câmera
├── employee_1_distance_1m.jpg     # 1 metro da câmera
├── employee_1_printed_photo.jpg   # Foto de uma foto impressa (anti-spoofing)
├── employee_1_screen_photo.jpg    # Foto de tela de celular (anti-spoofing)
├── employee_2_base.jpg
├── ...
├── employee_5_...
└── unknown_1.jpg                  # Pessoas não cadastradas
```

### 2. Requisitos Técnicos

- **Resolução mínima:** 640x480px
- **Formato:** JPG ou PNG
- **Rosto visível:** Frontal, bem iluminado
- **Tamanho arquivo:** <5MB

## Executar POC

```bash
# 1. Garantir que DeepFace API está rodando
cd deepface-api
source venv/bin/activate
python app.py
# API deve estar em http://localhost:5000

# 2. Em outro terminal, executar POC
cd tests/poc
php facial_recognition_poc.php
```

## Saída Esperada

```
=== POC Reconhecimento Facial ===
Iniciado em: 2025-01-15 10:30:00

Verificando DeepFace API... ✅ Online

Cadastrando funcionários...
  ✅ João Silva cadastrado (234ms)
  ✅ Maria Santos cadastrado (198ms)
  ✅ Pedro Oliveira cadastrado (215ms)
  ✅ Ana Costa cadastrado (220ms)
  ✅ Carlos Mendes cadastrado (209ms)

Testando reconhecimento em diferentes condições...

Funcionário: João Silva
  ✅ Manhã (luz natural): Similaridade=0.92, Tempo=1850ms
  ✅ Tarde: Similaridade=0.89, Tempo=1920ms
  ✅ Noite (luz artificial): Similaridade=0.85, Tempo=1780ms
  ✅ Com óculos: Similaridade=0.88, Tempo=1810ms
  ✅ Sem óculos: Similaridade=0.91, Tempo=1900ms
  ✅ Distância 30cm: Similaridade=0.94, Tempo=1750ms
  ✅ Distância 50cm: Similaridade=0.90, Tempo=1830ms
  ✅ Distância 1m: Similaridade=0.82, Tempo=1950ms

[... outros funcionários ...]

Testando anti-spoofing...
  ✅ Foto impressa: BLOQUEADO (anti-spoofing funcionou)
  ✅ Foto de tela (celular): BLOQUEADO (anti-spoofing funcionou)

Testando pessoas não cadastradas...
  ✅ Pessoa não reconhecida (correto)
  ✅ Pessoa não reconhecida (correto)

Gerando relatório...

=== Resultados Finais ===
Total de testes: 48
Reconhecimentos bem-sucedidos: 44
Taxa de acerto: 91.67%

✅ META ATINGIDA (>90%)

Relatório salvo em: tests/_output/poc_facial_report.csv
```

## Análise do Relatório CSV

```csv
Funcionário ID,Nome,Condição,Reconhecido,Similaridade,Tempo (ms)
1,João Silva,Manhã (luz natural),Sim,0.92,1850
1,João Silva,Tarde,Sim,0.89,1920
1,João Silva,Noite (luz artificial),Sim,0.85,1780
...
```

### Métricas Importantes

1. **Taxa de Acerto Global:** >90%
2. **Taxa de Falsos Positivos:** <5%
3. **Taxa de Falsos Negativos:** <10%
4. **Tempo Médio de Reconhecimento:** <2s
5. **Anti-Spoofing:** 100% de bloqueio

## Ações Baseadas nos Resultados

### ✅ Taxa >90% - APROVADO

Prosseguir com deploy em produção.

### ⚠️ Taxa 85-90% - AJUSTAR

- Reduzir threshold de 0.40 para 0.35
- Melhorar iluminação no local de registro
- Treinar funcionários sobre como posicionar rosto

### ❌ Taxa <85% - REVISAR

Considerar:
- Trocar modelo DeepFace (VGG-Face → Facenet ou ArcFace)
- Adicionar mais fotos de treino por funcionário
- Implementar fallback (código ou QR) quando facial falha

## Documentar Resultados

Após executar POC, documentar no relatório de qualidade:

```markdown
## Validação de Reconhecimento Facial

**Data:** 2025-01-15
**Participantes:** 5 funcionários voluntários
**Condições testadas:** 8 por funcionário
**Total de testes:** 48

### Resultados
- Taxa de acerto: 91.67% ✅
- Falsos positivos: 0%
- Falsos negativos: 8.33%
- Anti-spoofing: 100% efetivo
- Tempo médio: 1.85s

### Conclusão
Sistema APROVADO para produção com threshold 0.40.

### Recomendações
- Treinamento obrigatório para funcionários
- Iluminação adequada no ponto de registro
- Fallback via código em caso de falha facial
```

## Manutenção

- **Executar POC:** A cada atualização do DeepFace ou mudança de modelo
- **Frequência:** Trimestral ou quando taxa de acerto cair em produção
- **Monitoramento:** Logs de tentativas de reconhecimento (sucesso/falha)

## Troubleshooting

### DeepFace API Offline
```bash
curl http://localhost:5000/health
# Se não responder, reiniciar serviço
```

### Taxa de Acerto Baixa
1. Verificar qualidade das fotos (resolução, iluminação)
2. Testar com threshold menor (0.35 em vez de 0.40)
3. Considerar trocar modelo

### Falsos Positivos
- Aumentar threshold (0.45 ou 0.50)
- Habilitar detecção de liveness (anti-spoofing mais rigoroso)
