# DeepFace API - Facial Recognition Service

Microserviço de reconhecimento facial para o Sistema de Ponto Eletrônico Brasileiro.

## 🚀 Características

- ✅ **8 modelos de IA disponíveis:** VGG-Face, Facenet, ArcFace, Dlib, e mais
- ✅ **99.65% de acurácia** (VGG-Face)
- ✅ **Anti-spoofing integrado** - Detecta fotos falsas
- ✅ **Sem Docker** - Instalação simplificada via pip
- ✅ **400MB RAM** - Baixo consumo de recursos
- ✅ **Rate limiting** - Proteção contra abuso
- ✅ **CORS configurável** - Segurança de API
- ✅ **Logs detalhados** - Auditoria completa

## 📋 Requisitos

- **Python 3.8+**
- **4GB RAM** mínimo (recomendado)
- **Linux/Ubuntu 22.04** (recomendado)

## 🔧 Instalação

### 1. Navegar para o diretório

```bash
cd deepface-api
```

### 2. Criar ambiente virtual

```bash
python3 -m venv venv
source venv/bin/activate  # Linux/Mac
# ou
venv\Scripts\activate     # Windows
```

### 3. Instalar dependências

```bash
pip install --upgrade pip
pip install -r requirements.txt
```

### 4. Configurar variáveis de ambiente

```bash
cp .env.example .env
nano .env  # Editar conforme necessário
```

### 5. Criar diretórios necessários

```bash
mkdir -p logs
mkdir -p ../storage/faces/temp
```

## 🚀 Execução

### Modo Desenvolvimento

```bash
# Usando o script (recomendado)
../scripts/deepface_start.sh

# Ou manualmente
python app.py
```

### Modo Produção

```bash
# Usando Gunicorn (recomendado)
../scripts/deepface_start.sh --production

# Ou manualmente
gunicorn --bind 0.0.0.0:5000 --workers 4 --timeout 120 app:app
```

### Configurar como Serviço (systemd)

```bash
# Criar arquivo de serviço
sudo nano /etc/systemd/system/deepface-api.service
```

Conteúdo:

