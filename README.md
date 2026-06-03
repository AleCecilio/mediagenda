# MediAgenda

Sistema web para gerenciamento de consultas medicas, desenvolvido como projeto academico da disciplina de Programacao Web.

O projeto combina uma aplicacao PHP com MySQL/MariaDB, uma interface administrativa com Bootstrap e SweetAlert2, e um modulo Node.js com API REST para consulta e manutencao de agendamentos.

## Visao Geral

O MediAgenda centraliza o fluxo basico de uma agenda clinica:

- autenticacao de usuarios;
- calendario mensal de consultas;
- cadastro, edicao, filtro e cancelamento de agendamentos;
- cadastro de medicos com especialidade, CRM, telefone, e-mail e status;
- cadastro de especialidades com ativacao, inativacao e exclusao controlada;
- confirmacoes e mensagens visuais com SweetAlert2;
- layout responsivo com navbar fixa, sidebar recolhivel e suporte a mobile;
- API Node.js para listar, criar, atualizar, pesquisar e excluir agendamentos.

## Principais Interacoes

### Login

A tela `www/login.php` valida o preenchimento de usuario e senha antes de enviar os dados para `www/cadastrobanco.php`. Depois da autenticacao, o usuario e direcionado para o painel principal.

Usuarios iniciais cadastrados pelo `script.sql`:

| Usuario | Senha |
|---|---|
| `aluno` | `123456` |
| `professor` | `professor123` |

### Painel Principal

O arquivo `www/principal.php` exibe a agenda mensal com navegacao entre meses, destaque para o dia atual e cards de consultas por dia.

Interacoes disponiveis:

- abrir detalhes de uma consulta em modal;
- visualizar paciente, medico, especialidade, data, horario e status;
- cancelar agendamento com confirmacao via SweetAlert2;
- recolher ou expandir a sidebar;
- navegar para agendas, medicos e especialidades.

### Agendamentos

O arquivo `www/cadastro_agendas.php` concentra a gestao de consultas em formato de tabela.

Interacoes disponiveis:

- criar novo agendamento por modal;
- editar dados de um agendamento existente;
- cancelar agendamento com confirmacao;
- filtrar por paciente, medico, status e intervalo de datas;
- visualizar status com badges;
- manter uma base integrada a view `vw_agendamentos`.

### Medicos

O arquivo `www/cadastro_medicos.php` permite gerenciar os profissionais vinculados as especialidades.

Interacoes disponiveis:

- cadastrar novo medico;
- editar nome, CRM, especialidade, telefone e e-mail;
- filtrar por nome, especialidade e status;
- alternar entre status `Ativo` e `Inativo`;
- excluir medico quando nao houver agendamentos vinculados;
- usar requisicoes AJAX com respostas JSON para salvar, alterar status e excluir.

### Especialidades

O arquivo `www/cadastro_especialidades.php` gerencia as especialidades medicas.

Interacoes disponiveis:

- cadastrar nova especialidade;
- editar o nome da especialidade;
- ativar ou inativar registros;
- impedir inativacao quando houver medicos ativos vinculados;
- impedir exclusao quando houver medicos vinculados;
- executar as acoes por AJAX com feedback visual.

### Modulo Node.js

A pasta `nodejs/` contem uma aplicacao Express que serve uma interface propria e expoe endpoints REST.

Endpoints principais:

| Metodo | Rota | Descricao |
|---|---|---|
| `GET` | `/api/status` | Retorna o status do backend Node.js. |
| `GET` | `/api/consultas` | Lista consultas vindas da view `vw_agendamentos`. |
| `POST` | `/api/agendamentos` | Cadastra um novo agendamento. |
| `GET` | `/api/agendamentos/:id` | Busca um agendamento especifico. |
| `PUT` | `/api/agendamentos/:id` | Atualiza um agendamento. |
| `DELETE` | `/api/agendamentos/:id` | Exclui um agendamento. |
| `GET` | `/api/agendamentos/pesquisar/filtros` | Pesquisa agendamentos por filtros. |

## Tecnologias

| Tecnologia | Uso |
|---|---|
| PHP 8.2 | Aplicacao web principal |
| MySQL / MariaDB | Banco de dados relacional |
| MySQLi | Conexao PHP com o banco |
| Node.js + Express | API REST e interface alternativa |
| mysql2 | Conexao Node.js com MySQL |
| Bootstrap | Componentes e responsividade |
| Font Awesome | Icones da interface |
| SweetAlert2 | Alertas, confirmacoes e feedbacks |
| Docker Compose | Ambiente com PHP, Node.js, MySQL e phpMyAdmin |

## Estrutura do Projeto

