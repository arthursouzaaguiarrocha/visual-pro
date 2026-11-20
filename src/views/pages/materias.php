<?php $materias = $materias ?? [];
            $composicoes = $composicoes ?? [];
            $compItens = $compItens ?? [];
            $tiposComp = ['portao' => 'Portão', 'marquise_acm' => 'Marquise ACM', 'marquise_lona' => 'Marquise lona', 'toldo' => 'Toldo', 'tenda' => 'Tenda', 'outro' => 'Outro'];
            $unMat = ['unidade' => 'Unidade', 'm' => 'Metro (m)', 'm2' => 'm²', 'metro_linear' => 'Metro linear', 'kg' => 'Kg', 'litro' => 'Litro', 'pacote' => 'Pacote'];
            $modos = [
                'unidade' => 'Unidade (× qtd)',
                'fixo' => 'Fixo (× qtd serviço)',
                'por_unidade' => 'Por unidade',
                'por_largura' => 'Metro linear (largura)',
                'por_altura' => 'Metro linear (altura)',
                'por_espessura' => 'Metro linear (espessura)',
                'por_metro' => 'Metro linear (largura)',
                'por_m2' => 'm² (largura × altura)',
                'por_m2_le' => 'm² (largura × espessura)',
                'por_m2_ae' => 'm² (altura × espessura)',
                'perimetro_la' => 'Perímetro (L + A) × 2',
                'perimetro_le' => 'Perímetro (L + E) × 2',
                'perimetro_ae' => 'Perímetro (A + E) × 2',
            ];
            $compVariacoes = $compVariacoes ?? [];
            $varItens = $varItens ?? [];
            $compCustos = $compCustos ?? [];
        ?>
            <div class="panel mb-4">
                <h5 class="fw-bold">Nova matéria-prima</h5>
                <p class="small text-secondary">Cadastre motor, guias, lâmina, lona, metalon, parafuso, solda etc. com o custo unitário.</p>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="materia_save">
                    <div class="col-md-4"><label class="form-label">Nome</label><input class="form-control" name="nome" required placeholder="Ex: Motor deslizante 1/3 CV"></div>
                    <div class="col-md-3"><label class="form-label">Unidade</label>
                        <select class="form-select" name="unidade">
                            <?php foreach ($unMat as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Custo (R$)</label><input class="form-control" type="number" min="0" step="0.0001" name="custo" required placeholder="0,00"></div>
                    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-success w-100">Cadastrar</button></div>
                </form>
            </div>

            <div class="panel mb-4">
                <h5 class="fw-bold mb-3">Matérias-primas</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Unidade</th>
                                <th>Custo</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$materias): ?><tr>
                                    <td colspan="6" class="text-secondary">Nenhuma matéria cadastrada.</td>
                                </tr><?php endif ?>
                            <?php foreach ($materias as $m): ?>
                                <tr class="<?= !(int)$m['ativo'] ? 'text-secondary' : '' ?>">
                                    <td><?= (int)$m['id'] ?></td>
                                    <td><?= e($m['nome']) ?></td>
                                    <td><?= e($unMat[$m['unidade']] ?? $m['unidade']) ?></td>
                                    <td><?= money($m['custo']) ?></td>
                                    <td><?= (int)$m['ativo'] ? '<span class="badge text-bg-success">Ativa</span>' : '<span class="badge text-bg-secondary">Inativa</span>' ?></td>
                                    <td class="col-acoes">
                                        <div class="acoes">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="modal" data-bs-target="#editMat<?= (int)$m['id'] ?>"><i class="bi bi-pencil-square"></i></button>
                                            <?php if ((int)$m['ativo']): ?>
                                                <form method="post" onsubmit="return confirm('Desativar esta matéria? Ela não será apagada.')"><input type="hidden" name="action" value="materia_delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon" title="Desativar"><i class="bi bi-pause-circle"></i></button></form>
                                            <?php else: ?>
                                                <form method="post"><input type="hidden" name="action" value="materia_reativar"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-sm btn-outline-success btn-icon" title="Reativar"><i class="bi bi-play-circle"></i></button></form>
                                            <?php endif ?>
                                        </div>
                                    </td>
                                </tr>
                                <div class="modal fade" id="editMat<?= (int)$m['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar matéria</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="materia_save"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                                    <label class="form-label">Nome</label><input class="form-control mb-2" name="nome" value="<?= e($m['nome']) ?>" required>
                                                    <label class="form-label">Unidade</label>
                                                    <select class="form-select mb-2" name="unidade"><?php foreach ($unMat as $k => $v): ?><option value="<?= $k ?>" <?= $m['unidade'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach ?></select>
                                                    <label class="form-label">Custo (R$)</label><input class="form-control" type="number" min="0" step="0.0001" name="custo" value="<?= e($m['custo']) ?>" required>
                                                </div>
                                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-success">Salvar</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel mb-4">
                <h5 class="fw-bold">Nova composição (receita do serviço)</h5>
                <p class="small text-secondary">Ex.: Portão automático = motor + guias + lâminas. O preço de venda = custo das matérias + margem %.</p>
                <form method="post" id="formComposicao" class="row g-3">
                    <input type="hidden" name="action" value="composicao_save">
                    <div class="col-md-4"><label class="form-label">Nome</label><input class="form-control" name="nome" required placeholder="Ex: Portão automático residencial"></div>
                    <div class="col-md-3"><label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo"><?php foreach ($tiposComp as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach ?></select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Unidade de venda</label>
                        <select class="form-select" name="unidade">
                            <option value="unidade">Unidade</option>
                            <option value="m2">m²</option>
                            <option value="metro_linear">Metro linear</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Margem de lucro (%)</label><input class="form-control" type="number" min="0" step="0.01" name="margem_percentual" value="30" required></div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Matérias da composição</label>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Matéria</th>
                                        <th>Qtd base</th>
                                        <th>Como calcular</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="linhasComp"></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm" id="addLinhaComp">+ Matéria</button>
                    </div>
                    <div class="col-12"><button class="btn btn-success">Salvar composição</button></div>
                </form>
                <template id="tplLinhaComp">
                    <tr>
                        <td><select class="form-select" name="materia_id[]" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($materias as $m): if (!(int)$m['ativo']) continue; ?>
                                    <option value="<?= (int)$m['id'] ?>" data-custo="<?= e($m['custo']) ?>"><?= e($m['nome']) ?> — <?= money($m['custo']) ?></option>
                                <?php endforeach ?>
                            </select></td>
                        <td><input class="form-control" type="number" min="0.0001" step="0.0001" name="mat_qtd[]" value="1" required></td>
                        <td><select class="form-select" name="mat_modo[]"><?php foreach ($modos as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach ?></select></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-rm-comp">×</button></td>
                    </tr>
                </template>
            </div>

            <div class="panel">
                <h5 class="fw-bold mb-3">Composições cadastradas</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Unidade</th>
                                <th>Margem</th>
                                <th>Matérias</th>
                                <th>Custo base*</th>
                                <th>Preço ref.*</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$composicoes): ?><tr>
                                    <td colspan="9" class="text-secondary">Nenhuma composição ainda.</td>
                                </tr><?php endif ?>
                            <?php foreach ($composicoes as $c):
                                $its = $compItens[(int)$c['id']] ?? [];
                                $custoBase = 0.0;
                                foreach ($its as $it) $custoBase += (float)$it['quantidade'] * (float)$it['custo'];
                                $precoRef = round($custoBase * (1 + (float)$c['margem_percentual'] / 100), 2);
                            ?>
                                <tr class="<?= !(int)$c['ativo'] ? 'text-secondary' : '' ?>">
                                    <td><?= (int)$c['id'] ?></td>
                                    <td><?= e($c['nome']) ?></td>
                                    <td><?= e($tiposComp[$c['tipo']] ?? $c['tipo']) ?></td>
                                    <td><?= e($c['unidade']) ?></td>
                                    <td><?= e($c['margem_percentual']) ?>%</td>
                                    <td class="small"><?php
                                                        if (!$its) echo '—';
                                                        else echo e(implode(', ', array_map(fn($x) => $x['materia_nome'] . ' (' . $x['quantidade'] . ' · ' . $x['modo'] . ')', $its)));
                                                        ?></td>
                                    <td><?= money($custoBase) ?></td>
                                    <td class="fw-semibold"><?= money($precoRef) ?></td>
                                    <td class="col-acoes">
                                        <div class="acoes">
                                            <?php if ((int)$c['ativo']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="modal" data-bs-target="#editComp<?= (int)$c['id'] ?>" title="Editar"><i class="bi bi-pencil-square"></i></button>
                                            <?php endif ?>
                                            <form method="post" onsubmit="return confirm('Desativar esta composição?')"><input type="hidden" name="action" value="composicao_delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon" title="Desativar"><i class="bi bi-trash"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                                <?php if ((int)$c['ativo']): ?>
                                    <div class="modal fade" id="editComp<?= (int)$c['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <form method="post" class="form-edit-comp">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Editar composição #<?= (int)$c['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="composicao_save">
                                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Nome</label>
                                                                <input class="form-control" name="nome" value="<?= e($c['nome']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Tipo</label>
                                                                <select class="form-select" name="tipo">
                                                                    <?php foreach ($tiposComp as $k => $v): ?>
                                                                        <option value="<?= $k ?>" <?= $c['tipo'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                                                    <?php endforeach ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Unidade de venda</label>
                                                                <select class="form-select" name="unidade">
                                                                    <option value="unidade" <?= $c['unidade'] === 'unidade' ? 'selected' : '' ?>>Unidade</option>
                                                                    <option value="m2" <?= $c['unidade'] === 'm2' ? 'selected' : '' ?>>m²</option>
                                                                    <option value="metro_linear" <?= $c['unidade'] === 'metro_linear' ? 'selected' : '' ?>>Metro linear</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Margem de lucro (%)</label>
                                                                <input class="form-control" type="number" min="0" step="0.01" name="margem_percentual" value="<?= e($c['margem_percentual']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <label class="form-label fw-semibold">Matérias da composição</label>
                                                        <div class="table-responsive">
                                                            <table class="table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Matéria</th>
                                                                        <th>Qtd base</th>
                                                                        <th>Como calcular</th>
                                                                        <th></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="linhas-edit-comp">
                                                                    <?php if ($its): foreach ($its as $it): ?>
                                                                            <tr>
                                                                                <td>
                                                                                    <select class="form-select" name="materia_id[]" required>
                                                                                        <option value="">Selecione...</option>
                                                                                        <?php foreach ($materias as $m): if (!(int)$m['ativo'] && (int)$m['id'] !== (int)$it['materia_id']) continue; ?>
                                                                                            <option value="<?= (int)$m['id'] ?>" <?= (int)$m['id'] === (int)$it['materia_id'] ? 'selected' : '' ?>><?= e($m['nome']) ?> — <?= money($m['custo']) ?></option>
                                                                                        <?php endforeach ?>
                                                                                    </select>
                                                                                </td>
                                                                                <td><input class="form-control" type="number" min="0.0001" step="0.0001" name="mat_qtd[]" value="<?= e($it['quantidade']) ?>" required></td>
                                                                                <td>
                                                                                    <select class="form-select" name="mat_modo[]">
                                                                                        <?php foreach ($modos as $k => $v): ?>
                                                                                            <option value="<?= $k ?>" <?= $it['modo'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                                                                        <?php endforeach ?>
                                                                                    </select>
                                                                                </td>
                                                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-rm-comp">×</button></td>
                                                                            </tr>
                                                                        <?php endforeach;
                                                                    else: ?>
                                                                        <tr>
                                                                            <td>
                                                                                <select class="form-select" name="materia_id[]" required>
                                                                                    <option value="">Selecione...</option>
                                                                                    <?php foreach ($materias as $m): if (!(int)$m['ativo']) continue; ?>
                                                                                        <option value="<?= (int)$m['id'] ?>"><?= e($m['nome']) ?> — <?= money($m['custo']) ?></option>
                                                                                    <?php endforeach ?>
                                                                                </select>
                                                                            </td>
                                                                            <td><input class="form-control" type="number" min="0.0001" step="0.0001" name="mat_qtd[]" value="1" required></td>
                                                                            <td>
                                                                                <select class="form-select" name="mat_modo[]">
                                                                                    <?php foreach ($modos as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach ?>
                                                                                </select>
                                                                            </td>
                                                                            <td><button type="button" class="btn btn-sm btn-outline-danger btn-rm-comp">×</button></td>
                                                                        </tr>
                                                                    <?php endif ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <button type="button" class="btn btn-outline-success btn-sm btn-add-edit-comp">+ Matéria</button>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                        <button class="btn btn-success" type="submit">Salvar alterações</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif ?>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <p class="small text-secondary mb-0">* Custo base e preço de referência consideram 1 unidade com fator 1 (sem dimensões). No orçamento o valor é recalculado com largura/altura/espessura/quantidade.</p>
            </div>

            <div class="panel mb-4 mt-4">
                <h5 class="fw-bold">Outros custos da composição</h5>
                <p class="small text-secondary">Como no MubiSys: mão de obra, solda, instalação, acabamento fixo, taxa etc. Somam no <strong>custo</strong> antes da margem. Não são matérias-primas.</p>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="custo_extra_save">
                    <div class="col-md-4">
                        <label class="form-label">Composição</label>
                        <select class="form-select" name="composicao_id" id="selCustoComp" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($composicoes as $c): if (!(int)$c['ativo']) continue; ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['nome']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Valor (R$ ou %)</th>
                                        <th>Como calcular</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="linhasCusto"></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm" id="addLinhaCusto">+ Custo</button>
                    </div>
                    <div class="col-12"><button class="btn btn-success">Salvar outros custos</button></div>
                </form>
                <template id="tplLinhaCusto">
                    <tr>
                        <td><input class="form-control" name="custo_nome[]" placeholder="Ex: Mão de obra / Solda / Instalação" required></td>
                        <td><input class="form-control" type="number" min="0" step="0.01" name="custo_valor[]" value="0" required></td>
                        <td>
                            <select class="form-select" name="custo_modo[]">
                                <option value="fixo">Valor fixo (× qtd)</option>
                                <option value="por_unidade">Por unidade</option>
                                <option value="por_largura">× largura (m)</option>
                                <option value="por_altura">× altura (m)</option>
                                <option value="por_m2">× área (m²)</option>
                                <option value="percentual_materias">% sobre custo das matérias</option>
                            </select>
                        </td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-rm-custo">×</button></td>
                    </tr>
                </template>
                <div class="mt-3">
                    <h6 class="fw-semibold">Custos já salvos por composição</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Composição</th>
                                    <th>Custos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $temC = false;
                                foreach ($composicoes as $c):
                                    $cts = $compCustos[(int)$c['id']] ?? [];
                                    if (!$cts) continue;
                                    $temC = true;
                                    $lbl = ['fixo' => 'fixo', 'por_unidade' => 'unidade', 'por_largura' => '× largura', 'por_altura' => '× altura', 'por_m2' => '× m²', 'percentual_materias' => '% matérias'];
                                ?>
                                    <tr>
                                        <td><?= e($c['nome']) ?></td>
                                        <td class="small"><?= e(implode(' · ', array_map(fn($x) => $x['nome'] . ' ' . money($x['valor']) . ' (' . ($lbl[$x['modo']] ?? $x['modo']) . ')', $cts))) ?></td>
                                    </tr>
                                <?php endforeach ?>
                                <?php if (!$temC): ?><tr>
                                        <td colspan="2" class="text-secondary">Nenhum custo extra cadastrado.</td>
                                    </tr><?php endif ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="panel mb-4 mt-4">
                <h5 class="fw-bold">Variações da composição</h5>
                <p class="small text-secondary">Ex.: Portão com motor <strong>com nobreak</strong> ou <strong>sem nobreak</strong>. A variação soma matérias extras sobre a composição base.</p>
                <form method="post" class="row g-3" id="formVariacao">
                    <input type="hidden" name="action" value="variacao_save">
                    <div class="col-md-4">
                        <label class="form-label">Composição base</label>
                        <select class="form-select" name="composicao_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($composicoes as $c): if (!(int)$c['ativo']) continue; ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['nome']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nome da variação</label>
                        <input class="form-control" name="nome" required placeholder="Ex: Motor com nobreak">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo">
                            <option value="equipamento">Equipamento</option>
                            <option value="acabamento">Acabamento</option>
                            <option value="opcional" selected>Opcional</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Matérias extras da variação</label>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Matéria</th>
                                        <th>Qtd base</th>
                                        <th>Como calcular</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="linhasVar"></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm" id="addLinhaVar">+ Matéria</button>
                    </div>
                    <div class="col-12"><button class="btn btn-success">Salvar variação</button></div>
                </form>
            </div>

            <div class="panel">
                <h5 class="fw-bold mb-3">Variações cadastradas</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Composição</th>
                                <th>Variação</th>
                                <th>Tipo</th>
                                <th>Matérias</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $temVar = false;
                            foreach ($composicoes as $c):
                                foreach ($compVariacoes[(int)$c['id']] ?? [] as $v):
                                    if (!(int)$v['ativo']) continue;
                                    $temVar = true;
                                    $vits = $varItens[(int)$v['id']] ?? [];
                            ?>
                                    <tr>
                                        <td><?= e($c['nome']) ?></td>
                                        <td><?= e($v['nome']) ?></td>
                                        <td><?= e($v['tipo']) ?></td>
                                        <td class="small"><?= e(implode(', ', array_map(fn($x) => $x['materia_nome'] . ' (' . $x['quantidade'] . ' · ' . $x['modo'] . ')', $vits)) ?: '—') ?></td>
                                        <td>
                                            <form method="post" onsubmit="return confirm('Desativar esta variação?')">
                                                <input type="hidden" name="action" value="variacao_delete">
                                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endforeach; ?>
                            <?php if (!$temVar): ?><tr>
                                    <td colspan="5" class="text-secondary">Nenhuma variação ativa.</td>
                                </tr><?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <script>
                (function() {
                    const tpl = document.getElementById('tplLinhaComp');
                    const tbody = document.getElementById('linhasComp');
                    const add = document.getElementById('addLinhaComp');
                    if (tpl && tbody && add) {
                        function addRow() {
                            tbody.appendChild(tpl.content.cloneNode(true));
                        }
                        add.addEventListener('click', addRow);
                        addRow();
                    }
                    const tbodyVar = document.getElementById('linhasVar');
                    const addVar = document.getElementById('addLinhaVar');

                    function addVarRow() {
                        if (!tpl || !tbodyVar) return;
                        tbodyVar.appendChild(tpl.content.cloneNode(true));
                    }
                    if (addVar) {
                        addVar.addEventListener('click', addVarRow);
                        addVarRow();
                    }

                    const tplCusto = document.getElementById('tplLinhaCusto');
                    const tbodyCusto = document.getElementById('linhasCusto');
                    const addCusto = document.getElementById('addLinhaCusto');
                    const custosPorComp = <?= json_encode(array_map(function ($list) {
                                                return array_map(fn($x) => ['nome' => $x['nome'], 'valor' => (float)$x['valor'], 'modo' => $x['modo']], $list);
                                            }, $compCustos), JSON_UNESCAPED_UNICODE) ?: '{}' ?>;

                    function addCustoRow(data) {
                        if (!tplCusto || !tbodyCusto) return;
                        const tr = tplCusto.content.cloneNode(true).querySelector('tr');
                        if (data) {
                            tr.querySelector('[name="custo_nome[]"]').value = data.nome || '';
                            tr.querySelector('[name="custo_valor[]"]').value = data.valor ?? 0;
                            if (data.modo) tr.querySelector('[name="custo_modo[]"]').value = data.modo;
                        }
                        tbodyCusto.appendChild(tr);
                    }
                    if (addCusto) addCusto.addEventListener('click', () => addCustoRow());
                    document.addEventListener('click', e => {
                        if (e.target.closest('.btn-rm-custo')) {
                            const tr = e.target.closest('tr');
                            const tb = tr && tr.closest('tbody');
                            if (tb && tb.id === 'linhasCusto') tr.remove();
                        }
                    });
                    const selCusto = document.getElementById('selCustoComp');
                    if (selCusto && tbodyCusto) {
                        selCusto.addEventListener('change', () => {
                            tbodyCusto.innerHTML = '';
                            const list = custosPorComp[selCusto.value] || [];
                            if (list.length) list.forEach(addCustoRow);
                            else addCustoRow();
                        });
                        addCustoRow();
                    }

                    function matOptionsHtml() {
                        if (tpl) {
                            const sel = tpl.content.querySelector('select[name="materia_id[]"]');
                            if (sel) return sel.innerHTML;
                        }
                        return '<option value="">Selecione...</option>';
                    }

                    function modoOptionsHtml() {
                        if (tpl) {
                            const sel = tpl.content.querySelector('select[name="mat_modo[]"]');
                            if (sel) return sel.innerHTML;
                        }
                        return '';
                    }
                    document.addEventListener('click', e => {
                        if (e.target.closest('.btn-rm-comp')) {
                            const tr = e.target.closest('tr');
                            const tb = tr && tr.closest('tbody');
                            if (tb && tb.querySelectorAll('tr').length > 1) tr.remove();
                        }
                        const addEdit = e.target.closest('.btn-add-edit-comp');
                        if (addEdit) {
                            const modal = addEdit.closest('.modal');
                            const tb = modal && modal.querySelector('.linhas-edit-comp');
                            if (!tb) return;
                            const tr = document.createElement('tr');
                            tr.innerHTML = '<td><select class="form-select" name="materia_id[]" required>' + matOptionsHtml() + '</select></td>' +
                                '<td><input class="form-control" type="number" min="0.0001" step="0.0001" name="mat_qtd[]" value="1" required></td>' +
                                '<td><select class="form-select" name="mat_modo[]">' + modoOptionsHtml() + '</select></td>' +
                                '<td><button type="button" class="btn btn-sm btn-outline-danger btn-rm-comp">×</button></td>';
                            tb.appendChild(tr);
                        }
                    });
                })();
            </script>

