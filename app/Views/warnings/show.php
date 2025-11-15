<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-file-alt"></i> Advertência #<?= $warning->id ?>
                        <?php
                        $statusBadges = [
                            'pendente-assinatura' => '<span class="badge bg-warning text-dark">Pendente Assinatura</span>',
                            'assinado' => '<span class="badge bg-success">Assinado</span>',
                            'recusado' => '<span class="badge bg-dark">Recusado</span>'
                        ];
                        echo $statusBadges[$warning->status] ?? '';
                        ?>
                    </h4>
                </div>
                <div class="card-body">
                    <h5>Dados do Funcionário</h5>
                    <table class="table table-sm">
                        <tr>
                            <th width="30%">Nome:</th>
                            <td><?= esc($warningEmployee->name) ?></td>
                        </tr>
                        <tr>
                            <th>Departamento:</th>
                            <td><?= esc($warningEmployee->department) ?></td>
                        </tr>
                        <tr>
                            <th>CPF:</th>
                            <td><?= esc($warningEmployee->cpf ?? 'Não informado') ?></td>
                        </tr>
                    </table>

                    <hr>

                    <h5>Detalhes da Advertência</h5>
                    <table class="table table-sm">
                        <tr>
                            <th width="30%">Tipo:</th>
                            <td>
                                <?php
                                $types = ['verbal' => 'Verbal', 'escrita' => 'Escrita', 'suspensao' => 'Suspensão'];
                                echo '<strong>' . ($types[$warning->warning_type] ?? $warning->warning_type) . '</strong>';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Data da Ocorrência:</th>
                            <td><?= date('d/m/Y', strtotime($warning->occurrence_date)) ?></td>
                        </tr>
                        <tr>
                            <th>Emitida por:</th>
                            <td><?= esc($issuer->name) ?></td>
                        </tr>
                        <tr>
                            <th>Emitida em:</th>
                            <td><?= date('d/m/Y H:i', strtotime($warning->created_at)) ?></td>
                        </tr>
                    </table>

                    <hr>

                    <h5>Motivo</h5>
                    <div class="alert alert-light">
                        <?= nl2br(esc($warning->reason)) ?>
                    </div>

                    <?php if (!empty($warning->evidence_files)): ?>
                        <h5>Evidências Anexas</h5>
                        <ul class="list-group mb-3">
                            <?php foreach ($warning->evidence_files as $file): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-file"></i> <?= basename($file) ?>
                                    <a href="/<?= $file ?>" target="_blank" class="btn btn-sm btn-link">Visualizar</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($warning->employee_signature): ?>
                        <h5>Assinatura do Funcionário</h5>
                        <div class="alert alert-success">
                            <p><strong>Assinado em:</strong> <?= date('d/m/Y H:i', strtotime($warning->employee_signed_at)) ?></p>
                            <p><strong>Método:</strong> <?= esc($warning->employee_signature) ?></p>
                        </div>
                    <?php elseif ($warning->witness_name): ?>
                        <h5>Testemunha (Recusa de Assinatura)</h5>
                        <div class="alert alert-danger">
                            <p><strong>Nome:</strong> <?= esc($warning->witness_name) ?></p>
                            <p><strong>CPF:</strong> <?= esc($warning->witness_cpf) ?></p>
                            <p class="mb-0"><small>Funcionário recusou-se a assinar. Testemunha presencial adicionada.</small></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="/warnings" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <div>
                            <?php if ($warning->pdf_path): ?>
                                <a href="/warnings/<?= $warning->id ?>/download" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Download PDF
                                </a>
                            <?php endif; ?>

                            <?php if ($warning->status === 'pendente-assinatura' && $warning->employee_id === $employee['id']): ?>
                                <a href="/warnings/<?= $warning->id ?>/sign" class="btn btn-danger">
                                    <i class="fas fa-pen"></i> Assinar Agora
                                </a>
                            <?php endif; ?>

                            <?php if ($canAddWitness && in_array($employee['role'], ['admin', 'gestor'])): ?>
                                <a href="/warnings/<?= $warning->id ?>/add-witness" class="btn btn-warning">
                                    <i class="fas fa-user-plus"></i> Adicionar Testemunha
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informações</h5>
                </div>
                <div class="card-body">
                    <?php if ($warning->status === 'pendente-assinatura'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i>
                            <strong>Tempo decorrido:</strong> <?= round($hoursElapsed) ?> horas
                            <?php if ($hoursElapsed >= 48): ?>
                                <hr>
                                <small>Já passaram 48h. Gestor pode adicionar testemunha.</small>
                            <?php else: ?>
                                <hr>
                                <small>Faltam <?= round(48 - $hoursElapsed) ?> horas para adicionar testemunha.</small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <h6>CLT Art. 482</h6>
                    <p class="small text-muted">
                        Constituem justa causa para rescisão do contrato de trabalho: atos de indisciplina ou insubordinação,
                        desídia no desempenho das funções, e demais infrações previstas.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
