<div class="panel mb-4">
                <h5 class="fw-bold">Novo cliente</h5>
                <form method="post" class="row g-3"><input type="hidden" name="action" value="cliente_save">
                    <div class="col-md-3"><label class="form-label">Nome completo / empresa</label><input class="form-control" name="nome" required></div>
                    <div class="col-md-2"><label class="form-label">Contato</label><input class="form-control" name="contato" placeholder="Nome do contato"></div>
                    <div class="col-md-2"><label class="form-label">Telefone</label><input class="form-control" name="telefone" required></div>
                    <div class="col-md-2"><label class="form-label">E-mail <span class="text-secondary small">(opcional)</span></label><input class="form-control" type="email" name="email"></div>
                    <div class="col-md-3"><label class="form-label">Endereço</label><input class="form-control" name="endereco" placeholder="Rua, número, bairro, cidade"></div>
                    <div class="col-12 d-flex justify-content-end"><button class="btn btn-success">Cadastrar cliente</button></div>
                </form>
            </div>
            <div class="panel">
                <h5 class="fw-bold">Clientes cadastrados</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Contato</th>
                                <th>Telefone</th>
                                <th>E-mail</th>
                                <th>Endereço</th>
                                <th>Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($rows as $r): ?><tr>
                                    <td><?= $r['id'] ?></td>
                                    <td><?= e($r['nome']) ?></td>
                                    <td><?= e($r['contato'] ?? '—') ?></td>
                                    <td><?= e($r['telefone']) ?></td>
                                    <td><?= e($r['email'] ?: '—') ?></td>
                                    <td class="small"><?= e($r['endereco'] ?? '—') ?></td>
                                    <td><?= isset($r['criado_em']) ? date('d/m/Y', strtotime($r['criado_em'])) : '—' ?></td>
                                    <td class="col-acoes">
                                        <div class="acoes"><button class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="modal" data-bs-target="#editarCliente<?= (int)$r['id'] ?>" title="Visualizar / editar" aria-label="Visualizar / editar"><i class="bi bi-pencil-square"></i></button>
                                            <form method="post" onsubmit="return confirm('Excluir este cliente?')"><input type="hidden" name="action" value="cliente_delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon" title="Excluir" aria-label="Excluir"><i class="bi bi-trash"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                                <div class="modal fade" id="editarCliente<?= (int)$r['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar cliente #<?= (int)$r['id'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body"><input type="hidden" name="action" value="cliente_save"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><label class="form-label">Nome</label><input class="form-control mb-2" name="nome" value="<?= e($r['nome']) ?>" required><label class="form-label">Contato</label><input class="form-control mb-2" name="contato" value="<?= e($r['contato'] ?? '') ?>" placeholder="Nome do contato"><label class="form-label">Telefone</label><input class="form-control mb-2" name="telefone" value="<?= e($r['telefone']) ?>" required><label class="form-label">E-mail <span class="text-secondary small">(opcional)</span></label><input class="form-control mb-2" type="email" name="email" value="<?= e($r['email'] ?? '') ?>"><label class="form-label">Endereço</label><input class="form-control" name="endereco" value="<?= e($r['endereco'] ?? '') ?>" placeholder="Rua, número, bairro, cidade"></div>
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
