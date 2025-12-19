<?php
// Ativa a exibição de erros (útil durante desenvolvimento — remova/ajuste em produção).
ini_set('display_errors', 1);

// Define o nível de relatório de erros para exibir todos os tipos (E_ALL).
error_reporting(E_ALL);

// Inclui arquivo de configuração do banco de dados. Espera-se que este arquivo defina $conn (mysqli).
require_once __DIR__ . '/config/db.php';

// Inicia/retoma a sessão PHP para poder acessar $_SESSION em todo o script.
session_start();

// Verifica se a variável $conn (conexão com o banco) existe/e está válida.
// Se a conexão falhar, mysqli_connect_error() retorna a descrição do erro.
if (!$conn) {
    die("Erro de conexão com o banco: " . mysqli_connect_error());
}

// ==============================
// MODO MANUTENÇÃO
// ==============================

// Aqui verificamos se o usuário NÃO é admin. Se não for, checamos se o site está em modo manutenção.
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    // Prepara uma consulta para pegar duas configurações: 'modo_manutencao' e 'mensagem_manutencao'.
    // Usamos prepared statements por padrão (boa prática), mesmo sem parâmetros aqui.
    $stmt = $conn->prepare("SELECT nome, valor FROM configuracoes WHERE nome IN ('modo_manutencao', 'mensagem_manutencao')");

    // Se houve erro ao preparar, exibimos e interrompemos. Em produção trate/logue em vez de die().
    if (!$stmt) die("Erro no prepare da consulta de manutenção: " . $conn->error);

    // Executa a query preparada.
    $stmt->execute();

    // Faz o bind de resultados para as variáveis $nome e $valor.
    $stmt->bind_result($nome, $valor);

    // Array para armazenar as configurações retornadas (ex: ['modo_manutencao' => 1, 'mensagem_manutencao' => '...'])
    $config = [];

    // Enquanto houver linhas no resultado, preenche o array $config.
    while ($stmt->fetch()) $config[$nome] = $valor;

    // Fecha o statement para liberar recursos.
    $stmt->close();

    // Verifica se a configuração 'modo_manutencao' existe e é igual a 1 (ativada).
    if (isset($config['modo_manutencao']) && $config['modo_manutencao'] == 1) {
        // Exibe uma mensagem simples de manutenção. Usamos htmlspecialchars na mensagem para evitar XSS.
        echo "<h1>🚧 Site em Manutenção 🚧</h1>";
        echo "<p>" . htmlspecialchars($config['mensagem_manutencao'] ?? "Voltaremos em breve.") . "</p>";
        // Encerra a execução do script (nenhum outro conteúdo será renderizado).
        exit;
    }
}

// ==============================
// DADOS PRINCIPAIS (USADOS NA PÁGINA)
// ==============================

// Pega o nome do usuário da sessão, se existir; caso contrário, $usuario_nome = null
$usuario_nome = $_SESSION['usuario_nome'] ?? null;

// --- Comunidades populares ---
// Inicializa o array que receberá as comunidades.
$comunidades = [];

// Prepara consulta para buscar comunidades marcadas como populares, ordenadas pelo número de membros.
$stmt = $conn->prepare("SELECT id, nome, descricao, icone FROM comunidades WHERE popular = 1 ORDER BY membros DESC");
if ($stmt) {
    // Executa a consulta preparada.
    $stmt->execute();

    // Obtém o resultado como objeto mysqli_result (necessário para fetch_all).
    $result = $stmt->get_result();

    // Se o resultado for válido, converte tudo para array associativo.
    if ($result) {
        $comunidades = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Fecha o statement.
    $stmt->close();
}


// --- Últimas notícias ---
// Inicializa array de notícias.
$noticias = [];

// Consulta para obter 3 últimas notícias por data de criação.
$stmt = $conn->prepare("SELECT id, titulo, conteudo, imagem FROM noticias ORDER BY criado_em DESC LIMIT 3");
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($id, $titulo, $conteudo, $imagem);

    // Empilha cada notícia no array $noticias.
    while ($stmt->fetch()) $noticias[] = ['id'=>$id,'titulo'=>$titulo,'conteudo'=>$conteudo,'imagem'=>$imagem];

    $stmt->close();
}