```text
mediagenda/
|-- README.md
|-- script.sql
|-- docker-compose.yml
|-- dockerfile
|-- Dockerfile.node
|-- www/
|   |-- login.php
|   |-- cadastrobanco.php
|   |-- conexao.php
|   |-- principal.php
|   |-- cadastro_agendas.php
|   |-- cadastro_medicos.php
|   |-- cadastro_especialidades.php
|   |-- cancelar_agendamento.php
|   |-- logout.php
|   `-- img/
`-- nodejs/
    |-- server.js
    |-- conexao.js
    |-- package.json
    `-- public/
        |-- index.html
        |-- script.js
        `-- style.css
```

## Banco de Dados

O arquivo `script.sql` cria e popula o banco `labdbprog2`.

Ele inclui:

- tabela `usuario`;
- tabela `especialidades`;
- tabela `medicos`;
- tabela `agendamentos`;
- view `vw_agendamentos`;
- view `vw_medicos`;
- usuarios, especialidades, medicos e agendamentos iniciais para teste.

No codigo atual, as conexoes locais usam:

| Campo | Valor |
|---|---|
| Host | `localhost` |
| Porta | `3307` |
| Banco | `labdbprog2` |
| Usuario | `root` |
| Senha | vazia |

Arquivos de conexao:

- PHP: `www/conexao.php`
- Node.js: `nodejs/conexao.js`

## Como Executar Localmente

### 1. Criar o banco

Execute o script SQL no MySQL ou MariaDB:

```sql
SOURCE script.sql;
```

Ou importe o arquivo `script.sql` pelo phpMyAdmin, MySQL Workbench ou ferramenta equivalente.

### 2. Conferir a conexao

Verifique se `www/conexao.php` aponta para o seu ambiente MySQL:

```php
$host_bd = "localhost";
$login_bd = "root";
$password_bd = "";
$nome_bd = "labdbprog2";
$port = 3307;
```

Se a sua instalacao usa outra porta, usuario ou senha, ajuste esses valores.

### 3. Rodar a aplicacao PHP

Sirva a pasta `www/` em um servidor PHP/Apache e acesse:

```text
http://localhost/login.php
```

Em ambientes como XAMPP, WAMP ou Laragon, coloque a pasta do projeto no diretorio publico do servidor e acesse o arquivo `login.php`.

### 4. Rodar o modulo Node.js

Entre na pasta `nodejs/`, instale as dependencias e inicie o servidor:

```bash
npm install
npm start
```

Depois acesse:

```text
http://localhost:3000
```

## Executando com Docker Compose

O projeto possui `docker-compose.yml` com os seguintes servicos:

- `php`: Apache + PHP na porta `8080`;
- `nodejs`: Node.js na porta `3000`;
- `mysql`: MySQL 8;
- `phpmyadmin`: phpMyAdmin na porta `8081`.

Para subir os containers:

```bash
docker compose up --build
```

Acessos esperados:

| Servico | URL |
|---|---|
| PHP | `http://localhost:8080` |
| Node.js | `http://localhost:3000` |
| phpMyAdmin | `http://localhost:8081` |

Observacao: os arquivos `www/conexao.php` e `nodejs/conexao.js` estao configurados para MySQL local em `localhost:3307`. Para usar a rede interna do Docker Compose, ajuste o host para `mysql`, a porta para `3306` e as credenciais conforme o `docker-compose.yml`.

## Exemplos de API

### Verificar status

```bash
curl http://localhost:3000/api/status
```

### Listar consultas

```bash
curl http://localhost:3000/api/consultas
```

### Criar agendamento

```bash
curl -X POST "http://localhost:3000/api/agendamentos" \
  -H "Content-Type: application/json" \
  -d "{\"paciente\":\"Joao Teste\",\"medico_id\":1,\"especialidade_id\":1,\"data\":\"2026-05-19\",\"horario\":\"09:45\",\"status\":\"Confirmado\"}"
```

## Melhorias Recentes

- Sidebar padronizada entre as telas principais.
- Tela de cadastro de especialidades criada com acoes por AJAX.
- Cadastro de medicos atualizado com filtros, modal e alteracao de status.
- Calendario integrado a view de agendamentos.
- Confirmacoes de cancelamento, exclusao e alteracao de status com SweetAlert2.
- Estrutura Docker incluindo PHP, Node.js, MySQL e phpMyAdmin.

## Pontos de Atencao

- Algumas credenciais de banco ainda estao hardcoded nos arquivos de conexao.
- O cancelamento em `www/cancelar_agendamento.php` retorna sucesso, mas o bloco que atualiza o banco ainda esta comentado.
- A tela de agendamentos possui integracoes em andamento e alguns trechos marcados como TODO.
- A documentacao considera o estado atual do codigo na branch `main`.

## Objetivo Academico

Este projeto foi desenvolvido para praticar conceitos de desenvolvimento web, banco de dados relacional, integracao entre front-end e back-end, uso de sessoes, requisicoes AJAX e criacao de APIs REST.
