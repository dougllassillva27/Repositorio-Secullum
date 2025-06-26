<?php
/**
 * MIT License
 *
 * Copyright (c) 2025 Douglas Silva
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// ===================================================================================
// CONFIGURAÇÃO ROBUSTA DE SESSÃO PARA AMBIENTES DE HOSPEDAGEM COMPARTILHADA
// ===================================================================================

// PASSO 1: Definir um caminho privado e portável para salvar os arquivos de sessão.
// A pasta ficará um nível ACIMA do diretório público (public_html), o que é mais seguro.
// O nome 'sessions_repositorio' é para isolar desta aplicação.
$caminho_sessoes = $_SERVER['DOCUMENT_ROOT'] . '/../sessions_repositorio';

// Garante que o diretório exista. Se não, tenta criá-lo com permissões seguras (0700).
if (!is_dir($caminho_sessoes)) {
    mkdir($caminho_sessoes, 0700, true);
}
// Define o novo caminho para salvar as sessões. DEVE vir antes de session_start().
session_save_path($caminho_sessoes);


// PASSO 2: Definir o tempo de vida da sessão para 8 horas.
$tempo_de_vida_da_sessao = 28800; // 8 horas em segundos

// Configura o tempo que a sessão fica ativa no servidor (Garbage Collector)
ini_set('session.gc_maxlifetime', $tempo_de_vida_da_sessao);

// Configura o tempo que o cookie da sessão fica ativo no navegador do usuário
ini_set('session.cookie_lifetime', $tempo_de_vida_da_sessao);


// PASSO 3: Definir um nome exclusivo para a sessão desta aplicação para evitar conflitos.
session_name('RepositorioGeralSession');


// PASSO 4: Iniciar a sessão (apenas se não houver uma ativa) com todas as configs aplicadas.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// ===================================================================================
// FUNÇÕES HELPER DE SESSÃO
// ===================================================================================

/**
 * Verifica se o usuário está logado checando a existência da variável de sessão 'user_id'.
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se o usuário logado tem a permissão de 'admin'.
 * @return bool
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Protege uma página, redirecionando para a tela de login caso o usuário não esteja logado.
 * Deve ser chamada no topo de todas as páginas restritas.
 */
function protect_page() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
?>