<div class="panel">
                <h5 class="fw-bold">Fila de produção</h5>
                <p class="text-secondary small">Orçamentos aprovados são convertidos automaticamente em ordens de serviço.</p>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>OS</th>
                                <th>Cliente</th>
                                <th>Arte / trabalho</th>
                                <th>Prazo</th>
                                <th>Entrega</th>
                                <th>Prioridade</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($rows as $r): ?><tr>
                                    <td>#<?= str_pad((string)$r['id'], 5, '0', STR_PAD_LEFT) ?><div class="small text-secondary">Orçamento #<?= $r['orcamento_num'] ?></div>
                                    </td>
                                    <td><?= e($r['cliente']) ?><div class="small text-secondary"><?= e($r['telefone']) ?></div>
                                    </td>
                                    <td><?php if (!empty($r['imagem_path'])): ?><a href="<?= e(public_url($r['imagem_path'])) ?>" target="_blank" rel="noopener"><img class="art-thumb mb-2" src="<?= e(public_url($r['imagem_path'])) ?>" alt="Arte do orçamento #<?= $r['orcamento_num'] ?>" loading="lazy"></a><?php endif ?><div><?= e($r['descricao']) ?></div>
                                        <div class="small fw-semibold"><?= money($r['valor']) ?></div>
                                    </td>
                                    <td><?= e($r['prazo'] ? date('d/m/Y', strtotime($r['prazo'])) : '—') ?></td>
                                    <td class="small"><?php
                                        $de = $r['data_entrega'] ?? null;
                                        echo $de ? date('d/m/Y H:i', strtotime($de)) : '—';
                                    ?></td>
                                    <td><?= e(ucfirst($r['prioridade'])) ?></td>
                                    <td><?= e(str_replace('_', ' ', ucfirst($r['status']))) ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            <?php if (in_array($r['status'], ['fila', 'em_producao', 'aguardando_instalacao'], true)): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Confirmar baixa desta OS (marcar como concluída)?')">
                                                <input type="hidden" name="action" value="os_baixa">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <button class="btn btn-sm btn-success" title="Baixa na OS"><i class="bi bi-check2-circle"></i> Baixa</button>
                                            </form>
                                            <?php endif ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.open('?page=os_print&id=<?= (int)$r['id'] ?>&tipo=serralheria', '_blank')" title="Impressão Serralheria"><i class="bi bi-printer"></i> Serralheria</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.open('?page=os_print&id=<?= (int)$r['id'] ?>&tipo=instalacao', '_blank')" title="Impressão Instalação"><i class="bi bi-printer"></i> Instalação</button>
                                        </div>
                                        <form method="post" class="d-flex flex-column gap-1" style="min-width:200px"><input type="hidden" name="action" value="os_update"><input type="hidden" name="id" value="<?= $r['id'] ?>"><select class="form-select form-select-sm" name="status"><?php foreach (['fila', 'em_producao', 'aguardando_instalacao', 'concluida', 'cancelada'] as $s): ?><option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $s))) ?></option><?php endforeach ?></select><?php if (can(['admin'])): ?><select class="form-select form-select-sm" name="prioridade"><?php foreach (['baixa', 'normal', 'alta', 'urgente'] as $s): ?><option value="<?= $s ?>" <?= $r['prioridade'] === $s ? 'selected' : '' ?>>Prioridade <?= e($s) ?></option><?php endforeach ?></select><input class="form-control form-control-sm" type="date" name="prazo" value="<?= e($r['prazo']) ?>"><label class="form-label small mb-0">Data/hora entrega</label><input class="form-control form-control-sm" type="datetime-local" name="data_entrega" value="<?= !empty($r['data_entrega']) ? date('Y-m-d\TH:i', strtotime($r['data_entrega'])) : '' ?>"><select class="form-select form-select-sm" name="responsavel_id">
                                                    <option value="">Sem responsável</option><?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>" <?= $r['responsavel_id'] == $u['id'] ? 'selected' : '' ?>><?= e($u['nome']) ?></option><?php endforeach ?>
                                                </select><?php endif ?><input class="form-control form-control-sm" name="observacoes" value="<?= e($r['observacoes']) ?>" placeholder="Observações"><button class="btn btn-sm btn-success">Salvar</button></form>
                                    </td>
                                </tr><?php endforeach ?><?php if (!$rows): ?><tr>
                                    <td colspan="8" class="text-center text-secondary py-4">Nenhuma ordem de serviço ainda. Aprove um orçamento para gerar a primeira.</td>
                                </tr><?php endif ?></tbody>
                    </table>
                </div>
            </div>
