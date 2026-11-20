<?php $mesesPt = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
            $ano = $ano ?? (int)date('Y');
            $mes = $mes ?? date('m');
            $de = $de ?? '';
            $ate = $ate ?? '';
            $vendasResumo = $vendasResumo ?? ['qtd' => 0, 'total' => 0, 'ticket' => 0];
            $criadosResumo = $criadosResumo ?? ['qtd' => 0, 'total' => 0];
            $recebidoPeriodo = $recebidoPeriodo ?? 0;
            $caixaEntradas = $caixaEntradas ?? 0;
            $vendasLista = $vendasLista ?? [];
            $vendasPorMes = $vendasPorMes ?? [];
            $vendasAnos = $vendasAnos ?? [(int)date('Y')];
            $periodoLabel = $periodoLabel ?? '';
        ?>
            <div class="panel mb-4">
                <h5 class="fw-bold mb-3">Filtrar período</h5>
                <form method="get" class="row g-3 align-items-end">
                    <input type="hidden" name="page" value="vendas">
                    <div class="col-md-2">
                        <label class="form-label">Ano</label>
                        <select class="form-select" name="ano">
                            <?php foreach ($vendasAnos as $y): ?>
                                <option value="<?= (int)$y ?>" <?= (int)$y === (int)$ano ? 'selected' : '' ?>><?= (int)$y ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mês</label>
                        <select class="form-select" name="mes">
                            <option value="todos" <?= $mes === 'todos' ? 'selected' : '' ?>>Ano inteiro</option>
                            <?php for ($i = 1; $i <= 12; $i++): $mv = str_pad((string)$i, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= $mv ?>" <?= $mes === $mv ? 'selected' : '' ?>><?= $mesesPt[$i] ?></option>
                            <?php endfor ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">De (opcional)</label>
                        <input class="form-control" type="date" name="de" value="<?= e($de) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Até (opcional)</label>
                        <input class="form-control" type="date" name="ate" value="<?= e($ate) ?>">
                    </div>
                    <div class="col-md-4 d-flex gap-2 align-items-end">
                        <button class="btn btn-success" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                        <a class="btn btn-outline-secondary" href="?page=vendas">Mês atual</a>
                    </div>
                    <div class="col-12"><small class="text-secondary">Se preencher <strong>De</strong> e <strong>Até</strong>, o período personalizado tem prioridade sobre ano/mês. Vendas = orçamentos <strong>aprovados</strong> no período.</small></div>
                </form>
            </div>

            <div class="row g-3 mb-4">
                <?php foreach (
                    [
                        ['Vendido (aprovado)', money($vendasResumo['total']), (int)$vendasResumo['qtd'] . ' orçamento(s) · ' . e($periodoLabel)],
                        ['Ticket médio', money($vendasResumo['ticket']), 'Valor médio por venda'],
                        ['Orçamentos criados', money($criadosResumo['total']), (int)$criadosResumo['qtd'] . ' criado(s) no período'],
                        ['Recebido (contas)', money($recebidoPeriodo), 'Pagamentos em contas a receber'],
                        ['Entradas no caixa', money($caixaEntradas), 'Lançamentos de entrada'],
                    ] as $st
                ): ?>
                    <div class="col-6 col-xl">
                        <div class="stat h-100">
                            <div class="label"><?= e($st[0]) ?></div>
                            <div class="value" style="font-size:1.35rem"><?= $st[1] ?></div>
                            <div class="hint"><?= $st[2] ?></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-7">
                    <div class="panel h-100">
                        <h5 class="fw-bold">Vendas aprovadas por mês — <?= (int)($anoSerie ?? $ano) ?></h5>
                        <p class="small text-secondary mb-3">Comparativo mensal do ano (orçamentos aprovados)</p>
                        <canvas id="chartVendasMes" height="130"></canvas>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="panel h-100">
                        <h5 class="fw-bold">Resumo mensal</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mês</th>
                                        <th>Qtd</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $totQ = 0;
                                    $totV = 0;
                                    foreach ($vendasPorMes as $pm):
                                        $totQ += (int)$pm['qtd'];
                                        $totV += (float)$pm['total'];
                                        $destaque = ($mes !== 'todos' && (int)$mes === (int)$pm['m']);
                                    ?>
                                        <tr class="<?= $destaque ? 'table-primary' : '' ?>" style="<?= $destaque ? 'background:var(--brand-50)' : '' ?>">
                                            <td><?= e($mesesPt[(int)$pm['m']]) ?></td>
                                            <td><?= (int)$pm['qtd'] ?></td>
                                            <td><?= money($pm['total']) ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                    <tr class="fw-bold">
                                        <td>Total <?= (int)($anoSerie ?? $ano) ?></td>
                                        <td><?= $totQ ?></td>
                                        <td><?= money($totV) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0">Vendas no período · <?= e($periodoLabel) ?></h5>
                    <span class="text-secondary small"><?= count($vendasLista) ?> registro(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nº</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Aprovado em</th>
                                <th>Criado em</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$vendasLista): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-secondary py-4">Nenhuma venda aprovada neste período.</td>
                                </tr>
                            <?php endif ?>
                            <?php foreach ($vendasLista as $v): ?>
                                <tr>
                                    <td>#<?= str_pad((string)$v['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= e($v['cliente']) ?></td>
                                    <td><?= e(mb_strimwidth($v['descricao'] ?? '', 0, 80, '…')) ?></td>
                                    <td class="fw-semibold"><?= money($v['valor']) ?></td>
                                    <td><?= $v['atualizado_em'] ? date('d/m/Y', strtotime($v['atualizado_em'])) : '—' ?></td>
                                    <td><?= date('d/m/Y', strtotime($v['criado_em'])) ?></td>
                                    <td><a class="btn btn-sm btn-outline-primary btn-icon" target="_blank" href="?page=orcamento_pdf&id=<?= (int)$v['id'] ?>" title="PDF"><i class="bi bi-file-pdf"></i></a></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                (function() {
                    const labels = <?= json_encode(array_map(fn($i) => $mesesPt[$i], range(1, 12)), JSON_UNESCAPED_UNICODE) ?>;
                    const qtd = <?= json_encode(array_map(fn($r) => (int)$r['qtd'], $vendasPorMes)) ?>;
                    const tot = <?= json_encode(array_map(fn($r) => (float)$r['total'], $vendasPorMes)) ?>;
                    const el = document.getElementById('chartVendasMes');
                    if (!el || typeof Chart === 'undefined') return;
                    new Chart(el, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                    label: 'Qtd. vendas',
                                    data: qtd,
                                    backgroundColor: 'rgba(62, 64, 149, 0.8)',
                                    borderRadius: 8,
                                    yAxisID: 'y'
                                },
                                {
                                    label: 'Valor (R$)',
                                    data: tot,
                                    type: 'line',
                                    borderColor: '#00AFEF',
                                    backgroundColor: 'rgba(0, 175, 239, 0.14)',
                                    tension: 0.35,
                                    fill: true,
                                    yAxisID: 'y1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        drawOnChartArea: false
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                })();
            </script>

