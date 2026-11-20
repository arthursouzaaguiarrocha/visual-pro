<div class="panel mb-4">
                <h5 class="fw-bold">Cadastrar <?= e($page === 'produtos' ? 'produto' : 'serviço') ?></h5>
                <form method="post" class="row g-3"><input type="hidden" name="action" value="<?= $page === 'produtos' ? 'produto_save' : 'servico_save' ?>">
                    <div class="col-md-4"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div>
                    <div class="col-md-3"><label class="form-label">Unidade de cobrança</label><select class="form-select" name="unidade"><?php foreach (($page === 'produtos' ? ['m2' => 'Metro quadrado (m²)', 'metro_linear' => 'Metro linear', 'unidade' => 'Unidade'] : ['fixo' => 'Valor fixo', 'm2' => 'Metro quadrado (m²)', 'metro_linear' => 'Metro linear', 'unidade' => 'Unidade']) as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach ?></select></div><?php if ($page === 'produtos'): ?><div class="col-md-2"><label class="form-label">Categoria</label><select class="form-select" name="categoria">
                                <option value="banner">Banner</option>
                                <option value="adesivo">Adesivo</option>
                                <option value="placa">Placa</option>
                                <option value="fachada">Fachada</option>
                                <option value="lona">Lona</option>
                                <option value="outro">Outro</option>
                            </select></div><?php endif ?><div class="col-md-2"><label class="form-label">Preço (R$)</label><input class="form-control" type="number" name="preco" min="0" step="0.01" required></div>
                    <div class="col-md-1 d-flex align-items-end"><button class="btn btn-success">Salvar</button></div>
                </form>
            </div>
            <div class="panel">
                <h5 class="fw-bold">Lista de <?= e($page === 'produtos' ? 'produtos' : 'serviços') ?></h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th><?php if ($page === 'produtos'): ?><th>Categoria</th><?php endif ?><th>Unidade</th>
                                <th>Preço base</th>
                                <th>Ativo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($rows as $r): ?><tr>
                                    <td><?= $r['id'] ?></td>
                                    <td><?= e($r['nome']) ?></td><?php if ($page === 'produtos'): ?><td><?= e(ucfirst($r['categoria'])) ?></td><?php endif ?><td><?= e(str_replace(['m2', 'metro_linear', 'unidade', 'fixo'], ['m²', 'metro linear', 'unidade', 'fixo'], $r['unidade'])) ?></td>
                                    <td><?= money($r['preco']) ?></td>
                                    <td><span class="badge text-bg-<?= (isset($r['ativo']) && !$r['ativo']) ? 'secondary' : 'success' ?>"><?= (isset($r['ativo']) && !$r['ativo']) ? 'Inativo' : 'Ativo' ?></span></td>
                                    <td class="col-acoes">
                                        <div class="acoes"><button class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="modal" data-bs-target="#editarItem<?= (int)$r['id'] ?>" title="Visualizar / editar" aria-label="Visualizar / editar"><i class="bi bi-pencil-square"></i></button>
                                            <form method="post" onsubmit="return confirm('Excluir/desativar este registro?')"><input type="hidden" name="action" value="<?= $page === 'produtos' ? 'produto_delete' : 'servico_delete' ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon" title="Excluir" aria-label="Excluir"><i class="bi bi-trash"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                                <div class="modal fade" id="editarItem<?= (int)$r['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar <?= $page === 'produtos' ? 'produto' : 'serviço' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body"><input type="hidden" name="action" value="<?= $page === 'produtos' ? 'produto_save' : 'servico_save' ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><label class="form-label">Nome</label><input class="form-control mb-2" name="nome" value="<?= e($r['nome']) ?>" required><label class="form-label">Unidade</label><select class="form-select mb-2" name="unidade"><?php foreach (($page === 'produtos' ? ['m2' => 'Metro quadrado (m²)', 'metro_linear' => 'Metro linear', 'unidade' => 'Unidade'] : ['fixo' => 'Valor fixo', 'm2' => 'Metro quadrado (m²)', 'metro_linear' => 'Metro linear', 'unidade' => 'Unidade']) as $k => $v): ?><option value="<?= $k ?>" <?= $r['unidade'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach ?></select><?php if ($page === 'produtos'): ?><label class="form-label">Categoria</label><select class="form-select mb-2" name="categoria"><?php foreach (['banner', 'adesivo', 'placa', 'fachada', 'lona', 'outro'] as $cat): ?><option value="<?= $cat ?>" <?= $r['categoria'] === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option><?php endforeach ?></select><?php endif ?><label class="form-label">Preço (R$)</label><input class="form-control" type="number" min="0" step="0.01" name="preco" value="<?= e($r['preco']) ?>" required></div>
                                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-success">Salvar alterações</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
