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

/**
 * Class LinkHandler
 *
 * Gerencia toda a lógica de negócio para links e categorias.
 * Cada método público corresponde a uma ação que pode ser chamada pelo roteador da API.
 */
class LinkHandler
{
    /**
     * @var PDO A instância da conexão com o banco de dados.
     */
    private $pdo;

    /**
     * O construtor recebe a conexão com o banco de dados para ser usada nos métodos.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lê todas as categorias e seus respectivos links do banco de dados.
     */
    public function readAll()
    {
        $stmt_cat = $this->pdo->query("SELECT id, name, dashboard_name FROM categorias ORDER BY ordem ASC");
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        $stmt_links = $this->pdo->prepare("SELECT * FROM links WHERE categoria_id = ? ORDER BY ordem ASC");

        foreach ($categories as $key => $category) {
            $stmt_links->execute([$category['id']]);
            $categories[$key]['links'] = $stmt_links->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['categories' => $categories]);
    }

    /**
     * Salva a nova ordem das categorias e dos links.
     * Utiliza uma transação para garantir que todas as atualizações ocorram ou nenhuma ocorra.
     * @param array $input O array de dados recebido da requisição.
     */
    public function saveOrder($input)
    {
        $this->pdo->beginTransaction();

        try {
            $stmt_cat = $this->pdo->prepare("UPDATE categorias SET ordem = ? WHERE id = ?");
            $stmt_link = $this->pdo->prepare("UPDATE links SET ordem = ?, categoria_id = ? WHERE id = ?");

            if (isset($input['categories'])) {
                foreach ($input['categories'] as $cat_order => $category) {
                    $stmt_cat->execute([$cat_order, $category['id']]);
                    if (isset($category['links'])) {
                        foreach ($category['links'] as $link_order => $link) {
                            $stmt_link->execute([$link_order, $category['id'], $link['id']]);
                        }
                    }
                }
            }
            
            $this->pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Alterações salvas com sucesso!']);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e; // Relança a exceção para ser capturada pelo roteador principal
        }
    }

    /**
     * Adiciona um novo link ou atualiza um existente.
     * @param array $input O array de dados do link.
     * @param string $action A ação específica ('add_link' ou 'update_link').
     */
    public function saveLink($input, $action)
    {
        $categoria_id = $input['categoria_id'];

        // Se uma nova categoria for especificada, cria-a primeiro.
        if ($categoria_id === 'new' && !empty($input['new_category_name'])) {
            $stmt = $this->pdo->prepare("INSERT INTO categorias (name, dashboard_name) VALUES (?, ?)");
            $stmt->execute([
                $input['new_category_name'],
                $input['new_category_dashboard_name']
            ]);
            $categoria_id = $this->pdo->lastInsertId();
        }

        $sql = $action === 'add_link'
            ? "INSERT INTO links (text, url, dashboard_text, icon, showOnDashboard, openInNewTab, visibilidade, categoria_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            : "UPDATE links SET text=?, url=?, dashboard_text=?, icon=?, showOnDashboard=?, openInNewTab=?, visibilidade=?, categoria_id=? WHERE id=?";
        
        $params = [
            $input['text'],
            $input['url'],
            $input['dashboard_text'],
            $input['icon'],
            (int)$input['showOnDashboard'],
            (int)$input['openInNewTab'],
            $input['visibilidade'],
            $categoria_id
        ];

        if ($action === 'update_link') {
            $params[] = $input['id'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $message = $action === 'add_link' ? 'Link adicionado com sucesso!' : 'Link atualizado com sucesso!';
        echo json_encode(['success' => true, 'message' => $message]);
    }

    /**
     * Exclui um link específico.
     * @param array $input Deve conter o 'id' do link a ser excluído.
     */
    public function deleteLink($input)
    {
        $stmt = $this->pdo->prepare("DELETE FROM links WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true, 'message' => 'Link excluído com sucesso!']);
    }

    /**
     * Atualiza o nome e o nome de painel de uma categoria.
     * @param array $input Deve conter 'id', 'name', e 'dashboard_name'.
     */
    public function updateCategory($input)
    {
        $stmt = $this->pdo->prepare("UPDATE categorias SET name = ?, dashboard_name = ? WHERE id = ?");
        $stmt->execute([
            $input['name'],
            $input['dashboard_name'],
            $input['id']
        ]);
        echo json_encode(['success' => true, 'message' => 'Categoria atualizada com sucesso!']);
    }

    /**
     * Exclui uma categoria e todos os links associados a ela.
     * A operação é envolvida em uma transação para garantir a consistência dos dados.
     * @param array $input Deve conter o 'id' da categoria a ser excluída.
     */
    public function deleteCategory($input)
    {
        $this->pdo->beginTransaction();
        try {
            $categoryId = $input['id'];

            // 1. Excluir todos os links que pertencem a esta categoria
            $stmt_delete_links = $this->pdo->prepare("DELETE FROM links WHERE categoria_id = ?");
            $stmt_delete_links->execute([$categoryId]);

            // 2. Excluir a categoria em si
            $stmt_delete_category = $this->pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt_delete_category->execute([$categoryId]);

            $this->pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Categoria e seus links foram excluídos com sucesso!']);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}