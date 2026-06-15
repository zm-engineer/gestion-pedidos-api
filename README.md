# Gestión de Pedidos – API REST

API REST para un sistema de gestión de pedidos, desarrollada como prueba técnica de backend. Gestiona usuarios, productos y pedidos con sus líneas, aplicando reglas de negocio sobre stock y totales.

## Stack tecnológico

- **Laravel 12** (PHP 8.x)
- **MySQL 8.4**
- **Laravel Sail** (entorno Docker)
- **Laravel Sanctum** (autenticación por tokens)

## Decisiones técnicas

- Se eligió **Laravel 12** por estabilidad: es la versión madura inmediatamente anterior a la última (13), con soporte de seguridad hasta febrero de 2027. Se descartaron las versiones 10 y 11 por estar en fin de vida.
- Se usa **Docker mediante Laravel Sail** para garantizar un entorno reproducible sin necesidad de instalar PHP, Composer o MySQL en la máquina.

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

# 5. Generar la clave de la aplicación y migrar
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

La API quedará disponible en 

## Endpoints

| Método | URI            | Descripción                        | Auth |
|--------|----------------|------------------------------------|------|
| POST   | /api/register  | Registrar usuario, devuelve token  | No   |
| POST   | /api/login     | Login, devuelve token              | No   |
| POST   | /api/orders    | Crear pedido con sus líneas        | Sí   |
| GET    | /api/orders/{id} | Ver un pedido con sus líneas     | Sí   |


## Probar la API con Postman

La API corre en `http://localhost`. Todas las peticiones deben incluir el header:

- `Accept: application/json`

Las rutas protegidas requieren además el token devuelto por el login:

- `Authorization: Bearer {token}`

### Flujo básico

1. Hacer **registro** o **login** para obtener un `token`.
2. Copiar ese `token` de la respuesta.
3. Usarlo en las rutas protegidas (en Postman: pestaña *Authorization* → tipo *Bearer Token*).

### Ejemplos de petición

**Registro** — `POST /api/register`
```json
{
  "name": "Ana",
  "email": "ana@test.com",
  "password": "12345678"
}
```

**Login** — `POST /api/login`
```json
{
  "email": "test@example.com",
  "password": "password"
}
```
> El seeder crea el usuario `test@example.com` con contraseña `password`.

**Crear pedido** — `POST /api/orders` _(requiere token)_
```json
{
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 1 }
  ]
}
```