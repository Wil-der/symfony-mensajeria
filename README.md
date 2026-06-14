# 📦 Plataforma de Mensajería Bajo Demanda

Backend Symfony 7.1 + PHP 8.3 para plataforma de mensajería con tracking en tiempo real.

## Stack Tecnológico

- **PHP 8.3+** con Symfony 7.1 LTS
- **Doctrine ORM 3.x** + MySQL 8.x
- **Redis 7.x** para caché y colas
- **Mercure Hub** para comunicaciones real-time (SSE)
- **OSRM + Nominatim** self-hosted para geocodificación y rutas
- **Docker Compose** para orquestación

## Arquitectura Modular

```
src/
├── Auth/            # Autenticación, usuarios, JWT
├── Mensajero/       # Perfil, disponibilidad, aprobación
├── Envio/           # Ciclo de vida, workflow, asignación
├── Tracking/        # Ubicación en tiempo real, Mercure publisher
├── Chat/            # Mensajería entre cliente/mensajero
├── Pago/            # Comprobantes Transfermóvil/Enzona
├── Geocoding/       # Nominatim, OSRM, caché Redis
├── Admin/           # Reportes, configuración, aprobaciones
└── Shared/          # Servicios compartidos, DTOs, validadores
```

## Instalación Rápida

### Prerrequisitos
- Docker y Docker Compose instalados
- Al menos 4GB RAM disponibles

### Pasos

1. **Clonar repositorio**
```bash
cd /workspace
```

2. **Configurar variables de entorno**
```bash
cp .env .env.local
# Editar .env.local con valores apropiados
```

3. **Generar claves JWT**
```bash
docker-compose run --rm app php bin/console lexik:jwt:generate-keypair
```

4. **Iniciar servicios**
```bash
docker-compose up -d
```

5. **Instalar dependencias**
```bash
docker-compose exec app composer install
```

6. **Ejecutar migraciones**
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate
```

## Endpoints Principales

### Autenticación
- `POST /api/v1/auth/registro/cliente` - Registro cliente
- `POST /api/v1/auth/registro/mensajero` - Registro mensajero
- `POST /api/v1/auth/login` - Login (devuelve JWT + Mercure token)
- `POST /api/v1/auth/logout` - Logout (invalida JWT)

### Cliente
- `GET /api/v1/cliente/envios` - Listar envíos
- `POST /api/v1/cliente/envios` - Crear envío
- `GET /api/v1/cliente/envios/{uuid}/tracking` - Tracking en tiempo real

### Mensajero
- `POST /api/v1/mensajero/disponibilidad` - Activar/desactivar
- `POST /api/v1/mensajero/ubicacion` - Actualizar ubicación
- `GET /api/v1/mensajero/solicitudes/pendientes` - Ver solicitudes

### Admin
- `GET /api/v1/admin/mensajeros` - Mensajeros pendientes
- `POST /api/v1/admin/mensajeros/{uuid}/aprobar` - Aprobar mensajero
- `GET /api/v1/admin/pagos/pendientes` - Pagos por verificar

## Características Clave

### Real-time con Mercure
- Tracking de ubicación en vivo
- Notificaciones push a clientes
- Chat entre cliente y mensajero

### Caché Multicapa
- **L1**: APCu para configuraciones (5 min)
- **L2**: Redis para ubicaciones, geocoding, rutas
- **L3**: MySQL para persistencia

### Seguridad
- JWT RS256 con blacklist en Redis
- Datos sensibles cifrados (AES-256-GCM)
- Rate limiting en endpoints críticos
- Validadores personalizados (CI cubano, teléfono)

### Workflow de Envíos
```
pendiente → pagado → asignado → recogido → en_camino → entregado
```

## Documentación Completa

Ver `docs/ARQUITECTURA.md` para detalles completos de implementación.

## Licencia

Propietaria - Todos los derechos reservados