# PLANO DE APLICATIVO MOBILE - PONTO ELETRÔNICO
## Versão R0 - Revisão Inicial

---

## 📱 ÍNDICE

1. [Visão Geral do App Mobile](#1-visão-geral-do-app-mobile)
2. [Estratégia de Desenvolvimento](#2-estratégia-de-desenvolvimento)
3. [Funcionalidades MVP Mobile](#3-funcionalidades-mvp-mobile)
4. [Arquitetura Técnica](#4-arquitetura-técnica)
5. [Fluxos de Uso Mobile](#5-fluxos-de-uso-mobile)
6. [Integração com Backend](#6-integração-com-backend)
7. [Segurança e Autenticação](#7-segurança-e-autenticação)
8. [Testes e Validação Mobile](#8-testes-e-validação-mobile)
9. [Deploy e Distribuição](#9-deploy-e-distribuição)
10. [Timeline e Fases](#10-timeline-e-fases)
11. [Riscos Específicos Mobile](#11-riscos-específicos-mobile)

---

## 1. VISÃO GERAL DO APP MOBILE

### 1.1 Objetivo do App

Desenvolver aplicativo mobile nativo/híbrido para registro de ponto eletrônico, permitindo que funcionários:
- Registrem entrada/saída de qualquer local
- Validem presença com biometria facial e geolocalização
- Consultem histórico de registros
- Recebam notificações de lembretes
- Acessem espelho de ponto mensal

### 1.2 Público-Alvo

- **Funcionários**: CLT, 20-30 por empresa
- **Dispositivos**: Android 8.0+ e iOS 13.0+
- **Uso**: 4-8 interações/dia (entrada, saída, intervalos)

### 1.3 Diferenciais

✅ **Offline-first**: Registros salvos localmente se sem internet
✅ **Biometria nativa**: Face ID (iOS) + BiometricPrompt (Android)
✅ **Push notifications**: Lembretes automáticos de registro
✅ **Geofencing**: Validação automática de localização
✅ **Câmera integrada**: Captura facial direta no app

---

## 2. ESTRATÉGIA DE DESENVOLVIMENTO

### 2.1 Tecnologia Escolhida: **React Native**

**Justificativa:**
- ✅ Single codebase para iOS + Android (economia de 40-60% de tempo)
- ✅ Performance quase nativa com Hermes engine
- ✅ Acesso a APIs nativas (câmera, GPS, biometria)
- ✅ Comunidade ativa e bibliotecas maduras
- ✅ Hot reload para desenvolvimento ágil
- ✅ Expo para builds simplificados (opcional)

**Alternativas Consideradas:**
- ❌ **Flutter**: Curva de aprendizado maior (Dart)
- ❌ **Nativo puro**: Duplicação de código (Java/Kotlin + Swift)
- ❌ **PWA**: Limitações em biometria e notificações push

### 2.2 Abordagem de Desenvolvimento

**Opção Recomendada: React Native CLI** (sem Expo Go)

**Por quê?**
- Acesso total a módulos nativos customizados
- Integração com CompreFace via câmera nativa
- Controle sobre build e permissões
- Sem limitações do Expo managed workflow

**Quando usar Expo?**
- Prototipagem rápida (POC mobile)
- Se não precisar de módulos nativos customizados
- Para builds OTA (Over-The-Air updates)

---

## 3. FUNCIONALIDADES MVP MOBILE

### 3.1 Autenticação (v1.0)

| Funcionalidade | Descrição | Prioridade |
|----------------|-----------|------------|
| Login com CPF/senha | Autenticação básica | 🔴 CRÍTICO |
| Biometria local | Face ID / Touch ID / Fingerprint | 🔴 CRÍTICO |
| Lembrar-me | Sessão persistente | 🟡 IMPORTANTE |
| Recuperação de senha | Via e-mail | 🟡 IMPORTANTE |
| Logout | Encerrar sessão | 🔴 CRÍTICO |

### 3.2 Registro de Ponto (v1.0)

| Funcionalidade | Descrição | Prioridade |
|----------------|-----------|------------|
| Marcar ponto (entrada/saída) | Botão principal do app | 🔴 CRÍTICO |
| Captura de foto facial | Integração com câmera nativa | 🔴 CRÍTICO |
| Captura de GPS | Coordenadas automáticas | 🔴 CRÍTICO |
| Validação de geofencing | Checar se está no raio permitido | 🔴 CRÍTICO |
| Justificativa de ponto | Se fora do geofencing | 🟡 IMPORTANTE |
| Registro offline | Salvar localmente e sincronizar | 🟢 DESEJÁVEL |
| Feedback visual | Confirmação de registro | 🔴 CRÍTICO |

### 3.3 Consultas e Relatórios (v1.0)

| Funcionalidade | Descrição | Prioridade |
|----------------|-----------|------------|
| Espelho de ponto | Visualização mensal | 🔴 CRÍTICO |
| Histórico de registros | Últimos 30 dias | 🟡 IMPORTANTE |
| Exportar PDF | Download do espelho | 🟢 DESEJÁVEL |
| Banco de horas | Saldo acumulado | 🟡 IMPORTANTE |

### 3.4 Notificações (v1.1)

| Funcionalidade | Descrição | Prioridade |
|----------------|-----------|------------|
| Lembrete de entrada | Push às 08:00 (configurável) | 🟡 IMPORTANTE |
| Lembrete de saída | Push às 18:00 (configurável) | 🟡 IMPORTANTE |
| Inconsistências | Avisos de falta de registro | 🟢 DESEJÁVEL |

### 3.5 Configurações (v1.0)

| Funcionalidade | Descrição | Prioridade |
|----------------|-----------|------------|
| Alterar senha | Segurança | 🟡 IMPORTANTE |
| Habilitar/desabilitar biometria | Preferências | 🟡 IMPORTANTE |
| Configurar notificações | Horários personalizados | 🟢 DESEJÁVEL |

---

## 4. ARQUITETURA TÉCNICA

### 4.1 Stack Mobile

```
Framework:        React Native 0.73+
Linguagem:        TypeScript
Navegação:        React Navigation 6.x
Estado Global:    Redux Toolkit + RTK Query
Persistência:     AsyncStorage + SQLite (offline)
API Client:       Axios + interceptors
Biometria:        react-native-biometrics
Geolocalização:   @react-native-community/geolocation
Câmera:           react-native-vision-camera
Notificações:     @react-native-firebase/messaging
Maps:             react-native-maps
Geofencing:       react-native-geolocation-service
Forms:            React Hook Form + Zod (validação)
UI/UX:            React Native Paper (Material Design)
```

### 4.2 Estrutura de Pastas

```
mobile-app/
├── android/                 # Código nativo Android
├── ios/                     # Código nativo iOS
├── src/
│   ├── @types/             # TypeScript definitions
│   ├── assets/             # Imagens, ícones, fontes
│   ├── components/         # Componentes reutilizáveis
│   │   ├── common/         # Botões, inputs, cards
│   │   ├── forms/          # Formulários específicos
│   │   └── layouts/        # Headers, footers
│   ├── features/           # Features modulares
│   │   ├── auth/           # Autenticação
│   │   │   ├── screens/
│   │   │   ├── components/
│   │   │   ├── hooks/
│   │   │   └── api/
│   │   ├── clockin/        # Registro de ponto
│   │   ├── reports/        # Consultas e relatórios
│   │   └── settings/       # Configurações
│   ├── navigation/         # Rotas do app
│   ├── services/           # APIs, storage, geolocation
│   │   ├── api/            # Chamadas ao backend
│   │   ├── storage/        # AsyncStorage helpers
│   │   ├── geolocation/    # GPS e geofencing
│   │   ├── biometrics/     # Face ID / Touch ID
│   │   └── camera/         # Captura de imagens
│   ├── store/              # Redux store
│   │   ├── slices/         # Redux slices
│   │   └── api/            # RTK Query endpoints
│   ├── utils/              # Helpers e utilitários
│   │   ├── validators/     # Validações
│   │   ├── formatters/     # Formatação de dados
│   │   └── constants/      # Constantes
│   ├── hooks/              # Custom hooks
│   ├── theme/              # Cores, tipografia, espaçamento
│   └── App.tsx             # Entry point
├── .env                    # Variáveis de ambiente
├── package.json
└── tsconfig.json
```

### 4.3 Fluxo de Dados

```
[App Mobile] ─────┐
                  │
                  ├─> [RTK Query] ──> [API REST Backend] ──> [MySQL]
                  │                        ▲
                  ├─> [AsyncStorage] ─────┤ (sincronização)
                  │                        │
                  ├─> [SQLite] ────────────┘ (offline)
                  │
                  ├─> [Firebase] ──> [Push Notifications]
                  │
                  └─> [CompreFace API] ──> [Validação Facial]
```

---

## 5. FLUXOS DE USO MOBILE

### 5.1 Fluxo de Login

```
┌─────────────────────────────────────────────────────────┐
│                    TELA DE LOGIN                        │
│  ┌─────────────────────────────────────────────────┐  │
│  │  Digite CPF: [ 123.456.789-00 ]                │  │
│  │  Digite Senha: [ *********** ]                 │  │
│  │  □ Lembrar-me                                   │  │
│  │  [  ENTRAR  ]                                   │  │
│  │  Esqueci minha senha                            │  │
│  └─────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
                   API: POST /api/auth/login
                   { cpf, senha }
                          │
              ┌───────────┴───────────┐
              │                       │
              ▼ (200 OK)              ▼ (401)
       ┌──────────────┐         ┌─────────────┐
       │ Salvar Token │         │ Exibir Erro │
       │ AsyncStorage │         │ "CPF/Senha  │
       └──────┬───────┘         │  inválidos" │
              │                 └─────────────┘
              ▼
    Biometria habilitada?
              │
        ┌─────┴─────┐
        │           │
        ▼ Sim       ▼ Não
  ┌───────────┐  ┌──────────┐
  │ Registrar │  │   Ir p/  │
  │ Biometria │  │   Home   │
  └─────┬─────┘  └──────────┘
        │
        ▼
  ┌──────────┐
  │   Home   │
  └──────────┘
```

### 5.2 Fluxo de Registro de Ponto

```
┌─────────────────────────────────────────────────────────┐
│                  TELA HOME (Dashboard)                  │
│  ┌─────────────────────────────────────────────────┐  │
│  │  Olá, João Silva                                │  │
│  │  Último registro: Entrada às 08:00              │  │
│  │                                                  │  │
│  │       ┌─────────────────────────────┐           │  │
│  │       │  🕐  MARCAR PONTO          │           │  │
│  │       │     (Botão Principal)       │           │  │
│  │       └─────────────────────────────┘           │  │
│  │                                                  │  │
│  │  📊 Espelho de Ponto    ⏱️ Banco de Horas     │  │
│  └─────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼ (Clique no botão)
         ┌────────────────────────────────┐
         │  1. Solicitar Permissões       │
         │     - Câmera                   │
         │     - Localização              │
         └────────┬───────────────────────┘
                  ▼
         ┌────────────────────────────────┐
         │  2. Capturar Localização GPS   │
         │     navigator.geolocation...   │
         └────────┬───────────────────────┘
                  ▼
         ┌────────────────────────────────┐
         │  3. Validar Geofencing         │
         │     isWithinRadius(coords)     │
         └────────┬───────────────────────┘
                  │
        ┌─────────┴─────────┐
        │                   │
        ▼ Dentro            ▼ Fora do raio
┌───────────────┐    ┌──────────────────┐
│ Prosseguir p/ │    │ Exibir alerta:   │
│ Biometria     │    │ "Você está fora  │
└───────┬───────┘    │  da empresa.     │
        │            │  Deseja registrar│
        │            │  com justificativa?│
        │            └────────┬──────────┘
        │                     │
        │                     ▼ (Sim)
        │            ┌───────────────────┐
        │            │ Exibir campo de   │
        │            │ justificativa     │
        │            └────────┬──────────┘
        │                     │
        └─────────────────────┘
                  │
                  ▼
         ┌────────────────────────────────┐
         │  4. Captura Biometria Facial   │
         │     Abrir Câmera Nativa        │
         │     ┌────────────────┐         │
         │     │   📸 FOTO      │         │
         │     │   [Capturar]   │         │
         │     └────────────────┘         │
         └────────┬───────────────────────┘
                  ▼
         ┌────────────────────────────────┐
         │  5. Processar no Backend       │
         │     POST /api/registros        │
         │     {                          │
         │       timestamp,               │
         │       latitude,                │
         │       longitude,               │
         │       foto_base64,             │
         │       justificativa?           │
         │     }                          │
         └────────┬───────────────────────┘
                  ▼
         ┌────────────────────────────────┐
         │  Backend:                      │
         │  1. Validar foto CompreFace    │
         │  2. Validar GPS Haversine      │
         │  3. Salvar registro no MySQL   │
         └────────┬───────────────────────┘
                  │
        ┌─────────┴─────────┐
        │                   │
        ▼ Sucesso           ▼ Erro
┌───────────────┐    ┌──────────────────┐
│ Exibir:       │    │ Exibir erro:     │
│ ✅ "Ponto     │    │ ❌ "Rosto não    │
│  registrado   │    │  reconhecido" ou │
│  com sucesso!"│    │  "GPS inválido"  │
│               │    └──────────────────┘
│ Detalhes:     │
│ - Horário     │
│ - Local       │
│ - Tipo        │
└───────────────┘
```

### 5.3 Fluxo Offline

```
┌─────────────────────────────────────────┐
│  Usuário clica "Marcar Ponto"           │
└─────────┬───────────────────────────────┘
          ▼
   [ Verificar Conectividade ]
          │
    ┌─────┴─────┐
    │           │
    ▼ Online    ▼ Offline
┌──────────┐  ┌────────────────────────┐
│ Enviar   │  │ Salvar em SQLite Local │
│ direto   │  │ + Marcar como pending  │
│ p/ API   │  │                        │
└──────────┘  └────────┬───────────────┘
                       ▼
              ┌────────────────────────┐
              │ Exibir notificação:    │
              │ "📱 Sem internet.      │
              │  Registro salvo        │
              │  localmente e será     │
              │  enviado quando        │
              │  conectar."            │
              └────────┬───────────────┘
                       │
                       ▼
              [ Background Service ]
                       │
                       ▼ (Conectividade restaurada)
              ┌────────────────────────┐
              │ Sincronizar pendentes: │
              │ 1. Buscar SQLite       │
              │ 2. POST batch para API │
              │ 3. Limpar local        │
              └────────┬───────────────┘
                       │
                 ┌─────┴─────┐
                 │           │
                 ▼ Sucesso   ▼ Falha
         ┌─────────────┐  ┌──────────────┐
         │ ✅ "3 pontos│  │ ⚠️ Manter no │
         │   enviados  │  │  SQLite e    │
         │   com       │  │  tentar depois│
         │   sucesso!" │  └──────────────┘
         └─────────────┘
```

---

## 6. INTEGRAÇÃO COM BACKEND

### 6.1 Endpoints Necessários (Backend)

| Método | Endpoint | Descrição | Payload |
|--------|----------|-----------|---------|
| POST | `/api/auth/login` | Login | `{ cpf, senha }` |
| POST | `/api/auth/logout` | Logout | `{ token }` |
| POST | `/api/auth/refresh` | Renovar token | `{ refresh_token }` |
| POST | `/api/auth/reset-password` | Solicitar reset | `{ email }` |
| POST | `/api/registros` | Criar registro de ponto | `{ timestamp, lat, lng, foto_base64, justificativa? }` |
| GET | `/api/registros` | Listar registros | Query: `?data_inicio&data_fim` |
| GET | `/api/registros/espelho` | Espelho de ponto | Query: `?mes&ano` |
| GET | `/api/funcionarios/me` | Dados do funcionário | - |
| PUT | `/api/funcionarios/senha` | Alterar senha | `{ senha_atual, nova_senha }` |
| GET | `/api/empresas/geofencing` | Dados de geofencing | - |

### 6.2 Modelo de Dados (Request/Response)

#### POST /api/registros (Criar Ponto)

**Request:**
```json
{
  "timestamp": "2025-11-16T08:00:15.123Z",
  "latitude": -23.561414,
  "longitude": -46.656179,
  "foto": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
  "tipo": "entrada",
  "justificativa": "Reunião externa"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 12345,
    "funcionario_id": 10,
    "timestamp": "2025-11-16T08:00:15",
    "tipo": "entrada",
    "latitude": -23.561414,
    "longitude": -46.656179,
    "dentro_geofencing": false,
    "distancia_metros": 150.25,
    "facial_similarity": 0.95,
    "facial_reconhecido": true,
    "justificativa": "Reunião externa",
    "status": "aprovado"
  }
}
```

**Response (400 - Erro):**
```json
{
  "success": false,
  "error": {
    "code": "FACIAL_NOT_RECOGNIZED",
    "message": "Rosto não reconhecido. Tente novamente.",
    "details": {
      "similarity": 0.65,
      "threshold": 0.75
    }
  }
}
```

#### GET /api/registros/espelho (Espelho de Ponto)

**Request:**
```
GET /api/registros/espelho?mes=11&ano=2025
```

**Response:**
```json
{
  "success": true,
  "data": {
    "funcionario": {
      "id": 10,
      "nome": "João Silva",
      "cpf": "123.456.789-00",
      "cargo": "Desenvolvedor"
    },
    "periodo": {
      "mes": 11,
      "ano": 2025,
      "dias_uteis": 22,
      "dias_trabalhados": 18
    },
    "banco_horas": {
      "saldo": "+02:30:00",
      "extras": "05:15:00",
      "descontos": "-02:45:00"
    },
    "registros": [
      {
        "data": "2025-11-01",
        "dia_semana": "Segunda",
        "registros": [
          {
            "id": 101,
            "timestamp": "2025-11-01T08:00:15",
            "tipo": "entrada",
            "local": "Matriz - São Paulo",
            "status": "aprovado"
          },
          {
            "id": 102,
            "timestamp": "2025-11-01T12:00:45",
            "tipo": "saida_intervalo",
            "local": "Matriz - São Paulo",
            "status": "aprovado"
          },
          {
            "id": 103,
            "timestamp": "2025-11-01T13:00:30",
            "tipo": "entrada_intervalo",
            "local": "Matriz - São Paulo",
            "status": "aprovado"
          },
          {
            "id": 104,
            "timestamp": "2025-11-01T18:05:20",
            "tipo": "saida",
            "local": "Matriz - São Paulo",
            "status": "aprovado"
          }
        ],
        "total_horas": "08:04:50",
        "inconsistencias": []
      },
      {
        "data": "2025-11-02",
        "dia_semana": "Terça",
        "registros": [
          {
            "id": 105,
            "timestamp": "2025-11-02T07:55:10",
            "tipo": "entrada",
            "local": "Remoto - Campinas",
            "status": "pendente_aprovacao",
            "justificativa": "Home office"
          }
        ],
        "total_horas": "00:00:00",
        "inconsistencias": ["Falta registro de saída"]
      }
    ]
  }
}
```

### 6.3 Autenticação JWT

```typescript
// services/api/apiClient.ts
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

const apiClient = axios.create({
  baseURL: 'https://api.pontoeletronico.com.br',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Interceptor para adicionar token
apiClient.interceptors.request.use(
  async (config) => {
    const token = await AsyncStorage.getItem('@auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Interceptor para refresh token
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const refreshToken = await AsyncStorage.getItem('@refresh_token');
        const { data } = await axios.post('/api/auth/refresh', {
          refresh_token: refreshToken,
        });

        await AsyncStorage.setItem('@auth_token', data.access_token);
        apiClient.defaults.headers.Authorization = `Bearer ${data.access_token}`;

        return apiClient(originalRequest);
      } catch (refreshError) {
        // Logout e redirecionar para login
        await AsyncStorage.multiRemove(['@auth_token', '@refresh_token']);
        // NavigationService.navigate('Login');
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);

export default apiClient;
```

---

## 7. SEGURANÇA E AUTENTICAÇÃO

### 7.1 Fluxo de Segurança

```
┌─────────────────────────────────────────────────────────┐
│  CAMADAS DE SEGURANÇA NO APP MOBILE                     │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  1. AUTENTICAÇÃO                                        │
│     ├─ JWT Token (Access Token: 15min)                 │
│     ├─ Refresh Token (7 dias)                          │
│     └─ Biometria Local (Face ID / Touch ID)            │
│                                                          │
│  2. COMUNICAÇÃO                                         │
│     ├─ HTTPS/TLS 1.3                                   │
│     ├─ Certificate Pinning                             │
│     └─ Request Signing (HMAC)                          │
│                                                          │
│  3. ARMAZENAMENTO                                       │
│     ├─ AsyncStorage (dados não sensíveis)              │
│     ├─ Keychain/Keystore (tokens)                      │
│     └─ SQLite criptografado (SQLCipher)                │
│                                                          │
│  4. VALIDAÇÃO DE DADOS                                  │
│     ├─ Zod schemas (client-side)                       │
│     ├─ Validação server-side                           │
│     └─ Sanitização de inputs                           │
│                                                          │
│  5. PRIVACIDADE                                         │
│     ├─ Consentimento LGPD                              │
│     ├─ Criptografia de fotos                           │
│     └─ Anonimização de GPS (hash)                      │
│                                                          │
│  6. ANTI-FRAUDE                                         │
│     ├─ Device fingerprinting                           │
│     ├─ Liveness detection (foto ao vivo)               │
│     └─ Rate limiting (3 tentativas)                    │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### 7.2 Implementação de Certificate Pinning

```typescript
// services/api/certificatePinning.ts
import { Platform } from 'react-native';

export const certificatePins = {
  'api.pontoeletronico.com.br': {
    includeSubdomains: true,
    pins: [
      // SHA-256 hash do certificado SSL
      'sha256/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
      // Backup pin
      'sha256/BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB=',
    ],
  },
};

// React Native SSL Pinning
// https://github.com/MaxToyberman/react-native-ssl-pinning
```

### 7.3 Armazenamento Seguro

```typescript
// services/storage/secureStorage.ts
import * as Keychain from 'react-native-keychain';
import AsyncStorage from '@react-native-async-storage/async-storage';

class SecureStorage {
  // Para dados sensíveis (tokens, senhas)
  async setSecure(key: string, value: string): Promise<void> {
    await Keychain.setGenericPassword(key, value, {
      service: key,
      accessible: Keychain.ACCESSIBLE.WHEN_UNLOCKED,
    });
  }

  async getSecure(key: string): Promise<string | null> {
    const credentials = await Keychain.getGenericPassword({ service: key });
    return credentials ? credentials.password : null;
  }

  async removeSecure(key: string): Promise<void> {
    await Keychain.resetGenericPassword({ service: key });
  }

  // Para dados não sensíveis (preferências, cache)
  async set(key: string, value: any): Promise<void> {
    await AsyncStorage.setItem(key, JSON.stringify(value));
  }

  async get(key: string): Promise<any> {
    const value = await AsyncStorage.getItem(key);
    return value ? JSON.parse(value) : null;
  }

  async remove(key: string): Promise<void> {
    await AsyncStorage.removeItem(key);
  }
}

export default new SecureStorage();
```

---

## 8. TESTES E VALIDAÇÃO MOBILE

### 8.1 Estratégia de Testes

```
┌─────────────────────────────────────────────────────────┐
│  PIRÂMIDE DE TESTES MOBILE                              │
│                                                          │
│                    ┌─────────┐                          │
│                    │   E2E   │ 10%                      │
│                    │ Detox   │                          │
│                    └─────────┘                          │
│                ┌───────────────┐                        │
│                │  Integration  │ 30%                    │
│                │  React Native │                        │
│                │  Testing Lib  │                        │
│                └───────────────┘                        │
│            ┌─────────────────────┐                      │
│            │      Unit Tests      │ 60%                 │
│            │  Jest + TypeScript  │                      │
│            └─────────────────────┘                      │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### 8.2 Ferramentas de Teste

| Tipo | Ferramenta | Uso |
|------|------------|-----|
| Unit | Jest | Lógica de negócio, utils, hooks |
| Integration | React Native Testing Library | Componentes, navegação |
| E2E | Detox | Fluxos completos (login, registro) |
| Performance | Flipper | Profiling, network, logs |
| Beta Testing | TestFlight (iOS) + Google Play Beta (Android) | Testes com usuários reais |

### 8.3 Casos de Teste Críticos

#### Teste 1: Registro de Ponto com Sucesso
```typescript
// __tests__/features/clockin/ClockinFlow.test.tsx
import { render, waitFor } from '@testing-library/react-native';
import { ClockinScreen } from '@/features/clockin/screens/ClockinScreen';

describe('Clockin Flow', () => {
  it('should register clockin successfully', async () => {
    const { getByText, getByTestId } = render(<ClockinScreen />);

    // Mockar permissões
    jest.spyOn(PermissionsAndroid, 'request').mockResolvedValue('granted');

    // Mockar GPS
    jest.spyOn(Geolocation, 'getCurrentPosition').mockImplementation((success) => {
      success({
        coords: {
          latitude: -23.561414,
          longitude: -46.656179,
          accuracy: 10,
        },
      });
    });

    // Mockar câmera
    jest.spyOn(Camera, 'takePicture').mockResolvedValue({
      path: '/path/to/photo.jpg',
    });

    // Mockar API
    mockApiResponse('/api/registros', {
      success: true,
      data: { id: 123, timestamp: '2025-11-16T08:00:00' },
    });

    // Clicar no botão
    fireEvent.press(getByTestId('clockin-button'));

    // Aguardar sucesso
    await waitFor(() => {
      expect(getByText('✅ Ponto registrado com sucesso!')).toBeTruthy();
    });
  });
});
```

#### Teste 2: Offline Mode
```typescript
describe('Offline Mode', () => {
  it('should save clockin locally when offline', async () => {
    // Simular offline
    NetInfo.fetch.mockResolvedValue({ isConnected: false });

    // Registrar ponto
    await clockinService.register({
      timestamp: new Date(),
      latitude: -23.5,
      longitude: -46.6,
      photo: 'base64...',
    });

    // Verificar SQLite
    const pendingRecords = await database.getPendingRecords();
    expect(pendingRecords).toHaveLength(1);
  });

  it('should sync pending records when online', async () => {
    // Simular volta do online
    NetInfo.fetch.mockResolvedValue({ isConnected: true });

    // Disparar sincronização
    await syncService.syncPendingRecords();

    // Verificar chamada API
    expect(apiClient.post).toHaveBeenCalledWith('/api/registros/batch', ...);

    // Verificar limpeza do SQLite
    const pendingRecords = await database.getPendingRecords();
    expect(pendingRecords).toHaveLength(0);
  });
});
```

### 8.4 Testes em Dispositivos Reais

**Matriz de Testes:**

| Dispositivo | OS | Prioridade | Testes |
|-------------|-----|------------|--------|
| iPhone 12 Pro | iOS 17 | 🔴 Alta | Completo |
| iPhone SE 2020 | iOS 15 | 🟡 Média | Funcional |
| Samsung Galaxy S21 | Android 13 | 🔴 Alta | Completo |
| Xiaomi Redmi Note 10 | Android 11 | 🟡 Média | Funcional |
| Motorola Moto G8 | Android 10 | 🟢 Baixa | Smoke test |

**Checklist de Testes Manuais:**

- [ ] Permissões de câmera e GPS
- [ ] Captura de foto em diferentes iluminações
- [ ] GPS indoor vs outdoor
- [ ] Modo offline e sincronização
- [ ] Notificações push
- [ ] Biometria (Face ID / Touch ID / Fingerprint)
- [ ] Orientação (portrait/landscape)
- [ ] Diferentes tamanhos de tela
- [ ] Bateria e consumo de recursos
- [ ] Performance (60fps em animações)

---

## 9. DEPLOY E DISTRIBUIÇÃO

### 9.1 Processo de Build

#### iOS (Apple App Store)

```bash
# 1. Configurar certificados e provisioning profiles
#    - Apple Developer Account ($99/ano)
#    - Certificado de distribuição
#    - Provisioning profile (App Store)

# 2. Build de produção
cd ios
pod install
cd ..
npx react-native run-ios --configuration Release

# 3. Archive e upload
xcodebuild -workspace ios/PontoEletronico.xcworkspace \
           -scheme PontoEletronico \
           -configuration Release \
           -archivePath ios/build/PontoEletronico.xcarchive \
           archive

# 4. Exportar IPA
xcodebuild -exportArchive \
           -archivePath ios/build/PontoEletronico.xcarchive \
           -exportPath ios/build \
           -exportOptionsPlist ios/ExportOptions.plist

# 5. Upload para App Store Connect
xcrun altool --upload-app \
             --file ios/build/PontoEletronico.ipa \
             --username "seu-email@example.com" \
             --password "app-specific-password"
```

#### Android (Google Play Store)

```bash
# 1. Gerar signing key
keytool -genkeypair -v \
        -keystore ponto-eletronico-release.keystore \
        -alias ponto-eletronico \
        -keyalg RSA -keysize 2048 -validity 10000

# 2. Configurar gradle (android/gradle.properties)
MYAPP_UPLOAD_STORE_FILE=ponto-eletronico-release.keystore
MYAPP_UPLOAD_KEY_ALIAS=ponto-eletronico
MYAPP_UPLOAD_STORE_PASSWORD=****
MYAPP_UPLOAD_KEY_PASSWORD=****

# 3. Build de produção
cd android
./gradlew bundleRelease

# 4. AAB gerado em:
# android/app/build/outputs/bundle/release/app-release.aab

# 5. Upload manual para Google Play Console
# Ou via Fastlane:
fastlane android deploy
```

### 9.2 CI/CD com GitHub Actions

```yaml
# .github/workflows/build-and-deploy.yml
name: Build and Deploy Mobile App

on:
  push:
    branches: [main]
    tags:
      - 'v*'

jobs:
  build-ios:
    runs-on: macos-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Install dependencies
        run: npm ci

      - name: Install pods
        run: cd ios && pod install

      - name: Build iOS
        run: npx react-native run-ios --configuration Release

      - name: Run tests
        run: npm test

      - name: Upload to TestFlight
        if: startsWith(github.ref, 'refs/tags/v')
        run: fastlane ios beta

  build-android:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Setup JDK
        uses: actions/setup-java@v3
        with:
          java-version: '11'
          distribution: 'temurin'

      - name: Install dependencies
        run: npm ci

      - name: Build Android
        run: cd android && ./gradlew bundleRelease

      - name: Run tests
        run: npm test

      - name: Upload to Play Store
        if: startsWith(github.ref, 'refs/tags/v')
        run: fastlane android deploy
```

### 9.3 Versionamento Semântico

```
Padrão: MAJOR.MINOR.PATCH (Build Number)

Exemplo:
- 1.0.0 (1)   → Primeira versão pública
- 1.1.0 (2)   → Nova funcionalidade (notificações)
- 1.1.1 (3)   → Correção de bug crítico
- 2.0.0 (4)   → Breaking change (novo fluxo de auth)

iOS:    CFBundleShortVersionString (1.0.0) + CFBundleVersion (1)
Android: versionName (1.0.0) + versionCode (1)
```

### 9.4 Estratégia de Distribuição

**Fases de Rollout:**

1. **Alfa (Semana 1-2)**
   - Internal Testing (TestFlight + Internal Testing Track)
   - Equipe interna (5-10 pessoas)
   - Objetivo: Validar build e funcionalidades básicas

2. **Beta Fechado (Semana 3-4)**
   - TestFlight (100 testadores) + Closed Beta (Google Play)
   - Clientes piloto (2-3 empresas)
   - Objetivo: Validar em ambiente real

3. **Beta Aberto (Semana 5-6)**
   - Open Beta (Google Play)
   - 500-1000 testadores voluntários
   - Objetivo: Stress test e feedback em escala

4. **Produção Gradual (Semana 7+)**
   - 10% → 25% → 50% → 100% (phased rollout)
   - Monitorar crashlytics e reviews
   - Rollback se crash rate > 2%

---

## 10. TIMELINE E FASES

### 10.1 Cronograma de Desenvolvimento

```
┌──────────────────────────────────────────────────────────────┐
│  FASE MOBILE: 12 SEMANAS (3 MESES)                           │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  SEMANA 1-2: Setup e Estrutura Base                         │
│  ├─ Inicializar projeto React Native                        │
│  ├─ Configurar navegação e estado global                    │
│  ├─ Estrutura de pastas e arquitetura                       │
│  └─ Telas mockadas (UI básico)                              │
│                                                               │
│  SEMANA 3-4: Autenticação e Integração Backend              │
│  ├─ Telas de login e recuperação de senha                   │
│  ├─ Integração com API de autenticação                      │
│  ├─ Biometria local (Face ID / Touch ID)                    │
│  └─ Testes de autenticação                                  │
│                                                               │
│  SEMANA 5-7: Registro de Ponto (Core)                       │
│  ├─ Integração com câmera nativa                            │
│  ├─ Captura de GPS e geofencing                             │
│  ├─ Validação facial (CompreFace)                           │
│  ├─ Fluxo completo de registro                              │
│  └─ Tratamento de erros e edge cases                        │
│                                                               │
│  SEMANA 8: Modo Offline e Sincronização                     │
│  ├─ SQLite local para registros pendentes                   │
│  ├─ Background service para sync automático                 │
│  ├─ Indicadores de conectividade                            │
│  └─ Testes de sincronização                                 │
│                                                               │
│  SEMANA 9: Consultas e Relatórios                           │
│  ├─ Tela de espelho de ponto                                │
│  ├─ Histórico de registros                                  │
│  ├─ Exportação de PDF                                       │
│  └─ Banco de horas                                          │
│                                                               │
│  SEMANA 10: Notificações e Configurações                    │
│  ├─ Push notifications (Firebase)                           │
│  ├─ Lembretes de registro                                   │
│  ├─ Tela de configurações                                   │
│  └─ Gerenciamento de preferências                           │
│                                                               │
│  SEMANA 11: Testes e Refinamento                            │
│  ├─ Testes unitários e de integração                        │
│  ├─ Testes em dispositivos reais                            │
│  ├─ Correção de bugs                                        │
│  └─ Melhorias de UX                                         │
│                                                               │
│  SEMANA 12: Deploy e Distribuição                           │
│  ├─ Build de produção (iOS + Android)                       │
│  ├─ Submissão para App Store e Google Play                  │
│  ├─ Beta testing com clientes piloto                        │
│  └─ Documentação e treinamento                              │
│                                                               │
└──────────────────────────────────────────────────────────────┘

Total: 12 semanas (3 meses)
Buffer: +2 semanas para imprevistos
```

### 10.2 Dependências e Pré-requisitos

**Antes de Iniciar o Desenvolvimento Mobile:**

✅ Backend API deve ter os endpoints listados na Seção 6.1
✅ CompreFace deve estar configurado e acessível
✅ Firebase Project criado (para push notifications)
✅ Apple Developer Account ($99/ano)
✅ Google Play Developer Account ($25 taxa única)
✅ Designs de UI/UX finalizados (Figma/Adobe XD)

**Desenvolvimento Paralelo:**

- Mobile pode iniciar após **FASE 3 do backend** (API REST básica)
- Não precisa esperar dashboard web completo
- Backend e mobile podem evoluir em paralelo

---

## 11. RISCOS ESPECÍFICOS MOBILE

### 11.1 Matriz de Riscos Mobile

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| **Rejeição na App Store (iOS)** | Média (30%) | Alto | ✅ Revisar Apple Guidelines antes<br>✅ Solicitar consentimento LGPD claro<br>✅ Testar em TestFlight antes de submeter |
| **Fragmentação Android** | Alta (60%) | Médio | ✅ Testar em top 5 dispositivos Android<br>✅ Usar API Level 26+ (Android 8.0+)<br>✅ Implementar fallbacks para APIs antigas |
| **Performance em câmera** | Média (40%) | Alto | ✅ Usar react-native-vision-camera (otimizado)<br>✅ Reduzir resolução de captura (1280x720)<br>✅ Processar foto em background thread |
| **GPS impreciso indoor** | Alta (70%) | Médio | ✅ WiFi positioning como fallback<br>✅ Permitir justificativa manual<br>✅ Aumentar raio de tolerância |
| **Bateria alta consumo** | Média (40%) | Médio | ✅ Background service otimizado<br>✅ GPS apenas ao registrar (não continuous)<br>✅ Debounce em chamadas de API |
| **Armazenamento cheio** | Baixa (20%) | Baixo | ✅ Limpar fotos após upload<br>✅ Limpar registros sincronizados (> 30 dias)<br>✅ Alertar usuário se < 100MB livre |
| **Crash em devices antigos** | Média (30%) | Alto | ✅ Minimum OS: Android 8.0 / iOS 13.0<br>✅ Crashlytics para monitorar<br>✅ Testes em dispositivos low-end |
| **Sincronização conflitante** | Baixa (15%) | Médio | ✅ Timestamp + UUID único por registro<br>✅ Server-side deduplicação<br>✅ Retry com exponential backoff |

### 11.2 Plano de Contingência Mobile

**Cenário 1: Rejeição na App Store**
- **Sintoma**: Apple rejeita app por questões de privacidade
- **Ação Imediata**:
  - Revisar Privacy Policy e termos LGPD
  - Adicionar consentimento explícito para foto/GPS
  - Re-submeter em 48h
- **Alternativa**: Distribuição via Enterprise (se cliente tem conta)

**Cenário 2: Performance Inaceitável (< 30 FPS)**
- **Sintoma**: Animações travando, câmera lenta
- **Ação Imediata**:
  - Profiling com Flipper
  - Reduzir resolução de câmera
  - Lazy load de imagens
- **Alternativa**: Versão Lite sem animações complexas

**Cenário 3: GPS Não Funciona em 80% dos Casos**
- **Sintoma**: Precisão > 200m constantemente
- **Ação Imediata**:
  - Habilitar WiFi positioning (react-native-geolocation-service)
  - Permitir registro manual com justificativa
- **Alternativa**: Remover geofencing obrigatório (v1.1)

---

## 12. PRÓXIMOS PASSOS

### 12.1 Checklist Pré-Desenvolvimento

- [ ] **Aprovação de stakeholders** neste plano mobile
- [ ] **Backend API** com endpoints da Seção 6.1 prontos
- [ ] **Designs UI/UX** finalizados no Figma
- [ ] **Contas de desenvolvedor**:
  - [ ] Apple Developer ($99/ano)
  - [ ] Google Play Developer ($25)
- [ ] **Firebase Project** criado
- [ ] **Ambiente de dev** configurado:
  - [ ] Node.js 18+
  - [ ] Xcode 15+ (macOS)
  - [ ] Android Studio
  - [ ] React Native CLI

### 12.2 Primeira Sprint (Semana 1)

```bash
# Dia 1: Inicializar projeto
npx react-native@latest init PontoEletronicoMobile --template typescript
cd PontoEletronicoMobile

# Dia 2: Instalar dependências core
npm install @react-navigation/native @react-navigation/stack
npm install redux @reduxjs/toolkit react-redux
npm install axios @react-native-async-storage/async-storage

# Dia 3: Configurar estrutura de pastas
mkdir -p src/{components,features,services,store,utils,hooks,theme}

# Dia 4-5: Implementar navegação básica e telas mockadas
```

### 12.3 Métricas de Sucesso Mobile

**KPIs do App (v1.0):**

| Métrica | Meta | Medição |
|---------|------|---------|
| Taxa de Adoção | > 80% dos funcionários | Analytics |
| Crash-free Rate | > 99% | Crashlytics |
| Tempo Médio de Registro | < 15 segundos | Custom event |
| Taxa de Sucesso (1ª tentativa) | > 85% | Backend logs |
| App Store Rating | > 4.0 estrelas | Reviews |
| Sincronização Offline | 100% dos registros | Backend validation |
| Bateria Consumida (8h) | < 5% | Android Battery Historian |

---

## 13. CONCLUSÃO E RECOMENDAÇÕES

### 13.1 Decisão: GO / NO-GO

**Recomendação: 🟢 GO**

**Justificativas:**
1. ✅ Viabilidade técnica comprovada (React Native maduro)
2. ✅ Custo-benefício excelente (single codebase iOS+Android)
3. ✅ Integração clara com backend existente
4. ✅ Timeline realista (12 semanas + 2 buffer)
5. ✅ Riscos mapeados e mitigados

**Condições:**
- ⚠️ Backend API deve estar funcional antes da Semana 5
- ⚠️ Orçamento para contas de desenvolvedor ($124 total)
- ⚠️ Equipe com conhecimento de React/TypeScript

### 13.2 Roadmap Futuro (v2.0+)

**v1.1 (Q1 2026) - Melhorias:**
- [ ] Dark mode
- [ ] Widget iOS/Android (status de ponto)
- [ ] Suporte a tablets
- [ ] Idiomas: Inglês e Espanhol

**v2.0 (Q2 2026) - Avançado:**
- [ ] Apple Watch / Wear OS app
- [ ] Liveness detection avançado (anti-spoofing)
- [ ] QR Code para registro em totens
- [ ] Integração com Siri/Google Assistant

**v3.0 (Q3 2026) - Enterprise:**
- [ ] Modo offline total (30 dias)
- [ ] Múltiplas empresas (holding)
- [ ] Assinatura eletrônica ICP-Brasil
- [ ] Exportação para Sefip/eSocial

---

## 14. ANEXOS

### 14.1 Referências Técnicas

- [React Native Docs](https://reactnative.dev/docs/getting-started)
- [React Navigation](https://reactnavigation.org/docs/getting-started)
- [Redux Toolkit](https://redux-toolkit.js.org/)
- [React Native Vision Camera](https://github.com/mrousavy/react-native-vision-camera)
- [Detox E2E Testing](https://wix.github.io/Detox/)

### 14.2 Contatos

- **Tech Lead Mobile**: [Nome] ([email])
- **Backend API**: [Nome] ([email])
- **UI/UX Designer**: [Nome] ([email])
- **Product Owner**: [Nome] ([email])

---

**Documento criado em:** 2025-11-16
**Versão:** R0 (Revisão Inicial)
**Próxima revisão:** Após aprovação de stakeholders
**Status:** 🟡 Aguardando Aprovação

---

**Assinaturas:**

[ ] Tech Lead - Aprovado
[ ] Product Owner - Aprovado
[ ] Stakeholder - Aprovado

---

_Este documento é complementar ao "plano_de_elaboração" principal e deve ser lido em conjunto com a documentação do backend web._
