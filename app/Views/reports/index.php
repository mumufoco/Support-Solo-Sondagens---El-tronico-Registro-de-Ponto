<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Relatórios<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Date Range Picker CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .report-card {
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        border-color: #0d6efd;
    }
    .report-card.selected {
        border-color: #0d6efd;
        background-color: #f0f8ff;
    }
    .report-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    .filter-section {
        display: none;
        margin-top: 2rem;
    }
    .filter-section.active {
        display: block;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-file-alt me-2"></i>Gerador de Relatórios</h2>
            <p class="text-muted">Selecione o tipo de relatório e configure os filtros</p>
        </div>
    </div>

    <!-- Step 1: Report Type Selection -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Passo 1: Selecione o Tipo de Relatório</h5>
        </div>
        <div class="card-body">
            <div class="row g-3" id="reportTypeCards">
                <!-- Folha de Ponto -->
                <div class="col-md-3">
                    <div class="card report-card h-100 text-center p-3" data-type="folha-ponto">
                        <div class="report-icon text-primary">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h6>Folha de Ponto</h6>
                        <small class="text-muted">Registro completo de marcações</small>
                    </div>
                </div>

                <!-- Horas Extras -->
                <div class="col-md-3">
                    <div class="card report-card h-100 text-center p-3" data-type="horas-extras">
                        <div class="report-icon text-success">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <h6>Horas Extras</h6>
                        <small class="text-muted">Relatório de horas extras</small>
                    </div>
                </div>

                <!-- Faltas e Atrasos -->
                <div class="col-md-3">
                    <div class="card report-card h-100 text-center p-3" data-type="faltas-atrasos">
                        <div class="report-icon text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h6>Faltas e Atrasos</h6>
                        <small class="text-muted">Absenteísmo e pontualidade</small>
                    </div>
                </div>

                <!-- Banco de Horas -->
                <div class="col-md-3">
                    <div class="card report-card h-100 text-center p-3" data-type="banco-horas">
                        <div class="report-icon text-info">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <h6>Banco de Horas</h6>
                        <small class="text-muted">Saldo de horas por funcionário</small>
                    </div>
                </div>

                <!-- Consolidado Mensal -->
                <div class="col-md-3">
                    <div class="card report-card h-100 text-center p-3" data-type="consolidado-mensal">
                        <div class="report-icon text-warning">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h6>Consolidado Mensal</h6>
                        <small class="text-muted">Resumo do período</small>
                    </div>
                </div>

                <!-- Justificativas -->
                <div class="col-md-3">
                    <div class="card report-card h-100 text-center p-3" data-type="justificativas">
                        <div class="report-icon text-secondary">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h6>Justificativas</h6>
                        <small class="text-muted">Relatório de justificativas</small>
                    </div>
                </div>

                <!-- Advertências -->
                <div class="col-md-3">
                    <div class="card report-card h-100 text-center p-3" data-type="advertencias">
                        <div class="report-icon text-dark">
                            <i class="fas fa-gavel"></i>
                        </div>
                        <h6>Advertências</h6>
                        <small class="text-muted">Registro de advertências</small>
                    </div>
                </div>

                <!-- Personalizado -->
                <div class="col-md-3">
                    <div class="card report-card h-100 text-center p-3" data-type="personalizado">
                        <div class="report-icon text-purple">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h6>Personalizado</h6>
                        <small class="text-muted">Relatório customizado</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Filters -->
    <div class="card mb-4 filter-section" id="filterSection">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Passo 2: Configure os Filtros</h5>
        </div>
        <div class="card-body">
            <form id="reportForm">
                <input type="hidden" name="type" id="reportType">

                <div class="row g-3">
                    <!-- Date Range -->
                    <div class="col-md-4">
                        <label class="form-label">Período</label>
                        <input type="text" class="form-control" id="dateRange" name="date_range" placeholder="Selecione o período">
                    </div>

                    <!-- Department Filter -->
                    <div class="col-md-4" id="departmentFilter">
                        <label class="form-label">Departamento</label>
                        <select class="form-select" name="department">
                            <option value="">Todos os departamentos</option>
                            <option value="TI">TI</option>
                            <option value="RH">RH</option>
                            <option value="Financeiro">Financeiro</option>
                            <option value="Vendas">Vendas</option>
                        </select>
                    </div>

                    <!-- Employee Filter (Multi-select with Select2) -->
                    <div class="col-md-4" id="employeeFilter">
                        <label class="form-label">Funcionários</label>
                        <select class="form-select" name="employee_ids[]" id="employeeSelect" multiple>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>

                    <!-- Status Filter (for justifications) -->
                    <div class="col-md-4" id="statusFilter" style="display: none;">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Todos</option>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="rejeitado">Rejeitado</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Step 3: Format Selection -->
    <div class="card mb-4 filter-section" id="formatSection">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-file-export me-2"></i>Passo 3: Escolha o Formato</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-primary w-100" onclick="generateReport('html')">
                        <i class="fas fa-eye d-block mb-2" style="font-size: 2rem;"></i>
                        Visualizar
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100" onclick="generateReport('pdf')">
                        <i class="fas fa-file-pdf d-block mb-2" style="font-size: 2rem;"></i>
                        PDF
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-success w-100" onclick="generateReport('excel')">
                        <i class="fas fa-file-excel d-block mb-2" style="font-size: 2rem;"></i>
                        Excel
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-info w-100" onclick="generateReport('csv')">
                        <i class="fas fa-file-csv d-block mb-2" style="font-size: 2rem;"></i>
                        CSV
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="generateReport('json')">
                        <i class="fas fa-code d-block mb-2" style="font-size: 2rem;"></i>
                        JSON
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="card filter-section" id="resultsSection">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Resultados</h5>
        </div>
        <div class="card-body">
            <div id="reportResults">
                <!-- Results will be displayed here -->
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Moment.js -->
<script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
<!-- Date Range Picker -->
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
let selectedType = null;

$(document).ready(function() {
    // Initialize date range picker
    $('#dateRange').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'De',
            toLabel: 'Até',
            customRangeLabel: 'Personalizado',
            daysOfWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
            monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            firstDay: 0
        },
        ranges: {
            'Este Mês': [moment().startOf('month'), moment().endOf('month')],
            'Mês Passado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Últimos 7 Dias': [moment().subtract(6, 'days'), moment()],
            'Últimos 30 Dias': [moment().subtract(29, 'days'), moment()],
            'Últimos 90 Dias': [moment().subtract(89, 'days'), moment()]
        },
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month')
    });

    // Initialize Select2 for employees
    $('#employeeSelect').select2({
        placeholder: 'Selecione funcionários',
        ajax: {
            url: '<?= base_url('api/employees') ?>',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(emp => ({
                        id: emp.id,
                        text: emp.name + ' - ' + emp.department
                    }))
                };
            }
        },
        allowClear: true
    });

    // Report type selection
    $('.report-card').on('click', function() {
        $('.report-card').removeClass('selected');
        $(this).addClass('selected');

        selectedType = $(this).data('type');
        $('#reportType').val(selectedType);

        // Show filter section
        $('#filterSection').addClass('active');
        $('#formatSection').addClass('active');

        // Show/hide specific filters
        if (selectedType === 'justificativas') {
            $('#statusFilter').show();
        } else {
            $('#statusFilter').hide();
        }

        // Scroll to filters
        $('html, body').animate({
            scrollTop: $('#filterSection').offset().top - 100
        }, 500);
    });
});

