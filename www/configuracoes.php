<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['cod_usuario'])) {
    header('Location: login.php');
    exit;
}

$cod_usuario = $_SESSION['cod_usuario'];

// ── AJAX ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    $acao = $_POST['acao'];

    if ($acao === 'atualizar_perfil') {
        $nome  = trim($_POST['nome']  ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nome === '' || $email === '') {
            echo json_encode(['success' => false, 'message' => 'Nome e e-mail são obrigatórios.']);
            exit;
        }

        $stmt = $conexao_bd->prepare('UPDATE usuario SET nome = ?, email = ? WHERE cod_usuario = ?');
        $stmt->bind_param('ssi', $nome, $email, $cod_usuario);

        if ($stmt->execute()) {
            $_SESSION['nome_usuario'] = $nome;
            echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar perfil.']);
        }
        $stmt->close();
        exit;
    }

    if ($acao === 'alterar_senha') {
        $senha_atual = $_POST['senha_atual']    ?? '';
        $nova_senha  = $_POST['nova_senha']     ?? '';
        $confirma    = $_POST['confirma_senha'] ?? '';

        if ($senha_atual === '' || $nova_senha === '' || $confirma === '') {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos de senha.']);
            exit;
        }
        if ($nova_senha !== $confirma) {
            echo json_encode(['success' => false, 'message' => 'A nova senha e a confirmação não coincidem.']);
            exit;
        }
        if (strlen($nova_senha) < 6) {
            echo json_encode(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres.']);
            exit;
        }

        // Verifica senha atual
        $stmt = $conexao_bd->prepare('SELECT pass FROM usuario WHERE cod_usuario = ?');
        $stmt->bind_param('i', $cod_usuario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row['pass'] !== $senha_atual) {
            echo json_encode(['success' => false, 'message' => 'Senha atual incorreta.']);
            exit;
        }

        $stmt = $conexao_bd->prepare('UPDATE usuario SET pass = ? WHERE cod_usuario = ?');
        $stmt->bind_param('si', $nova_senha, $cod_usuario);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar senha.']);
        }
        $stmt->close();
        exit;
    }

    if ($acao === 'salvar_preferencias') {
        $max = (int)($_POST['max_agendamentos_dia'] ?? 3);
        if ($max < 1) $max = 1;
        if ($max > 10) $max = 10;

        $stmt = $conexao_bd->prepare('UPDATE usuario SET max_agendamentos_dia = ? WHERE cod_usuario = ?');
        $stmt->bind_param('ii', $max, $cod_usuario);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Preferências salvas com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar preferências.']);
        }
        $stmt->close();
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit;
}

