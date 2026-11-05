# Puesta en marcha de inventario, historial y reportes

## 1. Preparar la base de datos existente

1. Haga un respaldo de `vitasalud_db`.
2. Las definiciones de `inventario` y `ordenes_aplicacion` están al final de [vitasalud_db.sql](../vitasalud_db.sql); no se usa un segundo archivo SQL ni se crea otra base.
3. Como `vitasalud_db` ya está creada, no vuelva a importar el volcado completo sobre datos existentes: contiene tablas y registros iniciales. En phpMyAdmin seleccione `vitasalud_db` y ejecute los bloques de inventario y farmacia del final del archivo, incluyendo el `ALTER TABLE inventario` de compatibilidad para instalaciones que ya tenían la tabla antigua (`id`, `nombre`, `stock`, `unidad`).
4. Las recetas existentes continúan almacenándose en `recetas`; cada orden para aplicar en clínica se vincula al historial de la consulta.
5. Cree y mantenga los productos desde **Inventario y farmacia**. El campo «Stock actual» permite registrar la existencia inicial y los ajustes posteriores.

## 2. Flujo de atención y despacho

1. Enfermería inicia sesión y abre **Inventario y farmacia** para dar de alta medicamentos, sueros e insumos con su unidad y nivel mínimo.
2. El médico atiende una cita de su cola, registra motivo, diagnóstico y tratamiento y, si aplica, selecciona productos y cantidades.
3. Al guardar, la consulta se registra en el historial y cada producto clínico queda pendiente; todavía no se descuenta stock.
4. Enfermería abre el módulo de inventario y pulsa **Despachar / aplicar**. La orden y el producto se bloquean dentro de una transacción, se valida la existencia y se descuenta una sola vez.
5. Si el stock no alcanza, el sistema conserva la orden pendiente y no permite un saldo negativo. Los pedidos pendientes se consideran al validar nuevas indicaciones.

## 3. Historial clínico y documentos

- En la pantalla del médico, **Historial completo** presenta diagnósticos, tratamiento, signos vitales de cada consulta, recetas y órdenes de aplicación.
- El rol Administrador dispone de expedientes recientes y acceso a sus historiales desde **Reportes administrativos**.
- Al finalizar una consulta se abre su resumen listo para imprimir o guardar como PDF. También se puede imprimir desde cada registro histórico.

## 4. Acciones HTTP disponibles

- `inventario`, `guardar_producto`, `despachar_producto`
- `historial&paciente_id=<id>`
- `imprimir_consulta&cita_id=<id>`
- `reportes` (solo Administrador)

Las operaciones que modifican inventario o finalizan una consulta usan token CSRF. Las vistas escapan los datos dinámicos antes de mostrarlos.

## Recuperación de administrador y contraseñas

1. En una base ya instalada, ejecute también desde el final de [vitasalud_db.sql](../vitasalud_db.sql) el bloque que crea `configuracion_sistema` y su fila `administrador_inicial_creado`.
2. Abra la aplicación desde este equipo mediante `http://localhost/vitasalud/public/`. En el login aparecerá **Crear administrador de recuperación (uso único)** mientras la función no se haya usado.
3. Cree una cuenta nueva con contraseña de al menos 12 caracteres. La operación se guarda con hash seguro y el indicador se cierra en la misma transacción; no puede repetirse desde la aplicación.
4. Inicie sesión con esa cuenta y abra **Usuarios y contraseñas** para establecer contraseñas nuevas para cualquiera de las cuentas. No se muestran ni se recuperan las contraseñas anteriores.
5. El login valida contraseñas con `password_verify`; no hay contraseña universal temporal.

La creación inicial se restringe a conexiones `localhost`/`127.0.0.1` y exige CSRF. No habilite la recuperación exponiéndola directamente a la red pública.

## 5. Comprobación manual

1. Inicie sesión como Enfermería; cree un producto con stock 2 y mínimo 2.
2. Inicie sesión como médico, atienda una cita asignada y ordene 1 unidad del producto.
3. Compruebe que el panel de inventario muestra la orden pendiente y que el stock permanece en 2.
4. Despache la orden: el stock debe quedar en 1. Intente repetir el despacho; no debe descontarse otra unidad.
5. Revise el historial clínico y use la vista de impresión. En el rol Administrador, confirme el conteo diario, el total por médico y la alerta de stock bajo.
