<?php
// admin_compras.php
session_start();
require_once __DIR__ . '/../config/db.php'; // ajuste se necessário

// Verifica se é admin (adapte conforme sua app)
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    http_response_code(403);
    echo "Acesso negado.";
    exit;
}

// Helper: sanitizar saída
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// --- AÇÕES AJAX (fetch, details, update_status, delete, update_edit) ---
$action = $_REQUEST['action'] ?? '';

if ($action !== '') {

    // build WHERE com parâmetros (por referência)
    function buildWhereAndParams(&$params) {
        $where = " WHERE 1=1 ";
        $email = trim($_REQUEST['email'] ?? '');
        $produto = trim($_REQUEST['produto'] ?? '');
        $data_ini = trim($_REQUEST['data_ini'] ?? '');
        $data_fim = trim($_REQUEST['data_fim'] ?? '');
        $status = trim($_REQUEST['status'] ?? '');

        if ($email !== '') { $where .= " AND u.email LIKE ? "; $params[] = "%$email%"; }
        if ($produto !== '') { $where .= " AND p.nome LIKE ? "; $params[] = "%$produto%"; }
        if ($data_ini !== '') { $where .= " AND DATE(c.data_compra) >= ? "; $params[] = $data_ini; }
        if ($data_fim !== '') { $where .= " AND DATE(c.data_compra) <= ? "; $params[] = $data_fim; }
        if ($status !== '') { $where .= " AND c.status = ? "; $params[] = $status; }

        return $where;
    }

    // ----------------- FETCH (listagem via AJAX) -----------------
    if ($action === 'fetch') {
        $page = max(1, (int)($_REQUEST['page'] ?? 1));
        $perPage = (int)($_REQUEST['per_page'] ?? 10);
        $offset = ($page - 1) * $perPage;

        $params = [];
        $where = buildWhereAndParams($params);

        // total
        $count_sql = "SELECT COUNT(*) AS total
                      FROM compras c
                      LEFT JOIN usuarios u ON u.id = c.usuario_id
                      LEFT JOIN produtos p ON p.id = c.produto_id
                      $where";
        $stmt = $conn->prepare($count_sql);
        if (!$stmt) { die("Erro prepare count: ".$conn->error); }
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['total'];
        $total_pages = (int)ceil($total / max(1, $perPage));

        // seleção principal
        $sql = "SELECT c.*, u.nome AS usuario_nome, u.email AS usuario_email, p.nome AS produto_nome
                FROM compras c
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                LEFT JOIN produtos p ON p.id = c.produto_id
                $where
                ORDER BY c.data_compra DESC
                LIMIT ? OFFSET ?";

        // params para o select (originais + limit + offset)
        $params2 = $params;
        $params2[] = $perPage;
        $params2[] = $offset;

        // types
        $types = ($params ? str_repeat('s', count($params)) : '') . 'ii';

        $stmt2 = $conn->prepare($sql);
        if (!$stmt2) { die("Erro prepare select: ".$conn->error . " | Query: " . $sql); }
        if (!empty($params2)) {
            // bind apenas se houver parâmetros
            $stmt2->bind_param($types, ...$params2);
        }
        $stmt2->execute();
        $res = $stmt2->get_result();

        // Monta HTML
        ob_start();
        ?>
        <div class="bg-white rounded shadow overflow-x-auto text-black">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Usuário</th>
                        <th class="p-3 text-left">Produto</th>
                        <th class="p-3 text-left">Qtd</th>
                        <th class="p-3 text-left">Valor</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Data</th>
                        <th class="p-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($res->num_rows === 0): ?>
                    <tr class="border-t"><td class="p-4 text-center" colspan="8">Nenhuma compra encontrada.</td></tr>
                <?php else: ?>
                    <?php while ($c = $res->fetch_assoc()): ?>
                        <tr class="border-t">
                            <td class="p-2"><?= e($c['id']) ?></td>
                            <td class="p-2"><?= e($c['usuario_nome'] ?? $c['usuario_email'] ?? '—') ?></td>
                            <td class="p-2"><?= e($c['produto_nome'] ?? '—') ?></td>
                            <td class="p-2"><?= e($c['quantidade']) ?></td>
                            <td class="p-2 font-semibold text-green-700">R$ <?= number_format($c['valor_total'] ?? 0, 2, ',', '.') ?></td>
                            <td class="p-2">
                                <select class="status-select border rounded px-2 py-1" data-id="<?= e($c['id']) ?>">
                                    <option value="pago" <?= ($c['status'] === 'pago') ? 'selected' : '' ?>>Pago</option>
                                    <option value="pendente" <?= ($c['status'] === 'pendente') ? 'selected' : '' ?>>Pendente</option>
                                    <option value="estornado" <?= ($c['status'] === 'estornado') ? 'selected' : '' ?>>Estornado</option>
                                </select>
                            </td>
                            <td class="p-2"><?= !empty($c['data_compra']) ? date('d/m/Y H:i', strtotime($c['data_compra'])) : '—' ?></td>
                            <td class="p-2 text-center">
                                <button class="btn-detalhes px-2 py-1 bg-indigo-600 text-white rounded" data-id="<?= e($c['id']) ?>">Ver</button>
                                <button class="btn-editar px-2 py-1 bg-yellow-400 rounded" data-id="<?= e($c['id']) ?>">Editar</button>
                                <button class="btn-excluir px-2 py-1 bg-red-600 text-white rounded" data-id="<?= e($c['id']) ?>">Excluir</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div class="mt-3 flex items-center justify-between text-sm text-white">
            <div>Mostrando página <?= $page ?> de <?= $total_pages ?> — Total: <?= $total ?> resultados</div>
            <div class="flex items-center gap-2">
                <button class="px-3 py-1 border rounded btn-page" data-page="1">« Primeiro</button>
                <button class="px-3 py-1 border rounded btn-page" data-page="<?= max(1,$page-1) ?>">‹</button>
                <span class="px-3">Página <?= $page ?></span>
                <button class="px-3 py-1 border rounded btn-page" data-page="<?= min($total_pages,$page+1) ?>">›</button>
                <button class="px-3 py-1 border rounded btn-page" data-page="<?= $total_pages ?>">Último »</button>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        echo $html;
        exit;
    }

    // ----------------- DETAILS -----------------
    if ($action === 'details') {
        $id = (int)($_REQUEST['id'] ?? 0);
        $sql = "SELECT c.*, u.nome AS usuario_nome, u.email AS usuario_email, p.nome AS produto_nome
                FROM compras c
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                LEFT JOIN produtos p ON p.id = c.produto_id
                WHERE c.id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { echo json_encode(['error'=>'Erro prepare']); exit; }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) { echo json_encode(['error'=>'Compra não encontrada']); exit; }
        echo json_encode($row);
        exit;
    }

    // ----------------- UPDATE_STATUS -----------------
    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $novo = $_POST['status'] ?? '';
        if (!in_array($novo, ['pago','pendente','estornado'])) {
            echo json_encode(['ok'=>false,'msg'=>'Status inválido']); exit;
        }
        $stmt = $conn->prepare("UPDATE compras SET status = ? WHERE id = ?");
        if (!$stmt) { echo json_encode(['ok'=>false,'msg'=>$conn->error]); exit; }
        $stmt->bind_param('si', $novo, $id);
        $ok = $stmt->execute();
        echo json_encode(['ok'=>$ok]);
        exit;
    }

    // ----------------- UPDATE_EDIT (edição via modal) -----------------
    if ($action === 'update_edit') {
        $id = (int)($_POST['id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        // aceitar vírgula decimal ou ponto
        $valor_str = str_replace(',', '.', str_replace('.', '', $_POST['valor'] ?? '0'));
        $valor = (float)$valor_str;
        $status = $_POST['status'] ?? 'pendente';

        if (!in_array($status, ['pago','pendente','estornado'])) $status = 'pendente';

        $stmt = $conn->prepare("UPDATE compras SET quantidade = ?, valor_total = ?, status = ? WHERE id = ?");
        if (!$stmt) { echo json_encode(['ok'=>false,'msg'=>$conn->error]); exit; }
        $stmt->bind_param('idsi', $quantidade, $valor, $status, $id);
        $ok = $stmt->execute();
        echo json_encode(['ok'=>$ok, 'msg'=>$ok ? 'ok' : $conn->error]);
        exit;
    }

    // ----------------- DELETE -----------------
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM compras WHERE id = ?");
        if (!$stmt) { echo json_encode(['ok'=>false,'msg'=>$conn->error]); exit; }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        echo json_encode(['ok'=>$ok]);
        exit;
    }

    // ação não encontrada
    echo json_encode(['error'=>'Ação inválida']);
    exit;
}
// Fim das ações AJAX

