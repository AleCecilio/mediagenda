# MediAgenda

O **MediAgenda** é um sistema web para gerenciamento e agendamento de consultas médicas. O projeto foi desenvolvido com finalidade acadêmica na disciplina de Programação Web e reúne uma aplicação principal em PHP, banco de dados MySQL/MariaDB e um módulo complementar em Node.js com API REST.

## 👥 Integrantes
* Alessandro Cecilio
* João Paulo Borges
* João Victor Dizaró

## O Que o Sistema Faz

O sistema organiza o fluxo básico de uma agenda clínica, permitindo que o usuário:

- faça login no sistema;
- visualize consultas em um calendário mensal;
- cadastre, edite, filtre e cancele agendamentos;
- gerencie médicos e seus dados profissionais;
- cadastre e mantenha especialidades médicas;
- atualize dados de perfil, senha e foto do usuário;
- acompanhe status de consultas, médicos e especialidades;
- utilize uma interface responsiva com menu lateral, modais e alertas visuais.

## Acesso Inicial

O arquivo `script.sql` já cria usuários de teste para acessar o sistema:

| Usuário | Senha |
|---|---|
| `aluno` | `123456` |
| `professor` | `professor123` |

Após configurar o banco e iniciar a aplicação, acesse a tela de login:

```text
http://localhost/login.php
```

Se estiver usando Docker Compose, o acesso padrão da aplicação PHP será:

```text
http://localhost:8080
```

## Funcionalidades

### Login

A tela `www/login.php` valida se usuário e senha foram preenchidos antes de enviar os dados para `www/cadastrobanco.php`. Quando a autenticação é bem-sucedida, o usuário é direcionado para `www/principal.php`.

### Painel Principal

O arquivo `www/principal.php` exibe o calendário mensal da clínica.

Principais interações:

- navegação entre mês anterior, mês atual e próximo mês;
- destaque visual para o dia atual;
- exibição de cards de consultas dentro de cada dia;
- abertura de modal com os detalhes do agendamento;
- cancelamento de agendamento com confirmação via SweetAlert2;
- exibição da foto de perfil do usuário no menu superior, quando cadastrada;
- menu lateral responsivo para acessar as demais telas.

### Agendamentos

O arquivo `www/cadastro_agendas.php` centraliza a gestão das consultas cadastradas.

Principais interações:

- cadastro de novo agendamento por modal;
- edição de agendamentos existentes;
- cancelamento com confirmação;
- filtros por paciente, médico, status e período;
- listagem em tabela com badges de status;
- integração com a view `vw_agendamentos`.

### Médicos

O arquivo `www/cadastro_medicos.php` gerencia os profissionais da clínica.

Principais interações:

- cadastro de médico;
- edição de nome, CRM, especialidade, telefone e e-mail;
- filtros por nome, especialidade e status;
- ativação e inativação de médicos;
- exclusão somente quando não houver agendamentos vinculados;
- ações realizadas via AJAX com retorno em JSON.

### Especialidades

O arquivo `www/cadastro_especialidades.php` gerencia as áreas médicas disponíveis.

Principais interações:

- cadastro de especialidade;
- edição do nome da especialidade;
- ativação e inativação de registros;
- bloqueio de inativação quando houver médicos ativos vinculados;
- bloqueio de exclusão quando houver médicos vinculados;
- feedback visual com SweetAlert2.

### Configurações

O arquivo `www/configuracoes.php` adiciona uma área para manutenção da conta do usuário logado.

Principais interações:

- atualização de nome e e-mail do perfil;
- alteração de senha com validação da senha atual;
- envio de foto de perfil nos formatos JPG, PNG, GIF ou WebP;
- limite de imagem de até 2 MB;
- remoção da foto cadastrada;
- configuração do número máximo de agendamentos visíveis por dia no calendário;
- ações realizadas via AJAX com retorno em JSON e feedback com SweetAlert2.

### Módulo Node.js

A pasta `nodejs/` contém uma aplicação Express com uma interface simples e endpoints REST para agendamentos.

