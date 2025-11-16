# Mini CRM - Control de Visitas y Llamadas

Sistema web para el control de visitas y llamadas de vendedores a clientes, desarrollado en PHP + HTML + JavaScript con conexión a Microsoft SQL Server.

## Características Principales

- **Sistema de Autenticación** con roles (Admin, Supervisor, Vendedor) y control por sucursal
- **Dashboard** con métricas en tiempo real
- **Gestión de Catálogos**: Vendedores, Clientes, Sucursales, Tipos de Seguimiento
- **Registro de Seguimientos**: Visitas, llamadas y otros contactos con clientes
- **Asignación de Clientes** a vendedores con historial de cambios
- **Reportes** filtrables con exportación a CSV y PDF
- **Sistema de Auditoría** que registra todas las acciones
- **Diseño Responsive** y minimalista con colores neutros

## Requisitos del Sistema

- **Servidor Web**: Apache (XAMPP) o IIS
- **PHP**: 7.4 o superior
- **Base de Datos**: Microsoft SQL Server 2014 o superior
- **Extensiones PHP**:
  - sqlsrv (Microsoft Drivers for PHP for SQL Server)
  - fileinfo
  - gd (para manejo de imágenes)

## Instalación

### 1. Preparar la Base de Datos

1. Abrir SQL Server Management Studio (SSMS)
2. Crear una nueva base de datos:
```sql
CREATE DATABASE mini_crm;
GO
```

3. Ejecutar el script de esquema:
```sql
USE mini_crm;
GO
-- Ejecutar contenido de sql/schema.sql
```

4. Cargar datos de ejemplo:
```sql
USE mini_crm;
GO
-- Ejecutar contenido de sql/import_sample.sql
```

### 2. Configurar la Conexión

Editar el archivo `includes/conexion.php` con los datos de su servidor:

```php
$serverName = "localhost"; // o IP/nombre del servidor
$database = "mini_crm";
$username = "sa"; // usuario SQL Server
$password = "your_password"; // su contraseña
```

**Si ya tiene un archivo conexion.php existente:**
1. Coloque su archivo en `includes/conexion.php`
2. Asegúrese de que expone una variable `$conn` con la conexión SQLSRV
3. O implemente las funciones helper: `ejecutarConsulta()`, `obtenerRegistro()`, `obtenerRegistros()`, `insertarRegistro()`, `actualizarRegistro()`

### 3. Instalar en el Servidor Web

#### XAMPP (Apache)
1. Copiar la carpeta `mini-crm` a `C:\xampp\htdocs\`
2. Acceder vía: `http://localhost/mini-crm/`

#### IIS
1. Crear un sitio web apuntando a la carpeta `mini-crm`
2. Asegurar que PHP está configurado como handler
3. Configurar permisos de escritura en `assets/uploads/`

### 4. Configurar Permisos

Asegurar que el servidor web tiene permisos de escritura en:
- `assets/uploads/` (para archivos adjuntos)

En Windows:
```
icacls "C:\ruta\mini-crm\assets\uploads" /grant "IIS_IUSRS:(OI)(CI)F"
```

### 5. Ajustar Configuración

Revisar `includes/config.php`:
- `BASE_URL`: Ajustar si no está en `/mini-crm`
- `SESSION_LIFETIME`: Tiempo de expiración de sesión (default: 1 hora)
- `DEBUG_MODE`: Cambiar a `false` en producción

## Usuarios de Ejemplo

| Usuario | Contraseña | Rol | Descripción |
|---------|------------|-----|-------------|
| admin | admin123 | Admin | Acceso total a todos los módulos |
| supervisor_matriz | super123 | Supervisor | Supervisor de sucursal Matriz |
| supervisor_mazatlan | super123 | Supervisor | Supervisor de sucursal Mazatlán |
| vendedor1 | vende123 | Vendedor | Vendedor de sucursal Matriz |
| vendedor2 | vende123 | Vendedor | Vendedor de sucursal Matriz |
| vendedor3 | vende123 | Vendedor | Vendedor de sucursal Mazatlán |
| vendedor4 | vende123 | Vendedor | Vendedor de sucursal Mochis |

**Nota**: Las contraseñas están hasheadas con `password_hash()` de PHP.

## Estructura del Proyecto

```
mini-crm/
├── assets/
│   ├── css/
│   │   └── style.css           # Estilos principales
│   ├── js/
│   │   └── app.js              # JavaScript del frontend
│   └── uploads/                # Archivos adjuntos
├── includes/
│   ├── config.php              # Configuración global
│   ├── conexion.php            # Conexión a MSSQL
│   ├── auth.php                # Autenticación y autorización
│   ├── header.php              # Header y navegación
│   └── footer.php              # Footer
├── modules/
│   ├── catalogos/              # CRUD de catálogos
│   ├── seguimientos/           # Registro de visitas/llamadas
│   ├── asignaciones/           # Asignación de clientes
│   ├── reportes/               # Reportes y exportación
│   ├── auditoria/              # Log de auditoría
│   └── usuarios/               # Administración de usuarios
├── sql/
│   ├── schema.sql              # Esquema de BD
│   └── import_sample.sql       # Datos de ejemplo
├── index.php                   # Dashboard
├── login.php                   # Página de login
├── logout.php                  # Cerrar sesión
├── perfil.php                  # Perfil de usuario
└── README.md                   # Este archivo
```

## Roles y Permisos

