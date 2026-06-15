# Gestión de Pedidos – API REST

API REST para un sistema de gestión de pedidos, desarrollada como prueba técnica de backend. Gestiona usuarios, productos y pedidos con sus líneas, aplicando reglas de negocio sobre stock y totales.

## Stack tecnológico

- **Laravel 12** (PHP 8.2+)
- **MySQL 8.4**
- **Laravel Sail** (entorno Docker)
- **Laravel Sanctum** (autenticación por tokens)

## Decisiones técnicas

- **Laravel 12:** se eligió por estabilidad. Es la versión madura inmediatamente anterior a la última (13), con soporte de seguridad hasta febrero de 2027. Se descartaron las versiones 10 y 11 por estar en fin de vida (sin parches de seguridad).
- **Docker mediante Laravel Sail:** garantiza un entorno reproducible, sin necesidad de instalar PHP, Composer ni MySQL en la máquina del evaluador.
- **Registro de middleware:** en Laravel 12 ya no existe `app/Http/Kernel.php`; el alias del middleware `CheckOrderOwner` se registra en `bootstrap/app.php`, dentro de `->withMiddleware()`.
- **Manejo de errores centralizado:** configurado en `bootstrap/app.php` (`->withExceptions()`) para que todas las rutas `api/*` devuelvan errores en JSON con los códigos correctos (401, 403, 404, 422), independientemente del header `Accept`.

## Reglas de negocio

La lógica del dominio se aplica automáticamente al crear un pedido (`POST /api/orders`):

- **El precio se "congela":** al crear cada línea, el `unit_price` se copia del precio actual del producto. Si el producto cambia de precio más tarde, el pedido conserva el del momento de la compra.
- **El total se calcula solo:** un *Observer* sobre `OrderItem` recalcula `orders.total` (suma de subtotales) en cada cambio de línea. Nunca se asigna a mano.
- **Validación de stock previa:** antes de guardar, se valida que haya stock suficiente; si no, la API responde **422** y no se crea el pedido.
- **Descuento de stock con evento:** al crear el pedido se dispara `OrderCreated`, cuyo *Listener* descuenta el stock de cada producto. Como red de seguridad, si el stock no alcanza, lanza una excepción.
- **Atomicidad (transacción):** toda la creación ocurre dentro de una transacción de BD. Si algo falla (p. ej. el stock se agota por una compra concurrente), **se revierte todo**.
- **Restauración de stock al cancelar:** cancelar un pedido pendiente dispara `OrderCancelled`, cuyo listener devuelve el stock de cada producto. *(Mejora propia, no exigida por el enunciado.)*


## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y en ejecución.

## Instalación y puesta en marcha

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio>
cd gestion-pedidos

# 2. Copiar el archivo de entorno
cp .env.example .env

# 3. Instalar dependencias (usando la imagen de Composer, sin PHP local)
docker run --rm -v "$(pwd):/app" -w /app composer:latest composer install

# 4. Levantar los contenedores
./vendor/bin/sail up -d

# 5. Generar la clave, migrar y sembrar datos de prueba
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

La API queda disponible en `http://localhost`.

El seeder crea un usuario de prueba: **`test@example.com`** / **`password`**, y 15 productos.


## Endpoints

| Método | URI                      | Descripción                          | Auth |
|--------|--------------------------|--------------------------------------|------|
| POST   | /api/register            | Registrar usuario, devuelve token    | No   |
| POST   | /api/login               | Login, devuelve token                | No   |
| GET    | /api/products            | Listar productos (cacheado 5 min)    | Sí   |
| POST   | /api/orders              | Crear pedido con sus líneas          | Sí   |
| GET    | /api/orders              | Listar pedidos del usuario           | Sí   |
| GET    | /api/orders/{id}         | Ver un pedido con sus líneas         | Sí   |
| PUT    | /api/orders/{id}/cancel  | Cancelar pedido (si está pending)    | Sí   |


## Probar la API con Postman

Todas las peticiones deben incluir el header `Accept: application/json`. Las rutas protegidas requieren además `Authorization: Bearer {token}` (el token lo obtienes en el registro o el login).

> Flujo: haz **registro** o **login**, copia el `token` de la respuesta y úsalo en el resto de rutas (en Postman: *Authorization → Bearer Token*).

### Autenticación

**Registrar usuario** — `POST /api/register`
```json
{ "name": "Ana", "email": "ana@test.com", "password": "12345678" }
```
- `201` — usuario creado, devuelve usuario y token.
- `422` — datos inválidos: falta un campo, email mal formado, email ya registrado o contraseña con menos de 8 caracteres.

**Login** — `POST /api/login`
```json
{ "email": "test@example.com", "password": "password" }
```
- `200` — devuelve usuario y token.
- `422` — credenciales incorrectas o campos faltantes.

### Productos

**Listar productos** — `GET /api/products` _(requiere token)_
- `200` — lista de productos (cacheada 5 minutos).
- `401` — sin token o token inválido.

### Pedidos

**Crear pedido** — `POST /api/orders` _(requiere token)_
```json
{
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 1 }
  ]
}
```
- `201` — pedido creado con sus líneas; el stock se descuenta y el total se calcula.
- `401` — sin token.
- `422` — `items` vacío, `product_id` inexistente, `quantity` menor que 1, o **stock insuficiente** (en este último caso no se crea nada y el stock no cambia).

**Listar mis pedidos** — `GET /api/orders` _(requiere token)_
- `200` — lista de los pedidos del usuario autenticado.
- `401` — sin token.

**Ver un pedido** — `GET /api/orders/{id}` _(requiere token)_
- `200` — el pedido con sus líneas y productos.
- `401` — sin token.
- `403` — el pedido pertenece a otro usuario.
- `404` — el pedido no existe.

**Cancelar pedido** — `PUT /api/orders/{id}/cancel` _(requiere token)_
- `200` — pedido cancelado; se restaura el stock de los productos.
- `401` — sin token.
- `403` — el pedido pertenece a otro usuario.
- `404` — el pedido no existe.
- `422` — el pedido no está `pending` (ya estaba completado o cancelado).

### Cómo reproducir los errores

- **401:** llama a cualquier ruta protegida sin el header `Authorization` (o con un token inválido).
- **403:** crea un pedido con el usuario A y luego intenta verlo o cancelarlo autenticado como usuario B.
- **404:** usa un `{id}` que no exista, p. ej. `/api/orders/999999`.
- **422:** en crear pedido, manda una `quantity` mayor que el stock disponible; en cancelar, cancela dos veces el mismo pedido.

## Tests

```bash
./vendor/bin/sail artisan test
```

La suite de feature tests cubre:

- **Creación de pedido:** verifica que un pedido válido devuelve 201, calcula el total correctamente y descuenta el stock; y que un pedido con stock insuficiente devuelve 422, no se crea y deja el stock intacto (la transacción revierte).
- **Cancelación y restauración de stock:** verifica que al cancelar un pedido pendiente el estado pasa a `cancelled` y el stock del producto se restaura a su valor original.
- **Aislamiento por usuario:** verifica que un usuario no puede ver ni cancelar pedidos de otro usuario (respuesta 403), validando el middleware `CheckOrderOwner`.