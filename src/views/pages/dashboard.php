<?php if (can(['admin'])): ?><div class="row g-3 mb-4"><?php foreach ([['Clientes', $stats['clientes'], 'Cadastros no sistema'], ['Orçamentos', $stats['orcamentos'], 'Todos os criados'], ['Aprovados', $stats['aprovados'], money($stats['totalaprov']) . ' aprovados'], ['Pendentes', $stats['pendentes'], 'Aguardando resposta'], ['Entradas do mês', money($stats['entradas']), 'Caixa no mês atual'], ['Saídas do mês', money($stats['saidas']), 'Caixa no mês atual'], ['A receber', money($stats['receber_aberto'] ?? 0), 'Vencido: ' . money($stats['receber_vencido'] ?? 0)], ['A pagar', money($stats['pagar_aberto'] ?? 0), 'Vencido: ' . money($stats['pagar_vencido'] ?? 0)]] as $st): ?><div class="col-6 col-xl-3">
                            <div class="stat h-100">
                                <div class="label"><?= e($st[0]) ?></div>
                                <div class="value"><?= e($st[1]) ?></div>
                                <div class="hint"><?= e($st[2]) ?></div>
                            </div>
                        </div><?php endforeach ?></div>
                <div class="row g-3 mb-4">
                    <div class="col-xl-8">
                        <div class="panel h-100">
                            <h5 class="fw-bold">Evolução dos orçamentos</h5>
                            <p class="small text-secondary">Quantidade e valores por mês (últimos 6 meses)</p><canvas id="chartOrc" height="115"></canvas>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="panel h-100">
                            <h5 class="fw-bold">Status dos orçamentos</h5><canvas id="chartStatus" height="200"></canvas>
                            <div class="small text-secondary mt-2">Valor total cadastrado: <strong><?= money($stats['totalorc']) ?></strong><br>Ordens abertas: <strong><?= $stats['os'] ?></strong></div>
                        </div>
                    </div>
                </div>
                <script>
                    new Chart(document.getElementById('chartOrc'), {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode(array_column($chart, 'mes')) ?>,
                            datasets: [{
                                label: 'Orçamentos',
                                data: <?= json_encode(array_map('intval', array_column($chart, 'qtd'))) ?>,
                                backgroundColor: '#00AFEF',
                                yAxisID: 'y'
                            }, {
                                label: 'Valor aprovado (R$)',
                                data: <?= json_encode(array_map('floatval', array_column($chart, 'aprovado'))) ?>,
                                type: 'line',
                                borderColor: '#3E4095',
                                backgroundColor: '#3E4095',
                                yAxisID: 'y1',
                                tension: .3
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        drawOnChartArea: false
                                    }
                                }
                            }
                        }
                    });
                    new Chart(document.getElementById('chartStatus'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Aprovados', 'Pendentes', 'Outros'],
                            datasets: [{
                                data: [<?= $stats['aprovados'] ?>, <?= $stats['pendentes'] ?>, <?= max(0, $stats['orcamentos'] - $stats['aprovados'] - $stats['pendentes']) ?>],
                                backgroundColor: ['#00AFEF', '#3E4095', '#e62129'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            cutout: '68%',
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                </script><?php elseif (can(['vendedor'])): ?><div class="row g-3 mb-4"><?php foreach ([['Orçamentos', $stats['orcamentos']], ['Pendentes', $stats['pendentes']], ['Ordens em andamento', $stats['os']]] as $st): ?><div class="col-md-4">
                            <div class="stat">
                                <div class="label"><?= e($st[0]) ?></div>
                                <div class="value"><?= e($st[1]) ?></div>
                            </div>
                        </div><?php endforeach ?></div><?php else: ?><div class="panel mb-4">
                    <h5 class="fw-bold">Área de produção</h5>
                    <p class="text-secondary">Acompanhe suas ordens de serviço e atualize o andamento da produção.</p><a class="btn btn-success" href="?page=producao">Abrir fila de produção</a>
                </div><?php endif ?><?php if (can(['admin', 'vendedor'])): ?><div class="panel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Orçamentos recentes</h5><?php if (can(['admin', 'vendedor'])): ?><a class="btn btn-sm btn-success" href="?page=orcamentos">Ver orçamentos</a><?php endif ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Cliente</th>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody><?php foreach ($recent as $r): ?><tr>
                                        <td>#<?= $r['id'] ?></td>
                                        <td><?= e($r['cliente']) ?></td>
                                        <td><?= e($r['descricao']) ?></td>
                                        <td><?= money($r['valor']) ?></td>
                                        <td><span class="badge text-bg-<?= ['aprovado' => 'success', 'pendente' => 'warning', 'rejeitado' => 'danger', 'cancelado' => 'secondary'][$r['status']] ?? 'secondary' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($r['criado_em'])) ?></td>
                                    </tr><?php endforeach ?><?php if (!$recent): ?><tr>
                                        <td colspan="6" class="text-center text-secondary py-4">Ainda não há orçamentos cadastrados.</td>
                                    </tr><?php endif ?></tbody>
                        </table>
                    </div>
                </div><?php endif ?>
