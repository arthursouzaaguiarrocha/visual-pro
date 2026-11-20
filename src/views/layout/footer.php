        <footer class="text-secondary small text-center mt-4">Visual Pro · Gestão de comunicação visual</footer>
    </main>

    <script>
        (() => {
            // Pesquisa independente por tabela/módulo, sem requisições ao servidor.
            document.querySelectorAll('.table-responsive').forEach((wrap, index) => {
                const table = wrap.querySelector('table');
                if (!table || !table.tBodies.length) return;
                if (wrap.closest('form') || wrap.closest('.modal')) return; // não injetar busca em formulários/modais
                const tbody = table.tBodies[0];
                const rows = Array.from(tbody.rows);
                if (!rows.length) return;
                const toolbar = document.createElement('div');
                toolbar.className = 'search-toolbar';
                const label = document.createElement('div');
                label.className = 'module-search-wrap';
                const icon = document.createElement('span');
                icon.className = 'search-icon';
                icon.textContent = '⌕';
                const input = document.createElement('input');
                input.type = 'search';
                input.className = 'form-control module-search';
                input.placeholder = 'Pesquisar neste módulo...';
                input.setAttribute('aria-label', 'Pesquisar nesta tabela');
                label.append(icon, input);
                const count = document.createElement('span');
                count.className = 'search-count';
                count.setAttribute('aria-live', 'polite');
                toolbar.append(label, count);
                wrap.parentNode.insertBefore(toolbar, wrap);
                const empty = document.createElement('div');
                empty.className = 'empty-search';
                empty.textContent = 'Nenhum resultado encontrado para essa pesquisa.';
                wrap.insertAdjacentElement('afterend', empty);
                const update = () => {
                    const term = input.value.trim().toLocaleLowerCase('pt-BR');
                    let shown = 0;
                    rows.forEach(row => {
                        const match = row.innerText.toLocaleLowerCase('pt-BR').includes(term);
                        row.hidden = !match;
                        if (match) shown++;
                    });
                    count.textContent = `${shown} de ${rows.length} registro${rows.length===1?'':'s'}`;
                    empty.style.display = shown ? 'none' : 'block';
                    wrap.style.display = shown ? '' : 'none';
                };
                input.addEventListener('input', update);
                update();
            });
            // Modais e alertas são tratados pelo Bootstrap (bundle carregado abaixo).
        })();
    </script>


    <script>
        (function() {
            const dias = document.getElementById('cpIntervaloDias');
            const parcelas = document.getElementById('cpParcelas');
            const venc = document.getElementById('cpVencimento');
            const valor = document.getElementById('cpValor');
            const preview = document.getElementById('cpPreview');
            const list = document.getElementById('cpPreviewList');
            const per = document.getElementById('cpPeriodicidade');
            const wrap = document.getElementById('cpIntervaloWrap');
            if (!parcelas || !venc || !preview || !list) return;

            function fmt(d) {
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yy = d.getFullYear();
                return dd + '/' + mm + '/' + yy;
            }

            function money(n) {
                return n.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
            }

            function toggleIntervalo() {
                if (!wrap || !per) return;
                wrap.style.display = (per.value === 'personalizado') ? '' : 'none';
            }

            function updatePreview() {
                const qtd = Math.max(1, Math.min(120, parseInt(parcelas.value, 10) || 1));
                const dayVal = Math.max(1, Math.min(365, parseInt(dias && dias.value ? dias.value : '30', 10) || 30));
                const mensal = !per || per.value === 'mensal';
                const vencStr = venc.value;
                const total = parseFloat(valor && valor.value ? valor.value : 0) || 0;

                if (!vencStr || qtd < 2) {
                    preview.style.display = 'none';
                    return;
                }

                const cents = Math.round(total * 100);
                const base = Math.floor(cents / qtd);
                const resto = cents % qtd;

                let cur = new Date(vencStr + 'T12:00:00');
                const dayOfMonth = cur.getDate();
                list.innerHTML = '';
                for (let i = 0; i < qtd; i++) {
                    if (i > 0) {
                        if (mensal) {
                            cur = new Date(cur.getFullYear(), cur.getMonth() + 1, 1);
                            const last = new Date(cur.getFullYear(), cur.getMonth() + 1, 0).getDate();
                            cur.setDate(Math.min(dayOfMonth, last));
                        } else {
                            cur = new Date(cur.getTime());
                            cur.setDate(cur.getDate() + dayVal);
                        }
                    }
                    const v = (base + (i < resto ? 1 : 0)) / 100;
                    const chip = document.createElement('div');
                    chip.className = 'parcela-chip';
                    chip.innerHTML = '<strong>' + (i + 1) + 'ª parcela</strong><span>' + fmt(cur) + '</span><span>' + money(v) + '</span>';
                    list.appendChild(chip);
                }
                preview.style.display = '';
            }

            [parcelas, dias, venc, valor, per].forEach(function(el) {
                if (!el) return;
                el.addEventListener('input', updatePreview);
                el.addEventListener('change', function() { toggleIntervalo(); updatePreview(); });
            });
            toggleIntervalo();
            updatePreview();
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function() {
            // Template options from create form if available
            function productOptionsHtml() {
                const tpl = document.querySelector('#linhaProduto select.produto-select');
                if (tpl) return tpl.innerHTML;
                return '<option value="">Selecione...</option>';
            }
            document.addEventListener('click', function(e) {
                const add = e.target.closest('.btn-add-edit');
                if (add) {
                    const modal = add.closest('.modal');
                    const tbody = modal && modal.querySelector('.linhas-edit');
                    if (!tbody) return;
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td><select class="form-select produto-select" name="item_ref[]" required>' + productOptionsHtml() + '</select></td>' +
                        '<td><input class="form-control largura" type="text" inputmode="decimal" autocomplete="off" pattern="[0-9]*[.,]?[0-9]*" title="Use números, ex.: 2,5 ou 2.5" name="largura[]" placeholder="m (ex.: 2,5)"></td>' +
                        '<td><input class="form-control altura" type="text" inputmode="decimal" autocomplete="off" pattern="[0-9]*[.,]?[0-9]*" title="Use números, ex.: 2,5 ou 2.5" name="altura[]" placeholder="m (ex.: 2,5)"></td>' +
                        '<td><input class="form-control espessura" type="text" inputmode="decimal" autocomplete="off" pattern="[0-9]*[.,]?[0-9]*" title="Use números, ex.: 2,5 ou 2.5" name="espessura[]" placeholder="m (ex.: 2,5)"></td>' +
                        '<td><input class="form-control quantidade" type="number" min="0.01" step="0.01" name="quantidade[]" value="1" required></td>' +
                        '<td><button class="btn btn-outline-danger btn-sm removerLinha" type="button">×</button></td>';
                    tbody.appendChild(tr);
                }
                const rm = e.target.closest('.removerLinha');
                if (rm) {
                    const tbody = rm.closest('tbody');
                    if (tbody && tbody.querySelectorAll('tr').length > 1) rm.closest('tr').remove();
                }
            });
        })();
    </script>

    <script>
        (function() {
            var body = document.body,
                toggle = document.getElementById('menuToggle'),
                backdrop = document.getElementById('sidebarBackdrop');
            if (!toggle) return;

            function set(open) {
                body.classList.toggle('menu-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            toggle.addEventListener('click', function() {
                set(!body.classList.contains('menu-open'));
            });
            backdrop.addEventListener('click', function() {
                set(false);
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') set(false);
            });
        })();
    </script>

</body>

</html>