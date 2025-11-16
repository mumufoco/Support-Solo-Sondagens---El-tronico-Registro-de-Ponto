<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Erro Interno do Servidor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .error-container {
            text-align: center;
            color: white;
        }

        .error-code {
            font-size: 150px;
            font-weight: 700;
            line-height: 1;
            text-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: glitch 1s infinite;
        }

        @keyframes glitch {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
        }

        .error-icon {
            font-size: 100px;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.9; }
            50% { opacity: 0.5; }
        }

        .error-message {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .error-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .btn-home {
            background: white;
            color: #667eea;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            color: #764ba2;
        }

        .btn-retry {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            border: 2px solid white;
            margin-left: 1rem;
        }

        .btn-retry:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code">500</div>
        <div class="error-message">Erro Interno do Servidor</div>
        <div class="error-description">
            Ops! Algo deu errado no servidor.<br>
            Nossa equipe foi notificada e está trabalhando para resolver o problema.
        </div>
        <a href="<?= base_url() ?>" class="btn-home">
            <i class="fas fa-home me-2"></i>Voltar para o Início
        </a>
        <a href="javascript:location.reload()" class="btn-retry">
            <i class="fas fa-redo me-2"></i>Tentar Novamente
        </a>
        <div class="mt-5 opacity-75">
            <small>
                <i class="fas fa-code me-1"></i>
                Código de Referência: <?= date('YmdHis') . '-' . uniqid() ?>
            </small>
        </div>
    </div>
</body>
</html>
