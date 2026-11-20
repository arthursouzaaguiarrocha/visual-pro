<?php
declare(strict_types=1);

class CatalogoController extends Controller
{
    public function produtoSave(): void
    {

            $this->requireRole(['admin']);
            $nome = trim($_POST['nome'] ?? '');
            $preco = (float)($_POST['preco'] ?? 0);
            if ($nome === '' || $preco < 0) throw new RuntimeException('Informe nome e preço válido.');
            if (!in_array($_POST['unidade'] ?? 'unidade', ['unidade', 'm2', 'metro_linear'], true)) throw new RuntimeException('Unidade inválida.');
            $id = (int)($_POST['id'] ?? 0);
            if ($id) { $s = $this->pdo->prepare('UPDATE produtos SET nome=?,categoria=?,unidade=?,preco=? WHERE id=?'); $s->execute([$nome, $_POST['categoria'] ?? 'outro', $_POST['unidade'] ?? 'unidade', $preco, $id]); }
            else { $s = $this->pdo->prepare('INSERT INTO produtos(nome,categoria,unidade,preco) VALUES(?,?,?,?)'); $s->execute([$nome, $_POST['categoria'] ?? 'outro', $_POST['unidade'] ?? 'unidade', $preco]); }
            flash($id ? 'Produto atualizado.' : 'Produto cadastrado.');
            go('produtos');

    }

    public function produtoDelete(): void
    {

            $this->requireRole(['admin']); $id=(int)($_POST['id'] ?? 0);
            $check=$this->pdo->prepare('SELECT COUNT(*) FROM orcamento_itens_cv WHERE produto_id=?'); $check->execute([$id]);
            if ((int)$check->fetchColumn()>0) { $s=$this->pdo->prepare('UPDATE produtos SET ativo=0 WHERE id=?'); $s->execute([$id]); flash('Produto desativado porque está vinculado a orçamentos.'); }
            else { $s=$this->pdo->prepare('DELETE FROM produtos WHERE id=?'); $s->execute([$id]); flash('Produto excluído.'); }
            go('produtos');

    }

    public function servicoSave(): void
    {

            $this->requireRole(['admin']);
            $nome = trim($_POST['nome'] ?? '');
            $preco = (float)($_POST['preco'] ?? 0);
            if ($nome === '' || $preco < 0) throw new RuntimeException('Informe nome e preço válido.');
            $id = (int)($_POST['id'] ?? 0);
            if ($id) { $s=$this->pdo->prepare('UPDATE servicos SET nome=?,unidade=?,preco=? WHERE id=?'); $s->execute([$nome,$_POST['unidade'] ?? 'fixo',$preco,$id]); }
            else { $s = $this->pdo->prepare('INSERT INTO servicos(nome,unidade,preco) VALUES(?,?,?)'); $s->execute([$nome, $_POST['unidade'] ?? 'fixo', $preco]); }
            flash($id ? 'Serviço atualizado.' : 'Serviço cadastrado.');
            go('servicos');

    }

    public function servicoDelete(): void
    {

            $this->requireRole(['admin']); $id=(int)($_POST['id'] ?? 0); $s=$this->pdo->prepare('DELETE FROM servicos WHERE id=?'); $s->execute([$id]); flash('Serviço excluído.'); go('servicos');

    }

    public function materiaSave(): void
    {

            $this->requireRole(['admin']);
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $unidade = $_POST['unidade'] ?? 'unidade';
            $unidades = ['unidade','m','m2','metro_linear','kg','litro','pacote'];
            $custo = (float)($_POST['custo'] ?? 0);
            if ($nome === '' || $custo < 0 || !in_array($unidade, $unidades, true)) throw new RuntimeException('Informe nome, unidade e custo válidos.');
            if ($id) {
                $s = $this->pdo->prepare('UPDATE materias_primas SET nome=?,unidade=?,custo=? WHERE id=?');
                $s->execute([$nome, $unidade, $custo, $id]);
                flash('Matéria-prima atualizada.');
            } else {
                $s = $this->pdo->prepare('INSERT INTO materias_primas(nome,unidade,custo) VALUES(?,?,?)');
                $s->execute([$nome, $unidade, $custo]);
                flash('Matéria-prima cadastrada.');
            }
            go('materias');

    }

    public function materiaDelete(): void
    {

            $this->requireRole(['admin']);
            $id = (int)($_POST['id'] ?? 0);
            // Nunca exclui fisicamente: só desativa (não remove de composições históricas)
            $this->pdo->prepare('UPDATE materias_primas SET ativo=0 WHERE id=?')->execute([$id]);
            flash('Matéria-prima desativada (não foi excluída do sistema).');
            go('materias');

    }

    public function materiaReativar(): void
    {

            $this->requireRole(['admin']);
            $id = (int)($_POST['id'] ?? 0);
            $this->pdo->prepare('UPDATE materias_primas SET ativo=1 WHERE id=?')->execute([$id]);
            flash('Matéria-prima reativada.');
            go('materias');

    }

