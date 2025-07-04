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
include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/versao.php';
$base = '/Secullum';

require_once 'session.php';
protect_page();
require_once 'db_connection.php';

try {
    $pdo = get_db_connection();
    $role = $_SESSION['role'];

    $stmt_cat = $pdo->query("SELECT id, name, dashboard_name FROM categorias ORDER BY ordem ASC");
    $categories = $stmt_cat->fetchAll();

    $sql_links = "SELECT * FROM links WHERE categoria_id = ?";
    if ($role !== 'admin') {
        $sql_links .= " AND visibilidade = 'all'";
    }
    $sql_links .= " ORDER BY ordem ASC";
    $stmt_links = $pdo->prepare($sql_links);

    foreach ($categories as $key => $category) {
        $stmt_links->execute([$category['id']]);
        $categories[$key]['links'] = $stmt_links->fetchAll();
    }

} catch (PDOException $e) {
    die("Erro ao carregar os dados do sistema. Por favor, contate o administrador.");
}

function create_slug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Secullum Repositório" />
    <meta name="keywords" content="site, links, lanches, Secullum" />
    <meta name="author" content="Douglas Silva" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <meta property="og:title" content="Secullum Repositório" />
    <meta property="og:description" content="Secullum Repositório" />
    <meta property="og:url" content="https://www.dougllassillva27.com.br" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="https://dougllassillva27.com.br/<?= versao("$base/img/logo-social-share.webp") ?>">
    <meta property="og:image:width" content="512" />
    <meta property="og:image:height" content="512" />
    <meta property="og:site_name" content="Secullum Repositório" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Secullum Repositório" />
    <meta name="twitter:description" content="Secullum Repositório" />

    <title>Repositório Secullum</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?= versao("$base/style.css") ?>">
    <link rel="stylesheet" href="<?= versao("$base/admin.css") ?>">
