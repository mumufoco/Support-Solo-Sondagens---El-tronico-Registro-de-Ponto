# DeepFace POC - Proof of Concept

## Sistema de Ponto Eletrônico - Fase 0

Este é um POC (Proof of Concept) para validar a integração do DeepFace para reconhecimento facial no sistema de ponto eletrônico.

## 📋 Pré-requisitos

- Python 3.8+
- pip
- 4GB RAM (mínimo)
- Conexão com internet (para download dos modelos)

## 🚀 Instalação

### 1. Execute o script de setup:

```bash
./setup_deepface_poc.sh
```

### 2. Ative o ambiente virtual:

```bash
source venv_deepface/bin/activate
```

### 3. Execute o POC:

```bash
python test_deepface.py
```

## 📁 Estrutura de Testes

Para teste completo de acurácia, crie a seguinte estrutura:

```
test/
└── faces/
    ├── person1/
    │   ├── photo1.jpg
    │   └── photo2.jpg
    ├── person2/
    │   ├── photo1.jpg
    │   └── photo2.jpg
    └── person3/
        ├── photo1.jpg
        └── photo2.jpg
```

**Dicas para fotos de teste:**
- Use fotos reais de pessoas diferentes
- Boa iluminação
- Rosto frontal, sem óculos escuros
- Resolução mínima: 640x480px
- Formato: JPG ou PNG

## 📊 Testes Executados

O POC executa 5 testes:

1. **Verificação de Instalação** - Confirma que DeepFace está instalado
2. **Detecção de Rostos** - Testa pipeline de detecção
3. **Acurácia de Reconhecimento** - Compara fotos e calcula similaridade
4. **Anti-Spoofing Básico** - Valida métodos de segurança
5. **Performance** - Mede tempo de resposta (target: <2s)

## 📄 Relatório

Após execução, o relatório JSON é salvo em:

```
test/deepface_poc_report.json
```

## ✅ Critérios de Aceitação

- ✓ Instalação bem-sucedida
- ✓ Detecção de rostos funcionando
- ✓ Acurácia ≥ 90% (com fotos reais)
- ✓ Tempo de resposta < 2s
- ✓ Anti-spoofing básico implementado

## 🎯 Próximos Passos

Após POC bem-sucedido:

1. Implementar microserviço DeepFace API (Fase 2)
2. Integrar com backend PHP
3. Configurar anti-spoofing avançado
4. Otimizar performance com GPU (opcional)

## 🔧 Troubleshooting

### Erro: "No module named 'tensorflow'"
```bash
pip install tensorflow==2.15.0
```

### Erro: "Could not load dynamic library 'libcublas.so'"
Normal se não tiver GPU NVIDIA. DeepFace funciona em CPU.

### Performance lenta (>5s)
Considere:
- Usar GPU (CUDA)
- Reduzir resolução das imagens
- Trocar backend de detecção (opencv → retinaface)

## 📝 Observações

- Primeira execução é mais lenta (download dos modelos ~300MB)
- Modelos são salvos em `~/.deepface/weights/`
- Para produção, considere modelo VGG-Face ou Facenet512

## 📧 Suporte

Consulte a documentação do DeepFace:
https://github.com/serengil/deepface
