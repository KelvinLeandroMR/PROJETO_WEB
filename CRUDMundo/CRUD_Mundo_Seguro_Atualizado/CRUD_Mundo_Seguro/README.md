# CRUD Mundo - Programação Web + Segurança

Aplicação acadêmica em **PHP + MySQL + HTML5 + CSS3 + JavaScript**, baseada no enunciado do CRUD Mundo e complementada com os requisitos de autenticação e auditoria vistos em aula.

## Funcionalidades

- CRUD de Continentes, Países, Cidades e Governantes.
- Relacionamentos com chaves estrangeiras e `ON DELETE RESTRICT` para preservar integridade.
- Pesquisa dinâmica por nome com JavaScript.
- Dashboard com contadores e atividade recente.
- Login com `password_hash()` / `password_verify()`.
- Sessão regenerada após autenticação.
- Token CSRF em operações POST.
- Queries preparadas com PDO.
- Escapamento de saída com `htmlspecialchars()`.
- Bloqueio temporário após 3 falhas consecutivas de senha.
- Usuários com tipo administrador/usuário comum e status ativo/inativo/bloqueado.
- Troca de senha obrigatória no primeiro acesso.
- Usuários novos recebem uma senha inicial e são obrigados a alterá-la no primeiro acesso.
- Logs de auditoria para autenticação e operações.
- Área de usuários e logs exclusiva para administrador.

## Estrutura

```text
CRUD_Mundo_Seguro/
├── app/
│   ├── config/
│   │   ├── config.php
│   │   └── database.php
│   ├── lib/
│   │   └── security.php
│   └── views/
│       ├── header.php
│       └── footer.php
├── database/
│   └── schema.sql
├── public/
│   ├── assets/
│   │   ├── css/style.css
│   │   └── js/app.js
│   ├── index.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── continentes.php
│   ├── paises.php
│   ├── cidades.php
│   ├── governantes.php
│   ├── usuarios.php
│   ├── logs.php
│   └── trocar_senha.php
└── README.md
```

## Instalação no XAMPP/WAMP

1. Crie a pasta do projeto dentro do diretório web do servidor (por exemplo, `htdocs`).
2. Inicie Apache e MySQL.
3. Abra o phpMyAdmin e execute `database/schema.sql`.
4. Confira `app/config/config.php` e ajuste host, porta, usuário e senha do MySQL.
5. Aponte o navegador para a pasta `public` do projeto.
6. Primeiro acesso: **usuário `admin` / senha `Admin@123`**.
7. O sistema bloqueará o restante das telas até que a senha provisória seja trocada em **Trocar senha**.
8. Após a troca, o usuário poderá acessar normalmente o dashboard e os demais módulos.

## Requisitos

- PHP 8.1+ recomendado.
- Extensão PDO_MySQL habilitada.
- MySQL 8+ ou MariaDB compatível com os `CHECK` constraints utilizados.

## Segurança aplicada

A senha não é armazenada em texto puro. As operações que alteram dados usam POST, CSRF e prepared statements. O controle de acesso é feito por sessão e por tipo de usuário. O banco utiliza chaves estrangeiras com restrição de exclusão, e os eventos relevantes são registrados em `logs`.

## Observação sobre o Caso de Teste

O arquivo de documentação entregue junto ao projeto usa o mesmo conjunto de colunas do modelo fornecido: ID, título, pré-condições, entradas, passos, resultado esperado, resultado obtido, status e observações. Como o ambiente desta geração não possui um servidor MySQL executando, os resultados obtidos do documento são tratados como **validação por inspeção do código + verificação de sintaxe PHP**, não como um teste de execução ponta a ponta em MySQL.
