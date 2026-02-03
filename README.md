# API REST – SaaS Oficina Mecânica

API REST para cadastro e listagem de clientes (PF/PJ) com autenticação simples, em **PHP 8.2+**, **Slim Framework 4** e **PHP-DI**.

## Requisitos

- PHP 8.2+
- Composer
- MySQL 5.7+ ou MariaDB (para autenticação e, opcionalmente, persistência de clientes)

## Instalação

1. **Clonar ou acessar o projeto e instalar dependências:**

```bash
composer install
```

2. **Configurar ambiente:**

```bash
cp .env.example .env
# Editar .env com credenciais do banco e REPOSITORY (mysql ou json)
```

3. **Criar banco e tabelas (obrigatório para login):**

```bash
mysql -u root -p < database.sql
```

Se a tabela `clients` já existir sem a coluna `deleted_at`, execute:  
`ALTER TABLE clients ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at, ADD INDEX idx_deleted_at (deleted_at);`

4. **Criar um usuário para login (exemplo – senha `admin123`):**

```sql
INSERT INTO users (email, password_hash) VALUES ('admin@oficina.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
```

5. **Subir o servidor PHP:**

```bash
php -S localhost:8080 -t public
```

A API fica em `http://localhost:8080`.

## Endpoints

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| POST | `/login` | Não | Login (retorna token) |
| POST | `/logout` | Sim | Logout (cliente descarta o token) |
| GET | `/clients` | Sim | Lista clientes (paginação) |
| GET | `/clients/{id}` | Sim | Busca cliente por ID |
| POST | `/clients` | Sim | Cria cliente |
| PUT | `/clients/{id}` | Sim | Atualiza cliente |
| DELETE | `/clients/{id}` | Sim | Remove cliente |

### Autenticação

- **Login:** `POST /login` com body JSON: `{"email": "...", "password": "..."}`. Resposta: `{"status": "success", "data": {"token": "..."}}`.
- **Logout:** `POST /logout` com header `Authorization: Bearer <token>`. Resposta: `{"status": "success", "message": "Logout realizado com sucesso."}`. O cliente deve descartar o token (ex.: remover do localStorage).
- **Rotas protegidas:** enviar header `Authorization: Bearer <token>`.

### Listagem (GET /clients) – parâmetros padrão do projeto

- **page** (int): Página (padrão 1).
- **perPage** (int): Itens por página (padrão 10; máximo 200).
- **search** (string): Busca em nome, documento e telefone (LIKE).
- **sort** (string): Ordenação. Formato: `coluna:asc` ou `coluna:desc`; múltiplas separadas por vírgula (ex.: `name:asc,id:desc`). Colunas: id, name, phone, type, document, created_at, updated_at.
- **filter** (coluna = valor): Filtro exato. Ex.: `?type=PF` ou `?filter[type]=PF`. Para clientes: type (PF/PJ).

Exemplos:  
`GET /clients?page=1&perPage=20&search=Silva&sort=name:asc&type=PF`

### Formato de resposta

Todos os retornos seguem:

```json
{
  "status": "success",
  "data": { ... },
  "message": "..."
}
```

Em erro: `"status": "error"`, `"message"` e opcionalmente `"data"` com detalhes (ex.: `errors` de validação).

### Payload de cliente (POST/PUT)

- `name` (string, obrigatório)
- `phone` (string, obrigatório)
- `type` (string: `"PF"` ou `"PJ"`)
- `document` (string: CPF 11 dígitos ou CNPJ 14 dígitos)

## Decisões técnicas (SOLID)

- **Domínio:** `ClientRepositoryInterface` e `ClientService` concentram a regra de negócio e o contrato de persistência, sem depender de implementação concreta (Dependency Inversion).
- **Serviço:** `ClientService` orquestra uso do repositório (listar, criar, atualizar, excluir), mantendo controllers finos (Single Responsibility).
- **Repositórios:** Implementações `MySQLClientRepository` (PDO + prepared statements) e `JsonClientRepository` (arquivo em `storage/`) são intercambiáveis via container (Open/Closed, Liskov).
- **Validação:** Camada `Request/Validator` valida entrada antes de chegar ao Service, mantendo domínio e aplicação desacoplados da representação HTTP.
- **Injeção de dependência:** PHP-DI injeta repositórios nos services e services nas Actions; a escolha MySQL vs JSON é feita apenas no container (`config/container.php`).

## Alternar entre MySQL e JSON (clientes)

No `.env`:

- **MySQL (padrão):** `REPOSITORY=mysql`  
  Exige banco criado e tabela `clients`. Usuários continuam sempre em MySQL.

- **JSON:** `REPOSITORY=json`  
  Clientes são gravados em `storage/clients.json` (diretório criado automaticamente). Login continua usando a tabela `users` no MySQL.

Não é necessário alterar código: apenas trocar a variável e reiniciar a aplicação.

## Estrutura de pastas

```
src/
├── Application/
│   ├── Actions/          # Controllers (Login, CRUD clientes)
│   ├── Middleware/       # AuthMiddleware
│   └── Request/          # Validators; ListCriteriaConfig, ListCriteriaParser; ClientListCriteriaConfig
├── Domain/
│   ├── Common/           # ListCriteria, ListResult (padrão de listagem)
│   ├── Models/           # Client, User
│   ├── Repositories/     # Interfaces
│   └── Services/         # ClientService, AuthService
└── Infrastructure/
    └── Persistence/      # MySQLClientRepository, JsonClientRepository, MySQLUserRepository
config/
├── container.php         # Definições PHP-DI (bindings e REPOSITORY)
└── routes.php            # Rotas Slim + grupo protegido
public/
└── index.php             # Entry point
storage/                  # Arquivo clients.json quando REPOSITORY=json
database.sql              # Script das tabelas users e clients
```

## Produção (recomendações)

- **Migrations:** Usar ferramenta de migrations (ex.: Phinx, Doctrine Migrations) em vez de rodar `database.sql` direto.
- **Testes:** Unitários para `ClientService`, validators e repositórios; integração para endpoints (ex.: PHPUnit + TestCase Slim).
- **Logs:** PSR-3 (Monolog) em arquivo ou serviço externo; não expor stack trace em respostas.
- **Segurança:** HTTPS; trocar `AUTH_SECRET`; considerar JWT/OAuth2 em vez de token customizado; rate limiting e CORS configurados.
- **Ambiente:** Manter `REPOSITORY=mysql` em produção; JSON apenas para desenvolvimento/demo.

## Licença

Uso interno / projeto educacional.
