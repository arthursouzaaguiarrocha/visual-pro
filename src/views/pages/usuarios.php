<?php $meuId = (int)($_SESSION['user']['id'] ?? 0); ?>
<div class="panel mb-4">
    <h5 class="fw-bold">Criar usuário</h5>
    <form method="post" class="row g-3"><input type="hidden" name="action" value="user_save">
        <div class="col-md-3"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div>
        <div class="col-md-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required></div>
        <div class="col-md-3"><label class="form-label">Senha inicial</label><input class="form-control" type="password" name="senha" minlength="8" required autocomplete="new-password"></div>
        <div class="col-md-2"><label class="form-label">Perfil</label><select class="form-select" name="perfil">
                <option value="vendedor">Vendedor</option>
                <option value="producao">Produção</option>
                <option value="admin">Administrador</option>
            </select></div>
        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-success">Criar</button></div>
    </form>
</div>

<div class="panel">
    <h5 class="fw-bold">Usuários do sistema</h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= e($r['nome']) ?><?= (int)$r['id'] === $meuId ? ' <span class="badge text-bg-info">você</span>' : '' ?></td>
                        <td><?= e($r['email']) ?></td>
                        <td><?= e(ucfirst($r['perfil'])) ?></td>
                        <td><span class="badge text-bg-<?= $r['ativo'] ? 'success' : 'secondary' ?>"><?= $r['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td>
                            <div class="d-flex gap-1 align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-icon" title="Editar / redefinir senha"
                                    data-bs-toggle="modal" data-bs-target="#modalUser<?= (int)$r['id'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <?php if ((int)$r['id'] !== $meuId): ?>
                                    <form method="post" class="m-0" onsubmit="return confirm('<?= $r['ativo'] ? 'Desativar' : 'Reativar' ?> este usuário?')">
                                        <input type="hidden" name="action" value="user_toggle">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-<?= $r['ativo'] ? 'danger' : 'success' ?> btn-icon" title="<?= $r['ativo'] ? 'Desativar' : 'Reativar' ?>">
                                            <i class="bi bi-<?= $r['ativo'] ? 'person-dash' : 'person-check' ?>"></i>
                                        </button>
                                    </form>
                                <?php endif ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($rows as $r): ?>
    <div class="modal fade" id="modalUser<?= (int)$r['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content">
                <input type="hidden" name="action" value="user_update">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Editar usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input class="form-control" value="<?= e($r['email']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input class="form-control" name="nome" value="<?= e($r['nome']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Perfil</label>
                        <select class="form-select" name="perfil">
                            <?php foreach (['vendedor' => 'Vendedor', 'producao' => 'Produção', 'admin' => 'Administrador'] as $v => $lab): ?>
                                <option value="<?= $v ?>" <?= $r['perfil'] === $v ? 'selected' : '' ?>><?= $lab ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nova senha <span class="text-secondary small">(deixe em branco para manter)</span></label>
                        <input class="form-control" type="password" name="senha" minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach ?>
