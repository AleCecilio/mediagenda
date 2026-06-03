<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['cod_usuario'])) {
    header('Location: login.php');
    exit;
}

$cod_usuario = $_SESSION['cod_usuario'];
$result = mysqli_query(
    $conexao_bd, 
    "SELECT nome, email, foto 
    FROM usuario 
    WHERE cod_usuario = '$cod_usuario'"
);
$usuarioLogado = mysqli_fetch_assoc($result);
$operadorNome  = $usuarioLogado['nome']  ?? '';
$operadorEmail = $usuarioLogado['email'] ?? '';
$fotoUsuario   = $usuarioLogado['foto']  ?? null;

// ── AJAX ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    $acao = $_POST['acao'];

    if ($acao === 'salvar') {
        $id   = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            echo json_encode(['success' => false, 'message' => 'O nome da especialidade é obrigatório.']);
            exit;
        }

        if ($id > 0) {
            $stmt = $conexao_bd->prepare('UPDATE especialidades SET nome = ? WHERE id = ?');
            $stmt->bind_param('si', $nome, $id);
            $msg = 'Especialidade atualizada com sucesso!';
        } else {
            $stmt = $conexao_bd->prepare('INSERT INTO especialidades (nome) VALUES (?)');
            $stmt->bind_param('s', $nome);
            $msg = 'Especialidade cadastrada com sucesso!';
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            $err = $conexao_bd->errno === 1062
                ? 'Já existe uma especialidade com esse nome.'
                : 'Erro ao salvar: ' . $conexao_bd->error;
            echo json_encode(['success' => false, 'message' => $err]);
        }
        $stmt->close();
        exit;
    }

    if ($acao === 'alternar_status') {
        $id = (int)($_POST['id'] ?? 0);

        $check = $conexao_bd->prepare('SELECT COUNT(*) AS total FROM medicos WHERE especialidade_id = ? AND status = "Ativo"');
        $check->bind_param('i', $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();

        if ($row['total'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Não é possível inativar: há médicos ativos vinculados a esta especialidade.']);
            exit;
        }

        $stmt = $conexao_bd->prepare('UPDATE especialidades SET status = IF(status="Ativo","Inativo","Ativo") WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Status alterado com sucesso!']);
        $stmt->close();
        exit;
    }

    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);

        $check = $conexao_bd->prepare('SELECT COUNT(*) AS total FROM medicos WHERE especialidade_id = ?');
        $check->bind_param('i', $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();

        if ($row['total'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Não é possível excluir: há médicos vinculados a esta especialidade.']);
            exit;
        }

        $stmt = $conexao_bd->prepare('DELETE FROM especialidades WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Especialidade excluída com sucesso!']);
        $stmt->close();
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit;
}

