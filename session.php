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

// Define o tempo de vida da sessão para 8 horas (28800 segundos)
// Esta configuração deve vir ANTES de qualquer função de sessão (session_name, session_start)
$tempo_de_vida_da_sessao = 28800;

// Configura o tempo que a sessão fica ativa no servidor (Garbage Collector)
ini_set('session.gc_maxlifetime', $tempo_de_vida_da_sessao);

// Configura o tempo que o cookie da sessão fica ativo no navegador do usuário
ini_set('session.cookie_lifetime', $tempo_de_vida_da_sessao);

// Define um nome exclusivo para a sessão desta aplicação para evitar conflitos
// com outras aplicações no mesmo domínio.
session_name('RepositorioGeralSession');

// Inicia a sessão se ainda não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Função para verificar se o usuário está logado
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Função para verificar se o usuário é administrador
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Função para proteger uma página. Se não estiver logado, redireciona para o login.
function protect_page() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
?>