```ini
[Unit]
Description=DeepFace API - Facial Recognition Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/ponto-eletronico/deepface-api
Environment="PATH=/var/www/ponto-eletronico/deepface-api/venv/bin"
ExecStart=/var/www/ponto-eletronico/deepface-api/venv/bin/gunicorn --bind 0.0.0.0:5000 --workers 4 --timeout 120 app:app
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Habilitar e iniciar:

```bash
sudo systemctl daemon-reload
sudo systemctl enable deepface-api
sudo systemctl start deepface-api
sudo systemctl status deepface-api
```

## 📡 API Endpoints

### 1. Health Check

**GET** `/health`

Verifica se a API está funcionando.

**Resposta:**
```json
{
  "status": "ok",
  "service": "deepface-api",
  "version": "1.0.0",
  "model": "VGG-Face",
  "detector": "opencv",
  "timestamp": "2024-01-15T10:30:00"
}
```

### 2. Enroll (Cadastrar Rosto)

**POST** `/enroll`

Cadastra um novo rosto no banco de dados.

**Request:**
```json
{
  "employee_id": "123",
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Resposta Sucesso:**
```json
{
  "success": true,
  "employee_id": "123",
  "face_path": "../storage/faces/123/123_face.jpg",
  "image_hash": "abc123...",
  "confidence": 0.99,
  "facial_area": {"x": 100, "y": 120, "w": 150, "h": 180},
  "message": "Face enrolled successfully"
}
```

**Resposta Erro:**
```json
{
  "success": false,
  "error": "No face detected in the image"
}
```

### 3. Recognize (Reconhecer Rosto)

**POST** `/recognize`

Reconhece um rosto a partir do banco de dados.

**Request:**
```json
{
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
  "threshold": 0.40
}
```

**Resposta Reconhecido:**
```json
{
  "success": true,
  "recognized": true,
  "employee_id": "123",
  "distance": 0.25,
  "similarity": 0.75,
  "threshold": 0.40,
  "model": "VGG-Face",
  "detector": "opencv",
  "message": "Face recognized successfully"
}
```

**Resposta Não Reconhecido:**
```json
{
  "success": true,
  "recognized": false,
  "message": "No matching face found"
}
```

### 4. Verify (Verificar Similaridade)

**POST** `/verify`

Verifica se duas fotos são da mesma pessoa.

**Request:**
```json
{
  "photo1": "data:image/jpeg;base64,...",
  "photo2": "data:image/jpeg;base64,..."
}
```

**Resposta:**
```json
{
  "success": true,
  "verified": true,
  "distance": 0.18,
  "similarity": 0.82,
  "threshold": 0.40,
  "model": "VGG-Face",
  "message": "Faces verified successfully"
}
```

### 5. Analyze (Analisar Atributos)

**POST** `/analyze`

Analisa atributos faciais (idade, gênero, emoção, raça).

**Request:**
```json
{
  "photo": "data:image/jpeg;base64,..."
}
```

**Resposta:**
```json
{
  "success": true,
  "age": 28,
  "gender": "Man",
  "emotion": "happy",
  "race": "latino hispanic",
  "facial_area": {"x": 100, "y": 120, "w": 150, "h": 180},
  "message": "Face analyzed successfully"
}
```

## ⚙️ Configuração

### Variáveis de Ambiente (.env)

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `MODEL_NAME` | VGG-Face | Modelo de IA |
| `DETECTOR_BACKEND` | opencv | Detector de rosto |
| `DISTANCE_METRIC` | cosine | Métrica de distância |
| `THRESHOLD` | 0.40 | Threshold de reconhecimento |
| `PORT` | 5000 | Porta do servidor |
| `FACES_DB_PATH` | ../storage/faces | Path do banco de rostos |
| `MAX_FILE_SIZE` | 5242880 | Tamanho máximo (5MB) |
| `ANTI_SPOOFING_ENABLED` | True | Anti-spoofing |
| `RATELIMIT_DEFAULT` | 100 per minute | Rate limit |

### Modelos Disponíveis

| Modelo | Acurácia | Velocidade | Threshold Cosine |
|--------|----------|------------|------------------|
| **VGG-Face** | 99.65% | Média | 0.40 |
| **Facenet** | 99.20% | Rápida | 0.40 |
| **Facenet512** | 99.65% | Média | 0.30 |
| **ArcFace** | 99.40% | Média | 0.68 |
| **Dlib** | 99.38% | Rápida | 0.07 |
| **SFace** | 99.50% | Rápida | 0.593 |
| **OpenFace** | 93.80% | Muito Rápida | 0.10 |
| **DeepFace** | 97.35% | Lenta | 0.23 |

**Recomendação:** VGG-Face oferece o melhor equilíbrio entre acurácia e velocidade.

### Detectores Disponíveis

| Detector | Velocidade | Acurácia | Recomendado |
|----------|-----------|----------|-------------|
| **opencv** | Muito Rápida | Boa | ✅ Desenvolvimento |
| **ssd** | Rápida | Boa | Geral |
| **mtcnn** | Média | Muito Boa | Precisão |
| **retinaface** | Lenta | Excelente | Produção |
| **mediapipe** | Rápida | Boa | Mobile |

## 🔒 Segurança

### Rate Limiting

- **Enroll:** 20 req/min por IP
- **Recognize:** 10 req/min por IP
- **Verify:** 20 req/min por IP
- **Analyze:** 20 req/min por IP

### CORS

Configure os domínios permitidos em `CORS_ORIGINS`:

```env
CORS_ORIGINS=http://localhost:8000,https://seu-dominio.com.br
```

### Anti-Spoofing

O sistema detecta:
- ✅ Fotos impressas
- ✅ Fotos em telas de celular/monitor
- ✅ Faces muito pequenas (<80x80 pixels)
- ✅ Múltiplas faces na mesma foto

## 📊 Monitoramento

### Logs

Os logs são salvos em:
- `logs/deepface_api.log` - Log principal
- `logs/access.log` - Acessos (Gunicorn)
- `logs/error.log` - Erros (Gunicorn)

### Health Check

```bash
curl http://localhost:5000/health
```

### Verificar Status do Serviço

```bash
sudo systemctl status deepface-api
```

### Ver Logs em Tempo Real

```bash
tail -f logs/deepface_api.log
```

## 🧪 Testes

### Teste Manual com cURL

```bash
# Health check
curl http://localhost:5000/health

# Enroll (com arquivo)
curl -X POST http://localhost:5000/enroll \
  -H "Content-Type: application/json" \
  -d '{"employee_id":"123","photo":"'"$(base64 -w 0 test_face.jpg)"'"}'

# Recognize
curl -X POST http://localhost:5000/recognize \
  -H "Content-Type: application/json" \
  -d '{"photo":"'"$(base64 -w 0 test_face.jpg)"'","threshold":0.40}'
```

## 🐛 Troubleshooting

### Problema: "No module named 'deepface'"

**Solução:**
```bash
source venv/bin/activate
pip install -r requirements.txt
```

### Problema: "No face detected"

**Soluções:**
1. Usar foto com boa iluminação
2. Face centralizada e visível
3. Remover óculos escuros/máscaras
4. Tentar detector diferente (mtcnn, retinaface)

### Problema: "Rate limit exceeded"

**Solução:**
- Aguardar 1 minuto
- Ou ajustar `RATELIMIT_DEFAULT` no .env

### Problema: Reconhecimento com baixa acurácia

**Soluções:**
1. Ajustar threshold (aumentar = mais permissivo)
2. Usar modelo mais preciso (Facenet512, ArcFace)
3. Cadastrar com foto de melhor qualidade
4. Usar detector mais preciso (retinaface)

## 📚 Referências

- [DeepFace GitHub](https://github.com/serengil/deepface)
- [Flask Documentation](https://flask.palletsprojects.com/)
- [Gunicorn Documentation](https://gunicorn.org/)

## 📝 Licença

MIT License - Sistema de Ponto Eletrônico Brasileiro

---

**Desenvolvido com ❤️ para empresas brasileiras**