// ── Dados para a página ───────────────────────────────────────
$especialidades = $conexao_bd
    ->query('SELECT id, nome, status FROM especialidades ORDER BY nome')
    ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Cadastro de Especialidades</title>

    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --azul-primario: #0d6efd;
            --azul-escuro:   #084298;
            --azul-claro:    #e7f1ff;
            --cinza-fundo:   #f5f7fa;
            --cinza-borda:   #e3e6ea;
            --texto-escuro:  #1f2d3d;
            --sidebar-larg:  250px;
        }
        body { background-color: var(--cinza-fundo); font-family: 'Segoe UI', Tahoma, sans-serif; color: var(--texto-escuro); overflow-x: hidden; }

        .navbar-topo { background: linear-gradient(90deg, var(--azul-primario) 0%, var(--azul-escuro) 100%); height: 60px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: fixed; top: 0; left: 0; right: 0; z-index: 1030; }
        .navbar-topo .navbar-brand { color: #fff; font-weight: 600; font-size: 1.25rem; }
        .navbar-topo .navbar-brand i { margin-right: 8px; }
        .btn-sanduiche { background: transparent; border: none; color: #fff; font-size: 1.3rem; padding: 6px 12px; border-radius: 6px; transition: background 0.2s; }
        .btn-sanduiche:hover { background: rgba(255,255,255,0.15); }
        .operador-toggle { background: transparent; border: none; color: #fff; display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 30px; transition: background 0.2s; }
        .operador-toggle:hover, .operador-toggle:focus { background: rgba(255,255,255,0.15); color: #fff; }
        .operador-toggle i.fa-circle-user { font-size: 1.6rem; }
        .dropdown-menu-operador { min-width: 220px; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); border: none; }
        .dropdown-menu-operador .dropdown-item i { width: 22px; color: var(--azul-primario); }

        .sidebar { position: fixed; top: 60px; left: 0; width: var(--sidebar-larg); height: calc(100vh - 60px); background: #fff; border-right: 1px solid var(--cinza-borda); padding: 20px 0; transition: transform 0.3s ease; z-index: 1020; overflow-y: auto; }
        .sidebar.oculta { transform: translateX(calc(var(--sidebar-larg) * -1)); }
        .sidebar .nav-link { color: var(--texto-escuro); padding: 12px 20px; border-left: 3px solid transparent; transition: all 0.2s; display: flex; align-items: center; gap: 12px; }
        .sidebar .nav-link i { width: 22px; color: var(--azul-primario); font-size: 1.05rem; }
        .sidebar .nav-link:hover { background: var(--azul-claro); border-left-color: var(--azul-primario); color: var(--azul-escuro); }
        .sidebar .nav-link.ativo { background: var(--azul-claro); border-left-color: var(--azul-primario); color: var(--azul-escuro); font-weight: 600; }
        .sidebar-overlay { display: none; position: fixed; top: 60px; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 1010; }
        .sidebar-overlay.ativo { display: block; }

        .conteudo-principal { margin-top: 60px; margin-left: var(--sidebar-larg); padding: 25px; transition: margin-left 0.3s ease; min-height: calc(100vh - 60px); }
        .conteudo-principal.expandido { margin-left: 0; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(calc(var(--sidebar-larg) * -1)); }
            .sidebar.aberta { transform: translateX(0); box-shadow: 2px 0 12px rgba(0,0,0,0.15); }
            .conteudo-principal { margin-left: 0; }
        }

        .page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; }
        .page-header h2 { font-size: 1.4rem; font-weight: 700; color: var(--azul-escuro); margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h2 i { color: var(--azul-primario); }

        .card-pagina { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--cinza-borda); padding: 20px 24px; margin-bottom: 20px; }
        .card-pagina .card-titulo { font-weight: 600; font-size: 0.95rem; color: var(--azul-escuro); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .card-pagina .card-titulo i { color: var(--azul-primario); }

        .tabela-esp { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.88rem; }
        .tabela-esp thead th { background: var(--azul-claro); color: var(--azul-escuro); font-weight: 600; padding: 10px 14px; border-bottom: 2px solid var(--cinza-borda); }
        .tabela-esp tbody tr:hover { background: #f8fbff; }
        .tabela-esp tbody td { padding: 10px 14px; border-bottom: 1px solid var(--cinza-borda); vertical-align: middle; }
        .tabela-esp tbody tr:last-child td { border-bottom: none; }

        .badge-status { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
        .badge-ativo   { background: #d1e7dd; color: #0a3622; }
        .badge-inativo { background: #f8d7da; color: #58151c; }

        .modal-form .modal-header { background: var(--azul-primario); color: #fff; }
        .modal-form .modal-header .btn-close { filter: invert(1); }
        .modal-form label { font-weight: 500; font-size: 0.88rem; margin-bottom: 4px; }
    </style>
</head>
<body>

    <nav class="navbar-topo d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-sanduiche" id="btnSanduiche" title="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="navbar-brand mb-0 d-flex align-items-center" href="principal.php">
                <i class="fa-solid fa-stethoscope"></i>
                <span>MediAgenda</span>
            </a>
        </div>
        <div class="dropdown">
            <button class="operador-toggle" type="button" id="dropdownOperador" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if (!empty($fotoUsuario)): ?>
                    <img src="<?php echo $fotoUsuario ?>"
                        style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.6);">
                <?php else: ?>
                    <i class="fa-solid fa-circle-user"></i>
                <?php endif; ?>
                <span class="d-none d-md-inline"><?php echo htmlspecialchars($operadorNome) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-operador" aria-labelledby="dropdownOperador">
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i><?php echo htmlspecialchars($operadorNome) ?></a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($operadorEmail) ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="configuracoes.php"><i class="fa-solid fa-gear"></i>Configurações</a></li>
                <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Sair</a></li>
            </ul>
        </div>
    </nav>

    <aside class="sidebar" id="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="principal.php"><i class="fa-solid fa-calendar-days"></i> Calendário</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_agendas.php"><i class="fa-solid fa-calendar-plus"></i> Agendamentos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_medicos.php"><i class="fa-solid fa-user-doctor"></i> Cadastro de Médicos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ativo" href="cadastro_especialidades.php"><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</a>
            </li>
        </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="conteudo-principal" id="conteudoPrincipal">

        <div class="page-header">
            <h2><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFormEsp">
                <i class="fa-solid fa-plus me-1"></i> Nova Especialidade
            </button>
        </div>

        <div class="card-pagina">
            <div class="card-titulo d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-table-list"></i> Especialidades</span>
                <span class="text-muted" style="font-size:0.82rem; font-weight:400;">
                    <?php echo count($especialidades) ?> registro(s)
                </span>
            </div>

            <div class="table-responsive">
                <table class="tabela-esp">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($especialidades)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-circle-xmark me-2"></i>Nenhuma especialidade cadastrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($especialidades as $esp): ?>
                            <tr>
                                <td class="text-muted"><?php echo $esp['id'] ?></td>
                                <td><?php echo htmlspecialchars($esp['nome']) ?></td>
                                <td>
                                    <span class="badge-status <?php echo $esp['status'] === 'Ativo' ? 'badge-ativo' : 'badge-inativo' ?>">
                                        <?php echo $esp['status'] ?>
                                    </span>
                                </td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-editar"
                                            title="Editar"
                                            data-id="<?php echo $esp['id'] ?>"
                                            data-nome="<?php echo htmlspecialchars($esp['nome']) ?>">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning py-0 px-2 btn-toggle"
                                            title="<?php echo $esp['status'] === 'Ativo' ? 'Inativar' : 'Ativar' ?>"
                                            data-id="<?php echo $esp['id'] ?>"
                                            data-status="<?php echo $esp['status'] ?>">
                                        <i class="fa-solid fa-<?php echo $esp['status'] === 'Ativo' ? 'ban' : 'check' ?>"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2 btn-excluir"
                                            title="Excluir"
                                            data-id="<?php echo $esp['id'] ?>"
                                            data-nome="<?php echo htmlspecialchars($esp['nome']) ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- MODAL — Nova / Editar Especialidade -->
    <div class="modal fade modal-form" id="modalFormEsp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEspTitulo">
                        <i class="fa-solid fa-plus me-2"></i>Nova Especialidade
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="espId">
                    <div class="mb-3">
                        <label for="espNome">Nome da Especialidade <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="espNome"
                               placeholder="Ex: Cardiologia" maxlength="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarEsp()">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ── Sidebar toggle ────────────────────────────────────────
        var btnSanduiche      = document.getElementById('btnSanduiche');
        var sidebar           = document.getElementById('sidebar');
        var conteudoPrincipal = document.getElementById('conteudoPrincipal');
        var sidebarOverlay    = document.getElementById('sidebarOverlay');

        btnSanduiche.addEventListener('click', function() {
            if (window.innerWidth <= 991.98) {
                sidebar.classList.toggle('aberta');
                sidebarOverlay.classList.toggle('ativo');
            } else {
                sidebar.classList.toggle('oculta');
                conteudoPrincipal.classList.toggle('expandido');
            }
        });
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('aberta');
            sidebarOverlay.classList.remove('ativo');
        });
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991.98) {
                sidebar.classList.remove('aberta');
                sidebarOverlay.classList.remove('ativo');
            }
        });

        // ── Modal ─────────────────────────────────────────────────
        var modalFormEspEl = document.getElementById('modalFormEsp');
        var modalFormEsp   = new bootstrap.Modal(modalFormEspEl);
        var modoEdicao     = false;

        modalFormEspEl.addEventListener('show.bs.modal', function() {
            if (!modoEdicao) {
                document.getElementById('modalEspTitulo').innerHTML =
                    '<i class="fa-solid fa-plus me-2"></i>Nova Especialidade';
                document.getElementById('espId').value   = '';
                document.getElementById('espNome').value = '';
            }
            modoEdicao = false;
        });

        // ── Helper de POST ────────────────────────────────────────
        function post(body) {
            return fetch('cadastro_especialidades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function(r) { return r.json(); });
        }

        // ── Editar / Toggle / Excluir ─────────────────────────────
        document.querySelector('.tabela-esp').addEventListener('click', function(e) {
            var btnEditar  = e.target.closest('.btn-editar');
            var btnToggle  = e.target.closest('.btn-toggle');
            var btnExcluir = e.target.closest('.btn-excluir');

            if (btnEditar) {
                modoEdicao = true;
                document.getElementById('modalEspTitulo').innerHTML =
                    '<i class="fa-solid fa-pen me-2"></i>Editar Especialidade';
                document.getElementById('espId').value   = btnEditar.dataset.id;
                document.getElementById('espNome').value = btnEditar.dataset.nome;
                modalFormEsp.show();
            }

            if (btnToggle) {
                var statusAtual = btnToggle.dataset.status;
                var acao = statusAtual === 'Ativo' ? 'inativar' : 'ativar';
                Swal.fire({
                    title: 'Deseja ' + acao + ' esta especialidade?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim',
                    cancelButtonText: 'Não'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        post('acao=alternar_status&id=' + btnToggle.dataset.id)
                        .then(function(res) {
                            if (res.success) { window.location.reload(); }
                            else { Swal.fire('Erro', res.message, 'error'); }
                        });
                    }
                });
            }

            if (btnExcluir) {
                Swal.fire({
                    title: 'Excluir especialidade?',
                    html: 'Deseja excluir <strong>' + btnExcluir.dataset.nome + '</strong>?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sim, excluir',
                    cancelButtonText: 'Voltar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        post('acao=excluir&id=' + btnExcluir.dataset.id)
                        .then(function(res) {
                            if (res.success) { window.location.reload(); }
                            else { Swal.fire('Erro', res.message, 'error'); }
                        });
                    }
                });
            }
        });

        // ── Salvar especialidade ──────────────────────────────────
        function salvarEsp() {
            var id   = document.getElementById('espId').value;
            var nome = document.getElementById('espNome').value.trim();

            if (!nome) {
                Swal.fire('Atenção', 'Informe o nome da especialidade.', 'warning');
                return;
            }

            post('acao=salvar&id=' + encodeURIComponent(id) + '&nome=' + encodeURIComponent(nome))
            .then(function(res) {
                modalFormEsp.hide();
                Swal.fire(res.success ? 'Salvo!' : 'Erro', res.message, res.success ? 'success' : 'error')
                    .then(function() { if (res.success) window.location.reload(); });
            });
        }
    </script>
</body>
</html>