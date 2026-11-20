<?php $periodoQuery = $periodoQuery ?? '';
            $periodoLabel = $periodoLabel ?? 'Todos os períodos';
            $filtroAno = $filtroAno ?? '';
            $filtroMes = $filtroMes ?? '';
            $filtroDe = $filtroDe ?? '';
            $filtroAte = $filtroAte ?? '';
            $anosOpts = range((int)date('Y'), (int)date('Y') - 6);
            $mesesPt = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
        ?>
            <div class="panel mb-4">
                <h5 class="fw-bold mb-3">Filtrar período</h5>
                <form method="get" class="row g-3 align-items-end">
                    <input type="hidden" name="page" value="contas_receber">
                    <input type="hidden" name="filtro" value="<?= e($filtro ?? 'abertas') ?>">
                    <div class="col-md-2">
                        <label class="form-label">Ano</label>
                        <select class="form-select" name="ano">
                            <option value="">—</option>
                            <?php foreach ($anosOpts as $y): ?>
                                <option value="<?= $y ?>" <?= (string)$filtroAno === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mês</label>
                        <select class="form-select" name="mes">
                            <option value="todos" <?= $filtroMes === 'todos' || $filtroMes === '' ? 'selected' : '' ?>>Ano inteiro</option>
                            <?php for ($i = 1; $i <= 12; $i++): $mv = str_pad((string)$i, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= $mv ?>" <?= $filtroMes === $mv ? 'selected' : '' ?>><?= $mesesPt[$i] ?></option>
                            <?php endfor ?>
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">De</label><input class="form-control" type="date" name="de" value="<?= e((string)$filtroDe) ?>"></div>
                    <div class="col-md-2"><label class="form-label">Até</label><input class="form-control" type="date" name="ate" value="<?= e((string)$filtroAte) ?>"></div>
                    <div class="col-md-4 d-flex gap-2 flex-wrap align-items-end">
                        <button class="btn btn-success" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                        <a class="btn btn-outline-secondary" href="?page=contas_receber&filtro=<?= e($filtro ?? 'abertas') ?>">Limpar período</a>
                    </div>
                    <div class="col-12"><small class="text-secondary">Período ativo: <strong><?= e($periodoLabel) ?></strong>. Datas personalizadas têm prioridade. A lista usa vencimento (ou data de pagamento em “Pagas”).</small></div>
                </form>
            </div>
            <div class="row g-3 mb-4">
                <?php foreach (
                    [
                        ['Em aberto', money($resumo['aberto'] ?? 0), (int)($resumo['qtd_aberto'] ?? 0) . ' contas'],
                        ['Vencido', money($resumo['vencido'] ?? 0), 'Atenção aos prazos'],
                        ['Recebido no período', money($resumo['recebido_periodo'] ?? $resumo['recebido'] ?? 0), e($periodoLabel)],
                        ['Previsto no período', money($resumo['previsto_periodo'] ?? 0), 'Pelos vencimentos'],
                        ['Aberto no período', money($resumo['aberto_periodo'] ?? 0), 'Saldo em aberto'],
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
            <div class="panel mb-4">
                <h5 class="fw-bold">Nova conta a receber</h5>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="conta_receber_save">
                    <div class="col-md-3">
                        <label class="form-label">Cliente</label>
                        <select class="form-select" name="cliente_id">
                            <option value="">— opcional —</option>
                            <?php foreach ($clientes as $cl): ?><option value="<?= (int)$cl['id'] ?>"><?= e($cl['nome']) ?></option><?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Descrição</label><input class="form-control" name="descricao" required placeholder="Ex: Sinal do orçamento #12"></div>
                    <div class="col-md-2"><label class="form-label">Valor (R$)</label><input class="form-control" name="valor" type="number" min="0.01" step="0.01" required></div>
                    <div class="col-md-3"><label class="form-label">Emissão</label><input class="form-control" name="data_emissao" type="date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-3"><label class="form-label">Vencimento</label><input class="form-control" name="data_vencimento" type="date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Observações</label><input class="form-control" name="observacoes" placeholder="Opcional"></div>
                    <div class="col-12"><button class="btn btn-success">Cadastrar conta</button></div>
                </form>
            </div>
            <div class="panel">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Lista de contas</h5>
                    <div class="btn-group btn-group-sm">
                        <?php foreach (['abertas' => 'Abertas', 'vencidas' => 'Vencidas', 'pagas' => 'Pagas', 'canceladas' => 'Canceladas', 'todas' => 'Todas'] as $k => $lab): ?>
                            <a class="btn btn-outline-primary <?= ($filtro ?? 'abertas') === $k ? 'active' : '' ?>" href="?page=contas_receber&filtro=<?= $k ?><?= $periodoQuery ?>"><?= $lab ?></a>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Pago</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?><tr>
                                    <td colspan="8" class="text-secondary">Nenhuma conta neste filtro.</td>
                                </tr><?php endif ?>
                            <?php foreach ($rows as $r):
                                $saldo = (float)$r['valor'] - (float)$r['valor_pago'];
                                $vencido = in_array($r['status'], ['pendente', 'parcial'], true) && $r['data_vencimento'] < date('Y-m-d');
                                $badge = match ($r['status']) {
                                    'pago' => 'success',
                                    'parcial' => 'warning',
                                    'cancelado' => 'secondary',
                                    default => ($vencido ? 'danger' : 'primary'),
                                };
                            ?>
                                <tr class="<?= $vencido ? 'table-danger' : '' ?>">
                                    <td><?= (int)$r['id'] ?><?php if ($r['orcamento_id']): ?><br><small class="text-secondary">Orç. #<?= (int)$r['orcamento_id'] ?></small><?php endif ?></td>
                                    <td><?= e($r['cliente'] ?? '—') ?></td>
                                    <td><?= e($r['descricao']) ?></td>
                                    <td><?= money($r['valor']) ?></td>
                                    <td><?= money($r['valor_pago']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['data_vencimento'])) ?><?php if ($vencido): ?><br><span class="badge text-bg-danger">vencida</span><?php endif ?></td>
                                    <td><span class="badge text-bg-<?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                                    <td>
                                        <?php if ($r['status'] === 'pendente' && (float)$r['valor_pago'] == 0.0): ?><button type="button" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="modal" data-bs-target="#editarReceber<?= (int)$r['id'] ?>" title="Visualizar / editar" aria-label="Visualizar / editar"><i class="bi bi-pencil-square"></i></button>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Cancelar esta conta?')"><input type="hidden" name="action" value="conta_receber_delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon" title="Excluir / cancelar" aria-label="Excluir / cancelar"><i class="bi bi-trash"></i></button></form>
                                            <div class="modal fade" id="editarReceber<?= (int)$r['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="post">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Editar conta a receber</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body"><input type="hidden" name="action" value="conta_receber_update"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><label class="form-label">Cliente</label><select class="form-select mb-2" name="cliente_id">
                                                                    <option value="">Sem cliente</option><?php foreach ($clientes as $cl): ?><option value="<?= (int)$cl['id'] ?>" <?= (int)($r['cliente_id'] ?? 0) === (int)$cl['id'] ? 'selected' : '' ?>><?= e($cl['nome']) ?></option><?php endforeach ?>
                                                                </select><label class="form-label">Descrição</label><input class="form-control mb-2" name="descricao" value="<?= e($r['descricao']) ?>" required><label class="form-label">Valor</label><input class="form-control mb-2" name="valor" type="number" min="0.01" step="0.01" value="<?= e($r['valor']) ?>" required><label class="form-label">Emissão</label><input class="form-control mb-2" name="data_emissao" type="date" value="<?= e($r['data_emissao']) ?>" required><label class="form-label">Vencimento</label><input class="form-control mb-2" name="data_vencimento" type="date" value="<?= e($r['data_vencimento']) ?>" required><label class="form-label">Observações</label><textarea class="form-control" name="observacoes"><?= e($r['observacoes'] ?? '') ?></textarea></div>
                                                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-success">Salvar</button></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div><?php endif ?>

                                        <?php if (in_array($r['status'], ['pendente', 'parcial'], true)): ?>
                                            <form method="post" class="d-inline-flex flex-wrap gap-1 align-items-center">
                                                <input type="hidden" name="action" value="conta_receber_pagar">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <input class="form-control form-control-sm" style="width:100px" name="valor_pago" type="number" min="0.01" step="0.01" value="<?= number_format($saldo, 2, '.', '') ?>" title="Valor a receber agora">
                                                <input class="form-control form-control-sm" style="width:130px" name="data_pagamento" type="date" value="<?= date('Y-m-d') ?>">
                                                <label class="small text-nowrap"><input type="checkbox" name="registrar_caixa" value="1" checked> Caixa</label>
                                                <button class="btn btn-sm btn-success">Receber</button>
                                            </form>
                                            <?php if (can(['admin'])): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Cancelar esta conta?')">
                                                    <input type="hidden" name="action" value="conta_receber_cancelar">
                                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-secondary">Cancelar</button>
                                                </form>
                                            <?php endif ?>
                                        <?php else: ?>
                                            <span class="text-secondary small"><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '—' ?></span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>