// ==============================
// NOTIFICAÇÕES DO USUÁRIO
// ==============================

// Inicializa array de notificações e contador de não-lidas
$notificacoes = [];
$notifCount = 0;

// Se existe user_id na sessão, buscamos as notificações relacionadas
if (isset($_SESSION['user_id'])) {
    // Prepared statement para evitar SQL injection ao usar parâmetros.
    $stmt = $conn->prepare("SELECT id, mensagem, lida, criada_em FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC LIMIT 5");
    if ($stmt) {
        // Liga parâmetro (i = integer) com o id do usuário vindo da sessão
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();

        // Liga colunas de resultado a variáveis
        $stmt->bind_result($nid, $nmsg, $nlida, $ncriada);

        // Itera pelas notificações, monta o array e conta não-lidas
        while ($stmt->fetch()) {
            $notificacoes[] = ['id'=>$nid,'mensagem'=>$nmsg,'lida'=>$nlida,'criada_em'=>$ncriada];
            if ($nlida==0) $notifCount++;
        }

        $stmt->close();
    }
}
?>
<!-- Início do HTML da página -->
<!DOCTYPE html>
<!-- Declaração do tipo de documento: HTML5 -->
<html lang="pt-BR">
<!-- Define língua principal do documento para mecanismos de busca e leitores de tela -->

