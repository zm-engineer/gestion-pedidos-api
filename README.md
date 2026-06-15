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

_Se documentarán conforme se implementen._