-- =============================================
-- Mini CRM - Control de Visitas y Llamadas
-- Script de creación de base de datos MSSQL
-- =============================================

-- Crear la base de datos (ejecutar si no existe)
-- CREATE DATABASE mini_crm;
-- GO
-- USE mini_crm;
-- GO

-- =============================================
-- Tabla: sucursales
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'sucursales')
BEGIN
    CREATE TABLE sucursales (
        id INT IDENTITY(1,1) PRIMARY KEY,
        nombre NVARCHAR(100) NOT NULL UNIQUE,
        direccion NVARCHAR(255),
        telefono NVARCHAR(50),
        activo BIT DEFAULT 1,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
    );
END
GO

-- =============================================
-- Tabla: usuarios
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'usuarios')
BEGIN
    CREATE TABLE usuarios (
        id INT IDENTITY(1,1) PRIMARY KEY,
        username NVARCHAR(50) NOT NULL UNIQUE,
        password NVARCHAR(255) NOT NULL,
        nombre_completo NVARCHAR(150) NOT NULL,
        email NVARCHAR(100),
        rol NVARCHAR(20) NOT NULL CHECK (rol IN ('admin', 'supervisor', 'vendedor')),
        id_sucursal INT NOT NULL,
        activo BIT DEFAULT 1,
        ultimo_acceso DATETIME,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (id_sucursal) REFERENCES sucursales(id)
    );
END
GO

-- =============================================
-- Tabla: vendedores
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'vendedores')
BEGIN
    CREATE TABLE vendedores (
        id INT IDENTITY(1,1) PRIMARY KEY,
        id_usuario INT NOT NULL UNIQUE,
        codigo NVARCHAR(20) NOT NULL UNIQUE,
        nombre NVARCHAR(150) NOT NULL,
        telefono NVARCHAR(50),
        email NVARCHAR(100),
        id_sucursal INT NOT NULL,
        activo BIT DEFAULT 1,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
        FOREIGN KEY (id_sucursal) REFERENCES sucursales(id)
    );
END
GO

-- =============================================
-- Tabla: clientes
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'clientes')
BEGIN
    CREATE TABLE clientes (
        id INT IDENTITY(1,1) PRIMARY KEY,
        nombre NVARCHAR(200) NOT NULL,
        rfc NVARCHAR(20),
        direccion NVARCHAR(500),
        telefono1 NVARCHAR(50),
        telefono2 NVARCHAR(50),
        email NVARCHAR(100),
        id_sucursal INT NOT NULL,
        notas NVARCHAR(MAX),
        activo BIT DEFAULT 1,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (id_sucursal) REFERENCES sucursales(id)
    );
END
GO

-- =============================================
-- Tabla: tipos_seguimiento
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'tipos_seguimiento')
BEGIN
    CREATE TABLE tipos_seguimiento (
        id INT IDENTITY(1,1) PRIMARY KEY,
        nombre NVARCHAR(50) NOT NULL UNIQUE,
        descripcion NVARCHAR(200),
        activo BIT DEFAULT 1,
        created_at DATETIME DEFAULT GETDATE()
    );
END
GO

-- =============================================
-- Tabla: seguimientos
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'seguimientos')
BEGIN
    CREATE TABLE seguimientos (
        id INT IDENTITY(1,1) PRIMARY KEY,
        id_cliente INT NOT NULL,
        id_vendedor INT NOT NULL,
        id_tipo INT NOT NULL,
        fecha_hora DATETIME NOT NULL,
        duracion_minutos INT,
        asunto NVARCHAR(200) NOT NULL,
        observaciones NVARCHAR(MAX),
        proxima_accion NVARCHAR(500),
        fecha_proxima DATETIME,
        archivo_adjunto NVARCHAR(255),
        id_sucursal INT NOT NULL,
        created_by INT NOT NULL,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (id_cliente) REFERENCES clientes(id),
        FOREIGN KEY (id_vendedor) REFERENCES vendedores(id),
        FOREIGN KEY (id_tipo) REFERENCES tipos_seguimiento(id),
        FOREIGN KEY (id_sucursal) REFERENCES sucursales(id),
        FOREIGN KEY (created_by) REFERENCES usuarios(id)
    );
END
GO

-- =============================================
-- Tabla: asignaciones_clientes
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'asignaciones_clientes')
BEGIN
    CREATE TABLE asignaciones_clientes (
        id INT IDENTITY(1,1) PRIMARY KEY,
        id_cliente INT NOT NULL,
        id_vendedor INT NOT NULL,
        fecha_asignacion DATETIME DEFAULT GETDATE(),
        fecha_fin DATETIME,
        activo BIT DEFAULT 1,
        motivo_cambio NVARCHAR(500),
        asignado_por INT NOT NULL,
        created_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (id_cliente) REFERENCES clientes(id),
        FOREIGN KEY (id_vendedor) REFERENCES vendedores(id),
        FOREIGN KEY (asignado_por) REFERENCES usuarios(id)
    );
END
GO

-- =============================================
-- Tabla: auditoria
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'auditoria')
BEGIN
    CREATE TABLE auditoria (
        id INT IDENTITY(1,1) PRIMARY KEY,
        id_usuario INT NOT NULL,
        accion NVARCHAR(50) NOT NULL,
        entidad NVARCHAR(100) NOT NULL,
        id_entidad INT,
        descripcion NVARCHAR(MAX),
        ip_address NVARCHAR(50),
        id_sucursal INT,
        created_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
    );
END
GO

-- =============================================
-- Índices para mejorar rendimiento
-- =============================================
CREATE INDEX IX_usuarios_sucursal ON usuarios(id_sucursal);
CREATE INDEX IX_vendedores_sucursal ON vendedores(id_sucursal);
CREATE INDEX IX_clientes_sucursal ON clientes(id_sucursal);
CREATE INDEX IX_seguimientos_fecha ON seguimientos(fecha_hora);
CREATE INDEX IX_seguimientos_cliente ON seguimientos(id_cliente);
CREATE INDEX IX_seguimientos_vendedor ON seguimientos(id_vendedor);
CREATE INDEX IX_asignaciones_cliente ON asignaciones_clientes(id_cliente);
CREATE INDEX IX_asignaciones_vendedor ON asignaciones_clientes(id_vendedor);
CREATE INDEX IX_auditoria_fecha ON auditoria(created_at);
CREATE INDEX IX_auditoria_usuario ON auditoria(id_usuario);
GO