// ── Dados do usuário logado ───────────────────────────────────
$stmt = $conexao_bd->prepare('SELECT nome, email, username, max_agendamentos_dia FROM usuario WHERE cod_usuario = ?');
$stmt->bind_param('i', $cod_usuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$operadorNome  = $usuario['nome'];
$operadorEmail = $usuario['email'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Configurações</title>

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

        .card-config { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--cinza-borda); margin-bottom: 24px; overflow: hidden; }
        .card-config .card-config-header { background: var(--azul-claro); padding: 14px 22px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--cinza-borda); }
        .card-config .card-config-header i { color: var(--azul-primario); font-size: 1.1rem; }
        .card-config .card-config-header span { font-weight: 600; color: var(--azul-escuro); font-size: 0.95rem; }
        .card-config .card-config-body { padding: 22px; }

        .avatar-grande { width: 72px; height: 72px; border-radius: 50%; background: var(--azul-claro); color: var(--azul-primario); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700; flex-shrink: 0; border: 3px solid var(--azul-primario); }

        label { font-weight: 500; font-size: 0.88rem; margin-bottom: 4px; }

        .range-wrapper { display: flex; align-items: center; gap: 16px; }
        .range-wrapper input[type=range] { flex: 1; }
        .range-valor { background: var(--azul-primario); color: #fff; border-radius: 6px; padding: 2px 10px; font-weight: 700; font-size: 1rem; min-width: 36px; text-align: center; }
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
            <button class="operador-toggle" type="button" id="dropdownOperador"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user"></i>
                <span class="d-none d-md-inline"><?php echo htmlspecialchars($operadorNome) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-operador" aria-labelledby="dropdownOperador">
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i><?php echo htmlspecialchars($operadorNome) ?></a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($operadorEmail) ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item active" href="configuracoes.php"><i class="fa-solid fa-gear"></i>Configurações</a></li>
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
                <a class="nav-link" href="cadastro_especialidades.php"><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</a>
            </li>
        </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="conteudo-principal" id="conteudoPrincipal">

        <div class="page-header">
            <h2><i class="fa-solid fa-gear"></i> Configurações</h2>
        </div>

        <!-- ── Perfil ─────────────────────────────────────────── -->
        <div class="card-config">
            <div class="card-config-header">
                <i class="fa-solid fa-user"></i>
                <span>Dados do Perfil</span>
            </div>
            <div class="card-config-body">
                <div class="d-flex align-items-center gap-4 mb-4">
                    <div class="avatar-grande" id="avatarGrande">
                        <?php
                            $partes = explode(' ', $usuario['nome']);
                            $ini = '';
                            foreach ($partes as $p) {
                                $l = ltrim($p, '.');
                                if ($l !== '') { $ini .= mb_strtoupper(mb_substr($l, 0, 1)); if (mb_strlen($ini) === 2) break; }
                            }
                            echo htmlspecialchars($ini);
                        ?>
                    </div>
                    <div>
                        <div class="fw-semibold fs-5"><?php echo htmlspecialchars($usuario['nome']) ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($usuario['username']) ?></div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="perfNome">Nome completo</label>
                        <input type="text" class="form-control" id="perfNome"
                               value="<?php echo htmlspecialchars($usuario['nome']) ?>" maxlength="150">
                    </div>
                    <div class="col-md-6">
                        <label for="perfEmail">E-mail</label>
                        <input type="email" class="form-control" id="perfEmail"
                               value="<?php echo htmlspecialchars($usuario['email']) ?>" maxlength="150">
                    </div>
                    <div class="col-md-6">
                        <label>Usuário</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['username']) ?>" disabled>
                        <div class="form-text">O nome de usuário não pode ser alterado.</div>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="salvarPerfil()">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar perfil
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Senha ──────────────────────────────────────────── -->
        <div class="card-config">
            <div class="card-config-header">
                <i class="fa-solid fa-lock"></i>
                <span>Alterar Senha</span>
            </div>
            <div class="card-config-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="senhaAtual">Senha atual</label>
                        <input type="password" class="form-control" id="senhaAtual" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label for="novaSenha">Nova senha</label>
                        <input type="password" class="form-control" id="novaSenha" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label for="confirmaSenha">Confirmar nova senha</label>
                        <input type="password" class="form-control" id="confirmaSenha" maxlength="10">
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="alterarSenha()">
                        <i class="fa-solid fa-key me-1"></i> Alterar senha
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Preferências ───────────────────────────────────── -->
        <div class="card-config">
            <div class="card-config-header">
                <i class="fa-solid fa-sliders"></i>
                <span>Preferências do Calendário</span>
            </div>
            <div class="card-config-body">
                <label for="maxAgendamentos" class="mb-2">
                    Máximo de agendamentos visíveis por dia no calendário
                </label>
                <div class="range-wrapper">
                    <input type="range" id="maxAgendamentos" min="1" max="10"
                           value="<?php echo (int)$usuario['max_agendamentos_dia'] ?>">
                    <span class="range-valor" id="rangeValor"><?php echo (int)$usuario['max_agendamentos_dia'] ?></span>
                </div>
                <div class="form-text mt-1">
                    Agendamentos além desse limite aparecerão como "+ N mais" no calendário.
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="salvarPreferencias()">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar preferências
                    </button>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ── Sidebar ───────────────────────────────────────────────
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

        // ── Range slider ──────────────────────────────────────────
        var rangeInput = document.getElementById('maxAgendamentos');
        var rangeValor = document.getElementById('rangeValor');
        rangeInput.addEventListener('input', function() {
            rangeValor.textContent = this.value;
        });

        // ── Helper POST ───────────────────────────────────────────
        function post(body) {
            return fetch('configuracoes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function(r) { return r.json(); });
        }

        // ── Salvar perfil ─────────────────────────────────────────
        function salvarPerfil() {
            var nome  = document.getElementById('perfNome').value.trim();
            var email = document.getElementById('perfEmail').value.trim();

            if (!nome || !email) {
                Swal.fire('Atenção', 'Nome e e-mail são obrigatórios.', 'warning');
                return;
            }

            post('acao=atualizar_perfil&nome=' + encodeURIComponent(nome) + '&email=' + encodeURIComponent(email))
            .then(function(res) {
                Swal.fire(res.success ? 'Salvo!' : 'Erro', res.message, res.success ? 'success' : 'error')
                .then(function() { if (res.success) window.location.reload(); });
            });
        }

        // ── Alterar senha ─────────────────────────────────────────
        function alterarSenha() {
            var atual    = document.getElementById('senhaAtual').value;
            var nova     = document.getElementById('novaSenha').value;
            var confirma = document.getElementById('confirmaSenha').value;

            if (!atual || !nova || !confirma) {
                Swal.fire('Atenção', 'Preencha todos os campos de senha.', 'warning');
                return;
            }

            post('acao=alterar_senha&senha_atual=' + encodeURIComponent(atual) +
                 '&nova_senha=' + encodeURIComponent(nova) +
                 '&confirma_senha=' + encodeURIComponent(confirma))
            .then(function(res) {
                if (res.success) {
                    document.getElementById('senhaAtual').value    = '';
                    document.getElementById('novaSenha').value     = '';
                    document.getElementById('confirmaSenha').value = '';
                }
                Swal.fire(res.success ? 'Sucesso!' : 'Erro', res.message, res.success ? 'success' : 'error');
            });
        }

        // ── Salvar preferências ───────────────────────────────────
        function salvarPreferencias() {
            var max = document.getElementById('maxAgendamentos').value;
            post('acao=salvar_preferencias&max_agendamentos_dia=' + max)
            .then(function(res) {
                Swal.fire(res.success ? 'Salvo!' : 'Erro', res.message, res.success ? 'success' : 'error');
            });
        }
    </script>
</body>
</html>