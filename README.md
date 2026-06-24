# Automatismos Marpel MVP

MVP Laravel + PostgreSQL + Filament + PWA mobile-first para gestion de avisos, revisiones, presupuestos, facturas, contratos, materiales y partes de trabajo.

## Stack

- Laravel 12
- Filament 4
- PostgreSQL 16
- PHP 8.4
- Vite + Tailwind CSS 4
- Docker con Nginx + PHP-FPM en el contenedor `app`

## Modulos incluidos

- Clientes
- Instalaciones / ubicaciones
- Tipos de equipo administrables
- Equipos
- Avisos
- Revisiones
- Partes de trabajo
- Presupuestos
- Facturas
- Contratos
- Catalogo de materiales
- Usuarios y tecnicos
- Canales de integracion preparados
- Dashboard administracion
- Dashboard gerencia
- Calendario operativo
- PWA tecnico mobile-first

## Primer arranque local con Docker

```bash
cp .env.example .env
docker compose build
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --seed
```

Abrir:

- Login general: `http://localhost/login`
- Panel oficina/gerencia: `http://localhost/admin`
- PWA tecnico: `http://localhost/tecnico`

Usuarios semilla:

| Rol | Email | Password |
| --- | --- | --- |
| Administracion | `admin@marpel.local` | `password` |
| Tecnico | `tecnico@marpel.local` | `password` |
| Gerencia | `gerencia@marpel.local` | `password` |

Los usuarios de administracion y gerencia pueden entrar al panel Filament. Los tecnicos acceden a la PWA movil.

## Flujo tecnico movil

1. Entrar en `/tecnico`.
2. Ver avisos y revisiones asignadas.
3. Abrir Maps/Waze o llamar al contacto.
4. Iniciar parte.
5. Escribir trabajo realizado, observaciones, materiales y fotos.
6. Recoger firma.
7. Cerrar parte.

El formulario guarda borradores de texto en `localStorage` para reducir perdidas cuando hay poca cobertura.

## Despliegue VPS Linux

1. Instalar Docker y Docker Compose.
2. Subir este proyecto al servidor.
3. Crear `.env` desde `.env.example`.
4. Cambiar como minimo:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://tu-dominio.com`
   - `DB_PASSWORD=...`
5. Ejecutar:

```bash
docker compose build
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --force --seed
```

Para activar migraciones automaticas al levantar el contenedor:

```env
RUN_MIGRATIONS=true
```

En produccion real, es mejor ejecutar migraciones de forma explicita antes o durante la ventana de despliegue.

## Estructura principal

```text
app/
  Filament/
    Pages/
    Resources/
  Http/Controllers/TechnicianController.php
  Models/
  Services/
database/
  migrations/
  seeders/
docker/
  nginx/
  php/
  supervisor/
public/
  manifest.webmanifest
  service-worker.js
resources/
  css/
  js/
  views/
```

## Notas MVP

- El panel Filament cubre la operativa de administracion y gerencia.
- La PWA de tecnico esta separada del panel para mantener botones grandes, pocos pasos y velocidad en campo.
- Los avisos, revisiones, presupuestos, partes y facturas guardan `customer_id` e `installation_id` para busquedas rapidas y trazabilidad.
- Los contratos pueden asociarse al cliente completo o a una instalacion concreta.
- Un cliente abonado se determina por contrato activo, no por un campo booleano.
- Los materiales se descuentan al cerrar el parte.
- Al cerrar una revision se actualiza la ultima revision del equipo y se calcula la proxima.
- Cada equipo obtiene un codigo interno automatico tipo `EQ-000001`.
- La ficha de equipo centraliza avisos, revisiones, partes, materiales, fotos, presupuestos y facturacion vinculada.
- Quedan preparadas tablas para futuras integraciones: WhatsApp, Telegram, Lucas, Jarvis, formularios web, email y API externa.