    public function variacaoSave(): void
    {

            $this->requireRole(['admin']);
            $id = (int)($_POST['id'] ?? 0);
            $composicaoId = (int)($_POST['composicao_id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $tipo = $_POST['tipo'] ?? 'opcional';
            if ($composicaoId <= 0 || $nome === '' || !in_array($tipo, ['equipamento','acabamento','opcional'], true)) {
                throw new RuntimeException('Informe composição, nome e tipo da variação.');
            }
            $matIds = $_POST['materia_id'] ?? [];
            $qtds = $_POST['mat_qtd'] ?? [];
            $modos = $_POST['mat_modo'] ?? [];
            $modosOk = ['unidade','fixo','por_unidade','por_metro','por_largura','por_altura','por_espessura','por_m2','por_m2_le','por_m2_ae','perimetro_la','perimetro_le','perimetro_ae'];
            $linhas = [];
            if (is_array($matIds)) {
                foreach ($matIds as $i => $mid) {
                    $mid = (int)$mid;
                    if ($mid <= 0) continue;
                    $q = (float)($qtds[$i] ?? 0);
                    $modo = $modos[$i] ?? 'unidade';
                    if ($q <= 0) continue;
                    if (!in_array($modo, $modosOk, true)) $modo = 'unidade';
                    $linhas[] = [$mid, $q, $modo];
                }
            }
            if (!$linhas) throw new RuntimeException('Adicione ao menos uma matéria na variação.');
            $this->pdo->beginTransaction();
            if ($id) {
                $this->pdo->prepare('UPDATE composicao_variacoes SET nome=?,tipo=? WHERE id=? AND composicao_id=?')->execute([$nome,$tipo,$id,$composicaoId]);
                $this->pdo->prepare('DELETE FROM composicao_variacao_itens WHERE variacao_id=?')->execute([$id]);
                $vid = $id;
            } else {
                $this->pdo->prepare('INSERT INTO composicao_variacoes(composicao_id,nome,tipo) VALUES(?,?,?)')->execute([$composicaoId,$nome,$tipo]);
                $vid = (int)$this->pdo->lastInsertId();
            }
            $ins = $this->pdo->prepare('INSERT INTO composicao_variacao_itens(variacao_id,materia_id,quantidade,modo) VALUES(?,?,?,?)');
            foreach ($linhas as $ln) $ins->execute([$vid, $ln[0], $ln[1], $ln[2]]);
            $this->pdo->commit();
            flash($id ? 'Variação atualizada.' : 'Variação cadastrada.');
            go('materias');

    }

    public function variacaoDelete(): void
    {

            $this->requireRole(['admin']);
            $id = (int)($_POST['id'] ?? 0);
            $this->pdo->prepare('UPDATE composicao_variacoes SET ativo=0 WHERE id=?')->execute([$id]);
            flash('Variação desativada.');
            go('materias');

    }

    public function custoExtraSave(): void
    {

            $this->requireRole(['admin']);
            $composicaoId = (int)($_POST['composicao_id'] ?? 0);
            $nomes = $_POST['custo_nome'] ?? [];
            $valores = $_POST['custo_valor'] ?? [];
            $modos = $_POST['custo_modo'] ?? [];
            if ($composicaoId <= 0) throw new RuntimeException('Selecione a composição.');
            $modosOk = ['fixo','por_unidade','por_largura','por_altura','por_m2','percentual_materias'];
            $this->pdo->beginTransaction();
            $this->pdo->prepare('DELETE FROM composicao_custos WHERE composicao_id=?')->execute([$composicaoId]);
            $ins = $this->pdo->prepare('INSERT INTO composicao_custos(composicao_id,nome,valor,modo) VALUES(?,?,?,?)');
            if (is_array($nomes)) {
                foreach ($nomes as $i => $nome) {
                    $nome = trim((string)$nome);
                    if ($nome === '') continue;
                    $val = (float)($valores[$i] ?? 0);
                    $modo = $modos[$i] ?? 'fixo';
                    if (!in_array($modo, $modosOk, true)) $modo = 'fixo';
                    if ($val < 0) continue;
                    $ins->execute([$composicaoId, $nome, $val, $modo]);
                }
            }
            $this->pdo->commit();
            flash('Outros custos da composição salvos.');
            go('materias');

    }

    public function composicaoSave(): void
    {

            $this->requireRole(['admin']);
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $tipo = $_POST['tipo'] ?? 'outro';
            $tipos = ['portao','marquise_acm','marquise_lona','toldo','tenda','outro'];
            $unidade = $_POST['unidade'] ?? 'unidade';
            $unidades = ['unidade','m2','metro_linear'];
            $margem = (float)($_POST['margem_percentual'] ?? 30);
            if ($nome === '' || !in_array($tipo, $tipos, true) || !in_array($unidade, $unidades, true) || $margem < 0) {
                throw new RuntimeException('Preencha nome, tipo, unidade e margem válidos.');
            }
            $matIds = $_POST['materia_id'] ?? [];
            $qtds = $_POST['mat_qtd'] ?? [];
            $modos = $_POST['mat_modo'] ?? [];
            if (!is_array($matIds) || count($matIds) === 0) throw new RuntimeException('Adicione pelo menos uma matéria-prima na composição.');
            $linhas = [];
            foreach ($matIds as $i => $mid) {
                $mid = (int)$mid;
                if ($mid <= 0) continue;
                $q = (float)($qtds[$i] ?? 0);
                $modo = $modos[$i] ?? 'fixo';
                if ($q <= 0) throw new RuntimeException('Quantidade de matéria deve ser maior que zero.');
                $modosOk = ['unidade','fixo','por_unidade','por_metro','por_largura','por_altura','por_espessura','por_m2','por_m2_le','por_m2_ae','perimetro_la','perimetro_le','perimetro_ae'];
                if (!in_array($modo, $modosOk, true)) $modo = 'unidade';
                $linhas[] = [$mid, $q, $modo];
            }
            if (!$linhas) throw new RuntimeException('Adicione matérias-primas válidas.');
            $this->pdo->beginTransaction();
            if ($id) {
                $this->pdo->prepare('UPDATE composicoes SET nome=?,tipo=?,unidade=?,margem_percentual=? WHERE id=?')->execute([$nome,$tipo,$unidade,$margem,$id]);
                $this->pdo->prepare('DELETE FROM composicao_itens WHERE composicao_id=?')->execute([$id]);
                $cid = $id;
            } else {
                $this->pdo->prepare('INSERT INTO composicoes(nome,tipo,unidade,margem_percentual) VALUES(?,?,?,?)')->execute([$nome,$tipo,$unidade,$margem]);
                $cid = (int)$this->pdo->lastInsertId();
            }
            $ins = $this->pdo->prepare('INSERT INTO composicao_itens(composicao_id,materia_id,quantidade,modo) VALUES(?,?,?,?)');
            foreach ($linhas as $ln) $ins->execute([$cid, $ln[0], $ln[1], $ln[2]]);
            $this->pdo->commit();
            flash($id ? 'Composição atualizada.' : 'Composição cadastrada.');
            go('materias');

    }

    public function composicaoDelete(): void
    {

            $this->requireRole(['admin']);
            $id = (int)($_POST['id'] ?? 0);
            $this->pdo->prepare('UPDATE composicoes SET ativo=0 WHERE id=?')->execute([$id]);
            flash('Composição desativada.');
            go('materias');

    }


    public function produtos(): void
    {
        $page = 'produtos';

        $rows = $this->pdo->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll();

        $this->renderApp('produtos', get_defined_vars());
    }

    public function materias(): void
    {
        $page = 'materias';

        $materias = $this->pdo->query('SELECT * FROM materias_primas ORDER BY ativo DESC, nome')->fetchAll();
        $composicoes = $this->pdo->query('SELECT * FROM composicoes ORDER BY ativo DESC, tipo, nome')->fetchAll();
        $compItens = [];
        $compVariacoes = [];
        $varItens = [];
        if ($composicoes) {
            $ids = implode(',', array_map('intval', array_column($composicoes, 'id')));
            $rowsCi = $this->pdo->query("SELECT ci.*, m.nome materia_nome, m.custo, m.unidade materia_unidade FROM composicao_itens ci JOIN materias_primas m ON m.id=ci.materia_id WHERE ci.composicao_id IN ($ids) ORDER BY ci.id")->fetchAll();
            foreach ($rowsCi as $ci) $compItens[(int)$ci['composicao_id']][] = $ci;
            try {
                $vars = $this->pdo->query("SELECT * FROM composicao_variacoes WHERE composicao_id IN ($ids) ORDER BY ativo DESC, nome")->fetchAll();
                foreach ($vars as $v) $compVariacoes[(int)$v['composicao_id']][] = $v;
                if ($vars) {
                    $vids = implode(',', array_map('intval', array_column($vars, 'id')));
                    $vrows = $this->pdo->query("SELECT cvi.*, m.nome materia_nome, m.custo FROM composicao_variacao_itens cvi JOIN materias_primas m ON m.id=cvi.materia_id WHERE cvi.variacao_id IN ($vids)")->fetchAll();
                    foreach ($vrows as $vr) $varItens[(int)$vr['variacao_id']][] = $vr;
                }
            } catch (Throwable $e) {
                // tabelas de variação ainda não migradas
            }
            $compCustos = [];
            try {
                $crows = $this->pdo->query("SELECT * FROM composicao_custos WHERE composicao_id IN ($ids) ORDER BY id")->fetchAll();
                foreach ($crows as $cr) $compCustos[(int)$cr['composicao_id']][] = $cr;
            } catch (Throwable $e) {}
        }

        $this->renderApp('materias', get_defined_vars());
    }

    public function servicos(): void
    {
        $page = 'servicos';

        $rows = $this->pdo->query('SELECT * FROM servicos ORDER BY id DESC')->fetchAll();

        $this->renderApp('servicos', get_defined_vars());
    }
}
