# Sonara

Marketplace web accesible para emprendedores con discapacidad visual. Plataforma que conecta a emprendedores, clientes y administradores, con **registro guiado por voz**, **generación de afiches con IA**, **verificación de documentos con plazos** y cumplimiento de **WCAG 2.2 AA**.

## Requisitos

- PHP 8.2+
- Composer 2
- Node.js 20+ / npm
- MySQL 8+ o MariaDB (SQLite soportado para pruebas)

## Instalación

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Configura tu base de datos en .env (DB_CONNECTION=mysql, DB_DATABASE=sonara, etc.)
php artisan migrate --seed

npm run build
php artisan serve
```

Accede a `http://127.0.0.1:8000`.

## Cuentas de demostración (seeder)

| Rol         | Email                    | Contraseña    |
|-------------|--------------------------|---------------|
| Administrador | `admin@sonara.test`    | `Password123!` |
| Emprendedora | `emprendedor@sonara.test` | `Password123!` |
| Cliente     | `cliente@sonara.test`    | `Password123!` |

## Rapport de pruebas

```bash
php artisan test
```

## Documentación

- [Arquitectura](docs/architecture.md)
- [Base de datos](docs/database.md)
- [Accesibilidad (WCAG 2.2)](docs/accessibility.md)
- [Seguridad](docs/security.md)
- [Integración de IA (afiches)](docs/ai-integration.md)
- [Integración por voz (registro guiado)](docs/voice-integration.md)
- [Trazabilidad (auditoría y notificaciones)](docs/traceability.md)
- [Cumplimiento normativo](docs/compliance.md)
- [Verificación final y manual de pruebas](docs/final-verification.md)

## Módulos principales

- **Portal público**: catálogo, exploración por categorías/subcategorías, páginas de emprendimiento y publicación.
- **Panel emprendedor**: emprendimientos, publicaciones, afiches IA, solicitudes recibidas, documentación, asistencia, notificaciones.
- **Panel cliente**: dashboard y solicitudes enviadas.
- **Panel administrador**: usuarios, emprendedores, registro asistido, emprendimientos, publicaciones en revisión, categorías, subcategorías, solicitudes, asistencia, auditoría y configuración.
- **Registro por voz**: flujo conversacional completo (nombre, contacto, emprendimiento, contraseña) usando Web Speech API.