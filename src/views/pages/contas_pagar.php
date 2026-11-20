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
                    <input type="hidden" name="page" value="contas_pagar">
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
                        <a class="btn btn-outline-secondary" href="?page=contas_pagar&filtro=<?= e($filtro ?? 'abertas') ?>">Limpar período</a>
                    </div>
                    <div class="col-12"><small class="text-secondary">Período ativo: <strong><?= e($periodoLabel) ?></strong>. Datas personalizadas têm prioridade. A lista usa vencimento (ou data de pagamento em “Pagas”).</small></div>
                </form>
            </div>
            <div class="row g-3 mb-4">
                <?php foreach (
                    [
                        ['Em aberto', money($resumo['aberto'] ?? 0), (int)($resumo['qtd_aberto'] ?? 0) . ' contas'],
                        ['Vencido', money($resumo['vencido'] ?? 0), 'Atenção aos prazos'],
                        ['Pago no período', money($resumo['pago_periodo'] ?? $resumo['pago_total'] ?? 0), e($periodoLabel)],
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
                <h5 class="fw-bold">Nova conta a pagar</h5>
                <form method="post" class="row g-3" id="formContaPagar">
                    <input type="hidden" name="action" value="conta_pagar_save">
                    <div class="col-md-3"><label class="form-label">Fornecedor</label><input class="form-control" name="fornecedor" placeholder="Opcional"></div>
                    <div class="col-md-4"><label class="form-label">Descrição</label><input class="form-control" name="descricao" required placeholder="Ex: Aluguel, material..."></div>
                    <div class="col-md-2"><label class="form-label">Categoria</label><input class="form-control" name="categoria" placeholder="Despesa, material..."></div>
                    <div class="col-md-3"><label class="form-label">Valor total (R$)</label><input class="form-control" name="valor" id="cpValor" type="number" min="0.01" step="0.01" required></div>
                    <div class="col-md-3"><label class="form-label">Emissão</label><input class="form-control" name="data_emissao" type="date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-3"><label class="form-label">1º vencimento</label><input class="form-control" name="data_vencimento" id="cpVencimento" type="date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Qtd. parcelas</label><input class="form-control" name="parcelas" id="cpParcelas" type="number" min="1" max="120" value="1" required></div>
                    <div class="col-md-2">
                        <label class="form-label">Periodicidade</label>
                        <select class="form-select" name="periodicidade" id="cpPeriodicidade">
                            <option value="mensal" selected>Mensal (mesmo dia)</option>
                            <option value="personalizado">Personalizado (dias)</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="cpIntervaloWrap">
                        <label class="form-label">Intervalo (dias)</label>
                        <input class="form-control" name="intervalo_dias" id="cpIntervaloDias" type="number" min="1" max="365" value="30" title="Usado só no modo personalizado">
                    </div>
                    <div class="col-md-3"><label class="form-label">Modo de pagamento</label>
                        <select class="form-select" name="modo_pagamento">
                            <option value="pix">PIX</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao_credito">Cartão de crédito</option>
                            <option value="cartao_debito">Cartão de débito</option>
                            <option value="boleto">Boleto</option>
                            <option value="transferencia">Transferência bancária</option>
                            <option value="debito_automatico">Débito automático</option>
                            <option value="cheque">Cheque</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Observações</label><input class="form-control" name="observacoes" placeholder="Opcional"></div>
                    <div class="col-12">
                        <div class="parcelas-preview" id="cpPreview" style="display:none">
                            <h6>Prévia das parcelas</h6>
                            <div class="parcelas-preview-list" id="cpPreviewList"></div>
                        </div>
                    </div>
                    <div class="col-12"><small class="text-secondary">O valor informado é o total e será dividido entre as parcelas. Cada parcela fica com vencimento próprio e pode ser paga separadamente.</small></div>
                    <div class="col-12"><button class="btn btn-success" type="submit">Cadastrar conta</button></div>
                </form>
            </div>
            <div class="panel">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Lista de contas</h5>
                    <div class="btn-group btn-group-sm">
                        <?php foreach (['abertas' => 'Abertas', 'vencidas' => 'Vencidas', 'pagas' => 'Pagas', 'canceladas' => 'Canceladas', 'todas' => 'Todas'] as $k => $lab): ?>
                            <a class="btn btn-outline-primary <?= ($filtro ?? 'abertas') === $k ? 'active' : '' ?>" href="?page=contas_pagar&filtro=<?= $k ?><?= $periodoQuery ?>"><?= $lab ?></a>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fornecedor</th>
                                <th>Descrição / parcela</th>
                                <th>Categoria</th>
                                <th>Valor</th>
                                <th>Pago</th>
                                <th>Vencimento</th>
                                <th>Pagamento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?><tr>
                                    <td colspan="10" class="text-secondary">Nenhuma conta neste filtro.</td>
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
                                    <td><?= (int)$r['id'] ?></td>
                                    <td><?= e($r['fornecedor'] ?? '—') ?></td>
                                    <td>
                                        <?= e($r['descricao']) ?>
                                        <?php if (!empty($r['total_parcelas']) && (int)$r['total_parcelas'] > 1): ?>
                                            <div class="parcela-info">
                                                <span class="badge text-bg-info"><?= (int)$r['numero_parcela'] ?>/<?= (int)$r['total_parcelas'] ?></span>
                                                <?php
                                                $dias = (int)($r['intervalo_dias'] ?? 0);
                                                if ($dias > 0) {
                                                    $perLab = 'a cada ' . $dias . ' dia' . ($dias > 1 ? 's' : '');
                                                } else {
                                                    $perLab = match ($r['periodicidade'] ?? 'mensal') {
                                                        'semanal' => 'a cada 7 dias',
                                                        'quinzenal' => 'a cada 15 dias',
                                                        'bimestral' => 'bimestral',
                                                        'trimestral' => 'trimestral',
                                                        'semestral' => 'semestral',
                                                        'anual' => 'anual',
                                                        default => 'mensal',
                                                    };
                                                }
                                                ?>
                                                <span><?= e($perLab) ?></span>
                                            </div>
                                        <?php endif ?>
                                    </td>
                                    <td><?= e($r['categoria'] ?? '—') ?></td>
                                    <td><?= money($r['valor']) ?></td>
                                    <td><?= money($r['valor_pago']) ?><?php if ($r['status'] === 'parcial'): ?><div class="small text-secondary">falta <?= money($saldo) ?></div><?php endif ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['data_vencimento'])) ?><?php if ($vencido): ?><br><span class="badge text-bg-danger">vencida</span><?php endif ?></td>
                                    <td><?php
                                        $modos = ['pix' => 'PIX', 'dinheiro' => 'Dinheiro', 'cartao_credito' => 'Crédito', 'cartao_debito' => 'Débito', 'boleto' => 'Boleto', 'transferencia' => 'Transferência', 'debito_automatico' => 'Déb. auto', 'cheque' => 'Cheque', 'outro' => 'Outro'];
                                        echo e($modos[$r['modo_pagamento'] ?? 'pix'] ?? '—');
                                        ?></td>
                                    <td><span class="badge text-bg-<?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                                    <td>
                                        <?php if ($r['status'] === 'pendente' && (float)$r['valor_pago'] == 0.0): ?><button type="button" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="modal" data-bs-target="#editarPagar<?= (int)$r['id'] ?>" title="Visualizar / editar" aria-label="Visualizar / editar"><i class="bi bi-pencil-square"></i></button>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Cancelar esta conta?')"><input type="hidden" name="action" value="conta_pagar_delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon" title="Excluir / cancelar" aria-label="Excluir / cancelar"><i class="bi bi-trash"></i></button></form>
                                            <div class="modal fade" id="editarPagar<?= (int)$r['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="post">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Editar conta a pagar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body"><input type="hidden" name="action" value="conta_pagar_update"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><label class="form-label">Fornecedor</label><input class="form-control mb-2" name="fornecedor" value="<?= e($r['fornecedor'] ?? '') ?>"><label class="form-label">Descrição</label><input class="form-control mb-2" name="descricao" value="<?= e($r['descricao']) ?>" required><label class="form-label">Categoria</label><input class="form-control mb-2" name="categoria" value="<?= e($r['categoria'] ?? '') ?>"><label class="form-label">Valor</label><input class="form-control mb-2" name="valor" type="number" min="0.01" step="0.01" value="<?= e($r['valor']) ?>" required><label class="form-label">Emissão</label><input class="form-control mb-2" name="data_emissao" type="date" value="<?= e($r['data_emissao']) ?>" required><label class="form-label">Vencimento</label><input class="form-control mb-2" name="data_vencimento" type="date" value="<?= e($r['data_vencimento']) ?>" required><label class="form-label">Observações</label><textarea class="form-control" name="observacoes"><?= e($r['observacoes'] ?? '') ?></textarea></div>
                                                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-success">Salvar</button></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div><?php endif ?>
                                        <?php if (in_array($r['status'], ['pendente', 'parcial'], true)): ?>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#pagamentos<?= (int)$r['id'] ?>"><i class="bi bi-cash-coin"></i> Pagar / Vale</button>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Cancelar esta conta?')">
                                                <input type="hidden" name="action" value="conta_pagar_cancelar">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <button class="btn btn-sm btn-outline-secondary">Cancelar</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-secondary small"><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '—' ?></span>
                                            <?php if ((float)$r['valor_pago'] > 0): ?><button type="button" class="btn btn-sm btn-outline-secondary btn-icon" data-bs-toggle="modal" data-bs-target="#pagamentos<?= (int)$r['id'] ?>" title="Ver pagamentos" aria-label="Ver pagamentos"><i class="bi bi-receipt"></i></button><?php endif ?>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>

                <?php
                $tiposPag = ['pagamento' => 'Pagamento', 'vale' => 'Vale', 'adiantamento' => 'Adiantamento'];
                $formasPag = ['pix' => 'PIX', 'dinheiro' => 'Dinheiro', 'cartao_credito' => 'Cartão de crédito', 'cartao_debito' => 'Cartão de débito', 'boleto' => 'Boleto', 'transferencia' => 'Transferência', 'debito_automatico' => 'Débito automático', 'cheque' => 'Cheque', 'outro' => 'Outro'];
                foreach ($rows as $r):
                    $pagoConta = (float)$r['valor_pago'];
                    if ($r['status'] === 'cancelado' && $pagoConta <= 0) continue;
                    $idc = (int)$r['id'];
                    $faltaConta = max(0.0, (float)$r['valor'] - $pagoConta);
                    $hist = ($historico ?? [])[$idc] ?? [];
                    $somaHist = 0.0;
                    foreach ($hist as $h) $somaHist += (float)$h['valor'];
                    $anterior = round($pagoConta - $somaHist, 2);
                    $perc = (float)$r['valor'] > 0 ? (int)min(100, round($pagoConta / (float)$r['valor'] * 100)) : 0;
                    $aberta = in_array($r['status'], ['pendente', 'parcial'], true);
                ?>
                    <div class="modal fade" id="pagamentos<?= $idc ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h5 class="modal-title mb-0">Pagamentos da conta</h5>
                                        <div class="small text-secondary"><?= e($r['descricao']) ?><?= $r['fornecedor'] ? ' — ' . e($r['fornecedor']) : '' ?></div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-2 text-center mb-2">
                                        <div class="col-4"><div class="small text-secondary">Total</div><div class="fw-bold"><?= money($r['valor']) ?></div></div>
                                        <div class="col-4"><div class="small text-secondary">Já pago</div><div class="fw-bold text-success"><?= money($pagoConta) ?></div></div>
                                        <div class="col-4"><div class="small text-secondary">Falta pagar</div><div class="fw-bold text-danger"><?= money($faltaConta) ?></div></div>
                                    </div>
                                    <div class="progress mb-3" style="height:8px" role="progressbar" aria-valuenow="<?= $perc ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-success" style="width:<?= $perc ?>%"></div>
                                    </div>

                                    <h6 class="fw-bold">Histórico</h6>
                                    <?php if (!$hist && $anterior <= 0.009): ?>
                                        <p class="text-secondary small">Nenhum pagamento lançado ainda.</p>
                                    <?php else: ?>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead><tr><th>Data</th><th>Tipo</th><th>Forma</th><th>Obs.</th><th class="text-end">Valor</th><th></th></tr></thead>
                                                <tbody>
                                                    <?php if ($anterior > 0.009): ?>
                                                        <tr class="text-secondary">
                                                            <td>—</td><td colspan="3">Pagamentos anteriores ao histórico</td>
                                                            <td class="text-end"><?= money($anterior) ?></td><td></td>
                                                        </tr>
                                                    <?php endif ?>
                                                    <?php foreach ($hist as $h): ?>
                                                        <tr>
                                                            <td><?= date('d/m/Y', strtotime($h['data_pagamento'])) ?></td>
                                                            <td><span class="badge text-bg-<?= $h['tipo'] === 'vale' ? 'warning' : ($h['tipo'] === 'adiantamento' ? 'info' : 'secondary') ?>"><?= e($tiposPag[$h['tipo']] ?? ucfirst((string)$h['tipo'])) ?></span></td>
                                                            <td><?= e($formasPag[$h['forma'] ?? ''] ?? '—') ?></td>
                                                            <td><?= e($h['observacao'] ?? '') ?></td>
                                                            <td class="text-end"><?= money($h['valor']) ?></td>
                                                            <td class="text-end">
                                                                <?php if ($r['status'] !== 'cancelado'): ?>
                                                                    <form method="post" class="d-inline m-0" onsubmit="return confirm('Estornar este lançamento? A saída correspondente no caixa (se houver) também será removida.')">
                                                                        <input type="hidden" name="action" value="conta_pagar_estornar">
                                                                        <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                                                                        <button class="btn btn-sm btn-outline-danger btn-icon" title="Estornar" aria-label="Estornar"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                                    </form>
                                                                <?php endif ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif ?>

                                    <?php if ($aberta): ?>
                                        <h6 class="fw-bold">Novo lançamento</h6>
                                        <form method="post" class="row g-2">
                                            <input type="hidden" name="action" value="conta_pagar_pagar">
                                            <input type="hidden" name="id" value="<?= $idc ?>">
                                            <div class="col-md-5">
                                                <label class="form-label">Valor</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">R$</span>
                                                    <input class="form-control" id="valorPag<?= $idc ?>" name="valor_pago" type="text" inputmode="decimal" autocomplete="off" pattern="[0-9]*[.,]?[0-9]*" placeholder="ex.: 500,00" required>
                                                    <button type="button" class="btn btn-outline-secondary" data-restante="<?= number_format($faltaConta, 2, ',', '') ?>" data-alvo="valorPag<?= $idc ?>" title="Preencher com o que falta pagar">Restante</button>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Data</label>
                                                <input class="form-control" name="data_pagamento" type="date" value="<?= date('Y-m-d') ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Tipo</label>
                                                <select class="form-select" name="tipo">
                                                    <?php foreach ($tiposPag as $k => $lab): ?><option value="<?= $k ?>"><?= $lab ?></option><?php endforeach ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Forma</label>
                                                <select class="form-select" name="forma">
                                                    <?php foreach ($formasPag as $k => $lab): ?><option value="<?= $k ?>" <?= ($r['modo_pagamento'] ?? 'pix') === $k ? 'selected' : '' ?>><?= $lab ?></option><?php endforeach ?>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Observação <span class="text-secondary small">(opcional)</span></label>
                                                <input class="form-control" name="observacao" maxlength="255" placeholder="ex.: vale do dia 20">
                                            </div>
                                            <div class="col-12 d-flex justify-content-between align-items-center">
                                                <label class="small"><input type="checkbox" name="registrar_caixa" value="1" checked> Lançar a saída no Caixa</label>
                                                <button class="btn btn-success"><i class="bi bi-check2-circle"></i> Registrar</button>
                                            </div>
                                        </form>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
                <script>
                    document.querySelectorAll('[data-restante]').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const campo = document.getElementById(btn.dataset.alvo);
                            if (campo) { campo.value = btn.dataset.restante; campo.focus(); }
                        });
                    });
                </script>
            </div>

