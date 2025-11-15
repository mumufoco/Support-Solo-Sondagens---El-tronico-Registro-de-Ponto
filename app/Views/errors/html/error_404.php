<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página Não Encontrada</title>
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
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .error-icon {
            font-size: 100px;
            margin-bottom: 2rem;
            opacity: 0.9;
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
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        <div class="error-code">404</div>
        <div class="error-message">Página Não Encontrada</div>
        <div class="error-description">
            A página que você está procurando não existe ou foi movida.
        </div>
        <a href="<?= base_url() ?>" class="btn-home">
            <i class="fas fa-home me-2"></i>Voltar para o Início
        </a>
        <div class="mt-4">
            <a href="javascript:history.back()" class="text-white text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i>Voltar à página anterior
            </a>
        </div>
    </div>
</body>
</html>
