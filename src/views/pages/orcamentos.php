<?php
$baseDir = dirname(__DIR__, 3) . '/public/';
$resultado = preg_replace('/[\/\\\\]/', '/', $baseDir);
?>
<?php
$itensPorOrc = $itensPorOrc ?? [];
$composicoesOrc = $composicoesOrc ?? [];
$produtosOrc = $produtosOrc ?? [];
?>

<div class="panel mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="fw-bold mb-0">Cadastrar cliente rapidamente</h5>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#novoClienteRapido">Mostrar / ocultar</button>
    </div>
    <div class="collapse" id="novoClienteRapido">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="cliente_save">
            <input type="hidden" name="return_to" value="orcamentos">
            <div class="col-md-4">
                <label class="form-label">Nome do cliente</label>
                <input class="form-control" name="nome" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Telefone</label>
                <input class="form-control" name="telefone" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">E-mail</label>
                <input class="form-control" type="email" name="email">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-success w-100">Cadastrar cliente</button>
            </div>
        </form>
    </div>
</div>

<div class="panel mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0" id="tituloFormOrcamento">Novo orçamento</h5>
        <button type="button" id="btnCancelarEdicao" class="btn btn-outline-secondary btn-sm d-none">
            Cancelar edição
        </button>
    </div>

    <p class="small text-secondary">Selecione os produtos, informe as medidas e quantidades. O servidor recalcula os valores com base no catálogo cadastrado.</p>

    <form method="post" id="formOrcamento" enctype="multipart/form-data">
        <input type="hidden" name="action" value="orcamento_save">
        <input type="hidden" name="id" value="">

        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label">Cliente</label>
                <select class="form-select" name="cliente_id" required>
                    <option value="">Selecione um cliente...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label">Observações / detalhes do pedido</label>
                <input class="form-control" name="descricao" placeholder="Ex.: acabamento, cor, local de instalação">
            </div>
            <div class="col-12">
                <div class="art-upload">
                    <label class="form-label fw-semibold" for="imagemArte">Arte / imagem de referência do serviço</label>
                    <input class="form-control" type="file" id="imagemArte" name="imagem_arte" accept="image/jpeg,image/png">
                    <div class="form-text">JPG ou PNG · até 5 MB. A imagem ajuda a equipe a identificar a arte que será produzida.</div>
                    <div class="mt-3 d-none" id="previewArteWrap">
                        <img id="previewArte" class="art-thumb" style="width:160px;height:120px" alt="Prévia da arte selecionada">
                        <span class="small text-secondary ms-2">Prévia da arte</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="min-width:220px">Produto / composição</th>
                        <th style="min-width:90px">Largura (m)</th>
                        <th style="min-width:90px">Altura (m)</th>
                        <th style="min-width:90px">Espessura (m)</th>
                        <th style="min-width:90px">Qtd</th>
                        <th style="min-width:120px">Custo / base</th>
                        <th style="min-width:120px">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="linhasProdutos"></tbody>
            </table>
        </div>

        <button class="btn btn-outline-success btn-sm" type="button" id="adicionarProduto">+ Adicionar produto</button>

        <div class="d-flex justify-content-end align-items-center gap-3 mt-4">
            <div class="text-end">
                <div class="small text-secondary">Total estimado</div>
                <div class="fs-3 fw-bold" id="totalOrcamento">R$ 0,00</div>
            </div>
            <button class="btn btn-success btn-lg" id="btnSalvarOrcamento">Salvar orçamento</button>
        </div>
    </form>

    <template id="linhaProduto">
        <tr>
            <td>
                <select class="form-select produto-select" name="item_ref[]" required>
                    <option value="">Selecione...</option>
                    <?php if (!empty($composicoesOrc)): ?>
                        <optgroup label="Composições (materiais)">
                            <?php foreach ($composicoesOrc as $co): ?>
                                <option value="c:<?= (int)$co['id'] ?>"
                                    data-kind="comp"
                                    data-unit="<?= e($co['unidade']) ?>"
                                    data-price="<?= e($co['preco_ref'] ?? 0) ?>"
                                    data-margem="<?= e($co['margem_percentual']) ?>"
                                    data-itens="<?= e(json_encode($co['itens_json'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"
                                    data-variacoes="<?= e(json_encode($co['variacoes'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"
                                    data-custos="<?= e(json_encode($co['custos'] ?? [], JSON_UNESCAPED_UNICODE)) ?>">
                                    <?= e($co['nome']) ?>
                                </option>
                            <?php endforeach ?>
                        </optgroup>
                    <?php endif ?>
                    <optgroup label="Produtos">
                        <?php foreach ($produtosOrc as $prod): ?>
                            <option value="p:<?= (int)$prod['id'] ?>"
                                data-kind="prod"
                                data-unit="<?= e($prod['unidade']) ?>"
                                data-price="<?= e($prod['preco']) ?>">
                                <?= e($prod['nome']) ?> — <?= money($prod['preco']) ?> / <?= e(str_replace(['m2', 'metro_linear', 'unidade'], ['m²', 'm linear', 'un.'], $prod['unidade'])) ?>
                            </option>
                        <?php endforeach ?>
                    </optgroup>
                </select>
            </td>
            <td><input  class="form-control largura" type="text" inputmode="decimal" step="any" autocomplete="off" pattern="[0-9]*[.,]?[0-9]*" title="Use números, ex.: 2,5 ou 2.5" name="largura[]" placeholder="m (ex.: 2,5)"></td>
            <td><input  class="form-control altura" type="text" inputmode="decimal" step="any" autocomplete="off" pattern="[0-9]*[.,]?[0-9]*" title="Use números, ex.: 2,5 ou 2.5" name="altura[]" placeholder="m (ex.: 2,5)"></td>
            <td><input  class="form-control espessura" type="text" inputmode="decimal" step="any" autocomplete="off" pattern="[0-9]*[.,]?[0-9]*" title="Use números, ex.: 2,5 ou 2.5" name="espessura[]" placeholder="m (ex.: 2,5)"></td>
            <td><input class="form-control quantidade" type="number" min="0.01" step="0.01" name="quantidade[]" value="1" required></td>
            <td class="precoBase">—</td>
            <td class="totalLinha fw-semibold">R$ 0,00</td>
            <td><button class="btn btn-outline-danger btn-sm removerLinha" type="button" aria-label="Remover">×</button></td>
        </tr>
        <tr class="detalhe-materias d-none">
            <td colspan="8" class="bg-light small py-2">
                <div class="detalhe-body text-secondary">—</div>
                <div class="variacoes-wrap mt-2"></div>
            </td>
        </tr>
    </template>
