<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-shield-alt text-primary"></i> Gestão de Consentimentos LGPD</h2>
                    <p class="text-muted">Gerencie seus consentimentos de dados pessoais conforme a Lei Geral de Proteção de Dados</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="requestDataExport()">
                        <i class="fas fa-download"></i> Solicitar Exportação de Dados
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- LGPD Information Banner -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Seus Direitos LGPD</h5>
                <p class="mb-0">De acordo com a Lei nº 13.709/2018, você tem os seguintes direitos:</p>
                <ul class="mb-0 mt-2">
                    <li><strong>Confirmação e acesso:</strong> Confirmar se seus dados estão sendo tratados e acessá-los</li>
                    <li><strong>Correção:</strong> Solicitar correção de dados incompletos, inexatos ou desatualizados</li>
                    <li><strong>Portabilidade:</strong> Receber seus dados em formato estruturado e interoperável</li>
                    <li><strong>Revogação:</strong> Revogar consentimento a qualquer momento</li>
                    <li><strong>Eliminação:</strong> Solicitar eliminação de dados tratados com consentimento</li>
                </ul>
                <hr>
                <p class="mb-0"><strong>DPO (Encarregado de Proteção de Dados):</strong> <?= env('DPO_EMAIL', 'dpo@empresa.com') ?></p>
            </div>
        </div>
    </div>

    <!-- Consent Cards -->
    <div class="row">
        <?php foreach ($consentTypes as $type => $info): ?>
            <?php
                $active = null;
                foreach ($consents['active'] as $consent) {
                    if ($consent->consent_type === $type) {
                        $active = $consent;
                        break;
                    }
                }

                $revoked = null;
                foreach ($consents['revoked'] as $consent) {
                    if ($consent->consent_type === $type) {
                        $revoked = $consent;
                        break;
                    }
                }

                $hasConsent = $active !== null;
                $isPending = in_array($type, $consents['pending']);
            ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm <?= $hasConsent ? 'border-success' : ($info['required'] ? 'border-warning' : 'border-secondary') ?>">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?= esc($info['label']) ?>
                            <?php if ($info['required']): ?>
                                <span class="badge badge-warning ml-2">Obrigatório</span>
                            <?php endif; ?>
                        </h5>
                        <?php if ($hasConsent): ?>
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Concedido</span>
                        <?php elseif ($isPending): ?>
                            <span class="badge badge-secondary"><i class="fas fa-clock"></i> Pendente</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Revogado</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <p><strong>Finalidade:</strong></p>
                        <p class="text-muted"><?= esc($info['purpose']) ?></p>

                        <p><strong>Base Legal:</strong></p>
                        <p class="text-muted small"><?= esc($info['legal_basis']) ?></p>

                        <?php if ($active): ?>
                            <div class="consent-info mt-3 p-3 bg-light rounded">
                                <p class="mb-1"><small><strong>Concedido em:</strong> <?= date('d/m/Y H:i', strtotime($active->granted_at)) ?></small></p>
                                <p class="mb-1"><small><strong>IP:</strong> <?= esc($active->ip_address) ?></small></p>
                                <p class="mb-0"><small><strong>Versão do Termo:</strong> <?= esc($active->version) ?></small></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($revoked): ?>
                            <div class="consent-info mt-3 p-3 bg-warning rounded">
                                <p class="mb-0"><small><strong>Revogado em:</strong> <?= date('d/m/Y H:i', strtotime($revoked->revoked_at)) ?></small></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <?php if ($hasConsent && !$info['required']): ?>
                            <button class="btn btn-sm btn-danger btn-block"
                                    onclick="revokeConsent('<?= $type ?>', '<?= esc($info['label']) ?>')">
                                <i class="fas fa-times"></i> Revogar Consentimento
                            </button>
                        <?php elseif (!$hasConsent): ?>
                            <button class="btn btn-sm btn-success btn-block"
                                    onclick="grantConsent('<?= $type ?>', '<?= esc($info['label']) ?>', '<?= esc($info['purpose']) ?>', '<?= esc($info['legal_basis']) ?>')">
                                <i class="fas fa-check"></i> Conceder Consentimento
                            </button>
                        <?php else: ?>
                            <div class="alert alert-info mb-0 small">
                                <i class="fas fa-info-circle"></i> Este consentimento é obrigatório para uso do sistema
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Consent History -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Histórico de Consentimentos</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($consents['all'])): ?>
                        <p class="text-muted text-center py-4">Nenhum consentimento registrado ainda.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                        <th>IP</th>
                                        <th>Versão</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consents['all'] as $consent): ?>
                                        <tr>
                                            <td><?= esc($consentTypes[$consent->consent_type]['label'] ?? $consent->consent_type) ?></td>
                                            <td>
                                                <?php if ($consent->granted && !$consent->revoked_at): ?>
                                                    <span class="badge badge-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Revogado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($consent->revoked_at): ?>
                                                    <span class="text-muted"><?= date('d/m/Y H:i', strtotime($consent->revoked_at)) ?></span>
                                                <?php else: ?>
                                                    <?= date('d/m/Y H:i', strtotime($consent->granted_at)) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= esc($consent->ip_address) ?></small></td>
                                            <td><span class="badge badge-secondary"><?= esc($consent->version) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grant Consent Modal -->
