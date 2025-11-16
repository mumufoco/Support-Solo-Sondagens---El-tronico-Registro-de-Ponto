<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-1"><i class="fas fa-cog text-primary"></i> Configurações do Sistema</h2>
            <p class="text-muted">Gerencie todas as configurações do sistema de ponto eletrônico</p>
        </div>
    </div>

    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#general"><i class="fas fa-building"></i> Geral</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#workday"><i class="fas fa-clock"></i> Jornada</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#geolocation"><i class="fas fa-map-marker-alt"></i> Geolocalização</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#notifications"><i class="fas fa-bell"></i> Notificações</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#biometry"><i class="fas fa-fingerprint"></i> Biometria</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#apis"><i class="fas fa-plug"></i> APIs</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#icp"><i class="fas fa-certificate"></i> ICP-Brasil</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#lgpd"><i class="fas fa-shield-alt"></i> LGPD</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#backup"><i class="fas fa-database"></i> Backup</a></li>
    </ul>

    <div class="tab-content mt-3">
        <!-- TAB 1: GERAL -->
        <div class="tab-pane fade show active" id="general">
            <div class="card"><div class="card-header bg-primary text-white"><h5 class="mb-0">Configurações Gerais</h5></div>
            <div class="card-body">
                <form id="form-general">
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Nome da Empresa *</label>
                            <input type="text" class="form-control" name="company_name" value="<?= $settings['general']['company_name'] ?? '' ?>" required></div></div>
                        <div class="col-md-6"><div class="form-group"><label>CNPJ *</label>
                            <input type="text" class="form-control cnpj-mask" name="company_cnpj" value="<?= $settings['general']['company_cnpj'] ?? '' ?>" required></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Logo da Empresa</label>
                            <input type="file" class="form-control" name="company_logo" accept="image/*" id="logo-upload">
                            <?php if (!empty($settings['general']['company_logo'])): ?>
                                <img src="<?= base_url('writable/uploads/logos/' . $settings['general']['company_logo']) ?>" class="mt-2" style="max-height:100px" id="logo-preview">
                            <?php endif; ?></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Cor Primária *</label>
                            <input type="color" class="form-control" name="primary_color" value="<?= $settings['general']['primary_color'] ?? '#667eea' ?>" required></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Cor Secundária *</label>
                            <input type="color" class="form-control" name="secondary_color" value="<?= $settings['general']['secondary_color'] ?? '#764ba2' ?>" required></div></div>
                    </div>
                    <div class="form-group"><label>Timezone *</label>
                        <select class="form-control" name="timezone" required>
                            <option value="America/Sao_Paulo" <?= ($settings['general']['timezone'] ?? '') == 'America/Sao_Paulo' ? 'selected' : '' ?>>America/Sao_Paulo (BRT)</option>
                            <option value="America/Manaus">America/Manaus (AMT)</option><option value="America/Fortaleza">America/Fortaleza (BRT)</option>
                        </select></div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Configurações Gerais</button>
                </form>
            </div></div>
        </div>

        <!-- TAB 2: JORNADA -->
        <div class="tab-pane fade" id="workday">
            <div class="card"><div class="card-header bg-success text-white"><h5 class="mb-0">Configurações de Jornada</h5></div>
            <div class="card-body">
                <form id="form-workday">
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Horário de Expediente - Início *</label>
                            <input type="time" class="form-control" name="workday_start" value="<?= $settings['workday']['workday_start'] ?? '08:00' ?>" required></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Horário de Expediente - Fim *</label>
                            <input type="time" class="form-control" name="workday_end" value="<?= $settings['workday']['workday_end'] ?? '18:00' ?>" required></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Intervalo Obrigatório (horas) *</label>
                            <input type="number" class="form-control" name="mandatory_break_hours" value="<?= $settings['workday']['mandatory_break_hours'] ?? '1' ?>" step="0.25" min="0" max="4" required></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Tolerância Atraso (minutos) *</label>
                            <input type="number" class="form-control" name="late_tolerance_minutes" value="<?= $settings['workday']['late_tolerance_minutes'] ?? '15' ?>" min="0" max="60" required></div></div>
                    </div>
                    <div class="form-group"><label>Dias Úteis</label><br>
                        <?php $businessDays = json_decode($settings['workday']['business_days'] ?? '[]', true) ?? []; ?>
                        <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="business_days[]" value="1" <?= in_array('1', $businessDays) ? 'checked' : '' ?>><label class="form-check-label">Segunda</label></div>
                        <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="business_days[]" value="2" <?= in_array('2', $businessDays) ? 'checked' : '' ?>><label class="form-check-label">Terça</label></div>
                        <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="business_days[]" value="3" <?= in_array('3', $businessDays) ? 'checked' : '' ?>><label class="form-check-label">Quarta</label></div>
                        <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="business_days[]" value="4" <?= in_array('4', $businessDays) ? 'checked' : '' ?>><label class="form-check-label">Quinta</label></div>
                        <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="business_days[]" value="5" <?= in_array('5', $businessDays) ? 'checked' : '' ?>><label class="form-check-label">Sexta</label></div>
                        <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="business_days[]" value="6" <?= in_array('6', $businessDays) ? 'checked' : '' ?>><label class="form-check-label">Sábado</label></div>
                        <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="business_days[]" value="0" <?= in_array('0', $businessDays) ? 'checked' : '' ?>><label class="form-check-label">Domingo</label></div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar Configurações de Jornada</button>
                </form>
            </div></div>
        </div>

        <!-- TAB 3: GEOLOCALIZAÇÃO -->
        <div class="tab-pane fade" id="geolocation">
            <div class="card"><div class="card-header bg-info text-white"><h5 class="mb-0">Configurações de Geolocalização</h5></div>
            <div class="card-body">
                <form id="form-geolocation">
                    <div class="form-group"><div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="geolocationEnabled" name="geolocation_enabled" <?= ($settings['geolocation']['geolocation_enabled'] ?? 'false') == 'true' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="geolocationEnabled">Ativar Geolocalização</label></div></div>
                    <div class="form-group"><div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="geolocationRequired" name="geolocation_required" <?= ($settings['geolocation']['geolocation_required'] ?? 'false') == 'true' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="geolocationRequired">Tornar Obrigatório</label></div></div>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save"></i> Salvar Configurações de Geolocalização</button>
                </form>
                <hr><h6>Gerenciar Cercas Geográficas</h6>
                <button class="btn btn-primary btn-sm mb-2" onclick="openGeofenceModal()"><i class="fas fa-plus"></i> Nova Cerca</button>
                <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Nome</th><th>Latitude</th><th>Longitude</th><th>Raio (m)</th><th>Ações</th></tr></thead>
                <tbody><?php foreach ($geofences as $g): ?><tr><td><?= esc($g->name) ?></td><td><?= $g->latitude ?></td><td><?= $g->longitude ?></td><td><?= $g->radius ?></td>
                <td><button class="btn btn-sm btn-warning" onclick="editGeofence(<?= $g->id ?>)"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteGeofence(<?= $g->id ?>)"><i class="fas fa-trash"></i></button></td></tr><?php endforeach; ?></tbody></table></div>
            </div></div>
        </div>

        <!-- TAB 4: NOTIFICAÇÕES -->
        <div class="tab-pane fade" id="notifications">
            <div class="card"><div class="card-header bg-warning"><h5 class="mb-0">Configurações de Notificações</h5></div>
            <div class="card-body">
                <form id="form-notifications">
                    <div class="row">
                        <div class="col-md-4"><div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="emailEnabled" name="notifications_email_enabled" <?= ($settings['notifications']['notifications_email_enabled'] ?? 'true') == 'true' ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="emailEnabled"><i class="fas fa-envelope"></i> Email</label></div></div>
                        <div class="col-md-4"><div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="pushEnabled" name="notifications_push_enabled" <?= ($settings['notifications']['notifications_push_enabled'] ?? 'false') == 'true' ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="pushEnabled"><i class="fas fa-mobile-alt"></i> Push</label></div></div>
                        <div class="col-md-4"><div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="smsEnabled" name="notifications_sms_enabled" <?= ($settings['notifications']['notifications_sms_enabled'] ?? 'false') == 'true' ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="smsEnabled"><i class="fas fa-sms"></i> SMS</label></div></div>
                    </div>
                    <div class="form-group mt-3"><label>Lembrete de Ponto (minutos antes)</label>
                        <input type="number" class="form-control" name="punch_reminder_minutes" value="<?= $settings['notifications']['punch_reminder_minutes'] ?? '30' ?>" min="0" max="120"></div>
                    <h6 class="mt-4">Templates de E-mail (HTML)</h6>
                    <div class="form-group"><label>Template: Boas-vindas</label><textarea class="form-control tinymce" name="email_template_welcome" rows="4"><?= $settings['notifications']['email_template_welcome'] ?? '<h1>Bem-vindo!</h1>' ?></textarea></div>
                    <div class="form-group"><label>Template: Lembrete de Ponto</label><textarea class="form-control tinymce" name="email_template_punch_reminder" rows="4"><?= $settings['notifications']['email_template_punch_reminder'] ?? '<p>Lembre-se de bater o ponto!</p>' ?></textarea></div>
                    <div class="form-group"><label>Template: Justificativa</label><textarea class="form-control tinymce" name="email_template_justification" rows="4"><?= $settings['notifications']['email_template_justification'] ?? '<p>Nova justificativa enviada</p>' ?></textarea></div>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Salvar Configurações de Notificações</button>
                </form>
            </div></div>
        </div>

        <!-- TAB 5: BIOMETRIA -->
        <div class="tab-pane fade" id="biometry">
            <div class="card"><div class="card-header bg-danger text-white"><h5 class="mb-0">Configurações de Biometria (DeepFace)</h5></div>
            <div class="card-body">
                <form id="form-biometry">
                    <div class="form-group"><label>DeepFace API URL *</label>
                        <input type="url" class="form-control" name="deepface_api_url" value="<?= $settings['biometry']['deepface_api_url'] ?? 'http://localhost:5000' ?>" required placeholder="http://localhost:5000"></div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Threshold (Limite de Confiança) * <span id="threshold-value"><?= $settings['biometry']['deepface_threshold'] ?? '0.40' ?></span></label>
                            <input type="range" class="form-control-range" name="deepface_threshold" min="0.30" max="0.70" step="0.01" value="<?= $settings['biometry']['deepface_threshold'] ?? '0.40' ?>" id="threshold-slider">
                            <small class="form-text text-muted">Menor = mais rigoroso | Maior = mais flexível</small></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Modelo *</label>
                            <select class="form-control" name="deepface_model" required>
                                <?php $model = $settings['biometry']['deepface_model'] ?? 'VGG-Face'; ?>
                                <option value="VGG-Face" <?= $model == 'VGG-Face' ? 'selected' : '' ?>>VGG-Face</option>
                                <option value="Facenet" <?= $model == 'Facenet' ? 'selected' : '' ?>>Facenet</option>
                                <option value="Facenet512" <?= $model == 'Facenet512' ? 'selected' : '' ?>>Facenet512</option>
                                <option value="OpenFace" <?= $model == 'OpenFace' ? 'selected' : '' ?>>OpenFace</option>
                                <option value="DeepFace" <?= $model == 'DeepFace' ? 'selected' : '' ?>>DeepFace</option>
                                <option value="ArcFace" <?= $model == 'ArcFace' ? 'selected' : '' ?>>ArcFace</option>
                                <option value="Dlib" <?= $model == 'Dlib' ? 'selected' : '' ?>>Dlib</option>
                                <option value="SFace" <?= $model == 'SFace' ? 'selected' : '' ?>>SFace</option>
                            </select></div></div>
                    </div>
                    <div class="form-group"><div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="antiSpoofing" name="deepface_anti_spoofing" <?= ($settings['biometry']['deepface_anti_spoofing'] ?? 'false') == 'true' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="antiSpoofing">Ativar Anti-Spoofing (detecção de fotos)</label></div></div>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Salvar Configurações de Biometria</button>
                </form>
            </div></div>
        </div>

        <!-- TAB 6: APIs -->
        <div class="tab-pane fade" id="apis">
            <div class="card"><div class="card-header bg-secondary text-white"><h5 class="mb-0">Configurações de APIs Externas</h5></div>
            <div class="card-body">
                <form id="form-apis">
                    <h6>Nominatim (Geocoding)</h6>
                    <div class="form-group"><label>Endpoint Customizado (opcional)</label>
                        <input type="url" class="form-control" name="nominatim_endpoint" value="<?= $settings['apis']['nominatim_endpoint'] ?? 'https://nominatim.openstreetmap.org' ?>" placeholder="https://nominatim.openstreetmap.org"></div>
                    <hr><h6>Rate Limiting & Cache</h6>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Rate Limit (requisições/minuto)</label>
                            <input type="number" class="form-control" name="api_rate_limit" value="<?= $settings['apis']['api_rate_limit'] ?? '60' ?>" min="1" max="1000"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Cache TTL (segundos)</label>
                            <input type="number" class="form-control" name="api_cache_ttl" value="<?= $settings['apis']['api_cache_ttl'] ?? '3600' ?>" min="60" max="86400"></div></div>
                    </div>
                    <button type="submit" class="btn btn-secondary"><i class="fas fa-save"></i> Salvar Configurações de APIs</button>
                </form>
            </div></div>
        </div>

        <!-- TAB 7: ICP-BRASIL -->
        <div class="tab-pane fade" id="icp">
            <div class="card"><div class="card-header bg-dark text-white"><h5 class="mb-0">Configurações de ICP-Brasil (Assinatura Digital)</h5></div>
            <div class="card-body">
                <form id="form-icp">
                    <div class="form-group"><label>Certificado Digital (.pfx)</label>
                        <input type="file" class="form-control" name="icp_certificate" accept=".pfx,.p12"></div>
                    <div class="form-group"><label>Senha do Certificado</label>
                        <input type="password" class="form-control" name="icp_certificate_password" placeholder="Digite a senha do certificado"></div>
                    <?php if (!empty($settings['icp_brasil']['icp_certificate_valid_until'])): ?>
                        <div class="alert alert-info"><strong>Certificado Atual:</strong><br>
                            Válido até: <?= date('d/m/Y', strtotime($settings['icp_brasil']['icp_certificate_valid_until'])) ?><br>
                            <?php
                                $days = floor((strtotime($settings['icp_brasil']['icp_certificate_valid_until']) - time()) / 86400);
                                $class = $days < 30 ? 'danger' : ($days < 90 ? 'warning' : 'success');
                            ?>
                            Dias restantes: <span class="badge badge-<?= $class ?>"><?= $days ?> dias</span>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-dark"><i class="fas fa-save"></i> Salvar Certificado</button>
                    <button type="button" class="btn btn-outline-primary ml-2" onclick="testICPCertificate()"><i class="fas fa-check-circle"></i> Testar Assinatura</button>
                </form>
            </div></div>
        </div>

        <!-- TAB 8: LGPD -->
        <div class="tab-pane fade" id="lgpd">
            <div class="card"><div class="card-header bg-primary text-white"><h5 class="mb-0">Configurações de LGPD</h5></div>
            <div class="card-body">
                <form id="form-lgpd">
                    <h6>DPO (Encarregado de Proteção de Dados)</h6>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Nome do DPO *</label>
                            <input type="text" class="form-control" name="lgpd_dpo_name" value="<?= $settings['lgpd']['lgpd_dpo_name'] ?? '' ?>" required></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Email do DPO *</label>
                            <input type="email" class="form-control" name="lgpd_dpo_email" value="<?= $settings['lgpd']['lgpd_dpo_email'] ?? '' ?>" required></div></div>
                    </div>
                    <hr><h6>Política de Retenção de Dados</h6>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Registros de Ponto (dias)</label>
                            <select class="form-control" name="lgpd_retention_attendance">
                                <?php $val = $settings['lgpd']['lgpd_retention_attendance'] ?? '3650'; ?>
                                <option value="365" <?= $val == '365' ? 'selected' : '' ?>>1 ano</option>
                                <option value="1825" <?= $val == '1825' ? 'selected' : '' ?>>5 anos</option>
                                <option value="3650" <?= $val == '3650' ? 'selected' : '' ?>>10 anos (recomendado)</option>
                            </select></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Dados Biométricos (dias)</label>
                            <select class="form-control" name="lgpd_retention_biometric">
                                <?php $val = $settings['lgpd']['lgpd_retention_biometric'] ?? '1825'; ?>
                                <option value="365" <?= $val == '365' ? 'selected' : '' ?>>1 ano</option>
                                <option value="1825" <?= $val == '1825' ? 'selected' : '' ?>>5 anos (recomendado)</option>
                                <option value="3650" <?= $val == '3650' ? 'selected' : '' ?>>10 anos</option>
                            </select></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Logs de Auditoria (dias)</label>
                            <select class="form-control" name="lgpd_retention_audit">
                                <?php $val = $settings['lgpd']['lgpd_retention_audit'] ?? '3650'; ?>
                                <option value="1825" <?= $val == '1825' ? 'selected' : '' ?>>5 anos</option>
                                <option value="3650" <?= $val == '3650' ? 'selected' : '' ?>>10 anos (recomendado)</option>
                            </select></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Consentimentos (dias)</label>
                            <select class="form-control" name="lgpd_retention_consents">
                                <?php $val = $settings['lgpd']['lgpd_retention_consents'] ?? '-1'; ?>
                                <option value="-1" <?= $val == '-1' ? 'selected' : '' ?>>Permanente (recomendado)</option>
                                <option value="3650" <?= $val == '3650' ? 'selected' : '' ?>>10 anos</option>
                            </select></div></div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Configurações LGPD</button>
                </form>
            </div></div>
        </div>

        <!-- TAB 9: BACKUP -->
        <div class="tab-pane fade" id="backup">
            <div class="card"><div class="card-header bg-success text-white"><h5 class="mb-0">Configurações de Backup</h5></div>
            <div class="card-body">
                <form id="form-backup">
                    <div class="form-group"><label>Tipo de Backup</label>
                        <select class="form-control" name="backup_type" id="backup-type">
                            <?php $type = $settings['backup']['backup_type'] ?? 's3'; ?>
                            <option value="s3" <?= $type == 's3' ? 'selected' : '' ?>>Amazon S3</option>
                            <option value="ftp" <?= $type == 'ftp' ? 'selected' : '' ?>>FTP/SFTP</option>
                        </select></div>
                    
                    <!-- S3 Config -->
                    <div id="s3-config" style="<?= $type == 's3' ? '' : 'display:none' ?>">
                        <h6>Configurações S3</h6>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label>Access Key</label>
                                <input type="text" class="form-control" name="backup_s3_access_key" value="<?= $settings['backup']['backup_s3_access_key'] ?? '' ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label>Secret Key</label>
                                <input type="password" class="form-control" name="backup_s3_secret_key" value="<?= $settings['backup']['backup_s3_secret_key'] ?? '' ?>"></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label>Bucket</label>
                                <input type="text" class="form-control" name="backup_s3_bucket" value="<?= $settings['backup']['backup_s3_bucket'] ?? '' ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label>Region</label>
                                <select class="form-control" name="backup_s3_region">
                                    <?php $region = $settings['backup']['backup_s3_region'] ?? 'us-east-1'; ?>
                                    <option value="us-east-1" <?= $region == 'us-east-1' ? 'selected' : '' ?>>US East (N. Virginia)</option>
                                    <option value="sa-east-1" <?= $region == 'sa-east-1' ? 'selected' : '' ?>>South America (São Paulo)</option>
                                </select></div></div>
                        </div>
                    </div>

                    <!-- FTP Config -->
                    <div id="ftp-config" style="<?= $type == 'ftp' ? '' : 'display:none' ?>">
                        <h6>Configurações FTP</h6>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label>Host</label>
                                <input type="text" class="form-control" name="backup_ftp_host" value="<?= $settings['backup']['backup_ftp_host'] ?? '' ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label>Usuário</label>
                                <input type="text" class="form-control" name="backup_ftp_user" value="<?= $settings['backup']['backup_ftp_user'] ?? '' ?>"></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label>Senha</label>
                                <input type="password" class="form-control" name="backup_ftp_password" value="<?= $settings['backup']['backup_ftp_password'] ?? '' ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label>Caminho</label>
                                <input type="text" class="form-control" name="backup_ftp_path" value="<?= $settings['backup']['backup_ftp_path'] ?? '/backups' ?>"></div></div>
                        </div>
                    </div>

                    <hr><h6>Agendamento</h6>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Frequência</label>
                            <select class="form-control" name="backup_schedule">
                                <?php $schedule = $settings['backup']['backup_schedule'] ?? 'daily'; ?>
                                <option value="daily" <?= $schedule == 'daily' ? 'selected' : '' ?>>Diário</option>
                                <option value="weekly" <?= $schedule == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                            </select></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Retenção (dias)</label>
                            <input type="number" class="form-control" name="backup_retention_days" value="<?= $settings['backup']['backup_retention_days'] ?? '30' ?>" min="7" max="365"></div></div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar Configurações de Backup</button>
                </form>
            </div></div>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js"></script>