// ====================
// Renderização da página HTML (interface)
// ====================
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin - Compras</title>
<script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/estilos.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">

<!-- Sidebar -->
<div class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white shadow-lg">
  <div class="p-4 font-bold text-xl text-indigo-400">Admin GameZone</div>
  <nav class="flex flex-col gap-2 mt-4 px-4">
    <a href="admin_painel.php" class="hover:text-indigo-400">📊 Painel</a>
    <a href="admin_usuarios.php" class="hover:text-indigo-400">👥 Usuários</a>
    <a href="admin_jogos.php" class="hover:text-indigo-400">🎮 Jogos</a>
    <a href="admin_produtos.php" class="hover:text-indigo-400">🛍️ Produtos</a>
    <a href="admin_avaliacoes.php" class="hover:text-indigo-400">⭐ Avaliações</a>
    <a href="admin_denuncias.php" class="hover:text-indigo-400">🚨 Denúncias</a>
    <a href="admin_noticias.php" class="hover:text-indigo-400">📰 Notícias</a>
    <a href="admin_comunidades.php" class="hover:text-indigo-400">🌐 Comunidades</a>
    <a href="admin_compras.php" class="text-indigo-400 font-semibold">🧾 Compras</a>
    <a href="admin_equipe.php" class="hover:text-indigo-400">🧑‍💼 Equipe</a>
    <a href="admin_configuracoes.php" class="hover:text-indigo-400">⚙️ Configurações</a>
  </nav>