function generateReport(format) {
    if (!selectedType) {
        alert('Por favor, selecione um tipo de relatório');
        return;
    }

    // Get filters
    const dateRange = $('#dateRange').data('daterangepicker');
    const filters = {
        start_date: dateRange.startDate.format('YYYY-MM-DD'),
        end_date: dateRange.endDate.format('YYYY-MM-DD'),
        department: $('[name="department"]').val(),
        employee_ids: $('#employeeSelect').val(),
        status: $('[name="status"]').val()
    };

    // Show loading
    $('#reportResults').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3">Gerando relatório...</p></div>');
    $('#resultsSection').addClass('active');

    // Make AJAX request
    $.ajax({
        url: '<?= base_url('reports/generate') ?>',
        method: 'POST',
        data: {
            type: selectedType,
            format: format,
            filters: filters
        },
        xhrFields: {
            responseType: format === 'html' ? 'json' : 'blob'
        },
        success: function(response) {
            if (format === 'html') {
                displayResults(response);
            } else {
                // File download handled by browser
                if (response.queued) {
                    alert(response.message);
                }
            }
        },
        error: function(xhr) {
            $('#reportResults').html('<div class="alert alert-danger">Erro ao gerar relatório. Tente novamente.</div>');
        }
    });
}

function displayResults(response) {
    if (!response.success) {
        $('#reportResults').html('<div class="alert alert-danger">' + response.error + '</div>');
        return;
    }

    const data = response.data;

    if (!data || data.length === 0) {
        $('#reportResults').html('<div class="alert alert-info">Nenhum resultado encontrado para os filtros selecionados.</div>');
        return;
    }

    // Create table
    let html = '<div class="table-responsive">';
    html += '<table class="table table-striped table-hover">';
    html += '<thead class="table-light"><tr>';

    // Headers (dynamic based on first record)
    const keys = Object.keys(data[0]);
    keys.forEach(key => {
        html += '<th>' + key.replace(/_/g, ' ').toUpperCase() + '</th>';
    });

    html += '</tr></thead><tbody>';

    // Rows
    data.forEach(row => {
        html += '<tr>';
        keys.forEach(key => {
            html += '<td>' + (row[key] || '-') + '</td>';
        });
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    html += '<p class="text-muted mt-3">Total de registros: ' + data.length + '</p>';

    if (response.cached) {
        html += '<p class="text-info"><i class="fas fa-info-circle"></i> Resultado em cache (válido por 1 hora)</p>';
    }

    $('#reportResults').html(html);
}
</script>
<?= $this->endSection() ?>