</div>

<div class="panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Orçamentos registrados</h5>
        <span class="text-secondary small"><?= count($rows) ?> registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Arte</th>
                    <th>Nº</th>
                    <th>Cliente</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">Nenhum orçamento cadastrado ainda.</td>
                    </tr>
                <?php endif ?>

                <?php foreach ($rows as $r):
                    $obsEdit = $r['descricao'] ?? '';
                    if (preg_match('/Observações:\s*(.+)$/u', $obsEdit, $mObs)) {
                        $obsEdit = trim($mObs[1]);
                    } else {
                        $obsEdit = '';
                    }
                ?>
                    <tr>
                        <td>
                            <?php if (!empty($r['imagem_path'])): ?>
                                <button type="button" class="btn p-0 border-0"
                                    data-art-src="<?= e(public_url($r['imagem_path'])) ?>"
                                    data-art-title="Arte do orçamento #<?= (int)$r['id'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalArte">
                                    <img class="art-thumb" src="<?= e(public_url($r['imagem_path'])) ?>" alt="Arte #<?= (int)$r['id'] ?>" loading="lazy">
                                </button>
                            <?php else: ?>
                                <span class="text-secondary small">Sem arte</span>
                            <?php endif ?>
                        </td>
                        <td>#<?= str_pad((string)$r['id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td><?= e($r['cliente']) ?></td>
                        <td><?= e($r['descricao']) ?></td>
                        <td><?= money($r['valor']) ?></td>
                        <td>
                            <span class="badge text-bg-<?= $r['status'] === 'aprovado' ? 'success' : ($r['status'] === 'rejeitado' || $r['status'] === 'cancelado' ? 'secondary' : 'warning') ?>">
                                <?= e(ucfirst($r['status'])) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($r['criado_em'])) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-icon btn-pdf"
                                    title="Visualizar PDF"
                                    data-pdf-id="<?= (int)$r['id'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalPdf">
                                    <i class="bi bi-file-pdf"></i>
                                </button>

                                <?php if (in_array($r['status'], ['pendente', 'rejeitado'], true)): ?>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-icon btn-editar-orc"
                                        title="Editar"
                                        data-id="<?= (int)$r['id'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                <?php endif ?>

                                <form method="post" class="d-flex gap-1 align-items-center">
                                    <input type="hidden" name="action" value="orcamento_status">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <select class="form-select form-select-sm" name="status" style="width:auto;min-width:110px">
                                        <?php foreach (['pendente', 'aprovado', 'rejeitado', 'cancelado'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $r['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-success btn-icon" title="Atualizar status">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                </form>

                                <?php if (in_array($r['status'], ['pendente', 'rejeitado'], true)): ?>
                                    <form method="post" onsubmit="return confirm('Cancelar este orçamento?')">
                                        <input type="hidden" name="action" value="orcamento_delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger btn-icon" title="Cancelar">
                                            <i class="bi bi-trash"></i>
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

<!-- Modal de visualização da arte -->
<div class="modal fade" id="modalArte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalArteTitulo">Arte do serviço</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalArteImg" class="art-preview" src="" alt="Prévia ampliada da arte">
            </div>
        </div>
    </div>
</div>

<script>
    // Dados dos orçamentos para edição
    const orcamentosData = <?= json_encode(array_map(function ($r) use ($itensPorOrc) {
        $obs = $r['descricao'] ?? '';
        if (preg_match('/Observações:\s*(.+)$/u', $obs, $m)) {
            $obs = trim($m[1]);
        } else {
            $obs = '';
        }
        return [
            'id' => (int)$r['id'],
            'cliente_id' => (int)$r['cliente_id'],
            'descricao' => $obs,
            'imagem' => $r['imagem_path'] ?? '',
            'itens' => $itensPorOrc[(int)$r['id']] ?? []
        ];
    }, $rows), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script>
    document.getElementById('linhasProdutos').addEventListener('input', function (e) {
    if (!e.target.matches('.largura, .altura, .espessura')) return;
    let v = e.target.value.replace(/\D/g, '');
    e.target.value = (parseInt(v || '0', 10) / 100).toFixed(2);
});
    (() => {
        // Preview da imagem
        const file = document.getElementById('imagemArte');
        if (file) {
            file.addEventListener('change', () => {
                const f = file.files?.[0];
                const wrap = document.getElementById('previewArteWrap');
                const img = document.getElementById('previewArte');
                if (!f) {
                    wrap.classList.add('d-none');
                    img.removeAttribute('src');
                    return;
                }
                if (f.size > 5 * 1024 * 1024) {
                    alert('A imagem deve ter no máximo 5 MB.');
                    file.value = '';
                    wrap.classList.add('d-none');
                    return;
                }
                const url = URL.createObjectURL(f);
                img.src = url;
                wrap.classList.remove('d-none');
            });
        }

        // Modal da arte
        document.querySelectorAll('[data-art-src]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('modalArteImg').src = btn.dataset.artSrc;
                document.getElementById('modalArteTitulo').textContent = btn.dataset.artTitle || 'Arte do serviço';
            });
        });
    })();

    // =====================================================
    // Formulário de NOVO orçamento + cálculo
    // =====================================================
    (() => {
        const body = document.getElementById('linhasProdutos');
        const template = document.getElementById('linhaProduto');
        const add = document.getElementById('adicionarProduto');
        const total = document.getElementById('totalOrcamento');

        const money = n => 'R$ ' + Number(n || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        // Aceita vírgula ou ponto: "2,5", "2.5", "1.234,5"
        const num = v => {
            let s = String(v ?? '').trim().replace(/\s/g, '');
            if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.');
            const n = parseFloat(s);
            return Number.isFinite(n) && n > 0 ? n : 0;
        };
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function calc(row) {
            const sel = row.querySelector('.produto-select');
            if (!sel) return;

            const opt = sel.options[sel.selectedIndex];
            const kind = opt?.dataset.kind || 'prod';
            const unit = opt?.dataset.unit || '';
            const price = Number(opt?.dataset.price || 0);
            const margem = Number(opt?.dataset.margem || 0);
            const w = num(row.querySelector('.largura')?.value);
            const h = num(row.querySelector('.altura')?.value);
            const e = num(row.querySelector('.espessura')?.value);
            const q = Number(row.querySelector('.quantidade')?.value || 0);

            const detalheTr = row.nextElementSibling && row.nextElementSibling.classList.contains('detalhe-materias')
                ? row.nextElementSibling
                : null;

            if (row.querySelector('.largura')) row.querySelector('.largura').required = unit === 'm2' || unit === 'metro_linear';
            if (row.querySelector('.altura')) row.querySelector('.altura').required = unit === 'm2';

            let line = 0;
            let detalheHtml = '';

            const fator = (modo) => {
                if (modo === 'por_m2') return Math.max(0.0001, w * h) * q;
                if (modo === 'por_m2_le') return Math.max(0.0001, w * e) * q;
                if (modo === 'por_m2_ae') return Math.max(0.0001, h * e) * q;
                if (modo === 'por_metro' || modo === 'por_largura') return Math.max(0.0001, w) * q;
                if (modo === 'por_altura') return Math.max(0.0001, h) * q;
                if (modo === 'por_espessura') return Math.max(0.0001, e) * q;
                if (modo === 'perimetro_la') return Math.max(0.0001, (w + h) * 2) * q;
                if (modo === 'perimetro_le') return Math.max(0.0001, (w + e) * 2) * q;
                if (modo === 'perimetro_ae') return Math.max(0.0001, (h + e) * 2) * q;
                return q;
            };

            const calcItens = (itens) => {
                let custo = 0;
                const lines = [];
                (itens || []).forEach(it => {
                    const qtd = Number(it.qtd || 0) * fator(it.modo);
                    const c = qtd * Number(it.custo || 0);
                    custo += c;
                    lines.push({
                        nome: it.nome || 'Matéria',
                        qtd,
                        c,
                        modo: it.modo
                    });
                });
                return { custo, lines };
            };

            if (kind === 'comp' && opt?.dataset.itens) {
                let itens = [];
                try { itens = JSON.parse(opt.dataset.itens); } catch (err) { itens = []; }

                let vars = [];
                try { vars = JSON.parse(opt.dataset.variacoes || '[]'); } catch (err) { vars = []; }

                let varIds = [];
                if (detalheTr) {
                    detalheTr.querySelectorAll('.var-check:checked').forEach(ch => varIds.push(Number(ch.value)));
                }

                let allLines = [];
                let base = calcItens(itens);
                let custoMaterias = base.custo;
                let custo = base.custo;
                allLines = allLines.concat(base.lines);

                vars.forEach(v => {
                    if (varIds.indexOf(Number(v.id)) === -1) return;
                    const extra = calcItens(v.itens || []);
                    custo += extra.custo;
                    custoMaterias += extra.custo;
                    extra.lines.forEach(l => {
                        l.nome = l.nome + ' [' + v.nome + ']';
                        allLines.push(l);
                    });
                });

                let custosExtra = [];
                try { custosExtra = JSON.parse(opt.dataset.custos || '[]'); } catch (err) { custosExtra = []; }

                custosExtra.forEach(cx => {
                    let linha = 0;
                    const val = Number(cx.valor || 0);
                    if (cx.modo === 'percentual_materias') linha = custoMaterias * (val / 100);
                    else if (cx.modo === 'por_largura') linha = val * Math.max(0.0001, w) * q;
                    else if (cx.modo === 'por_altura') linha = val * Math.max(0.0001, h) * q;
                    else if (cx.modo === 'por_m2') linha = val * Math.max(0.0001, w * h) * q;
                    else linha = val * q;
                    custo += linha;
                    allLines.push({
                        nome: (cx.nome || 'Outro custo') + ' (outro custo)',
                        qtd: 1,
                        c: linha,
                        modo: cx.modo
                    });
                });

                line = Math.round(custo * (1 + margem / 100) * 100) / 100; // mesmo arredondamento do servidor
                row.querySelector('.precoBase').textContent = sel.value
                    ? ('custo ' + money(custo) + (margem ? ' + ' + margem + '%' : ''))
                    : '—';

                detalheHtml = '<div class="d-flex flex-wrap gap-3 mb-2">' +
                    '<span class="badge text-bg-secondary">Custo matérias: <strong>' + money(custo) + '</strong></span>' +
                    '<span class="badge text-bg-info">Margem: ' + margem + '%</span>' +
                    '<span class="badge text-bg-success">Preço venda: <strong>' + money(line) + '</strong></span>' +
                    '</div>' +
                    '<strong class="text-dark">Detalhe de matérias (interno — não vai no PDF)</strong>' +
                    '<div class="table-responsive mt-1"><table class="table table-sm table-bordered mb-0 bg-white"><thead><tr><th>Matéria</th><th>Qtd calc.</th><th>Custo unit.</th><th>Subtotal</th></tr></thead><tbody>';

                allLines.forEach(l => {
                    const unitC = l.qtd ? (l.c / l.qtd) : 0;
                    detalheHtml += '<tr><td>' + esc(l.nome) + '</td><td>' + Number(l.qtd).toLocaleString('pt-BR', {
                        maximumFractionDigits: 4
                    }) + '</td><td>' + money(unitC) + '</td><td><strong>' + money(l.c) + '</strong></td></tr>';
                });

                detalheHtml += '<tr class="table-light"><td colspan="3" class="text-end fw-semibold">Custos totais</td><td class="fw-bold">' + money(custo) + '</td></tr></tbody></table></div>';

                if (margem > 0) {
                    detalheHtml += '<p class="small text-secondary mb-0 mt-1">O total do orçamento usa o <strong>preço de venda</strong> (custo + margem). Confira o <strong>Custo matérias</strong> acima para ver a margem aplicada.</p>';
                }

                if (detalheTr) {
                    const wrap = detalheTr.querySelector('.variacoes-wrap');
                    if (wrap && vars.length) {
                        let html = '<div class="fw-semibold text-dark mb-1">Variações</div>';
                        vars.forEach(v => {
                            const checked = varIds.indexOf(Number(v.id)) >= 0 ? 'checked' : '';
                            const rowIdx = Array.from(body.children).filter(r => !r.classList.contains('detalhe-materias')).indexOf(row);
                            html += '<label class="me-3 small"><input type="checkbox" class="var-check" name="variacoes[' + rowIdx + '][]" value="' + v.id + '" ' + checked + '> ' + esc(v.nome) + ' <span class="text-secondary">(' + esc(v.tipo || '') + ')</span></label>';
                        });
                        wrap.innerHTML = html;
                        wrap.querySelectorAll('.var-check').forEach(ch => {
                            ch.onchange = () => calc(row);
                        });
                    } else if (wrap) {
                        wrap.innerHTML = '';
                    }
                }

                if (detalheTr) {
                    detalheTr.classList.remove('d-none');
                    detalheTr.querySelector('.detalhe-body').innerHTML = detalheHtml;
                }
            } else {
                row.querySelector('.precoBase').textContent = sel.value
                    ? money(price) + ' / ' + ({
                        'm2': 'm²',
                        'metro_linear': 'm linear',
                        'unidade': 'un.'
                    }[unit] || unit)
                    : '—';

                const base = unit === 'm2' ? w * h : unit === 'metro_linear' ? w : 1;
                line = Math.round(base * price * q * 100) / 100;

                if (detalheTr) {
                    detalheTr.classList.add('d-none');
                    detalheTr.querySelector('.detalhe-body').innerHTML = '';
                    const wrap = detalheTr.querySelector('.variacoes-wrap');
                    if (wrap) wrap.innerHTML = '';
                }
            }

            row.querySelector('.totalLinha').textContent = money(line);
            row.dataset.total = String(line);

            let sum = 0;
            Array.from(body.children).forEach(r => {
                if (!r.classList.contains('detalhe-materias')) {
                    sum += Number(r.dataset.total || 0);
                }
            });
            total.textContent = money(sum);
        }

        function addRow() {
            const nodes = template.content.cloneNode(true);
            const row = nodes.querySelector('tr');
            const det = nodes.querySelector('tr.detalhe-materias');

            body.appendChild(row);
            if (det) body.appendChild(det);

            row.addEventListener('input', () => calc(row));
            row.addEventListener('change', () => calc(row));

            row.querySelector('.removerLinha').addEventListener('click', () => {
                if (det && det.parentNode) det.remove();
                row.remove();

                let sum = 0;
                Array.from(body.children).forEach(r => {
                    if (!r.classList.contains('detalhe-materias')) {
                        sum += Number(r.dataset.total || 0);
                    }
                });
                total.textContent = money(sum);
            });

            calc(row);
        }

        // Antes de enviar: alinha o índice das variações (variacoes[i][]) com a posição real de cada item
        document.getElementById('formOrcamento').addEventListener('submit', () => {
            let i = 0;
            Array.from(body.children).forEach(r => {
                if (r.classList.contains('detalhe-materias')) return;
                const det = r.nextElementSibling;
                if (det && det.classList.contains('detalhe-materias')) {
                    det.querySelectorAll('.var-check').forEach(ch => { ch.name = 'variacoes[' + i + '][]'; });
                }
                i++;
            });
        });

        add.addEventListener('click', addRow);
        addRow(); // linha inicial
    })();

    // =====================================================
    // Edição no mesmo formulário
    // =====================================================
    (() => {
        const form = document.getElementById('formOrcamento');
        const titulo = document.getElementById('tituloFormOrcamento');
        const btnSalvar = document.getElementById('btnSalvarOrcamento');
        const btnCancelar = document.getElementById('btnCancelarEdicao');
        const body = document.getElementById('linhasProdutos');
        const selectCliente = form.querySelector('[name="cliente_id"]');
        const inputDescricao = form.querySelector('[name="descricao"]');
        const inputAction = form.querySelector('[name="action"]');
        const inputId = form.querySelector('[name="id"]');
        // mostra medidas salvas no formato brasileiro (2.500 -> 2,5)
        const fmtDec = v => (v == null || v === '' || isNaN(parseFloat(v))) ? '' : String(parseFloat(v)).replace('.', ',');

        function modoNovo() {
            form.reset();
            inputAction.value = 'orcamento_save';
            inputId.value = '';
            titulo.textContent = 'Novo orçamento';
            btnSalvar.textContent = 'Salvar orçamento';
            btnCancelar.classList.add('d-none');

            // Limpa linhas e cria uma nova
            body.innerHTML = '';
            document.getElementById('adicionarProduto').click();

            // Limpa preview
            const previewWrap = document.getElementById('previewArteWrap');
            if (previewWrap) previewWrap.classList.add('d-none');
        }

        function carregarEdicao(id) {
            const orc = orcamentosData.find(o => o.id === id);
            if (!orc) return;

            inputAction.value = 'orcamento_update';
            inputId.value = orc.id;
            titulo.textContent = `Editando orçamento #${String(orc.id).padStart(5, '0')}`;
            btnSalvar.textContent = 'Salvar alterações';
            btnCancelar.classList.remove('d-none');

            selectCliente.value = orc.cliente_id;
            inputDescricao.value = orc.descricao || '';

            // Limpa linhas atuais
            body.innerHTML = '';

            if (orc.itens && orc.itens.length) {
                orc.itens.forEach(item => {
                    document.getElementById('adicionarProduto').click();

                    const rows = Array.from(body.children).filter(r => !r.classList.contains('detalhe-materias'));
                    const row = rows[rows.length - 1];

                    const select = row.querySelector('.produto-select');
                    const ref = item.composicao_id
                        ? `c:${item.composicao_id}`
                        : `p:${item.produto_id}`;

                    select.value = ref;

                    if (row.querySelector('.largura')) row.querySelector('.largura').value = fmtDec(item.largura);
                    if (row.querySelector('.altura')) row.querySelector('.altura').value = fmtDec(item.altura);
                    if (row.querySelector('.espessura')) row.querySelector('.espessura').value = fmtDec(item.espessura);
                    if (row.querySelector('.quantidade')) row.querySelector('.quantidade').value = item.quantidade ?? 1;

                    // Dispara o cálculo (renderiza as variações da composição)
                    select.dispatchEvent(new Event('change', { bubbles: true }));

                    // Restaura as variações que estavam marcadas no orçamento salvo
                    if (item.variacoes_json) {
                        let ids = [];
                        try { ids = JSON.parse(item.variacoes_json) || []; } catch (err) { ids = []; }
                        const det = row.nextElementSibling;
                        if (det && ids.length) {
                            det.querySelectorAll('.var-check').forEach(ch => { ch.checked = ids.includes(Number(ch.value)); });
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                });
            } else {
                document.getElementById('adicionarProduto').click();
            }

            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Botões de editar
        document.querySelectorAll('.btn-editar-orc').forEach(btn => {
            btn.addEventListener('click', () => {
                carregarEdicao(Number(btn.dataset.id));
            });
        });

        // Cancelar edição
        btnCancelar?.addEventListener('click', () => {
            if (confirm('Deseja cancelar a edição? As alterações não salvas serão perdidas.')) {
                modoNovo();
            }
        });
    })();
</script>

<!-- Modal de pré-visualização do PDF -->
<div class="modal fade" id="modalPdf" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPdfTitulo">Pré-visualização do orçamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfFrame" title="Pré-visualização do PDF" style="width:100%;height:72vh;border:0;background:var(--surface-2)"></iframe>
            </div>
            <div class="modal-footer">
                <span class="small text-secondary me-auto">Se o PDF não aparecer neste celular/navegador, use “Abrir em nova aba”.</span>
                <a id="pdfNovaAba" class="btn btn-outline-secondary" target="_blank" rel="noopener" href="#"><i class="bi bi-box-arrow-up-right"></i> Abrir em nova aba</a>
                <a id="pdfBaixar" class="btn btn-success" href="#"><i class="bi bi-download"></i> Baixar PDF</a>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const frame = document.getElementById('pdfFrame');
        const modal = document.getElementById('modalPdf');
        if (!frame || !modal) return;
        document.querySelectorAll('.btn-pdf').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.pdfId;
                const base = '?page=orcamento_pdf&id=' + encodeURIComponent(id);
                document.getElementById('modalPdfTitulo').textContent = 'Orçamento #' + String(id).padStart(5, '0');
                document.getElementById('pdfNovaAba').href = base;
                document.getElementById('pdfBaixar').href = base + '&download=1';
                frame.src = base;
            });
        });
        // libera memória ao fechar
        modal.addEventListener('hidden.bs.modal', () => { frame.src = 'about:blank'; });
    })();
</script>
