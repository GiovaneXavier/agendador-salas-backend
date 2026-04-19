# Instruções: Ativar Persistência no Banco de Dados

Estas instruções descrevem tudo que precisa ser feito para sair do `InMemoryRepository`
(dados em memória, perdidos ao reiniciar) e passar a usar o `EloquentRepository`
com SQL Server real.

---

## Pré-requisitos

- SQL Server acessível (instância local ou remota)
- Driver PHP `sqlsrv` e `pdo_sqlsrv` instalados e habilitados no `php.ini`
- Banco de dados criado (ex: `agendador_salas`)

Para verificar se os drivers estão disponíveis:

```bash
php -m | grep -i sqlsrv
```

Deve retornar `sqlsrv` e `pdo_sqlsrv`.

---

## Passo 1 — Configurar o `.env`

No diretório `api/`, edite o arquivo `.env` com as credenciais do SQL Server:

```env
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=agendador_salas
DB_USERNAME=sa
DB_PASSWORD=SUA_SENHA_AQUI
```

> Se o banco usar autenticação Windows (sem usuário/senha), use:
> ```env
> DB_USERNAME=
> DB_PASSWORD=
> ```
> e configure o DSN no `config/database.php` conforme necessário.

Verifique se a conexão funciona:

```bash
php artisan db:monitor
# ou simplesmente:
php artisan tinker --execute="DB::select('SELECT 1 AS ok')"
```

---

## Passo 2 — Executar as Migrations

```bash
php artisan migrate
```

Isso criará as tabelas:
- `rooms` — salas com id, name, color_bg, color_accent, capacity, resources
- `bookings` — reservas com id, room_id, date, start_minute, duration_minutes, username

> As migrations padrão do Laravel (`users`, `cache`, `jobs`) também serão executadas
> e não causam problema — podem ser ignoradas.

Confirme que as tabelas foram criadas:

```bash
php artisan tinker --execute="echo implode(', ', array_column(DB::select('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES'), 'TABLE_NAME'));"
```

---

## Passo 3 — Popular as Salas (Seeder)

```bash
php artisan db:seed
```

Isso executa `RoomSeeder` e `BookingSeeder`, inserindo:

**Salas:**
| ID | Nome | Capacidade | Recursos |
|----|------|-----------|----------|
| `a1b2c3d4-0001-...` | Sala Carvalho | 8 pessoas | TV, HDMI |
| `a1b2c3d4-0002-...` | Sala Ipê | 4 pessoas | Quadro branco |
| `a1b2c3d4-0003-...` | Sala Aroeira | 12 pessoas | Projetor, Videoconferência |
| `a1b2c3d4-0004-...` | Sala Cedro | 6 pessoas | TV, Quadro branco |

**Bookings de exemplo** para hoje (usados para testar a timeline).

> O seeder usa `updateOrCreate`, então pode ser rodado novamente sem duplicar dados.

---

## Passo 4 — Trocar os Bindings no AppServiceProvider

Edite o arquivo `app/Providers/AppServiceProvider.php`:

**Antes:**
```php
use App\Infrastructure\Persistence\InMemory\InMemoryBookingRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryRoomRepository;

public function register(): void
{
    $this->app->singleton(RoomRepositoryInterface::class, InMemoryRoomRepository::class);
    $this->app->singleton(BookingRepositoryInterface::class, InMemoryBookingRepository::class);
}
```

**Depois:**
```php
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentBookingRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoomRepository;

public function register(): void
{
    $this->app->singleton(RoomRepositoryInterface::class, EloquentRoomRepository::class);
    $this->app->singleton(BookingRepositoryInterface::class, EloquentBookingRepository::class);
}
```

> Remova também os dois `use` antigos dos InMemory para não deixar imports mortos.

---

## Passo 5 — Limpar o Cache da Aplicação

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Passo 6 — Verificar

Suba o servidor local:

```bash
php artisan serve
```

Teste os endpoints:

```bash
# Listar salas
curl http://localhost:8000/api/rooms

# Listar reservas de hoje
curl "http://localhost:8000/api/bookings?room_id=a1b2c3d4-0001-0001-0001-000000000001&date=$(date +%Y-%m-%d)"

# Criar uma reserva
curl -X POST http://localhost:8000/api/bookings \
  -H "Content-Type: application/json" \
  -d '{"room_id":"a1b2c3d4-0001-0001-0001-000000000001","date":"2026-04-22","start_minute":660,"duration_minutes":60,"username":"g.xavier"}'
```

Se `GET /api/rooms` retornar as 4 salas, a migração foi bem-sucedida.

---

## Estrutura de Arquivos Envolvidos

```
api/
├── app/
│   ├── Providers/
│   │   └── AppServiceProvider.php          ← EDITAR (Passo 4)
│   └── Infrastructure/Persistence/
│       ├── Eloquent/
│       │   ├── Models/
│       │   │   ├── BookingModel.php         (pronto — não editar)
│       │   │   └── RoomModel.php            (pronto — não editar)
│       │   └── Repositories/
│       │       ├── EloquentBookingRepository.php  (pronto — não editar)
│       │       └── EloquentRoomRepository.php     (pronto — não editar)
│       └── InMemory/                        (pode remover após a troca)
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_rooms_table.php
│   │   └── 2024_01_01_000002_create_bookings_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoomSeeder.php
│       └── BookingSeeder.php
└── .env                                     ← EDITAR (Passo 1)
```

---

## Possíveis Erros

| Erro | Causa | Solução |
|------|-------|---------|
| `could not find driver` | Driver `pdo_sqlsrv` não instalado | Instalar e habilitar no `php.ini` |
| `Login failed for user` | Credenciais erradas no `.env` | Corrigir `DB_USERNAME` / `DB_PASSWORD` |
| `Cannot open database` | Banco não existe | Criar o banco no SQL Server Management Studio |
| `SSL connection error` | SQL Server sem SSL configurado | Adicionar `DB_TRUST_SERVER_CERTIFICATE=true` no `.env` e `'TrustServerCertificate' => 'true'` no `config/database.php` em `options` |
| `Table 'bookings' doesn't exist` | Migrations não rodaram | Executar `php artisan migrate` novamente |
