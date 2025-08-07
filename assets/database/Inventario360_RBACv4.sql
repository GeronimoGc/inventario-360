-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 10-06-2025 a las 20:00:00
-- Versión del servidor: 5.7.24
-- Versión de PHP: 8.3.1

-- Establecimiento de la zona horaria
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Creación de la base de datos si no existe
--
CREATE DATABASE IF NOT EXISTS `inventario360` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `inventario360`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_rol`
--
CREATE TABLE `inventario360_rol` (
  `idrol` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL UNIQUE,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo' COMMENT 'Indica si el rol está activo o inactivo',
  PRIMARY KEY (`idrol`),
  UNIQUE KEY `nombre_UNIQUE` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena roles de los usuarios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_usuario`
--
CREATE TABLE `inventario360_usuario` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL UNIQUE,
  `contrasenia` varchar(255) NOT NULL,
  `estado` ENUM('activo', 'inactivo', 'eliminado') NOT NULL DEFAULT 'activo', -- Esta línea se agregó en la conversación anterior
  `rol_id` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo_UNIQUE` (`correo`),
  KEY `fk_usuario_rol_idx` (`rol_id`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `inventario360_rol` (`idrol`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena información de usuarios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_permiso`
--
CREATE TABLE `inventario360_permiso` (
  `idpermiso` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre único y descriptivo del permiso (ej: producto_ver, bodega_crear)',
  `descripcion` TEXT NULL COMMENT 'Descripción detallada del permiso',
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo' COMMENT 'Indica si el permiso está activo o inactivo',
  PRIMARY KEY (`idpermiso`),
  UNIQUE KEY `nombre_UNIQUE` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena los permisos disponibles en el sistema';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_rol_permiso`
--
CREATE TABLE `inventario360_rol_permiso` (
  `rol_id` INT(11) UNSIGNED NOT NULL,
  `permiso_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`rol_id`, `permiso_id`),
  KEY `fk_rol_permiso_permiso_idx` (`permiso_id`),
  CONSTRAINT `fk_rol_permiso_rol` FOREIGN KEY (`rol_id`) REFERENCES `inventario360_rol` (`idrol`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rol_permiso_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `inventario360_permiso` (`idpermiso`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla intermedia que relaciona roles con permisos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_bodega`
--
CREATE TABLE `inventario360_bodega` (
  `idbodega` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `direccion` text,
  `capacidad_maxima` int(11) NOT NULL,
  `capacidad_actual` int(11) NOT NULL,
  PRIMARY KEY (`idbodega`),
  KEY `nombre_idx` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena información de bodegas';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_categoria`
--
CREATE TABLE `inventario360_categoria` (
  `idcategoria` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text,
  PRIMARY KEY (`idcategoria`),
  UNIQUE KEY `nombre_UNIQUE` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena categorías de productos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_tipo_movimiento`
--
CREATE TABLE `inventario360_tipo_movimiento` (
  `idtipo_movimiento` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL UNIQUE COMMENT 'Nombre del tipo de movimiento (ej: Entrada, Salida, Transferencia, Ajuste)',
  PRIMARY KEY (`idtipo_movimiento`),
  UNIQUE KEY `nombre_UNIQUE` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena los tipos de movimiento de inventario';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_producto`
--
CREATE TABLE `inventario360_producto` (
  `idproducto` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero_producto` varchar(50) NOT NULL UNIQUE,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo','averiado') NOT NULL,
  `fecha_registro` date NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `idcategoria` int(11) UNSIGNED NOT NULL,
  `idbodega` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`idproducto`),
  UNIQUE KEY `numero_producto_UNIQUE` (`numero_producto`),
  KEY `fk_producto_categoria_idx` (`idcategoria`),
  KEY `fk_producto_bodega_idx` (`idbodega`),
  CONSTRAINT `fk_producto_bodega` FOREIGN KEY (`idbodega`) REFERENCES `inventario360_bodega` (`idbodega`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`idcategoria`) REFERENCES `inventario360_categoria` (`idcategoria`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena información de productos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_historial_estadisticas`
--
CREATE TABLE `inventario360_historial_estadisticas` (
  `idhistorial` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) UNSIGNED NOT NULL,
  `cantidad_vendida` int(11) NOT NULL,
  `ingresos` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idhistorial`), -- Corrección: se añadió 'KEY' después de 'PRIMARY'
  KEY `fk_historial_estadisticas_producto_idx` (`producto_id`),
  CONSTRAINT `fk_historial_estadisticas_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario360_producto` (`idproducto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena estadísticas de ventas de productos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_historial_precios`
--
CREATE TABLE `inventario360_historial_precios` (
  `idhistorial` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) UNSIGNED NOT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `precio_actual` decimal(10,2) NOT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idhistorial`),
  KEY `fk_historial_precios_producto_idx` (`producto_id`),
  CONSTRAINT `fk_historial_precios_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario360_producto` (`idproducto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena el historial de precios de productos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_lugar`
--
CREATE TABLE `inventario360_lugar` (
  `idlugar` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `region` varchar(45) DEFAULT NULL,
  `ciudad` varchar(45) DEFAULT NULL,
  `direccion` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idlugar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_producto_lugar`
--
CREATE TABLE `inventario360_producto_lugar` (
  `idproducto_lugar` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `producto_idproducto` int(11) UNSIGNED NOT NULL,
  `lugar_idlugar` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`idproducto_lugar`),
  KEY `fk_producto_lugar_producto1_idx` (`producto_idproducto`),
  KEY `fk_producto_lugar_lugar1_idx` (`lugar_idlugar`),
  CONSTRAINT `fk_producto_lugar_lugar1` FOREIGN KEY (`lugar_idlugar`) REFERENCES `inventario360_lugar` (`idlugar`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_producto_lugar_producto1` FOREIGN KEY (`producto_idproducto`) REFERENCES `inventario360_producto` (`idproducto`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_proveedor`
--
CREATE TABLE `inventario360_proveedor` (
  `idproveedor` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`idproveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que almacena información de proveedores';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_proveedor_producto`
--
CREATE TABLE `inventario360_proveedor_producto` (
  `idproveedor_producto` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `producto_idproducto` int(11) UNSIGNED NOT NULL,
  `proveedor_idproveedor` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`idproveedor_producto`),
  KEY `fk_proveedor_producto_producto1_idx` (`producto_idproducto`),
  KEY `fk_proveedor_producto_proveedor1_idx` (`proveedor_idproveedor`),
  CONSTRAINT `fk_proveedor_producto_producto1` FOREIGN KEY (`producto_idproducto`) REFERENCES `inventario360_producto` (`idproducto`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_proveedor_producto_proveedor1` FOREIGN KEY (`proveedor_idproveedor`) REFERENCES `inventario360_proveedor` (`idproveedor`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_registro_actividad`
--
CREATE TABLE `inventario360_registro_actividad` (
  `idreac` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `descripcion` text,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `producto_idproducto` int(11) UNSIGNED DEFAULT NULL,
  `proveedor_idproveedor` int(11) UNSIGNED DEFAULT NULL,
  `historial_precios_idhistorial` int(11) UNSIGNED DEFAULT NULL,
  `lugar_idlugar` int(11) UNSIGNED DEFAULT NULL,
  `historial_estadisticas_idhistorial` int(11) UNSIGNED DEFAULT NULL,
  `categoria_idcategoria` int(11) UNSIGNED DEFAULT NULL,
  `bodega_idbodega` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`idreac`),
  KEY `fecha_idx` (`fecha`),
  KEY `fk_registro_actividad_usuario1_idx` (`usuario_id`),
  KEY `fk_registro_actividad_producto1_idx` (`producto_idproducto`),
  KEY `fk_registro_actividad_proveedor1_idx` (`proveedor_idproveedor`),
  KEY `fk_registro_actividad_historial_precios1_idx` (`historial_precios_idhistorial`),
  KEY `fk_registro_actividad_lugar1_idx` (`lugar_idlugar`),
  KEY `fk_registro_actividad_historial_estadisticas1_idx` (`historial_estadisticas_idhistorial`),
  KEY `fk_registro_actividad_categoria1_idx` (`categoria_idcategoria`),
  KEY `fk_registro_actividad_bodega1_idx` (`bodega_idbodega`),
  CONSTRAINT `fk_registro_actividad_bodega1` FOREIGN KEY (`bodega_idbodega`) REFERENCES `inventario360_bodega` (`idbodega`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_registro_actividad_categoria1` FOREIGN KEY (`categoria_idcategoria`) REFERENCES `inventario360_categoria` (`idcategoria`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_registro_actividad_historial_estadisticas1` FOREIGN KEY (`historial_estadisticas_idhistorial`) REFERENCES `inventario360_historial_estadisticas` (`idhistorial`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_registro_actividad_historial_precios1` FOREIGN KEY (`historial_precios_idhistorial`) REFERENCES `inventario360_historial_precios` (`idhistorial`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_registro_actividad_lugar1` FOREIGN KEY (`lugar_idlugar`) REFERENCES `inventario360_lugar` (`idlugar`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_registro_actividad_producto1` FOREIGN KEY (`producto_idproducto`) REFERENCES `inventario360_producto` (`idproducto`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_registro_actividad_proveedor1` FOREIGN KEY (`proveedor_idproveedor`) REFERENCES `inventario360_proveedor` (`idproveedor`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_registro_actividad_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `inventario360_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE -- ¡Modificado a ON DELETE CASCADE!
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que registra actividades y cambios en el sistema';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_movimientos`
--
CREATE TABLE `inventario360_movimientos` (
  `idmovimiento` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_movimiento_id` int(11) UNSIGNED NOT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `bodega_origen_id` int(11) UNSIGNED DEFAULT NULL,
  `bodega_destino_id` int(11) UNSIGNED DEFAULT NULL,
  `descripcion` text,
  `documento_referencia` varchar(100) DEFAULT NULL,
  `estado_movimiento` ENUM('abierto', 'cerrado') NOT NULL DEFAULT 'abierto' COMMENT 'Indica si el movimiento está abierto o cerrado',
  PRIMARY KEY (`idmovimiento`),
  KEY `fk_movimientos_usuario_idx` (`usuario_id`),
  KEY `fk_movimientos_bodega_origen_idx` (`bodega_origen_id`),
  KEY `fk_movimientos_bodega_destino_idx` (`bodega_destino_id`),
  KEY `fk_movimientos_tipo_movimiento_idx` (`tipo_movimiento_id`),
  CONSTRAINT `fk_movimientos_bodega_destino` FOREIGN KEY (`bodega_destino_id`) REFERENCES `inventario360_bodega` (`idbodega`),
  CONSTRAINT `fk_movimientos_bodega_origen` FOREIGN KEY (`bodega_origen_id`) REFERENCES `inventario360_bodega` (`idbodega`),
  CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `inventario360_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE, -- ¡Modificado a ON DELETE CASCADE!
  CONSTRAINT `fk_movimientos_tipo_movimiento` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `inventario360_tipo_movimiento` (`idtipo_movimiento`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que registra los movimientos de productos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario360_movimientos_productos`
--
CREATE TABLE `inventario360_movimientos_productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movimiento_id` int(11) NOT NULL,
  `producto_id` int(11) UNSIGNED NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_movimientos_productos_movimiento_idx` (`movimiento_id`),
  KEY `fk_movimientos_productos_producto_idx` (`producto_id`),
  CONSTRAINT `fk_movimientos_productos_movimiento` FOREIGN KEY (`movimiento_id`) REFERENCES `inventario360_movimientos` (`idmovimiento`),
  CONSTRAINT `fk_movimientos_productos_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario360_producto` (`idproducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla que registra los productos involucrados en cada movimiento';

/*!40101 SET CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@@COLLATION_CONNECTION */;

USE `inventario360`;

INSERT IGNORE INTO `inventario360_rol` (`idrol`, `nombre`, `estado`) VALUES
(1, 'Administrador', 'activo');

INSERT INTO `inventario360_usuario` (`nombre`, `correo`, `contrasenia`, `estado`, `rol_id`) VALUES
('Administrador Principal', 'admin@inventario360.com', 'Ad12345', 'activo', 1);

-- Permisos de Usuarios
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_crear_usuario', 'Permiso creacion usuario', 'activo'),
('acceso_actualizar_usuario', 'permiso para editar usuarios', 'activo'),
('acceso_eliminar_usuario', 'permiso eliminar usuario', 'activo'),
('acceso_view_gestor_usuario', 'permiso acceso gestor usuarios', 'activo');

-- Permisos de Roles y Permisos (ya tienes algunos, pero se incluyen para asegurar)
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_crear_permiso', 'permiso para creacion de permisos', 'activo'),
('acceso_editar_permisos', 'acceso para editar permisos', 'activo'),
('acceso_eliminar_permiso', 'acceso para eliminar permiso', 'activo'),
('acceso_crear_rol', 'permiso creacion de roles', 'activo'),
('acceso_editar_rol', 'permiso para la edicion de roles', 'activo'),
('acceso_eliminar_rol', 'acceso para eliminar rol', 'activo'),
('acceso_view_gestor_rol_permisos', 'permiso para gestor de roles y permisos', 'activo');

-- Permisos de Login y Admin
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_login', 'Permiso para iniciar session', 'activo'),
('acceso_admin', 'Permiso para el Acceso al panel de administracion', 'activo');

-- Permisos para Bodega
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_bodega_crear', 'Permiso para crear bodega', 'activo'),
('acceso_bodega_editar', 'Permiso para editar bodega', 'activo'),
('acceso_bodega_eliminar', 'Permiso para eliminar bodega', 'activo'),
('acceso_bodega_listar', 'Permiso para listar bodegas', 'activo'),
('acceso_bodega_reportes', 'Permiso para la carpeta de reportes de bodega', 'activo'),
('acceso_bodega_index', 'Permiso para el index.php dentro de reportes de bodega', 'activo');

-- Permisos para Categoria
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_categoria', 'Permiso acceso general a categorias', 'activo'),
('acceso_op_crear_categoria', 'Permiso para crear categorias', 'activo'),
('acceso_op_editar_categoria', 'Permiso para editar categorias', 'activo'),
('acceso_op_eliminar_categoria', 'Permiso para eliminar categorias', 'activo');

-- Permisos para Estadisticas
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_estadisticas', 'Permiso acceso general a estadisticas', 'activo');

-- Permisos para Movimientos
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_add_movement_products', 'Permiso para añadir productos a movimientos', 'activo'),
('acceso_create_movement', 'Permiso para crear movimientos', 'activo'),
('acceso_delete_movement_product', 'Permiso para eliminar producto de un movimiento', 'activo'),
('acceso_drop_movement', 'Permiso para eliminar movimientos', 'activo'),
('acceso_get_movement_products', 'Permiso para obtener productos de movimientos', 'activo'),
('acceso_search_products_movimientos', 'Permiso para buscar productos en movimientos', 'activo'),
('acceso_update_movement', 'Permiso para actualizar movimientos', 'activo'),
('acceso_view_gestor_movimientos', 'Permiso para gestor de movimientos', 'activo');

-- Permisos para Producto
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_consultar_producto', 'Permiso para consultar productos', 'activo'),
('acceso_crear_multiples_productos', 'Permiso para crear multiples productos', 'activo'),
('acceso_crear_producto', 'Permiso para crear un producto', 'activo'),
('acceso_modificar_producto', 'Permiso para modificar un producto', 'activo'),
('acceso_obtener_producto', 'Permiso para obtener un producto', 'activo'),
('acceso_view_gestor_productos', 'Permiso para gestor de productos', 'activo');

-- Permisos para Proveedores
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_crear_proveedor', 'Permiso para crear proveedor', 'activo'),
('acceso_editar_proveedor', 'Permiso para editar proveedor', 'activo'),
('acceso_eliminar_proveedor', 'Permiso para eliminar proveedor', 'activo');

-- Permisos para Registro (Exportar/Imprimir)
INSERT IGNORE INTO `inventario360_permiso` (`nombre`, `descripcion`, `estado`) VALUES
('acceso_exportar_excel', 'Permiso para exportar a Excel', 'activo'),
('acceso_imprimir_pdf_html', 'Permiso para imprimir PDF/HTML', 'activo');


INSERT IGNORE INTO `inventario360_rol_permiso` (`rol_id`, `permiso_id`)
SELECT 1, idpermiso FROM `inventario360_permiso` WHERE `estado` = 'activo'
