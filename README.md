# Agendador de Salas — API

API REST para o sistema de agendamento de salas (totem/tablet), construída com **Laravel 11** e arquitetura **DDD**.

---

## Stack

| Tecnologia | Versão |
|---|---|
| PHP | 8.2+ |
| Laravel | 11 |
| Banco de dados | SQL Server (via `sqlsrv`) |
| ORM | Eloquent (encapsulado em Repositórios) |
| Testes | Pest PHP 4 |

---

## Instalação

### 1. Clonar e instalar dependências
```bash
git clone <repo>
cd api
composer install
```

### 2. Configurar variáveis de ambiente
```bash
cp .env.example .env
php artisan key:generate
```

Edite `.env` com as credenciais do SQL Server:
```env
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=agendador_salas
DB_USERNAME=sa
DB_PASSWORD=SuaSenha

FRONTEND_URL=http://localhost:5173
```

> **Para desenvolvimento local com SQLite**, use:
> ```env
> DB_CONNECTION=sqlite
> ```
> E crie o arquivo: `touch database/database.sqlite`

### 3. Rodar migrations e seeders
```bash
php artisan migrate
php artisan db:seed
```

### 4. Iniciar o servidor
```bash
php artisan serve
# API disponível em http://localhost:8000/api
```

---

## Endpoints

### Salas

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/rooms` | Lista todas as salas |

**Resposta:**
```json
{
  "data": [
    {
      "id": "a1b2c3d4-0001-0001-0001-000000000001",
      "name": "Sala Carvalho",
      "color_bg": "#eae4dc",
      "color_accent": "#4a3d2f"
    }
  ]
}
```

---

### Reservas

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/bookings?room_id=&date=` | Lista reservas por sala e data |
| `POST` | `/api/bookings` | Cria nova reserva |
| `PATCH` | `/api/bookings/{id}/extend` | Estende +30 minutos |
| `DELETE` | `/api/bookings/{id}` | Cancela reserva |

#### `POST /api/bookings`
```json
// Body
{
  "room_id": "a1b2c3d4-0001-0001-0001-000000000001",
  "date": "2024-06-15",
  "start_minute": 600,
  "duration_minutes": 30,
  "username": "g.xavier"
}

// Conflito → 409
{ "error": "slot_unavailable", "message": "Conflito de horário: 09:30–11:00 já está reservado por time.produto." }
```

#### `DELETE /api/bookings/{id}`
```json
// Body
{ "username": "g.xavier" }

// Username incorreto → 404
{ "error": "booking_not_found", "message": "Usuário 'm.silva' não é o organizador desta reserva." }
```

---

## Regras de Negócio

| Regra | Detalhe |
|---|---|
| Range de horários | 07:00–20:00 (420–1200 minutos) |
| Durações válidas | 30, 60, 90 ou 120 minutos |
| Conflito | Sem sobreposição de reservas na mesma sala e data |
| Cancelamento | `username` deve ser idêntico ao organizador (case-insensitive) |
| Extensão | +30 min, respeitando 20h e sem conflito |

---

## Arquitetura DDD

```
app/
├── Domain/              # Regras de negócio puras (sem dependência de framework)
│   ├── Room/
│   │   ├── Entities/Room.php
│   │   ├── ValueObjects/RoomId.php
│   │   └── Repositories/RoomRepositoryInterface.php
│   └── Booking/
│       ├── Entities/Booking.php
│       ├── ValueObjects/{BookingId, BookingPeriod}.php
│       ├── Repositories/BookingRepositoryInterface.php
│       ├── Events/{BookingCreated, BookingCancelled, BookingExtended}.php
│       ├── Services/BookingConflictService.php
│       └── Exceptions/{SlotUnavailableException, BookingNotFoundException}.php
│
├── Application/         # Orquestração dos casos de uso
│   ├── Room/UseCases/ListRoomsUseCase.php
│   └── Booking/UseCases/{ListBookings,CreateBooking,ExtendBooking,CancelBooking}UseCase.php
│
├── Infrastructure/      # Eloquent — implementações concretas
│   └── Persistence/Eloquent/
│       ├── Models/{RoomModel, BookingModel}.php
│       └── Repositories/{EloquentRoom,EloquentBooking}Repository.php
│
└── Presentation/        # HTTP (Controllers, Form Requests, API Resources)
    └── Http/
        ├── Controllers/{Room,Booking}Controller.php
        ├── Requests/{ListBookings,CreateBooking,CancelBooking}Request.php
        └── Resources/{Room,Booking}Resource.php
```

---

## Testes

```bash
# Todos os testes (sem banco de dados)
php vendor/bin/pest tests/Unit tests/Feature/Application

# Resultado esperado: 21 testes, 32 assertions, 0 falhas
```

---

## SQL Server — Driver PHP

Para usar SQL Server, habilite os drivers no `php.ini`:
```ini
extension=php_sqlsrv_83_ts_x64.dll
extension=php_pdo_sqlsrv_83_ts_x64.dll
```
Download: https://learn.microsoft.com/pt-br/sql/connect/php/download-drivers-php-sql-server