<script>
// CNPJ Mask
$('.cnpj-mask').mask('00.000.000/0000-00');

// Logo Preview
$('#logo-upload').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#logo-preview').attr('src', e.target.result).show();
        };
        reader.readAsDataURL(file);
    }
});

// Threshold Slider
$('#threshold-slider').on('input', function() {
    $('#threshold-value').text($(this).val());
});

// Backup Type Toggle
$('#backup-type').on('change', function() {
    if ($(this).val() === 's3') {
        $('#s3-config').show();
        $('#ftp-config').hide();
    } else {
        $('#s3-config').hide();
        $('#ftp-config').show();
    }
});

// TinyMCE
tinymce.init({ selector: '.tinymce', height: 200 });

// Form Submissions
$('#form-general').on('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    submitForm('/settings/save-general', formData);
});

$('#form-workday').on('submit', function(e) {
    e.preventDefault();
    submitForm('/settings/save-workday', $(this).serialize());
});

$('#form-geolocation').on('submit', function(e) {
    e.preventDefault();
    submitForm('/settings/save-geolocation', $(this).serialize());
});

$('#form-notifications').on('submit', function(e) {
    e.preventDefault();
    tinymce.triggerSave();
    submitForm('/settings/save-notifications', $(this).serialize());
});

$('#form-biometry').on('submit', function(e) {
    e.preventDefault();
    submitForm('/settings/save-biometry', $(this).serialize());
});

