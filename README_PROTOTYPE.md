# Protótipo - Interface de Registro de Ponto

## Sistema de Ponto Eletrônico - Fase 0

Protótipo HTML puro da interface de registro de ponto eletrônico.

## 🚀 Como Usar

### Abra o arquivo no navegador:

```bash
# Opção 1: Abrir diretamente
firefox prototype_punch.html

# Opção 2: Com servidor HTTP simples
python3 -m http.server 8000
# Acesse: http://localhost:8000/prototype_punch.html
```

## ✨ Funcionalidades

### 1. Relógio em Tempo Real
- Hora atualizada a cada segundo
- Data por extenso em português
- Indicador de status online/offline

### 2. Três Métodos de Registro

#### Código (8 dígitos)
- Input formatado: XXXX-XXXX
- Validação em tempo real
- Simulação de autenticação

#### QR Code
- Interface preparada para scanner
- Acesso à câmera traseira
- *(Leitura de QR não implementada no protótipo)*

#### Reconhecimento Facial
- Acesso à câmera frontal
- Botão de captura de foto
- Simulação de reconhecimento
- Taxa de similaridade mockada (85-99%)

### 3. Botão de Registro
- Design circular de 250x250px
- Animações hover/active
- Feedback visual imediato

### 4. Histórico de Marcações
- Últimas 5 marcações
- Cores por tipo:
  - 🟢 Entrada (verde)
  - 🔴 Saída (vermelho)
  - 🟡 Início Intervalo (amarelo)
  - 🔵 Fim Intervalo (azul)
- Indicador de sincronização pendente

### 5. Feedback Visual
- Mensagens de sucesso (verde)
- Mensagens de erro (vermelho)
- Auto-dismiss após 5 segundos

### 6. Funcionamento Offline
- Dados salvos em localStorage
- Detecção de status de rede
- Badge online/offline
- Fila de sincronização (simulada)

## 🎨 Design

- **Framework CSS**: Bootstrap 5
- **Ícones**: Font Awesome 6
- **Cores**: Gradiente roxo (#667eea → #764ba2)
- **Responsivo**: Mobile-first
- **Acessibilidade**: Textos legíveis, contrastes adequados

## 💾 Armazenamento

Dados salvos em localStorage:

```javascript
{
  "punches": [
    {
      "id": 1234567890,
      "employee": "João Silva",
      "employee_id": "12345678",
      "method": "facial",
      "type": "entrada",
      "timestamp": "2024-01-17T08:00:00.000Z",
      "similarity": "0.95",
      "synced": false
    }
  ]
}
```

## 🔄 Lógica de Tipos de Marcação

1. **Primeira marcação do dia** → ENTRADA
2. Após entrada → INÍCIO INTERVALO
3. Após início intervalo → FIM INTERVALO
4. Após fim intervalo → SAÍDA
5. No dia seguinte, recomeça do ENTRADA

## 📱 Compatibilidade

- ✅ Chrome/Edge (recomendado)
- ✅ Firefox
- ✅ Safari
- ⚠️ IE11 não suportado

### Permissões Necessárias

- 📷 Acesso à câmera (métodos QR e Facial)
- 💾 localStorage (armazenamento offline)

## 🛠️ Tecnologias

- HTML5
- CSS3 (Bootstrap 5)
- JavaScript vanilla
- LocalStorage API
- MediaDevices API (câmera)

## 🎯 Próximos Passos

Após validação do protótipo:

1. Integrar com backend PHP (CodeIgniter 4)
2. Implementar validação real de códigos
3. Adicionar scanner de QR Code (biblioteca html5-qrcode)
4. Integrar reconhecimento facial (DeepFace API)
5. Implementar sincronização real offline/online
6. Adicionar geolocalização
7. Gerar comprovantes em PDF

## 📝 Limitações do Protótipo

- ❌ Sem validação real de códigos
- ❌ QR Code não implementado (apenas UI)
- ❌ Reconhecimento facial simulado
- ❌ Sem integração com backend
- ❌ Sem geolocalização
- ❌ Sem geração de comprovantes

**Este é um POC para validação de UX/UI e fluxo de trabalho.**

## 🔍 Testando

1. Abra o protótipo no navegador
2. Escolha um método de registro
3. Para **Código**: digite 8 dígitos
4. Para **Facial**: permita acesso à câmera e clique em "Capturar Foto"
5. Clique em "BATER PONTO"
6. Verifique o feedback e o histórico
7. Teste o funcionamento offline (desconecte WiFi)
8. Recarregue a página - dados persistem!

## 📧 Feedback

Durante os testes, observe:

- ✅ Facilidade de uso
- ✅ Clareza das instruções
- ✅ Tempo de resposta
- ✅ Feedback visual adequado
- ✅ Responsividade mobile
- ✅ Acessibilidade

Registre sugestões de melhoria para implementação final.
