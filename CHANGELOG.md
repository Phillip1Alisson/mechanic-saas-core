# Changelog – API REST SaaS Oficina

Todas as alterações notáveis deste backend serão registradas aqui. O formato segue as diretrizes do [Keep a Changelog](https://keepachangelog.com/) e as versões aderem ao [SemVer](https://semver.org/).

---

## [1.3.0] - 2026-02-03
### Melhorado
- **CORS**: extraído para classe dedicada `CorsMiddleware` em `src/Application/Middleware/CorsMiddleware.php`.
- Configuração CORS via `CORS_ALLOWED_ORIGINS` e `CORS_EXTRA_HEADERS` em variáveis de ambiente (origens dinâmicas, headers extras).
- Bootstrap em `public/index.php` simplificado.

## [1.2.0] - 2026-02-03
### Adicionado
- Repositórios baseados em arquivo (`JsonClientRepository`, `JsonUserRepository`) com toggle via `REPOSITORY=json`.
- Configuração `STORAGE_PATH` para definir onde os arquivos JSON serão gerados (`storage/` por padrão).
- Documentação complementar em `DOCUMENTATION.md` descrevendo arquitetura e fluxos internos.
### Alterado
- `config/container.php` agora seleciona dinamicamente a implementação de repositório com base nas variáveis de ambiente.
- README atualizado com instruções para alternar entre MySQL e JSON.

## [1.1.0] - 2026-02-03
### Adicionado
- Camada completa de validação de entrada (`ClientRequestValidator`, `LoginRequestValidator`) e parser de listagem (`ListCriteriaParser`, `ClientListCriteriaConfig`).
- Serviço de domínio `ClientService` com regra de unicidade de CPF/CNPJ (`DocumentAlreadyExistsException`).
- Middleware de autenticação (`AuthMiddleware`) reutilizável em rotas protegidas.
- Script `database/seed_initial_user.sql` para provisionar usuário admin.
### Melhorado
- Respostas de listagem padronizadas via `ListResult` (metadados + itens) e suporte a busca, ordenação e filtros.

## [1.0.0] - 2026-02-03
### Adicionado
- Estrutura inicial em **Slim 4** com PHP-DI, CORS básico e middlewares essenciais.
- Autenticação simples (`POST /login`), emissão de token HMAC e `POST /logout`.
- CRUD completo de clientes (`GET/POST/PUT/DELETE /clients`) com persistência MySQL (`MySQLClientRepository`, `MySQLUserRepository`).
- Scripts de banco (`database/database.sql`) e instruções no README para preparar ambiente local.

---

*Para futuras entregas, documente sempre as mudanças técnicas e o racional para facilitar auditoria e comunicação com o time frontend.*
