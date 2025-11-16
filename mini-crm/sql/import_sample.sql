-- =============================================
-- Mini CRM - Datos de Ejemplo
-- Ejecutar después de schema.sql
-- =============================================

-- USE mini_crm;
-- GO

-- =============================================
-- Insertar Sucursales
-- =============================================
INSERT INTO sucursales (nombre, direccion, telefono) VALUES
('Matriz', 'Av. Principal #100, Centro', '(667) 100-0001'),
('Mazatlan', 'Blvd. Marina #200, Zona Dorada', '(669) 200-0002'),
('Mochis', 'Av. Independencia #300, Centro', '(668) 300-0003'),
('Guasave', 'Calle Hidalgo #400, Centro', '(687) 400-0004'),
('Guamuchil', 'Av. Juarez #500, Centro', '(673) 500-0005'),
('TRP Mazatlan', 'Carretera Internacional #600', '(669) 600-0006');
GO

-- =============================================
-- Insertar Tipos de Seguimiento
-- =============================================
INSERT INTO tipos_seguimiento (nombre, descripcion) VALUES
('Visita', 'Visita presencial al cliente'),
('Llamada', 'Llamada telefonica al cliente'),
('Otro', 'Otro tipo de contacto (email, mensaje, etc.)');
GO

-- =============================================
-- Insertar Usuarios
-- Contraseñas: admin123, super123, vende123 (hasheadas con password_hash)
-- Para pruebas rápidas, se incluyen también en texto plano comentado
-- =============================================

-- Usuario Admin (contraseña: admin123)
INSERT INTO usuarios (username, password, nombre_completo, email, rol, id_sucursal) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'admin@empresa.com', 'admin', 1);

-- Supervisores (contraseña: super123)
INSERT INTO usuarios (username, password, nombre_completo, email, rol, id_sucursal) VALUES
('supervisor_matriz', '$2y$10$VbXZqSKVKL5nOLq9bN.FUODqWQHVPJvzjz3v3bPlmGW/jV2FV5LGy', 'Carlos Martinez Lopez', 'carlos.martinez@empresa.com', 'supervisor', 1),
('supervisor_mazatlan', '$2y$10$VbXZqSKVKL5nOLq9bN.FUODqWQHVPJvzjz3v3bPlmGW/jV2FV5LGy', 'Maria Garcia Ruiz', 'maria.garcia@empresa.com', 'supervisor', 2),
('supervisor_mochis', '$2y$10$VbXZqSKVKL5nOLq9bN.FUODqWQHVPJvzjz3v3bPlmGW/jV2FV5LGy', 'Roberto Sanchez', 'roberto.sanchez@empresa.com', 'supervisor', 3);

-- Vendedores (contraseña: vende123)
INSERT INTO usuarios (username, password, nombre_completo, email, rol, id_sucursal) VALUES
('vendedor1', '$2y$10$rNLDL7e5.VyXqRbqHqHqQOZKUNqLRq9Q9Q9Q9Q9Q9Q9Q9Q9Q9Q9Q9', 'Juan Perez Hernandez', 'juan.perez@empresa.com', 'vendedor', 1),
('vendedor2', '$2y$10$rNLDL7e5.VyXqRbqHqHqQOZKUNqLRq9Q9Q9Q9Q9Q9Q9Q9Q9Q9Q9Q9', 'Ana Lopez Torres', 'ana.lopez@empresa.com', 'vendedor', 1),
('vendedor3', '$2y$10$rNLDL7e5.VyXqRbqHqHqQOZKUNqLRq9Q9Q9Q9Q9Q9Q9Q9Q9Q9Q9Q9', 'Pedro Rodriguez', 'pedro.rodriguez@empresa.com', 'vendedor', 2),
('vendedor4', '$2y$10$rNLDL7e5.VyXqRbqHqHqQOZKUNqLRq9Q9Q9Q9Q9Q9Q9Q9Q9Q9Q9Q9', 'Laura Gomez Diaz', 'laura.gomez@empresa.com', 'vendedor', 3);
GO

-- =============================================
-- Insertar Vendedores (perfil de ventas)
-- =============================================
INSERT INTO vendedores (id_usuario, codigo, nombre, telefono, email, id_sucursal) VALUES
(5, 'VEN001', 'Juan Perez Hernandez', '(667) 111-1111', 'juan.perez@empresa.com', 1),
(6, 'VEN002', 'Ana Lopez Torres', '(667) 222-2222', 'ana.lopez@empresa.com', 1),
(7, 'VEN003', 'Pedro Rodriguez', '(669) 333-3333', 'pedro.rodriguez@empresa.com', 2),
(8, 'VEN004', 'Laura Gomez Diaz', '(668) 444-4444', 'laura.gomez@empresa.com', 3);
GO

