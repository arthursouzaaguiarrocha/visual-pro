<div class="row g-3 mb-4"><?php foreach ([['Entradas acumuladas', money($cash['entradas'])], ['Saídas acumuladas', money($cash['saidas'])], ['Saldo atual', money($cash['saldo'])]] as $st): ?><div class="col-md-4">
                        <div class="stat">
                            <div class="label"><?= e($st[0]) ?></div>
                            <div class="value"><?= e($st[1]) ?></div>
                        </div>
                    </div><?php endforeach ?></div>
            <div class="panel mb-4">
                <h5 class="fw-bold">Registrar movimentação</h5>
                <form method="post" class="row g-3"><input type="hidden" name="action" value="caixa_save">
                    <div class="col-md-2"><label class="form-label">Tipo</label><select class="form-select" name="tipo">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Descrição</label><input class="form-control" name="descricao" required></div>
                    <div class="col-md-2"><label class="form-label">Categoria</label><input class="form-control" name="categoria" placeholder="Material, venda..."></div>
                    <div class="col-md-2"><label class="form-label">Valor (R$)</label><input class="form-control" name="valor" type="number" min="0.01" step="0.01" required></div>
                    <div class="col-md-2"><label class="form-label">Data</label><input class="form-control" name="data_movimento" type="date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-12"><button class="btn btn-success">Registrar</button></div>
                </form>
            </div>
            <div class="panel">
                <h5 class="fw-bold">Últimas movimentações</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Valor</th>
                                <th>Responsável</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($rows as $r): ?><tr>
                                    <td><?= date('d/m/Y', strtotime($r['data_movimento'])) ?></td>
                                    <td><span class="badge text-bg-<?= $r['tipo'] === 'entrada' ? 'success' : 'danger' ?>"><?= ucfirst($r['tipo']) ?></span></td>
                                    <td><?= e($r['descricao']) ?></td>
                                    <td><?= e($r['categoria']) ?></td>
                                    <td><?= money($r['valor']) ?></td>
                                    <td><?= e($r['usuario'] ?? '—') ?></td>
                                    <td class="col-acoes">
                                        <div class="acoes"><button class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="modal" data-bs-target="#editarCaixa<?= (int)$r['id'] ?>" title="Visualizar / editar" aria-label="Visualizar / editar"><i class="bi bi-pencil-square"></i></button>
                                            <form method="post" onsubmit="return confirm('Excluir esta movimentação?')"><input type="hidden" name="action" value="caixa_delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon" title="Excluir" aria-label="Excluir"><i class="bi bi-trash"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                                <div class="modal fade" id="editarCaixa<?= (int)$r['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar movimentação</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body"><input type="hidden" name="action" value="caixa_save"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><label class="form-label">Tipo</label><select class="form-select mb-2" name="tipo">
                                                        <option value="entrada" <?= $r['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                                                        <option value="saida" <?= $r['tipo'] === 'saida' ? 'selected' : '' ?>>Saída</option>
                                                    </select><label class="form-label">Descrição</label><input class="form-control mb-2" name="descricao" value="<?= e($r['descricao']) ?>" required><label class="form-label">Categoria</label><input class="form-control mb-2" name="categoria" value="<?= e($r['categoria']) ?>"><label class="form-label">Valor</label><input class="form-control mb-2" type="number" min="0.01" step="0.01" name="valor" value="<?= e($r['valor']) ?>" required><label class="form-label">Data</label><input class="form-control" type="date" name="data_movimento" value="<?= e($r['data_movimento']) ?>" required></div>
                                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-success">Salvar</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div><?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