<head>
  <!-- Metadados -->
  <meta charset="UTF-8"> <!-- Define codificação de caracteres para UTF-8 -->
  <title>GameZone - Início</title> <!-- Título mostrado na aba do navegador -->

  <!-- Tailwind via CDN (rápido para desenvolvimento). Em produção, prefira build local/configurada. -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- jQuery (usado em algumas partes; se não usar muito, pode tirar e usar apenas Fetch/API nativa) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Font Awesome para ícones (CDN). Dependendo do uso, considere baixar/servir localmente. -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Google Fonts: Oxanium e Rajdhani (carrega fontes externas). -->
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700&family=Rajdhani:wght@500;600&display=swap" rel="stylesheet">

  <style>
  /* Estilos personalizados:
     - .card-hover adiciona elevação e movimento quando o usuário passa o mouse.
     - .slider-* são usados para os carrosséis (comportamento controlado via JS abaixo).
  */
  .card-hover:hover{transform:translateY(-5px);box-shadow:0 10px 20px rgba(0,0,0,0.5);transition:all 0.3s ease;}
  .slider-container{overflow-x:hidden;padding-bottom:1rem;display:flex;gap:1rem;position:relative;}
  .slider-track{display:flex;gap:1rem;transition:transform 0.5s ease;}
  .slider-item{flex:0 0 auto;}
  .slider-btn{position:absolute;top:50%;transform:translateY(-50%);z-index:50;cursor:pointer;color:#6366f1;font-size:2rem;padding:0.2rem 0.5rem;background:rgba(0,0,0,0.4);border-radius:50%;transition:background 0.3s;}
  .slider-btn:hover{background:rgba(0,0,0,0.7);}
  </style>
</head>

<!-- Body da página: classes Tailwind para cor de fundo, cor do texto e fonte -->
<body class="bg-gray-900 text-white font-['Oxanium']">

<!-- Barra de navegação fixa no topo -->
<nav class="fixed top-0 left-0 right-0 z-30 bg-gray-800 border-b border-gray-700 h-16 flex items-center justify-between px-6 shadow-lg">
  <!-- Container esquerdo: logo e links principais -->
  <div class="flex items-center gap-6">
    <a class="text-3xl font-bold text-indigo-500">Game<span class="text-gray-100">Zone</span></a>

    <div class="hidden md:flex gap-4 items-center text-sm">
      <a href="index.php" class="hover:text-indigo-400 transition">Início</a>

      <div class="relative group">
        <button class="hover:text-indigo-400 transition">Comunidade</button>

        <ul class="absolute hidden group-hover:block bg-gray-800 shadow-lg rounded mt-1 p-2 w-44 z-50">
          <li><a href="./pages/minhas_comunidades.php" class="block px-3 py-1 hover:text-indigo-400">Minhas Comunidades</a></li>
          <li><a href="./pages/comunidade/chat.php" class="block px-3 py-1 hover:text-indigo-400">Chat</a></li>
          <li><a href="./pages/comunidade/amigos.php" class="block px-3 py-1 hover:text-indigo-400">Amigos</a></li>
          <li><a href="./pages/comunidade/conversas.php" class="block px-3 py-1 hover:text-indigo-400">Conversas</a></li>
          <li><a href="./pages/comunidade/criar_comunidade.php" class="block px-3 py-1 hover:text-indigo-400">Criar Comunidade</a></li>
        </ul>
      </div>

      <a href="./pages/comunidade/explorar_comunidades.php" class="hover:text-indigo-400 transition">Explorar</a>
      <a href="ranking.php" class="hover:text-indigo-400 transition">Ranking</a>
      <a href="loja.php" class="hover:text-indigo-400 transition">Loja</a>
    </div>
  </div>

  <!-- Container direito -->
  <div class="relative flex items-center gap-4">
    <?php if (isset($_SESSION['email'])): ?>

      <!-- NOTIFICAÇÕES -->
      <div class="relative">
        <button id="notifBtn" class="text-gray-300 hover:text-indigo-400 relative">
          <i class="fas fa-bell text-2xl"></i>

          <?php if (!empty($notifCount)): ?>
            <span id="notifCount"
                  class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full px-1.5">
              <?= (int)$notifCount ?>
            </span>
          <?php endif; ?>
        </button>

        <!-- DROPDOWN -->
        <ul id="notifDropdown"
            class="absolute right-0 top-full mt-2 w-72 bg-gray-900 shadow-lg rounded-lg py-2 hidden z-50">

          <?php if (!empty($notificacoes)): ?>

            <?php foreach ($notificacoes as $n): ?>
              <?php
                $nid = isset($n['id']) ? (int)$n['id'] : 0;
                $mensagem = $n['mensagem'] ?? 'Notificação inválida';
                $mensagem = htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8');
                $mensagem = mb_strimwidth($mensagem, 0, 100, '...');

                $data = !empty($n['criada_em'])
                        ? date('d/m/Y H:i', strtotime($n['criada_em']))
                        : '00/00/0000 00:00';
              ?>

              <li class="px-4 py-2 border-b border-gray-700 last:border-0 hover:bg-gray-800 transition">
                <a href="notificacao_ver.php?id=<?= $nid ?>" class="block text-sm text-gray-200">
                  <?= $mensagem ?>
                  <div class="text-xs text-gray-500"><?= $data ?></div>
                </a>
              </li>

            <?php endforeach; ?>

          <?php else: ?>
            <li class="px-4 py-2 text-gray-400">Nenhuma notificação.</li>
          <?php endif; ?>

        </ul>
      </div>

      <!-- MENU USUÁRIO -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center text-gray-300 hover:text-indigo-400 transition">
          <i class="fas fa-user-circle text-2xl mr-1"></i>
          <span class="hidden sm:inline text-sm"><?= htmlspecialchars($_SESSION['email']) ?></span>
        </button>

        <ul id="userDropdown"
            class="absolute right-0 mt-2 w-48 bg-gray-900 shadow-lg rounded-lg py-2 hidden z-50">

          <li><a href="./conta/perfil.php"
                 class="block px-4 py-2 text-gray-200 hover:text-indigo-400">Meu Perfil</a></li>

          <?php if (!empty($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
            <li><a href="./admin/admin_painel.php"
                   class="block px-4 py-2 text-gray-200 hover:text-indigo-400">Painel Administrativo</a></li>
          <?php endif; ?>

          <li><a href="./conta/configuracoes.php"
                 class="block px-4 py-2 text-gray-200 hover:text-indigo-400">Configurações</a></li>

          <li><a href="./pages/security/logout.php"
                 class="block px-4 py-2 text-red-500 hover:text-red-400">Sair</a></li>

        </ul>
      </div>

    <?php else: ?>

      <!-- USUÁRIO DESLOGADO -->
      <div class="flex gap-2">
        <a href="./pages/security/entrar.php" class="text-sm hover:text-indigo-400 transition">Entrar</a>
        <a href="./pages/security/cadastrar.html" class="text-sm hover:text-indigo-400 transition">Cadastrar</a>
      </div>

    <?php endif; ?>
  </div>
</nav>


<!-- Conteúdo principal da página: padding e espaço no topo para não ficar embaixo da nav fixa -->
<main class="p-6 pt-24 space-y-14">

<!-- Seção: Carrossel de Comunidades Populares -->
<section>
  <h2 class="text-2xl font-bold mb-6 bg-gray-900 text-white">🌐 Comunidades Populares</h2>
  <div class="relative">
        <div class="slider-container" id="comunidadesSlider">
             <div class="slider-track" id="comunidadesTrack">
             <?php foreach ($comunidades as $c): ?>
          <?php 
            // Conversão segura da imagem BLOB para Base64
            $iconeSrc = "https://via.placeholder.com/300x200"; // fallback

            if (!empty($c['icone'])) {
                $data = $c['icone']; // BLOB
                $mime = "image/jpeg";
                if (extension_loaded('fileinfo')) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeDetected = $finfo->buffer($data);
                    if ($mimeDetected) $mime = $mimeDetected;
                }
                $iconeSrc = "data:$mime;base64," . base64_encode($data);
            }
          ?>

          <a href="./pages/comunidade/ver_comunidade.php?id=<?= $c['id'] ?>" 
            class="slider-item w-64 bg-gray-800 rounded-xl overflow-hidden shadow-md card-hover">

            <div class="h-40 bg-gray-700">
                <img src="<?= $iconeSrc ?>" 
                    alt="<?= htmlspecialchars($c['nome']) ?>" 
                    class="w-full h-full object-cover">
            </div>

            <div class="p-4">
                <h3 class="text-lg font-semibold mb-1"><?= htmlspecialchars($c['nome']) ?></h3>
                <p class="text-gray-400 text-sm"><?= htmlspecialchars($c['descricao']) ?></p>
            </div>

          </a>
        <?php endforeach; ?>
      </div>
    </div>

        <div class="slider-btn left-0" onclick="scrollSlider('comunidadesTrack', -300)"><i class="fas fa-chevron-left"></i></div>
    <div class="slider-btn right-0" onclick="scrollSlider('comunidadesTrack', 300)"><i class="fas fa-chevron-right"></i></div>
  </div>
</section>

<!-- Seção: Últimas Notícias -->
<section>
  <h2 class="text-2xl font-bold mb-6">📰 Últimas Notícias</h2>
  <div class="relative">
    <div class="slider-container" id="noticiasSlider">
      <div class="slider-track" id="noticiasTrack">
        
        <?php foreach ($noticias as $n): ?>
          <?php
            // 1. Tratamento de ID e Título
            $id = isset($n['id']) ? (int)$n['id'] : 0;
            $titulo = isset($n['titulo']) ? htmlspecialchars($n['titulo'], ENT_QUOTES, 'UTF-8') : "Sem título";
            
            // 2. Tratamento do Conteúdo (Limitando tamanho para não quebrar o card)
            $conteudoRaw = isset($n['conteudo']) ? $n['conteudo'] : "";
            $conteudo = htmlspecialchars(mb_strimwidth($conteudoRaw, 0, 100, '...'), ENT_QUOTES, 'UTF-8');

            // 3. LÓGICA DE IMAGEM (BLOB -> BASE64)
            // Verifica se existe dado binário na coluna imagem
            if (!empty($n['imagem'])) {
                // Tenta detectar o tipo da imagem (PNG, JPG, etc)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($n['imagem']);
                
                // Se falhar a detecção, assume jpeg, senão usa o detectado
                if (!$mimeType) $mimeType = 'image/jpeg';

                // Converte o binário para base64
                $base64 = base64_encode($n['imagem']);
                
                // Monta o src no formato Data URI
                $imagemSrc = "data:$mimeType;base64,$base64";
            } else {
                // Caminho para uma imagem padrão caso não exista no banco
                // Certifique-se de ter essa imagem na pasta ou troque por uma URL externa
                $imagemSrc = "uploads/noticia/img/"; 
            }
          ?>

          <a href="noticias.php?id=<?= $id ?>" class="slider-item w-80 bg-gray-800 rounded-xl overflow-hidden shadow-md card-hover">
            <div class="h-40 bg-gray-700">
              <img src="<?= $imagemSrc ?>" 
                   alt="<?= $titulo ?>" 
                   class="w-full h-full object-cover">
            </div>

            <div class="p-4">
              <h3 class="text-lg font-semibold mb-1"><?= $titulo ?></h3>
              <p class="text-gray-400 text-sm"><?= $conteudo ?></p>
            </div>
          </a>

        <?php endforeach; ?>
        
      </div>
    </div>

    <div class="slider-btn left-0" onclick="scrollSlider('noticiasTrack', -300)"><i class="fas fa-chevron-left"></i></div>
    <div class="slider-btn right-0" onclick="scrollSlider('noticiasTrack', 300)"><i class="fas fa-chevron-right"></i></div>
  </div>
</section>
</main>

<!-- Botões extras antes do rodapé -->
<div class="flex justify-center gap-6 mt-12 mb-8">
  <!-- Botão denunciar usuário -->
  <a href="./pages/reportar/denunciar_usuario.php"
     class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-lg transition">
     🚨 Denunciar Usuário
  </a>

  <!-- Botão avaliar plataforma -->
  <a href="avaliar_plataforma.php"
     class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-lg transition">
     ⭐ Avaliar Plataforma
  </a>
</div>


<!-- Rodapé com links e copyright -->
<footer class="text-center py-6 text-gray-400 text-sm border-t border-gray-700 mt-14">
  <div class="mb-2">
    <a href="contato.php" class="hover:text-indigo-400 transition mx-2">Contato</a> |
    <a href="privacidade.php" class="hover:text-indigo-400 transition mx-2">Privacidade</a> |
    <a href="sobre.php" class="hover:text-indigo-400 transition mx-2">Sobre</a> |
    <a href="termos.php" class="hover:text-indigo-400 transition mx-2">Termos</a> |
    <a href="equipe.php" class="hover:text-indigo-400 transition mx-2">Equipe</a>
  </div>

  <!-- Exibe o ano atual dinamicamente com PHP (date('Y')). -->
  <div>© <?= date('Y') ?> GameZone - Conectando jogadores.</div>
</footer>

<!-- Scripts JavaScript -->
<script>
// Seleciona elementos do DOM usados nos dropdowns e notificações.
const userBtn = document.getElementById("userMenuBtn");
const userDropdown = document.getElementById("userDropdown");
const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");
const notifCountEl = document.getElementById("notifCount");

// Toggle Menu Usuário
if(userBtn && userDropdown){
    userBtn.addEventListener("click", e => { 
        e.stopPropagation();
        userDropdown.classList.toggle("hidden");
    });
}

// Toggle Notificações
if(notifBtn && notifDropdown){
    notifBtn.addEventListener("click", e => { 
        e.stopPropagation(); 
        notifDropdown.classList.toggle("hidden"); 

        if (!notifDropdown.classList.contains("hidden")) {
            fetch('marcar_notificacoes_lidas.php')
              .then(()=>{
                if(notifCountEl) notifCountEl.style.display='none';
              })
              .catch(err=>console.error("Erro:", err));
        }
    });
}

window.addEventListener("click", () => { 
    if(userDropdown) userDropdown.classList.add("hidden"); 
    if(notifDropdown) notifDropdown.classList.add("hidden"); 
});

// ==============================
// FUNÇÕES DO CARROSSEL (CORRIGIDAS)
// ==============================

function scrollSlider(trackId, value){
    const track = document.getElementById(trackId);
    if(!track) return;
    // Se tiver poucos itens, não permite scroll manual
    if(track.children.length <= 1) return;

    track.style.transition = 'transform 0.5s ease';
    track.style.transform = `translateX(${(track._offset || 0) + value}px)`;
    track._offset = (track._offset || 0) + value;
}

// ==============================
// SLIDER INFINITO CORRIGIDO
// ==============================

function infiniteSlider(trackId){
    const track = document.getElementById(trackId);
    if(!track) return;

    // 1. CORREÇÃO: Verifica se há itens suficientes
    const itemCount = track.children.length;
    
    // Se tiver 0 ou 1 item, ou se a largura total dos itens for menor que a tela
    // (track.scrollWidth <= track.parentElement.offsetWidth), não ativa o slider.
    if(itemCount <= 1) {
        // Opcional: Esconder as setas se não houver scroll
        const container = track.parentElement;
        const btns = container.querySelectorAll('.slider-btn');
        btns.forEach(btn => btn.style.display = 'none');
        return; // Interrompe a função aqui
    }

    // Clone do primeiro elemento (só executa se passar pela verificação acima)
    const first = track.firstElementChild.cloneNode(true);
    track.appendChild(first);

    let offset = 0; 

    setInterval(()=>{
        offset -= 1; 
        track.style.transform = `translateX(${offset}px)`;

        if(Math.abs(offset) >= first.offsetWidth){
            offset = 0;
            track.appendChild(track.firstElementChild);
            track.style.transition = 'none';
            track.style.transform = `translateX(0px)`;
            setTimeout(()=>track.style.transition='transform 0.5s linear',20);
        }
    }, 30); // Ajustei para 30ms para ficar um pouco mais suave, ajuste conforme gosto
}

// Inicializa sliders
infiniteSlider('comunidadesTrack');
infiniteSlider('noticiasTrack');

// ==============================
// ATUALIZAÇÃO DE NOTIFICAÇÕES
// ==============================
function atualizarNotificacoes() { 
  fetch('buscar_notificacoes.php')
    .then(res => res.json())
    .then(data => {
      if(notifCountEl) { 
        notifCountEl.textContent = data.count; 
        notifCountEl.style.display = data.count>0?'inline-block':'none'; 
      }
      if(notifDropdown) {
        notifDropdown.innerHTML = ''; 
        if(data.notificacoes.length===0) {
          notifDropdown.innerHTML='<li class="px-4 py-2 text-gray-500">Nenhuma notificação</li>';
        } else {
          data.notificacoes.forEach(n=>{
            const li = document.createElement('li');
            li.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700'+(n.lida==0?' font-bold':'');
            li.innerHTML = `${n.mensagem} <span class="text-xs text-gray-400 float-right">${new Date(n.criada_em).toLocaleString('pt-BR',{ day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' })}</span>`;
            notifDropdown.appendChild(li);
          });
        }
      }
    })
    .catch(err => console.error("Erro ao buscar notificações:", err));
}

setInterval(atualizarNotificacoes, 5000);
atualizarNotificacoes();
</script>

</body>
</html>
