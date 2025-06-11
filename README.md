# Repositório de Links Centralizado

![License](https://img.shields.io/badge/license-MIT-blue.svg)

Um portal de dashboard seguro e auto-hospedado, projetado para centralizar links importantes para equipes ou uso pessoal. O sistema conta com um painel de administração completo para gerenciamento de usuários, categorias e links.

## Sobre o projeto

Este projeto nasceu da necessidade de criar um ponto de acesso único e organizado para diversos links e recursos online. Em vez de depender de favoritos desorganizados ou planilhas, este sistema oferece um dashboard visualmente agradável e um menu de navegação, com acesso controlado por permissões de usuário (Admin e Usuário Comum).

A aplicação foi construída com foco em segurança, manutenibilidade e facilidade de uso.

### Funcionalidades Principais

- **Autenticação Segura:** Sistema de login com senhas criptografadas.
- **Controle de Acesso por Papel:**
  - **Admin:** Acesso irrestrito, incluindo o painel de gerenciamento.
  - **Usuário:** Acesso apenas ao dashboard e aos links públicos.
- **Painel de Administração Completo:**
  - **Gerenciamento de Usuários:** Crie, edite e remova usuários.
  - **Gerenciamento de Categorias:** Crie, renomeie e exclua categorias de links.
  - **Gerenciamento de Links:** Adicione, edite e remova links dentro das categorias.
- **Organização Dinâmica:** Reordene links e categorias com uma interface de "arrastar e soltar" (`drag-and-drop`).
- **Dashboard Personalizável:** Escolha quais links devem aparecer como botões de destaque no painel principal.
- **Primeiro Acesso Simplificado:** O sistema cria automaticamente um usuário `admin` no primeiro uso, facilitando a configuração inicial.

### Tecnologias Utilizadas

- **Back-end:** PHP 8+
- **Front-end:** HTML5, CSS3, JavaScript (Vanilla JS)
- **Banco de Dados:** Compatível com qualquer SGBD que suporte PDO (ex: MySQL/MariaDB, PostgreSQL, SQLite).
- **Bibliotecas Front-end:**
  - [SortableJS](https://sortablejs.github.io/Sortable/) para a funcionalidade de arrastar e soltar.
  - [Font Awesome](https://fontawesome.com/) para os ícones.

## Começando

Para obter uma cópia local e funcional do projeto, siga os passos abaixo.

### Pré-requisitos

- Um servidor web com suporte a PHP (ex: Apache, Nginx).
- PHP 8 ou superior com a extensão PDO para seu banco de dados (ex: `php-mysql`).
- Um servidor de banco de dados (ex: MariaDB, MySQL).

### Instalação

1.  **Clone o repositório:**

    ```sh
    git clone [https://github.com/seu-usuario/seu-repositorio.git](https://github.com/seu-usuario/seu-repositorio.git)
    ```

2.  **Configure o Banco de Dados:**

    - Crie um novo banco de dados no seu servidor.
    - Importe a estrutura das tabelas usando o código SQL abaixo. Você pode salvar este código em um arquivo `schema.sql` e importá-lo.

    ```sql
    -- Estrutura da tabela `usuarios`
    CREATE TABLE `usuarios` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL UNIQUE,
      `password` varchar(255) NOT NULL,
      `role` enum('user','admin') NOT NULL DEFAULT 'user',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Estrutura da tabela `categorias`
    CREATE TABLE `categorias` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL,
      `dashboard_name` varchar(255) DEFAULT NULL,
      `ordem` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Estrutura da tabela `links`
    CREATE TABLE `links` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `categoria_id` int(11) NOT NULL,
      `text` varchar(100) NOT NULL,
      `url` varchar(2048) NOT NULL,
      `dashboard_text` varchar(255) DEFAULT NULL,
      `icon` varchar(50) DEFAULT 'fa-link',
      `showOnDashboard` tinyint(1) NOT NULL DEFAULT 1,
      `openInNewTab` tinyint(1) NOT NULL DEFAULT 0,
      `visibilidade` enum('all','admin') NOT NULL DEFAULT 'all',
      `ordem` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `categoria_id` (`categoria_id`),
      CONSTRAINT `links_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ```

3.  **Configure a Conexão:**

    - A aplicação busca as credenciais do banco de dados de um arquivo localizado **fora** da raiz pública para maior segurança.
    - Crie a seguinte estrutura de pastas um nível **acima** da pasta do seu projeto:
      ```
      /sua-pasta-de-servidor/
      |-- configs/
      |   |-- repositorio-geral/
      |       |-- config_segura.php  <-- Crie este arquivo aqui
      |
      |-- seu-projeto/             <-- Sua aplicação está aqui
          |-- index.php
          |-- api.php
          |-- ...
      ```
    - Adicione o seguinte conteúdo ao arquivo `config_segura.php`, substituindo com suas credenciais:

    ```php
    <?php
    // configs/repositorio-geral/config_segura.php

    // Configurações do Banco de Dados
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'nome_do_seu_banco');
    define('DB_USER', 'seu_usuario_de_banco');
    define('DB_PASS', 'sua_senha_de_banco');
    define('DB_CHARSET', 'utf8mb4');

    // Função global para obter a conexão PDO
    function get_db_connection() {
        static $pdo = null;
        if ($pdo === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (\PDOException $e) {
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        return $pdo;
    }
    ```

4.  **Acesse a Aplicação:**
    - Abra o projeto no seu navegador. Você será direcionado para a página de login.

## Uso

No primeiro acesso, o sistema detectará que não há nenhum administrador e criará uma conta padrão para você:

- **Usuário:** `admin`
- **Senha:** `admin123`

**AVISO IMPORTANTE:** É crucial que você altere a senha padrão imediatamente após o primeiro login por questões de segurança. Você pode fazer isso através do link "Alterar Senha" no menu lateral.

## Princípios de Arquitetura

O código foi refatorado para seguir os princípios de:

- **SRP (Single Responsibility Principle):** Cada classe (Handler) e arquivo tem uma responsabilidade única e bem definida.
- **DRY (Don't Repeat Yourself):** A lógica comum de front-end foi abstraída para o `utils.js` e os estilos de admin para o `admin.css`, eliminando duplicação de código.

## Licença

Distribuído sob a Licença MIT. Veja o cabeçalho dos arquivos para mais informações.