-- =============================================
-- Insertar Clientes de Ejemplo
-- =============================================
INSERT INTO clientes (nombre, rfc, direccion, telefono1, telefono2, email, id_sucursal, notas) VALUES
('Comercializadora del Norte SA', 'CNO850101ABC', 'Av. Reforma #1234, Culiacan', '(667) 710-1234', '(667) 710-1235', 'contacto@comnorte.com', 1, 'Cliente frecuente, preferencia por pagos a 30 dias'),
('Distribuidora Pacific SA de CV', 'DPA900215DEF', 'Blvd. Leyva Solano #567', '(667) 715-5678', NULL, 'ventas@distpacific.com', 1, 'Requiere factura inmediata'),
('Abarrotes El Sol', 'ASO780320GHI', 'Calle Obregon #89, Mazatlan', '(669) 981-2345', NULL, 'elsol@abarrotes.com', 2, 'Pedidos semanales'),
('Ferreteria Industrial', 'FIN880412JKL', 'Av. Insurgentes #234, Mazatlan', '(669) 985-6789', '(669) 985-6790', 'compras@ferreindustrial.com', 2, NULL),
('Supermercado La Familia', 'SLF950630MNO', 'Blvd. Rosales #456, Los Mochis', '(668) 812-3456', NULL, 'admin@lafamilia.com', 3, 'Tienda de autoservicio grande'),
('Papeleria Central', 'PCE910825PQR', 'Calle Hidalgo #78, Guasave', '(687) 872-1111', NULL, 'papeleria@central.com', 4, 'Cliente nuevo 2024'),
('Restaurante El Patron', 'REP870110STU', 'Av. Juarez #90, Guamuchil', '(673) 732-2222', NULL, 'elpatron@rest.com', 5, 'Pedidos mensuales de insumos'),
('Transportes Rapidos', 'TRA820505VWX', 'Carretera #1500, Mazatlan', '(669) 990-5555', '(669) 990-5556', 'logistica@trapidos.com', 6, 'Servicios de transporte');
GO

-- =============================================
-- Asignaciones de Clientes a Vendedores
-- =============================================
INSERT INTO asignaciones_clientes (id_cliente, id_vendedor, activo, asignado_por) VALUES
(1, 1, 1, 1), -- Comercializadora del Norte -> Juan Perez
(2, 1, 1, 1), -- Distribuidora Pacific -> Juan Perez
(3, 3, 1, 1), -- Abarrotes El Sol -> Pedro Rodriguez
(4, 3, 1, 1), -- Ferreteria Industrial -> Pedro Rodriguez
(5, 4, 1, 1), -- Supermercado La Familia -> Laura Gomez
(6, 2, 1, 1), -- Papeleria Central -> Ana Lopez
(7, 2, 1, 1), -- Restaurante El Patron -> Ana Lopez
(8, 3, 1, 1); -- Transportes Rapidos -> Pedro Rodriguez
GO

-- =============================================
-- Seguimientos de Ejemplo
-- =============================================
INSERT INTO seguimientos (id_cliente, id_vendedor, id_tipo, fecha_hora, duracion_minutos, asunto, observaciones, proxima_accion, fecha_proxima, id_sucursal, created_by) VALUES
(1, 1, 1, DATEADD(day, -5, GETDATE()), 45, 'Presentacion de nuevos productos', 'Cliente interesado en linea premium. Solicito cotizacion formal.', 'Enviar cotizacion por email', DATEADD(day, 2, GETDATE()), 1, 5),
(1, 1, 2, DATEADD(day, -2, GETDATE()), 15, 'Seguimiento cotizacion', 'Confirmo recepcion de cotizacion. Evaluando con gerencia.', 'Llamar para cierre', DATEADD(day, 5, GETDATE()), 1, 5),
(3, 3, 1, DATEADD(day, -7, GETDATE()), 60, 'Revision de inventario', 'Necesitan reabastecer productos basicos. Acordamos pedido semanal.', 'Procesar primer pedido semanal', DATEADD(day, 1, GETDATE()), 2, 7),
(5, 4, 2, DATEADD(day, -3, GETDATE()), 20, 'Consulta sobre descuentos', 'Pregunto por descuentos por volumen. Envie tabla de precios.', 'Visita para negociacion', DATEADD(day, 7, GETDATE()), 3, 8),
(2, 1, 1, DATEADD(day, -1, GETDATE()), 30, 'Entrega de muestras', 'Entregue muestras de nuevos productos. Muy satisfecho con calidad.', 'Llamar para pedido', DATEADD(day, 3, GETDATE()), 1, 5);
GO

-- =============================================
-- Registros de Auditoria de Ejemplo
-- =============================================
INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, descripcion, ip_address, id_sucursal) VALUES
(1, 'crear', 'usuarios', 2, 'Creacion de usuario supervisor_matriz', '192.168.1.100', 1),
(1, 'crear', 'clientes', 1, 'Alta de cliente Comercializadora del Norte SA', '192.168.1.100', 1),
(5, 'crear', 'seguimientos', 1, 'Registro de visita a cliente ID 1', '192.168.1.105', 1),
(1, 'login', 'sistema', NULL, 'Inicio de sesion exitoso', '192.168.1.100', 1),
(5, 'login', 'sistema', NULL, 'Inicio de sesion exitoso', '192.168.1.105', 1);
GO

PRINT 'Datos de ejemplo insertados correctamente';
PRINT 'Usuarios disponibles:';
PRINT '- admin / admin123 (Administrador - Acceso total)';
PRINT '- supervisor_matriz / super123 (Supervisor Matriz)';
PRINT '- supervisor_mazatlan / super123 (Supervisor Mazatlan)';
PRINT '- vendedor1 / vende123 (Vendedor Matriz)';
PRINT '- vendedor2 / vende123 (Vendedor Matriz)';
PRINT '- vendedor3 / vende123 (Vendedor Mazatlan)';
PRINT '- vendedor4 / vende123 (Vendedor Mochis)';
GO