### Administrador (admin)
- Acceso completo a todos los módulos
- Puede elegir cualquier sucursal al iniciar sesión
- Gestiona usuarios de todos los niveles
- Accede a auditoría completa

### Supervisor (supervisor)
- Acceso a catálogos de su sucursal
- Gestiona vendedores y clientes de su sucursal
- Puede crear usuarios (excepto admin)
- Accede a reportes de su sucursal
- No puede ver auditoría general

### Vendedor (vendedor)
- Ve solo sus clientes asignados
- Registra seguimientos propios
- Reportes limitados a su actividad
- Puede modificar su perfil

## Seguridad

- **Autenticación**: Sesiones PHP seguras con regeneración de ID
- **Autorización**: Control granular por rol y sucursal
- **SQL Injection**: Consultas parametrizadas con SQLSRV
- **XSS**: Sanitización de entradas con `htmlspecialchars()`
- **CSRF**: Tokens de protección en formularios
- **Contraseñas**: Hash con `password_hash()` (bcrypt)
- **Auditoría**: Registro de todas las acciones críticas

## Guía de Pruebas Manuales

### 1. Probar Login
- [ ] Login como admin con cualquier sucursal
- [ ] Login como supervisor solo con su sucursal
- [ ] Login fallido con credenciales incorrectas
- [ ] Verificar redirección después de login

### 2. Probar Dashboard
- [ ] Métricas se actualizan según filtros de rol/sucursal
- [ ] Próximas acciones muestran datos correctos
- [ ] Últimos seguimientos visibles

### 3. Probar Catálogos
- [ ] Crear, editar y eliminar clientes
- [ ] Crear vendedores (requiere usuario vendedor)
- [ ] Filtros funcionan correctamente
- [ ] Permisos por sucursal respetados

### 4. Probar Seguimientos
- [ ] Crear nuevo seguimiento
- [ ] Subir archivo adjunto (PDF, JPG)
- [ ] Editar seguimiento existente
- [ ] Ver detalle completo
- [ ] Filtros por tipo/vendedor/fecha

### 5. Probar Asignaciones
- [ ] Asignar cliente a vendedor
- [ ] Reasignar (genera historial)
- [ ] Ver clientes sin asignar
- [ ] Historial de asignaciones

### 6. Probar Reportes
- [ ] Filtrar por múltiples criterios
- [ ] Exportar a CSV (verificar encoding UTF-8)
- [ ] Generar PDF para impresión
- [ ] Estadísticas se calculan correctamente

### 7. Probar Auditoría (solo admin)
- [ ] Visualizar log de acciones
- [ ] Filtrar por usuario/acción/fecha
- [ ] Exportar a CSV

### 8. Probar Perfil
- [ ] Actualizar información personal
- [ ] Cambiar contraseña (validar actual)
- [ ] Verificar información de sesión

### 9. Probar Usuarios
- [ ] Crear nuevo usuario (supervisor no puede crear admin)
- [ ] Editar usuario existente
- [ ] Activar/desactivar usuario
- [ ] No puede desactivarse a sí mismo

## Personalización

### Cambiar Colores
Editar variables CSS en `assets/css/style.css`:
```css
:root {
    --color-primary: #4a5568;
    --color-primary-dark: #2d3748;
    /* ... más colores ... */
}
```

### Agregar Nueva Sucursal
1. Ir a Catálogos > Sucursales (como admin)
2. Click en "Nueva Sucursal"
3. Completar datos y guardar

### Cambiar Tiempo de Sesión
En `includes/config.php`:
```php
define('SESSION_LIFETIME', 7200); // 2 horas
```

## Solución de Problemas

### Error de conexión a SQL Server
- Verificar que el servicio SQL Server está ejecutándose
- Comprobar credenciales en `conexion.php`
- Asegurar que las extensiones sqlsrv están habilitadas
- Para XAMPP: Descargar drivers de https://docs.microsoft.com/en-us/sql/connect/php/

### Sesión expira muy rápido
- Ajustar `SESSION_LIFETIME` en `config.php`
- Verificar configuración de PHP `session.gc_maxlifetime`

### No puedo subir archivos
- Verificar permisos en carpeta `assets/uploads/`
- Revisar `upload_max_filesize` en php.ini
- Confirmar extensiones permitidas en `config.php`

### Caracteres especiales se ven mal
- Asegurar que la base de datos usa collation UTF-8
- Verificar que los archivos PHP están guardados en UTF-8
- El driver SQLSRV debería tener `"CharacterSet" => "UTF-8"`

## Mejoras Futuras Sugeridas

1. **Notificaciones**: Sistema de alertas para próximas acciones
2. **API REST**: Endpoints para integración con apps móviles
3. **Gráficas**: Implementar Chart.js para visualización de datos
4. **Backup**: Script automatizado de respaldo
5. **Multi-idioma**: Soporte para inglés
6. **Importación masiva**: Carga de clientes desde Excel
7. **Geolocalización**: Registro de ubicación en visitas
8. **Calendario**: Vista de calendario para seguimientos

## Créditos

Desarrollado como sistema de control de visitas y llamadas para equipos de ventas.

- PHP 7.4+
- Microsoft SQL Server
- HTML5 / CSS3 / JavaScript ES6
- Diseño minimalista y responsive

## Licencia

Código fuente disponible para uso interno de la empresa. Modificación y redistribución permitida según acuerdo.

---

**Mini CRM v1.0** - Control de Visitas y Llamadas

Para soporte técnico o consultas, contactar al administrador del sistema.
