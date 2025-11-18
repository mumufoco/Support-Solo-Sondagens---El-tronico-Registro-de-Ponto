<?= $this->extend('install/layout') ?>

<?= $this->section('content') ?>

<div style="text-align: center; padding: 20px 0;">
    <div style="font-size: 64px; margin-bottom: 20px;">⏰</div>
    <h2 style="font-size: 24px; color: #333; margin-bottom: 15px;">Bem-vindo ao Instalador</h2>
    <p style="color: #666; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
        Este assistente irá guiá-lo através do processo de instalação do<br>
        <strong>Sistema de Ponto Eletrônico Brasileiro</strong>
    </p>
</div>

<div class="card">
    <h3 style="font-size: 18px; margin-bottom: 15px; color: #333;">📋 O que será configurado:</h3>
    <ul style="list-style: none; padding: 0;">
        <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
            <strong>✓</strong> Verificação de requisitos do sistema
        </li>
        <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
            <strong>✓</strong> Configuração do banco de dados MySQL
        </li>
        <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
            <strong>✓</strong> Criação da estrutura de tabelas
        </li>
        <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
            <strong>✓</strong> Inserção de dados iniciais
        </li>
        <li style="padding: 10px 0;">
            <strong>✓</strong> Criação do usuário administrador
        </li>
    </ul>
</div>

<div class="alert alert-info">
    <strong>ℹ️ Antes de começar:</strong><br>
    Certifique-se de que você tem:<br>
    • Servidor MySQL instalado e rodando<br>
    • Credenciais de acesso ao MySQL (usuário e senha)<br>
    • Permissões para criar banco de dados
</div>

<div class="button-group" style="justify-content: center;">
    <a href="/install/requirements" class="btn btn-primary" style="padding: 15px 40px; font-size: 18px;">
        Iniciar Instalação →
    </a>
</div>

<div style="text-align: center; margin-top: 30px; color: #999; font-size: 13px;">
    Versão 1.0.0 | © 2024 Support Solo Sondagens
</div>

<?= $this->endSection() ?>