Endpoints disponíveis:

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/status` | Retorna o status do backend Node.js. |
| `GET` | `/api/consultas` | Lista as consultas da view `vw_agendamentos`. |
| `POST` | `/api/agendamentos` | Cadastra um novo agendamento. |
| `GET` | `/api/agendamentos/:id` | Busca um agendamento específico. |
| `PUT` | `/api/agendamentos/:id` | Atualiza um agendamento. |
| `DELETE` | `/api/agendamentos/:id` | Exclui um agendamento. |
| `GET` | `/api/agendamentos/pesquisar/filtros` | Pesquisa agendamentos por filtros. |

## Tecnologias Utilizadas

| Tecnologia | Finalidade |
|---|---|
| PHP 8.2 | Aplicação web principal |
| MySQL / MariaDB | Banco de dados relacional |
| MySQLi | Conexão da aplicação PHP com o banco |
| Node.js | Backend complementar |
| Express | Criação da API REST |
| mysql2 | Conexão do Node.js com o MySQL |
| Bootstrap | Layout, componentes e responsividade |
| Font Awesome | Ícones da interface |
| SweetAlert2 | Alertas, confirmações e mensagens de feedback |
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
|   |-- configuracoes.php
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

O arquivo `script.sql` cria o banco `labdbprog2`, suas tabelas, views e dados iniciais.

Objetos criados:

- tabela `usuario`;
- tabela `especialidades`;
- tabela `medicos`;
- tabela `agendamentos`;
- view `vw_agendamentos`;
- view `vw_medicos`;
- registros iniciais para testes.

A tabela `usuario` também armazena preferências e dados visuais do perfil:

| Campo | Finalidade |
|---|---|
| `max_agendamentos_dia` | Preferência de quantidade máxima de agendamentos visíveis por dia no calendário. |
| `foto` | Foto de perfil armazenada em base64. |

Configuração local usada atualmente:

| Campo | Valor |
|---|---|
| Host | `localhost` |
| Porta | `3307` |
| Banco | `labdbprog2` |
| Usuário | `root` |
| Senha | vazia |

Arquivos que configuram a conexão:

- PHP: `www/conexao.php`
- Node.js: `nodejs/conexao.js`

## Como Executar Localmente

### 1. Criar o Banco de Dados

Execute ou importe o arquivo `script.sql` no MySQL/MariaDB.

Pelo terminal MySQL, uma opção é:

```sql
SOURCE script.sql;
```

Também é possível importar o arquivo pelo phpMyAdmin, MySQL Workbench, DBeaver ou ferramenta semelhante.

### 2. Conferir a Conexão do PHP

Abra `www/conexao.php` e confira se os dados correspondem ao seu ambiente:

```php
$host_bd = "localhost";
$login_bd = "root";
$password_bd = "";
$nome_bd = "labdbprog2";
$port = 3307;
```

Altere esses valores se o seu MySQL estiver em outra porta ou usar outro usuário/senha.

### 3. Iniciar a Aplicação PHP

Sirva a pasta `www/` em um servidor PHP/Apache.

Em ambientes como XAMPP, WAMP ou Laragon, coloque a pasta do projeto no diretório público do servidor e acesse:

```text
http://localhost/login.php
```

### 4. Iniciar o Módulo Node.js

Entre na pasta `nodejs/`, instale as dependências e inicie o servidor:

```bash
npm install
npm start
```

Depois acesse:

```text
http://localhost:3000
```

## Execução com Docker Compose

O arquivo `docker-compose.yml` define quatro serviços:

| Serviço | Descrição | Acesso |
|---|---|---|
| `php` | Apache com PHP | `http://localhost:8080` |
| `nodejs` | Aplicação Node.js | `http://localhost:3000` |
| `mysql` | Banco MySQL 8 | `localhost:3306` |
| `phpmyadmin` | Interface para administrar o banco | `http://localhost:8081` |

Para iniciar o ambiente:

```bash
docker compose up --build
```

Observação: os arquivos `www/conexao.php` e `nodejs/conexao.js` estão configurados para um MySQL local em `localhost:3307`. Para usar o MySQL do Docker Compose, ajuste o host para `mysql`, a porta para `3306` e as credenciais de acordo com o `docker-compose.yml`.

## Exemplos de Uso da API

### Verificar Status

```bash
curl http://localhost:3000/api/status
```

### Listar Consultas

```bash
curl http://localhost:3000/api/consultas
```

### Criar Agendamento

```bash
curl -X POST "http://localhost:3000/api/agendamentos" \
  -H "Content-Type: application/json" \
  -d "{\"paciente\":\"João Teste\",\"medico_id\":1,\"especialidade_id\":1,\"data\":\"2026-05-19\",\"horario\":\"09:45\",\"status\":\"Confirmado\"}"
```

## Melhorias Recentes

- Padronização da sidebar entre as telas principais.
- Criação da tela de cadastro de especialidades.
- Criação da tela de configurações do usuário.
- Inclusão de foto de perfil no dropdown das telas principais.
- Atualização da tabela `usuario` com campos de preferência e foto.
- Atualização do cadastro de médicos com filtros, modal e alteração de status.
- Integração do calendário com a view de agendamentos.
- Confirmações de cancelamento, exclusão e alteração de status com SweetAlert2.
- Estrutura Docker com PHP, Node.js, MySQL e phpMyAdmin.

## Pontos de Atenção

- As credenciais de banco ainda estão definidas diretamente nos arquivos de conexão.
- O arquivo `www/cancelar_agendamento.php` retorna sucesso, mas o bloco que atualiza o banco ainda está comentado.
- A tela de agendamentos possui integrações em andamento e alguns trechos marcados como `TODO`.
- A preferência `max_agendamentos_dia` é salva em `usuario`, mas o calendário ainda usa o limite fixo de 3 cards por dia em `www/principal.php`.
- A documentação considera o estado atual da branch `main`.

## Objetivo Acadêmico

Este projeto tem como objetivo praticar desenvolvimento web com PHP, banco de dados relacional, sessões, requisições AJAX, componentes de interface, integração com API REST e versionamento com Git/GitHub.