<div class="modal fade" id="grantConsentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Conceder Consentimento</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <h6 id="consent-title"></h6>
                <hr>
                <div class="consent-details">
                    <p><strong>Finalidade:</strong></p>
                    <p id="consent-purpose" class="text-muted"></p>

                    <p><strong>Base Legal:</strong></p>
                    <p id="consent-legal" class="text-muted small"></p>
                </div>
                <hr>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="acceptConsent">
                    <label class="form-check-label" for="acceptConsent">
                        Li e concordo com os termos acima. Estou ciente de que posso revogar este consentimento a qualquer momento.
                    </label>
                </div>
                <input type="hidden" id="consent-type">
                <input type="hidden" id="consent-purpose-hidden">
                <input type="hidden" id="consent-legal-hidden">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmGrant" disabled>
                    <i class="fas fa-check"></i> Confirmar Consentimento
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Revoke Consent Modal -->
<div class="modal fade" id="revokeConsentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Revogar Consentimento</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja revogar o consentimento para <strong id="revoke-title"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Atenção:</strong> Ao revogar este consentimento, os dados relacionados serão removidos do sistema conforme a LGPD.
                </div>
                <div class="form-group">
                    <label for="revoke-reason">Motivo da Revogação (Opcional):</label>
                    <textarea class="form-control" id="revoke-reason" rows="3"></textarea>
                </div>
                <input type="hidden" id="revoke-type">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmRevoke">
                    <i class="fas fa-times"></i> Confirmar Revogação
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Grant consent
function grantConsent(type, label, purpose, legalBasis) {
    $('#consent-title').text(label);
    $('#consent-purpose').text(purpose);
    $('#consent-legal').text(legalBasis);
    $('#consent-type').val(type);
    $('#consent-purpose-hidden').val(purpose);
    $('#consent-legal-hidden').val(legalBasis);
    $('#acceptConsent').prop('checked', false);
    $('#confirmGrant').prop('disabled', true);
    $('#grantConsentModal').modal('show');
}

// Enable confirm button when checkbox is checked
$('#acceptConsent').on('change', function() {
    $('#confirmGrant').prop('disabled', !this.checked);
});

// Confirm grant
$('#confirmGrant').on('click', async function() {
    const type = $('#consent-type').val();
    const purpose = $('#consent-purpose-hidden').val();
    const legalBasis = $('#consent-legal-hidden').val();
    const consentText = `Finalidade: ${purpose}\n\nBase Legal: ${legalBasis}\n\nConcordado em: ${new Date().toLocaleString('pt-BR')}`;

    try {
        const response = await fetch('/lgpd/grant-consent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                consent_type: type,
                purpose: purpose,
                consent_text: consentText,
                legal_basis: legalBasis,
                version: '1.0'
            })
        });

        const result = await response.json();

        if (result.success) {
            $('#grantConsentModal').modal('hide');
            alert('Consentimento registrado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao processar solicitação: ' + error.message);
    }
});

// Revoke consent
function revokeConsent(type, label) {
    $('#revoke-title').text(label);
    $('#revoke-type').val(type);
    $('#revoke-reason').val('');
    $('#revokeConsentModal').modal('show');
}

// Confirm revoke
$('#confirmRevoke').on('click', async function() {
    const type = $('#revoke-type').val();
    const reason = $('#revoke-reason').val();

    try {
        const response = await fetch('/lgpd/revoke-consent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                consent_type: type,
                reason: reason
            })
        });

        const result = await response.json();

        if (result.success) {
            $('#revokeConsentModal').modal('hide');
            alert('Consentimento revogado com sucesso!\n\nRegistros deletados: ' + result.deleted_records);
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao processar solicitação: ' + error.message);
    }
});

// Request data export
async function requestDataExport() {
    if (!confirm('Deseja solicitar a exportação de todos os seus dados pessoais?\n\nVocê receberá um e-mail com o link para download.')) {
        return;
    }

    try {
        const response = await fetch('/lgpd/request-export', {
            method: 'POST'
        });

        const result = await response.json();

        if (result.success) {
            alert('Exportação solicitada com sucesso!\n\nVocê receberá 2 e-mails:\n1. Link para download do arquivo\n2. Senha para descompactar\n\nO arquivo estará disponível por 48 horas.');
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao processar solicitação: ' + error.message);
    }
}
</script>

<style>
.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

.consent-info {
    font-size: 0.85rem;
}

.border-success {
    border-width: 2px !important;
}

.border-warning {
    border-width: 2px !important;
}
</style>

<?= $this->endSection() ?>
