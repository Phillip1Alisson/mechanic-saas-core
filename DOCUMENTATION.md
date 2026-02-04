# Documentação Técnica – API REST SaaS Oficina

Este documento complementa o `README.md` com detalhes de arquitetura, fluxos internos e diretrizes de manutenção do backend em PHP 8.2 + Slim 4.

---

## 1. Visão Geral da Arquitetura

- **Bootstrap (`public/index.php`)**: carrega `.env`, instancia o container (`config/container.php`), aplica middlewares globais (CORS, body parsing, error handler) e registra rotas (`config/routes.php`).
- **Camada Application (`src/Application/`)**
  - **Actions**: controladores finos (ex.: `ListClientsAction`) recebem dependências por injeção e orquestram serviços/validadores.
  - **Middleware**: `AuthMiddleware` valida o token nos headers antes de permitir acesso às rotas protegidas.
  - **Request**: validadores (`ClientRequestValidator`, `LoginRequestValidator`) e utilitários de paginação (`ListCriteriaParser`, `ClientListCriteriaConfig`) convertem a entrada HTTP em objetos de domínio.
- **Camada Domain (`src/Domain/`)**
  - **Models** (`Client`, `User`), **Services** (`ClientService`, `AuthService`), **Common** (`ListCriteria`, `ListResult`), **Repositories (interfaces)** e **Exceptions**.
  - Esta camada não conhece Slim nem PDO; tudo é expresso em objetos puros.
- **Camada Infrastructure (`src/Infrastructure/`)**
  - Repositórios concretos para MySQL e JSON. Implementam `ClientRepositoryInterface` e `UserRepositoryInterface`.
  - Responsáveis por mapear objetos para SQL ou arquivos.
- **Configuração/DI (`config/container.php`)**
  - Usa PHP-DI. Alterna o repositório a partir de `REPOSITORY=mysql|json`.
  - Expõe configurações (`settings`) com dados de DB, storage, auth secret.

---

## 2. Fluxos Principais

### 2.1 Login (`POST /login`)
1. `LoginAction` recebe o corpo JSON e delega ao `LoginRequestValidator`.
2. `AuthService::login` busca o usuário no repositório (`MySQLUserRepository` ou `JsonUserRepository`) e valida a senha com `password_verify`.
3. Em caso de sucesso, gera token base64 com assinatura HMAC usando `AUTH_SECRET`. O cliente deve enviar `Authorization: Bearer <token>`.

### 2.2 Rotas protegidas (`/logout`, `/clients/*`)
1. `AuthMiddleware` lê o header `Authorization`.
2. `AuthService::validateToken` decodifica, valida expiração e HMAC; se falhar retorna 401.
3. A Action correspondente roda com as dependências resolvidas pelo container.

### 2.3 CRUD de Clientes
1. `ClientRequestValidator` garante formato de nome, telefone, tipo (PF/PJ) e documento (CPF/CNPJ).
2. `ClientService` aplica regras de negócio (ex.: `DocumentAlreadyExistsException` antes de criar/atualizar).
3. Repositório persiste: 
   - **MySQL**: `MySQLClientRepository` usa PDO com prepared statements.
   - **JSON**: `JsonClientRepository` grava em `storage/clients.json` e realiza soft delete via `deleted_at`.
4. `ListResult` padroniza resposta de paginação (itens + metainformações).

---

## 3. Persistência e Ambiente

- **MySQL** (padrão)
  - Script base em `database/database.sql` (tabelas `users` e `clients` com índices e soft delete).
  - `database/seed_initial_user.sql` repõe o usuário admin (`admin@mecanica.com / 123456`).
  - Ajuste credenciais no `.env`: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- **JSON**
  - Defina `REPOSITORY=json` e, opcionalmente, `STORAGE_PATH`.
  - Dados ficam em `storage/clients.json` e `storage/users.json` (criados sob demanda). Útil para desenvolvimento offline.
- **Variáveis sensíveis**
  - `AUTH_SECRET` assina tokens HMAC; troque em produção.
  - Mantenha `.env` fora do controle de versão (já listado no `.gitignore`).

---

## 4. Validações e Listagem

- **ListCriteria**: abstrai paginação, busca textual (LIKE em nome/documento/telefone) e filtros simples (`type=PF`). Configurações padrão ficam em `ClientListCriteriaConfig` (page=1, perPage=10, limite máx. 200, colunas permitidas para ordenação).
- **ClientRequestValidator**: normaliza CPF/CNPJ para comparar duplicidade, aplica regex básica de telefone e garante que `type` seja PF/PJ.
- **Document uniqueness**: tratado no serviço e reforçado no repositório com filtros por documento (incluindo exclusão de registros soft deleted).

---

## 5. Guia de Manutenção

- **Adicionar campo ao Cliente**
  1. Atualize `App\Domain\Models\Client` com o novo atributo e `toArray`.
  2. Ajuste `ClientRequestValidator` e `ClientService` (se aplicável).
  3. Atualize ambos os repositórios (MySQL: alterar schema + queries; JSON: serialização).
  4. Expanda `database/database.sql`/migrations.
- **Criar nova Action**
  1. Implemente em `src/Application/Actions`.
  2. Registre rota em `config/routes.php`.
  3. Injete dependências via PHP-DI (autowire ou definição explícita).
- **Trocar regras de autenticação**
  - Centralize em `AuthService` para manter `AuthMiddleware` e Actions desacopladas. Para JWT, substitua a implementação mantendo o contrato `login()`/`validateToken()`.

---

## 6. Testes e Observabilidade (Roadmap)

- **Testes Unitários**
  - `ClientService` (regra de duplicidade), `AuthService` (token inválido/expirado), `ListCriteriaParser`.
- **Testes de Integração**
  - Exercitar Actions via Slim TestCase com repositórios in-memory/JSON.
- **Logs**
  - Integrar PSR-3 (ex.: Monolog) no middleware global para rastrear requisições e exceções sem expor stack traces em produção.

Este guia deve ser usado em conjunto com o `README.md` para acelerar onboarding e padronizar futuras evoluções do backend.