$('#form-apis').on('submit', function(e) {
    e.preventDefault();
    submitForm('/settings/save-apis', $(this).serialize());
});

$('#form-icp').on('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    submitForm('/settings/save-icp-brasil', formData);
});

$('#form-lgpd').on('submit', function(e) {
    e.preventDefault();
    submitForm('/settings/save-lgpd', $(this).serialize());
});

$('#form-backup').on('submit', function(e) {
    e.preventDefault();
    submitForm('/settings/save-backup', $(this).serialize());
});

function submitForm(url, data) {
    $.ajax({
        url: url,
        method: 'POST',
        data: data,
        processData: typeof data === 'string',
        contentType: typeof data === 'string' ? 'application/x-www-form-urlencoded' : false,
        success: function(response) {
            if (response.success) {
                alert('✓ ' + response.message);
            } else {
                alert('✗ ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao salvar configurações');
        }
    });
}

function testICPCertificate() {
    $.post('/settings/test-icp-certificate', function(response) {
        if (response.success) {
            alert(`✓ Certificado Válido\n\nTitular: ${response.data.subject}\nEmissor: ${response.data.issuer}\nVálido até: ${response.data.valid_to}\nDias restantes: ${response.data.days_remaining}`);
        } else {
            alert('✗ ' + response.message);
        }
    });
}
</script>
<?= $this->endSection() ?>
