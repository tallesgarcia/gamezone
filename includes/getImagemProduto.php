<?php

function getImagemProduto($nomeImagem) {

    // Pasta relativa que será usada no HTML
    $pastaRelativa = "uploads/produtos/";

    // Caminho absoluto da pasta no servidor
    $pastaAbsoluta = realpath(__DIR__ . "/../uploads/produtos/");

    // Fallback
    $fallback = "assets/img/produto_padrao.png";

    // Se não tiver nome da imagem
    if (empty($nomeImagem)) {
        return $fallback;
    }

    // Se realpath falhar, evita erro
    if ($pastaAbsoluta === false) {
        return $fallback;
    }

    // Monta caminho absoluto do arquivo
    $caminhoAbsoluto = $pastaAbsoluta . DIRECTORY_SEPARATOR . $nomeImagem;

    // Caminho relativo usado no <img>
    $caminhoRelativo = $pastaRelativa . $nomeImagem;

    // Verificar se é realmente um arquivo
    if (is_file($caminhoAbsoluto) && file_exists($caminhoAbsoluto)) {
        return $caminhoRelativo;
    }

    return $fallback;
}