</div>
<!-- Topbar -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shadow-sm">
  <!-- Logo e links principais -->
  <div class="flex items-center gap-6">
    <a class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
      Game<span class="text-gray-800 dark:text-gray-100">Zone</span>
    </a>

    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="../index.php" class="hover:underline text-gray-700 dark:text-gray-300">Início</a>

      <div class="relative group">
        <button class="hover:underline text-gray-700 dark:text-gray-300">Comunidade</button>
        <ul class="absolute hidden group-hover:block bg-white dark:bg-gray-800 shadow rounded mt-1 p-2 w-44 z-50">
          <li><a href="../pages/minhas_comunidades.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Minhas Comunidades</a></li>
          <li><a href="../pages/comunidade/chat.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Chat</a></li>
          <li><a href="../pages/comunidade/amigos.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Amigos</a></li>
          <li><a href="../pages/comunidade/conversas.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Conversas</a></li>
          <li><a href="../pages/comunidade/criar_comunidade.php" class="block px-3 py-1 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Criar Comunidade</a></li>
        </ul>
      </div>

      <div class="relative group">
        <a href="../pages/comunidade/explorar_comunidades.php" class="hover:underline text-gray-700 dark:text-gray-300">Explorar</a>
      </div>

      <a href="../ranking.php" class="hover:underline text-gray-700 dark:text-gray-300">Ranking</a>
    </div>

    <a href="../loja.php" class="hover:underline text-gray-700 dark:text-gray-300">Loja</a>
  </div>

  <!-- Notificações & Usuário -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>
      <!-- Notificações -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-600 hover:text-indigo-500 relative">
          <i class="fas fa-bell text-2xl"></i>
          <?php if(isset($notifCount) && $notifCount > 0): ?>
            <span id="notifCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
              <?= (int)$notifCount ?>
            </span>
          <?php endif; ?>
        </button>

        <ul id="notifDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">

          <?php if (!empty($notificacoes)): ?>
            <?php foreach ($notificacoes as $n): ?>

              <li class="px-4 py-2 border-b last:border-b-0 hover:bg-gray-100 dark:hover:bg-gray-700">

                <a href="notificacao_ver.php?id=<?= isset($n['id']) ? (int)$n['id'] : 0 ?>"
                   class="block text-sm text-gray-800 dark:text-gray-200">

                  <?= htmlspecialchars(
                        mb_strimwidth(
                          $n['mensagem'] ?? 'Notificação inválida',
                          0,
                          100,
                          '...'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                      );
                  ?>

                  <div class="text-xs text-gray-500">
                    <?php
                      if (!empty($n['criada_em'])) {
                        echo date('d/m/Y H:i', strtotime($n['criada_em']));
                      } else {
                        echo '00/00/0000 00:00';
                      }
                    ?>
                  </div>

                </a>
              </li>

            <?php endforeach; ?>
          <?php else: ?>
            <li class="px-4 py-2 text-gray-500">Nenhuma notificação.</li>
          <?php endif; ?>

        </ul>
      </div>

      <!-- Menu do usuário -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-500">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>

        <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 hidden z-50">
          <li><a href="../conta/perfil.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Meu Perfil</a></li>

          <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="../admin/admin_painel.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Painel Administrativo</a></li>
          <?php endif; ?>

          <li><a href="../conta/configuracoes.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Configurações</a></li>
          <li><a href="../pages/security/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</a></li>
        </ul>
      </div>

    <?php else: ?>
      <div class="flex gap-2">
        <a href="../pages/security/entrar.php" class="text-sm hover:underline">Entrar</a>
        <a href="../pages/security/cadastrar.html" class="text-sm hover:underline">Cadastrar</a>
      </div>
    <?php endif; ?>
  </div>

</nav>

<div class="ml-64 pt-20 p-8">

    <header class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-indigo-300">Compras — Painel Administrativo</h1>
        <div class="flex gap-2">
            <button id="btn_export_csv" class="px-4 py-2 bg-green-600 rounded">Exportar CSV</button>
            <button id="btn_export_xlsx" class="px-4 py-2 bg-blue-600 rounded">Exportar XLSX</button>
            <button id="btn_export_pdf" class="px-4 py-2 bg-red-600 rounded">Exportar PDF</button>
        </div>
    </header>

    <!-- filtros -->
    <section class="bg-zinc-800 p-4 rounded mb-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <input id="f_email" type="text" placeholder="E-mail do usuário" class="p-2 rounded bg-zinc-700 text-white">
            <input id="f_produto" type="text" placeholder="Nome do produto" class="p-2 rounded bg-zinc-700 text-white">
            <input id="f_data_ini" type="date" class="p-2 rounded bg-zinc-700 text-white">
            <input id="f_data_fim" type="date" class="p-2 rounded bg-zinc-700 text-white">
            <select id="f_status" class="p-2 rounded bg-zinc-700 text-white">
                <option value="">Todos os status</option>
                <option value="pago">Pago</option>
                <option value="pendente">Pendente</option>
                <option value="estornado">Estornado</option>
            </select>
            <select id="f_perpage" class="p-2 rounded bg-zinc-700 text-white">
                <option value="10">10 por página</option>
                <option value="20">20 por página</option>
                <option value="50">50 por página</option>
            </select>
        </div>
        <div class="mt-3 flex gap-2">
            <button id="btn_buscar" class="px-4 py-2 bg-indigo-600 rounded">Aplicar</button>
            <button id="btn_limpar" class="px-4 py-2 bg-gray-600 rounded">Limpar</button>
        </div>
    </section>

    <!-- resultado -->
    <div id="result_area"></div>

</div>

<!-- Modal Detalhes -->
<div id="modalDetalhes" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50">
    <div class="bg-white text-black rounded-lg w-11/12 md:w-2/3 p-4">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold">Detalhes da Compra</h3>
            <button id="closeDetalhes" class="text-red-600 font-bold">Fechar</button>
        </div>
        <div id="detalhesConteudo"></div>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEditar" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50">
    <div class="bg-white text-black rounded-lg w-11/12 md:w-1/2 p-4">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold">Editar Compra</h3>
            <button id="closeEditar" class="text-red-600 font-bold">Fechar</button>
        </div>
        <form id="formEditar">
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-1 gap-3">
                <label>Quantidade
                    <input type="number" name="quantidade" id="edit_quantidade" class="p-2 border rounded w-full">
                </label>
                <label>Valor (R$)
                    <input type="text" name="valor" id="edit_valor" class="p-2 border rounded w-full">
                </label>
                <label>Status
                    <select name="status" id="edit_status" class="p-2 border rounded w-full">
                        <option value="pago">Pago</option>
                        <option value="pendente">Pendente</option>
                        <option value="estornado">Estornado</option>
                    </select>
                </label>
            </div>
            <div class="mt-3 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 rounded">Salvar</button>
                <button type="button" id="btnCancelarEdit" class="px-4 py-2 bg-gray-600 rounded">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
$(function(){
    let currentPage = 1;

    function carregarTabela(page=1){
        currentPage = page;
        const payload = {
            action: 'fetch',
            page: page,
            per_page: $('#f_perpage').val(),
            email: $('#f_email').val(),
            produto: $('#f_produto').val(),
            data_ini: $('#f_data_ini').val(),
            data_fim: $('#f_data_fim').val(),
            status: $('#f_status').val()
        };
        $('#result_area').html('<div class="p-6 text-center text-white">Carregando...</div>');
        $.post('admin_compras.php', payload, function(html){
            $('#result_area').html(html);
        });
    }

    carregarTabela();
    // BUSCAR / LIMPAR
    $('#btn_buscar').click(function(){ carregarTabela(1); });
    $('#btn_limpar').click(function(){
        $('#f_email,#f_produto,#f_data_ini,#f_data_fim,#f_status').val('');
        $('#f_perpage').val('10');
        carregarTabela(1);
    });

    // Delegação: detalhes
    $('#result_area').on('click', '.btn-detalhes', function(){
        const id = $(this).data('id');
        $.post('admin_compras.php', { action:'details', id: id }, function(data){
            try {
                const obj = JSON.parse(data);
                let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-black">';
                html += '<div><strong>ID:</strong> '+(obj.id ?? '')+'</div>';
                html += '<div><strong>Usuário:</strong> '+(obj.usuario_nome ?? obj.usuario_email ?? '')+'</div>';
                html += '<div><strong>Produto:</strong> '+(obj.produto_nome ?? '')+'</div>';
                html += '<div><strong>Quantidade:</strong> '+(obj.quantidade ?? '')+'</div>';
                html += '<div><strong>Valor:</strong> R$ '+(parseFloat(obj.valor_total ?? 0).toFixed(2))+'</div>';
                html += '<div class="col-span-2"><strong>Data:</strong> '+(obj.data_compra ?? '')+'</div>';
                html += '<div class="col-span-2"><strong>Dados brutos:</strong><pre>'+JSON.stringify(obj, null, 2)+'</pre></div>';
                html += '</div>';
                $('#detalhesConteudo').html(html);
                $('#modalDetalhes').removeClass('hidden').addClass('flex');
            } catch(e){
                alert('Erro ao buscar detalhes');
            }
        });
    });

    $('#closeDetalhes').click(function(){ $('#modalDetalhes').addClass('hidden').removeClass('flex'); });

    // Edit
    $('#result_area').on('click', '.btn-editar', function(){
        const id = $(this).data('id');
        $.post('admin_compras.php', { action:'details', id: id }, function(data){
            try {
                const obj = JSON.parse(data);
                $('#edit_id').val(obj.id);
                $('#edit_quantidade').val(obj.quantidade);
                $('#edit_valor').val(obj.valor_total);
                $('#edit_status').val(obj.status);
                $('#modalEditar').removeClass('hidden').addClass('flex');
            } catch(e) { alert('Erro ao abrir edição'); }
        });
    });
    $('#closeEditar, #btnCancelarEdit').click(function(){ $('#modalEditar').addClass('hidden').removeClass('flex'); });

    $('#formEditar').submit(function(e){
        e.preventDefault();
        const dados = {
            action: 'update_edit',
            id: $('#edit_id').val(),
            quantidade: $('#edit_quantidade').val(),
            valor: $('#edit_valor').val(),
            status: $('#edit_status').val()
        };
        $.post('admin_compras.php', dados, function(resp){
            try {
                const obj = JSON.parse(resp);
                if (obj.ok) {
                    alert('Atualizado com sucesso');
                    $('#modalEditar').addClass('hidden').removeClass('flex');
                    carregarTabela(currentPage);
                } else {
                    alert('Erro: ' + (obj.msg || 'unknown'));
                }
            } catch(e) { alert('Resposta inválida'); }
        });
    });

    // Delete
    $('#result_area').on('click', '.btn-excluir', function(){
        if (!confirm('Confirma exclusão desta compra?')) return;
        const id = $(this).data('id');
        $.post('admin_compras.php', { action: 'delete', id: id }, function(resp){
            try {
                const obj = JSON.parse(resp);
                if (obj.ok) { alert('Excluído'); carregarTabela(currentPage); }
                else alert('Erro ao excluir');
            } catch(e) { alert('Erro inesperado'); }
        });
    });

    // update status quick
    $('#result_area').on('change', '.status-select', function(){
        const id = $(this).data('id');
        const status = $(this).val();
        $.post('admin_compras.php', { action: 'update_status', id: id, status: status }, function(resp){
            try {
                const obj = JSON.parse(resp);
                if (!obj.ok) alert('Falha ao atualizar');
            } catch(e){ alert('Erro'); }
        });
    });

    // paginação delegada
    $('#result_area').on('click', '.btn-page', function(){
        const p = $(this).data('page') || 1;
        carregarTabela(p);
    });

    // Export buttons: geram form POST para arquivos separados
    function downloadViaPost(url, data) {
        const form = $('<form method="POST" action="'+url+'"></form>');
        for (let k in data) {
            form.append('<input type="hidden" name="'+k+'">').find('input[name="'+k+'"]').last().val(data[k]);
        }
        $('body').append(form);
        form.submit();
        form.remove();
    }

    $('#btn_export_csv').click(function(){
        const params = {
            email: $('#f_email').val(),
            produto: $('#f_produto').val(),
            data_ini: $('#f_data_ini').val(),
            data_fim: $('#f_data_fim').val(),
            status: $('#f_status').val()
        };
        downloadViaPost('exportacoes/exportar_compras_csv.php', params);
    });
    $('#btn_export_xlsx').click(function(){
        const params = {
            email: $('#f_email').val(),
            produto: $('#f_produto').val(),
            data_ini: $('#f_data_ini').val(),
            data_fim: $('#f_data_fim').val(),
            status: $('#f_status').val()
        };
        downloadViaPost('exportacoes/exportar_compras_xlsx.php', params);
    });
    $('#btn_export_pdf').click(function(){
        const params = {
            email: $('#f_email').val(),
            produto: $('#f_produto').val(),
            data_ini: $('#f_data_ini').val(),
            data_fim: $('#f_data_fim').val(),
            status: $('#f_status').val()
        };
        downloadViaPost('exportacoes/exportar_compras_pdf.php', params);
    });

}); // fim jQuery

const notifBtn = document.getElementById("notifBtn");
    const notifDropdown = document.getElementById("notifDropdown");
    const userBtn = document.getElementById("userMenuBtn");
    const userDropdown = document.getElementById("userDropdown");
    const notifCountEl = document.getElementById("notifCount");

// Atualiza notificações
function atualizarNotificacoes() { 
  fetch('buscar_notificacoes.php') 
    .then(res => res.json()) 
    .then(data => {
      if(notifCountEl) { 
        notifCountEl.textContent = data.count;
        notifCountEl.style.display = data.count > 0 ? 'inline-block' : 'none'; 
      }
      if(notifDropdown) { 
        notifDropdown.innerHTML = '';
        if(data.notificacoes.length === 0) { 
          notifDropdown.innerHTML = '<li class="px-4 py-2 text-gray-500">Nenhuma notificação</li>'; 
        } else { 
          data.notificacoes.forEach(n => { 
            const li = document.createElement('li');
            li.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700' + (n.lida==0 ? ' font-bold' : '');
            li.innerHTML = `${n.mensagem} <span class="text-xs text-gray-400 float-right">${new Date(n.criada_em).toLocaleString('pt-BR',{ day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' })}</span>`;
            notifDropdown.appendChild(li); 
          });
        } 
      }
    }); 
}
setInterval(atualizarNotificacoes, 5000);
atualizarNotificacoes();

// Toggle dropdown notificações
if (notifBtn && notifDropdown) {
  notifBtn.addEventListener("click", e => { 
    e.stopPropagation();
    notifDropdown.classList.toggle("hidden");
    if (userDropdown && !userDropdown.classList.contains("hidden")) userDropdown.classList.add("hidden");
    if (!notifDropdown.classList.contains("hidden")) fetch('marcar_notificacoes_lidas.php').then(()=>{if(notifCountEl) notifCountEl.style.display='none';});
  });
}

// Toggle dropdown usuário
if (userBtn && userDropdown) {
  userBtn.addEventListener("click", e => {
    e.stopPropagation();
    userDropdown.classList.toggle("hidden");
    if (notifDropdown && !notifDropdown.classList.contains("hidden")) notifDropdown.classList.add("hidden");
  });
}

// Fecha dropdowns ao clicar fora
window.addEventListener("click", () => {
  if(userDropdown) userDropdown.classList.add("hidden");
  if(notifDropdown) notifDropdown.classList.add("hidden");
});
</script>

</body>
</html>