</head>
<body>
    <nav id="sidebar" class="custom-scrollbar">
        <div class="sidebar-header">
            <button id="menu-button" aria-label="Alternar menu">
                <i class="fas fa-bars"></i>
                <span class="menu-button-text">Minimizar</span>
            </button>
        </div>
        <a href="#" id="home-link" class="sidebar-link" data-tooltip="Home" data-slug="home">
            <i class="fas fa-home sidebar-icon"></i><span class="link-text">Home</span>
        </a>
        
        <div class="sidebar-separator"></div>

        <?php
        if (!empty($categories)) {
            foreach ($categories as $category) {
                if (empty($category['links'])) continue;
                
                echo '<div class="sidebar-category-title">' . htmlspecialchars($category['name']) . '</div>';
                
                foreach ($category['links'] as $link) {
                    $newTabAttribute = !empty($link['openInNewTab']) ? 'data-open-in-new-tab="true"' : '';
                    $slug = create_slug($link['text']);
                    $iconClass = htmlspecialchars($link['icon'] ?? 'fa-link');
                    echo '<a href="' . htmlspecialchars($link['url']) . '" class="sidebar-link" data-url="' . htmlspecialchars($link['url']) . '" ' . $newTabAttribute . ' data-tooltip="' . htmlspecialchars($link['text']) . '" data-slug="' . $slug . '">';
                    echo '<i class="fas ' . $iconClass . ' sidebar-icon"></i><span class="link-text">' . htmlspecialchars($link['text']) . '</span></a>';
                }

                echo '<div class="sidebar-separator"></div>';
            }
        }
        ?>

        <div class="sidebar-footer">
            <?php if (is_admin()): ?>
            <a href="gerenciar_links.php" class="admin-link sidebar-link" title="Gerenciar Links" data-tooltip="Gerenciar Links"><i class="fas fa-link sidebar-icon"></i><span class="link-text">Gerenciar Links</span></a>
            <a href="gerenciar_usuarios.php" class="admin-link sidebar-link" title="Gerenciar Usuários" data-tooltip="Gerenciar Usuários"><i class="fas fa-users-cog sidebar-icon"></i><span class="link-text">Gerenciar Usuários</span></a>
            <?php endif; ?>
            <a href="#" id="btn-change-password" class="admin-link sidebar-link" title="Alterar Senha" data-tooltip="Alterar Senha">
                <i class="fas fa-key sidebar-icon"></i>
                <span class="link-text">Alterar Senha</span>
            </a>
            <a href="logout.php" class="admin-link sidebar-link" title="Sair do Sistema" data-tooltip="Sair">
                <i class="fas fa-sign-out-alt sidebar-icon"></i>
                <span class="link-text">Sair</span>
            </a>
        </div>
    </nav>

    <div class="theme-switch-wrapper">
        <label class="theme-switch" for="theme-checkbox" title="Alternar tema">
            <input type="checkbox" id="theme-checkbox" />
            <div class="slider round">
                <span class="icon-sun"><i class="fas fa-sun"></i></span>
                <span class="icon-moon"><i class="fas fa-moon"></i></span>
            </div>
        </label>
    </div>

    <main id="main-content">
        <div class="content-wrapper">
            <div id="dashboard-content" class="custom-scrollbar">
                <div class="dashboard-inner-wrapper">
                    <h2 class="welcome-title">Bem-vindo, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
                    <h1>Painel de Controle</h1>
                    <?php
                    if (!empty($categories)) {
                        foreach ($categories as $category) {
                            $dashboard_links_for_category = array_filter($category['links'], fn($link) => !empty($link['showOnDashboard']));
                            if (!empty($dashboard_links_for_category)) {
                                $category_title = !empty($category['dashboard_name']) ? $category['dashboard_name'] : $category['name'];
                                echo '<h2 class="dashboard-category-title">' . htmlspecialchars($category_title) . '</h2>';
                                echo '<ul class="dashboard-link-list">';
                                foreach ($dashboard_links_for_category as $link) {
                                    $newTabAttribute = !empty($link['openInNewTab']) ? 'data-open-in-new-tab="true"' : '';
                                    $slug = create_slug($link['dashboard_text'] ?: $link['text']);
                                    echo '<li><a href="' . htmlspecialchars($link['url']) . '" class="sidebar-link" data-url="' . htmlspecialchars($link['url']) . '" ' . $newTabAttribute . ' data-tooltip="' . htmlspecialchars($link['dashboard_text'] ?: $link['text']) . '" data-slug="' . $slug . '">' . htmlspecialchars($link['dashboard_text'] ?: $link['text']) . '</a></li>';
                                }
                                echo '</ul>';
                            }
                        }
                    }
                    ?>
                </div>
            </div>
            
            <iframe 
                id="contentFrame"
                class="d-none" 
                title="Conteúdo Externo" 
                sandbox="allow-downloads allow-forms allow-modals allow-popups allow-presentation allow-same-origin allow-scripts">
            </iframe>
        </div>

        <footer id="footer">
            <p>
                Desenvolvido por
                <a href="https://www.linkedin.com/in/dougllassillva27/" target="_blank">Douglas Silva</a>
            </p>
        </footer>
    </main>

    <div id="feedback-modal" class="modal-backdrop">
        <div class="modal-content">
            <h3 id="feedback-modal-title"></h3>
            <p id="feedback-modal-message"></p>
            <div class="modal-actions">
                <button type="button" id="feedback-modal-ok" class="btn btn-sm btn-primary">OK</button>
            </div>
        </div>
    </div>
    
    <div id="change-password-modal" class="modal-backdrop">
        <div class="modal-content">
            <h3>Alterar Minha Senha</h3>
            <div class="form-grid">
                <div class="form-group-full">
                    <label for="new_password">Nova Senha:</label>
                    <input type="password" id="new_password" required>
                </div>
                <div class="form-group-full">
                    <label for="confirm_password">Confirmar Nova Senha:</label>
                    <input type="password" id="confirm_password" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" id="change-password-cancel" class="btn btn-sm btn-secondary">Cancelar</button>
                <button type="button" id="change-password-save" class="btn btn-sm btn-primary">Salvar Senha</button>
            </div>
        </div>
    </div>

    <script src="<?= versao("$base/script.js") ?>"></script>
</body>
</html>