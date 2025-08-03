<?php

$name_corp = "Inventario 360";
$copy = "Inventario 360 V2.0 © 2025 - Todos los derechos reservados";
$logo = "/assets/img/logo.png";

// Sistema desarrollado para gestión integral de inventarios
// diseñada por Digitalgrit.online";

// Variables de permisos 

$admin = "acceso_admin"; // Permiso para el Acceso al panel de administracion
$op_validar = "acceso_login"; // Permiso para iniciar session

// Variables de permisos para 'bodega'
$bodega_crear = "acceso_bodega_crear";  // falta creacion 
$bodega_editar = "acceso_bodega_editar";
$bodega_eliminar = "acceso_bodega_eliminar";
$bodega_listar = "acceso_bodega_listar";
$bodega_reportes = "acceso_bodega_reportes"; // Para la carpeta de reportes
$bodega_index = "acceso_bodega_index"; // Para el index.php dentro de reportes

// Variables de permisos para 'categoria'
$categoria_index = "acceso_categoria";
$op_crear_categoria = "acceso_op_crear_categoria";
$op_editar_categoria = "acceso_op_editar_categoria";
$op_eliminar_categoria = "acceso_op_eliminar_categoria";

// Variables de permisos para 'estadisticas'
$estadisticas_index = "acceso_estadisticas";

// Permisos para Tipos de Movimiento
$op_view_gestor_tipo_movimientos = 'acceso_view_gestor_tipo_movimientos';
$op_crear_tipo_movimiento = 'acceso_crear_tipo_movimiento';
$op_modificar_tipo_movimiento = 'acceso_modificar_tipo_movimiento';
$op_eliminar_tipo_movimiento = 'acceso_eliminar_tipo_movimiento';

// Variables de permisos para 'movimientos'
$op_add_movement_products = "acceso_add_movement_products";
$op_create_movement = "acceso_create_movement";
$op_delete_movement_product = "acceso_delete_movement_product";
$op_drop_movement = "acceso_drop_movement";
$op_get_movement_products = "acceso_get_movement_products";
$op_search_products_movimientos = "acceso_search_products_movimientos"; // Para diferenciar si hay otro search_products
$op_update_movement = "acceso_update_movement";
$op_view_gestor_movimientos = "acceso_view_gestor_movimientos"; // permiso acceso gestor movimientos

// Variables de permisos para 'producto'
$op_consultar_producto = "acceso_consultar_producto";
$op_crear_multiples_productos = "acceso_crear_multiples_productos";
$op_crear_producto = "acceso_crear_producto";
$op_modificar_producto = "acceso_modificar_producto";
$op_obtener_producto = "acceso_obtener_producto";
$op_view_gestor_productos = "acceso_view_gestor_productos"; // permiso acceso gestor productos

// Variables de permisos para 'proveedores'
$op_crear_proveedor = "acceso_crear_proveedor";
$op_editar_proveedor = "acceso_editar_proveedor";
$op_eliminar_proveedor = "acceso_eliminar_proveedor";

// Variables de permisos para 'registro'
$op_exportar_excel = "acceso_exportar_excel";
$op_imprimir_pdf_html = "acceso_imprimir_pdf_html";

// Variables de permisos para 'permisos'
$op_crear_permiso = "acceso_crear_permiso"; // permiso creacion de permisos
$op_editar_permisos = "acceso_editar_permisos"; // acceso para editar permisos
$op_eliminar_permiso = "acceso_eliminar_permiso"; // acceso eliminar permiso

// Variables de permisos para 'rol'
$op_crear_rol = "acceso_crear_rol"; // permiso creacion de roles (asumiendo este nombre para el archivo)
$op_editar_rol = "acceso_editar_rol"; // permiso para la edicion de roles
$op_eliminar_rol = "acceso_eliminar_rol"; // acceso eliminar rol
$op_view_gestor_rol_permisos = "acceso_view_gestor_rol_permisos"; // permiso para gestor de roles y permisos

// Variables de permisos para 'usuarios'
$op_create_user = "acceso_crear_usuario"; // permiso creacion usuario
$op_update_user = "acceso_actualizar_usuario"; // permiso para editar usuarios
$op_eliminar_usuario = "acceso_eliminar_usuario"; // acceso eliminar usuario
$op_view_gestor_usuario = "acceso_view_gestor_usuario"; // permiso acceso gestor usuarios

?>