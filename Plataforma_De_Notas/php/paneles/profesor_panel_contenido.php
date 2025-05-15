<?php
// php/paneles/profesor_panel_contenido.php
// (Inicio del archivo igual que antes: protecciones, variables de sesión, etc.)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol_id']) || $_SESSION['user_rol_id'] != 2) {
    echo "<p>Acceso restringido. Este panel es solo para profesores.</p>";
    return;
}
$id_profesor_actual = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? 'inicio_profesor';
$id_grupo_seleccionado = isset($_REQUEST['id_grupo']) ? (int)$_REQUEST['id_grupo'] : null;
$id_actividad_seleccionada = isset($_REQUEST['id_actividad']) ? (int)$_REQUEST['id_actividad'] : null;
$fecha_asistencia_seleccionada = isset($_REQUEST['fecha_asistencia']) ? trim($_REQUEST['fecha_asistencia']) : date('Y-m-d');
$tipo_destinatario_informe = isset($_REQUEST['tipo_destinatario_informe']) ? $_REQUEST['tipo_destinatario_informe'] : '';
$id_estudiante_destinatario_informe = isset($_REQUEST['id_estudiante_destinatario']) ? (int)$_REQUEST['id_estudiante_destinatario'] : null;
$tipo_destinatario_mensaje = isset($_REQUEST['tipo_destinatario_mensaje']) ? $_REQUEST['tipo_destinatario_mensaje'] : ''; // 'estudiante' o 'grupo'
$id_estudiante_destinatario_mensaje = isset($_REQUEST['id_estudiante_mensaje']) ? (int)$_REQUEST['id_estudiante_mensaje'] : null;


// ----------------------------------------------------------------------------
// PARTE 1: PROCESAMIENTO DE ACCIONES (Formularios que guardan y redirigen)
// ----------------------------------------------------------------------------
if ($action == 'guardar_nueva_tarea') {
    // ... (Código existente de guardar_nueva_tarea - SIN CAMBIOS) ...
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_tarea'])) {
        $id_grupo_fk = (int)$_POST['id_grupo_fk']; $titulo_actividad = trim($_POST['titulo_actividad']); $descripcion_actividad = trim($_POST['descripcion_actividad']); $tipo_actividad = trim($_POST['tipo_actividad']); $fecha_entrega_limite_str = trim($_POST['fecha_entrega_limite']); $ponderacion_str = trim($_POST['ponderacion']); $max_calificacion_str = trim($_POST['max_calificacion']); $errors = [];
        if (empty($id_grupo_fk)) $errors[] = "ID de grupo no especificado."; if (empty($titulo_actividad)) $errors[] = "El título es obligatorio."; if (empty($tipo_actividad)) $errors[] = "El tipo de actividad es obligatorio."; $tipos_permitidos = ['Tarea', 'Proyecto', 'Laboratorio']; if (!in_array($tipo_actividad, $tipos_permitidos)) $errors[] = "Tipo de actividad no válido para Tarea/Trabajo."; if (empty($max_calificacion_str) || !is_numeric($max_calificacion_str) || $max_calificacion_str <= 0) { $errors[] = "La calificación máxima debe ser un número positivo."; } $max_calificacion = (float)$max_calificacion_str; $fecha_entrega_limite_db = NULL; if (!empty($fecha_entrega_limite_str)) { try { $date_obj = new DateTime($fecha_entrega_limite_str); $fecha_entrega_limite_db = $date_obj->format('Y-m-d H:i:s'); } catch (Exception $e) { $errors[] = "Formato de fecha límite inválido."; } } $ponderacion_db = NULL; if (!empty($ponderacion_str)) { if (!is_numeric($ponderacion_str) || $ponderacion_str < 0 || $ponderacion_str > 1) { $errors[] = "La ponderación debe ser un número entre 0 y 1 (ej: 0.20)."; } else { $ponderacion_db = (float)$ponderacion_str; } }
        if (empty($errors)) { $sql_check_group_owner = "SELECT id_grupo FROM GRUPOS_CURSO WHERE id_grupo = ? AND id_profesor_fk = ?"; $stmt_check_owner = mysqli_prepare($conn, $sql_check_group_owner); if ($stmt_check_owner) { mysqli_stmt_bind_param($stmt_check_owner, "ii", $id_grupo_fk, $id_profesor_actual); mysqli_stmt_execute($stmt_check_owner); $result_check_owner = mysqli_stmt_get_result($stmt_check_owner); if (mysqli_num_rows($result_check_owner) == 0) { $errors[] = "No tienes permiso para asignar tareas a este grupo."; } mysqli_stmt_close($stmt_check_owner); } else { $errors[] = "Error de seguridad al verificar el grupo."; error_log("Error al preparar stmt_check_owner en guardar_nueva_tarea: " . mysqli_error($conn)); } }
        if (!empty($errors)) { $_SESSION['message'] = implode("<br>", $errors); $_SESSION['message_type'] = 'error'; header("Location: dashboard.php?action=asignar_tareas_trabajos&id_grupo=" . $id_grupo_fk); exit; }
        $sql_insert_actividad = "INSERT INTO ACTIVIDADES_EVALUABLES (id_grupo_fk, titulo_actividad, descripcion_actividad, tipo_actividad, fecha_publicacion, fecha_entrega_limite, ponderacion, max_calificacion) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)"; $stmt_insert = mysqli_prepare($conn, $sql_insert_actividad);
        if ($stmt_insert) { mysqli_stmt_bind_param($stmt_insert, "issssdd", $id_grupo_fk, $titulo_actividad, $descripcion_actividad, $tipo_actividad, $fecha_entrega_limite_db, $ponderacion_db, $max_calificacion ); if (mysqli_stmt_execute($stmt_insert)) { $_SESSION['message'] = "Tarea/Trabajo '" . htmlspecialchars($titulo_actividad) . "' asignada exitosamente."; $_SESSION['message_type'] = 'success'; } else { $_SESSION['message'] = "Error al guardar la tarea: " . mysqli_stmt_error($stmt_insert); $_SESSION['message_type'] = 'error'; error_log("Error al ejecutar insert actividad (tarea): " . mysqli_stmt_error($stmt_insert)); } mysqli_stmt_close($stmt_insert);
        } else { $_SESSION['message'] = "Error al preparar la consulta para guardar la tarea: " . mysqli_error($conn); $_SESSION['message_type'] = 'error'; error_log("Error al preparar insert actividad (tarea): " . mysqli_error($conn)); }
        header("Location: dashboard.php?action=asignar_tareas_trabajos&id_grupo=" . $id_grupo_fk); exit;
    } else { $_SESSION['message'] = "Acceso inválido para guardar tarea."; $_SESSION['message_type'] = 'error'; header("Location: dashboard.php?action=asignar_tareas_trabajos"); exit; }
} elseif ($action == 'guardar_nueva_evaluacion') {
    // ... (Código existente de guardar_nueva_evaluacion - SIN CAMBIOS) ...
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_evaluacion'])) {
        $id_grupo_fk = (int)$_POST['id_grupo_fk']; $titulo_actividad = trim($_POST['titulo_actividad']); $descripcion_actividad = trim($_POST['descripcion_actividad']); $tipo_actividad = trim($_POST['tipo_actividad']); $fecha_evaluacion_str = trim($_POST['fecha_evaluacion']); $ponderacion_str = trim($_POST['ponderacion']); $max_calificacion_str = trim($_POST['max_calificacion']); $errors = [];
        if (empty($id_grupo_fk)) $errors[] = "ID de grupo no especificado."; if (empty($titulo_actividad)) $errors[] = "El título de la evaluación es obligatorio."; if (empty($tipo_actividad)) $errors[] = "El tipo de evaluación es obligatorio."; $tipos_eval_permitidos = ['Examen Parcial', 'Examen Final', 'Quiz']; if (!in_array($tipo_actividad, $tipos_eval_permitidos)) $errors[] = "Tipo de evaluación no válido."; if (empty($max_calificacion_str) || !is_numeric($max_calificacion_str) || $max_calificacion_str <= 0) { $errors[] = "La calificación máxima debe ser un número positivo."; } $max_calificacion = (float)$max_calificacion_str; $fecha_evaluacion_db = NULL; if (!empty($fecha_evaluacion_str)) { try { $date_obj = new DateTime($fecha_evaluacion_str); $fecha_evaluacion_db = $date_obj->format('Y-m-d H:i:s'); } catch (Exception $e) { $errors[] = "Formato de fecha de evaluación inválido."; } } $ponderacion_db = NULL; if (!empty($ponderacion_str)) { if (!is_numeric($ponderacion_str) || $ponderacion_str < 0 || $ponderacion_str > 1) { $errors[] = "La ponderación debe ser un número entre 0 y 1."; } else { $ponderacion_db = (float)$ponderacion_str; } }
        if (empty($errors)) { $sql_check_group_owner = "SELECT id_grupo FROM GRUPOS_CURSO WHERE id_grupo = ? AND id_profesor_fk = ?"; $stmt_check_owner = mysqli_prepare($conn, $sql_check_group_owner); if ($stmt_check_owner) { mysqli_stmt_bind_param($stmt_check_owner, "ii", $id_grupo_fk, $id_profesor_actual); mysqli_stmt_execute($stmt_check_owner); $result_check_owner = mysqli_stmt_get_result($stmt_check_owner); if (mysqli_num_rows($result_check_owner) == 0) { $errors[] = "No tienes permiso para asignar evaluaciones a este grupo."; } mysqli_stmt_close($stmt_check_owner); } else { $errors[] = "Error de seguridad al verificar el grupo."; error_log("Error al preparar stmt_check_owner en guardar_nueva_evaluacion: " . mysqli_error($conn)); } }
        if (!empty($errors)) { $_SESSION['message'] = implode("<br>", $errors); $_SESSION['message_type'] = 'error'; header("Location: dashboard.php?action=cargar_evaluaciones_profesor&id_grupo=" . $id_grupo_fk); exit; }
        $sql_insert_evaluacion = "INSERT INTO ACTIVIDADES_EVALUABLES (id_grupo_fk, titulo_actividad, descripcion_actividad, tipo_actividad, fecha_publicacion, fecha_entrega_limite, ponderacion, max_calificacion) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)"; $stmt_insert = mysqli_prepare($conn, $sql_insert_evaluacion);
        if ($stmt_insert) { mysqli_stmt_bind_param($stmt_insert, "issssdd", $id_grupo_fk, $titulo_actividad, $descripcion_actividad, $tipo_actividad, $fecha_evaluacion_db, $ponderacion_db, $max_calificacion ); if (mysqli_stmt_execute($stmt_insert)) { $_SESSION['message'] = "Evaluación '" . htmlspecialchars($titulo_actividad) . "' cargada exitosamente."; $_SESSION['message_type'] = 'success'; } else { $_SESSION['message'] = "Error al guardar la evaluación: " . mysqli_stmt_error($stmt_insert); $_SESSION['message_type'] = 'error'; error_log("Error al ejecutar insert actividad (evaluacion): " . mysqli_stmt_error($stmt_insert)); } mysqli_stmt_close($stmt_insert);
        } else { $_SESSION['message'] = "Error al preparar la consulta para guardar la evaluación: " . mysqli_error($conn); $_SESSION['message_type'] = 'error'; error_log("Error al preparar insert actividad (evaluacion): " . mysqli_error($conn)); }
        header("Location: dashboard.php?action=cargar_evaluaciones_profesor&id_grupo=" . $id_grupo_fk); exit;
    } else { $_SESSION['message'] = "Acceso inválido para guardar evaluación."; $_SESSION['message_type'] = 'error'; header("Location: dashboard.php?action=cargar_evaluaciones_profesor"); exit; }
} elseif ($action == 'guardar_asistencia_grupo') {
    // ... (Código existente de guardar_asistencia_grupo - SIN CAMBIOS) ...
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_asistencia'])) {
        $id_grupo_fk_asistencia = isset($_POST['id_grupo_fk']) ? (int)$_POST['id_grupo_fk'] : null; $fecha_asistencia_post = isset($_POST['fecha_asistencia']) ? trim($_POST['fecha_asistencia']) : null; $asistencias_data = $_POST['asistencia'] ?? []; $errors_asistencia = [];
        if (empty($id_grupo_fk_asistencia)) $errors_asistencia[] = "ID de grupo no especificado para la asistencia."; if (empty($fecha_asistencia_post)) { $errors_asistencia[] = "Fecha de asistencia no especificada."; } else { try { $date_obj_asist = new DateTime($fecha_asistencia_post); $fecha_asistencia_db_validada = $date_obj_asist->format('Y-m-d'); } catch (Exception $e) { $errors_asistencia[] = "Formato de fecha de asistencia inválido."; $fecha_asistencia_db_validada = null; } }
        if (empty($errors_asistencia) && $id_grupo_fk_asistencia) { $sql_check_group_owner_asist = "SELECT id_grupo FROM GRUPOS_CURSO WHERE id_grupo = ? AND id_profesor_fk = ?"; $stmt_check_owner_asist = mysqli_prepare($conn, $sql_check_group_owner_asist); if ($stmt_check_owner_asist) { mysqli_stmt_bind_param($stmt_check_owner_asist, "ii", $id_grupo_fk_asistencia, $id_profesor_actual); mysqli_stmt_execute($stmt_check_owner_asist); $result_check_owner_asist = mysqli_stmt_get_result($stmt_check_owner_asist); if (mysqli_num_rows($result_check_owner_asist) == 0) { $errors_asistencia[] = "No tienes permiso para registrar asistencia en este grupo."; } mysqli_stmt_close($stmt_check_owner_asist); } else { $errors_asistencia[] = "Error de seguridad al verificar el grupo para asistencia."; error_log("Error al preparar stmt_check_owner_asist: " . mysqli_error($conn)); } }
        $estados_asistencia_validos = ['Presente', 'Ausente', 'Justificado', 'Tardanza']; $registros_procesados_ok = 0; $registros_con_error = 0;
        if (empty($errors_asistencia) && $fecha_asistencia_db_validada && !empty($asistencias_data)) {
            foreach ($asistencias_data as $id_matricula_str => $data_asistencia) { $id_matricula = (int)$id_matricula_str; $estado_asistencia = trim($data_asistencia['estado']); $observaciones = trim($data_asistencia['observaciones']); if (!in_array($estado_asistencia, $estados_asistencia_validos)) { $registros_con_error++; error_log("Estado de asistencia inválido ('$estado_asistencia') para matrícula ID $id_matricula"); continue; }
                $sql_check_asist = "SELECT id_asistencia FROM ASISTENCIA_CLASE WHERE id_matricula_fk = ? AND fecha_clase = ?"; $stmt_check_asist = mysqli_prepare($conn, $sql_check_asist); mysqli_stmt_bind_param($stmt_check_asist, "is", $id_matricula, $fecha_asistencia_db_validada); mysqli_stmt_execute($stmt_check_asist); $result_check_asist = mysqli_stmt_get_result($stmt_check_asist); $existing_record = mysqli_fetch_assoc($result_check_asist); mysqli_stmt_close($stmt_check_asist);
                if ($existing_record) { $sql_update_asist = "UPDATE ASISTENCIA_CLASE SET estado_asistencia = ?, observaciones = ? WHERE id_asistencia = ?"; $stmt_upsert = mysqli_prepare($conn, $sql_update_asist); mysqli_stmt_bind_param($stmt_upsert, "ssi", $estado_asistencia, $observaciones, $existing_record['id_asistencia']);
                } else { $sql_insert_asist = "INSERT INTO ASISTENCIA_CLASE (id_matricula_fk, fecha_clase, estado_asistencia, observaciones) VALUES (?, ?, ?, ?)"; $stmt_upsert = mysqli_prepare($conn, $sql_insert_asist); mysqli_stmt_bind_param($stmt_upsert, "isss", $id_matricula, $fecha_asistencia_db_validada, $estado_asistencia, $observaciones); }
                if ($stmt_upsert && mysqli_stmt_execute($stmt_upsert)) { $registros_procesados_ok++; } else { $registros_con_error++; error_log("Error al guardar/actualizar asistencia para matrícula ID $id_matricula: " . ($stmt_upsert ? mysqli_stmt_error($stmt_upsert) : mysqli_error($conn))); } if ($stmt_upsert) mysqli_stmt_close($stmt_upsert); }
        } elseif (empty($asistencias_data) && empty($errors_asistencia)) { $errors_asistencia[] = "No se recibieron datos de asistencia para procesar."; }
        if (!empty($errors_asistencia)) { $_SESSION['message'] = implode("<br>", $errors_asistencia); $_SESSION['message_type'] = 'error';
        } else { $success_msg = "Asistencia guardada. Registros procesados exitosamente: $registros_procesados_ok."; if ($registros_con_error > 0) { $success_msg .= " Registros con error: $registros_con_error."; $_SESSION['message_type'] = 'warning'; } else { $_SESSION['message_type'] = 'success'; } $_SESSION['message'] = $success_msg; }
        header("Location: dashboard.php?action=registrar_asistencia&id_grupo=" . $id_grupo_fk_asistencia . "&fecha_asistencia=" . $fecha_asistencia_post); exit;
    } else { $_SESSION['message'] = "Acceso inválido para guardar asistencia."; $_SESSION['message_type'] = 'error'; header("Location: dashboard.php?action=registrar_asistencia"); exit; }
} elseif ($action == 'guardar_notas_actividad') {
    // ... (Código existente de guardar_notas_actividad - SIN CAMBIOS, con la corrección de 'id_nota_actividad') ...
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_notas'])) {
        $id_grupo_param = isset($_POST['id_grupo_fk']) ? (int)$_POST['id_grupo_fk'] : null; $id_actividad_param = isset($_POST['id_actividad_fk']) ? (int)$_POST['id_actividad_fk'] : null; $notas_data = $_POST['notas'] ?? []; $errors_notas = []; $max_calif_actividad_check = null;
        if (empty($id_grupo_param)) $errors_notas[] = "ID de grupo no especificado."; if (empty($id_actividad_param)) $errors_notas[] = "ID de actividad no especificado.";
        if (empty($errors_notas)) { $sql_check_act_group = "SELECT ae.id_actividad, ae.max_calificacion FROM ACTIVIDADES_EVALUABLES ae JOIN GRUPOS_CURSO gc ON ae.id_grupo_fk = gc.id_grupo WHERE ae.id_actividad = ? AND gc.id_grupo = ? AND gc.id_profesor_fk = ?"; $stmt_check_act_group = mysqli_prepare($conn, $sql_check_act_group); if ($stmt_check_act_group) { mysqli_stmt_bind_param($stmt_check_act_group, "iii", $id_actividad_param, $id_grupo_param, $id_profesor_actual); mysqli_stmt_execute($stmt_check_act_group); $result_act_group = mysqli_stmt_get_result($stmt_check_act_group); if ($act_data_check = mysqli_fetch_assoc($result_act_group)) { $max_calif_actividad_check = (float)$act_data_check['max_calificacion']; } else { $errors_notas[] = "Actividad no válida o no tienes permiso para este grupo/actividad."; } mysqli_stmt_close($stmt_check_act_group); } else { $errors_notas[] = "Error de seguridad al verificar actividad/grupo."; error_log("Error al preparar stmt_check_act_group: " . mysqli_error($conn)); } }
        $registros_notas_ok = 0; $registros_notas_error = 0;
        if (empty($errors_notas) && $max_calif_actividad_check !== null && !empty($notas_data)) {
            foreach ($notas_data as $id_matricula_str => $data_nota) { $id_matricula = (int)$id_matricula_str; $calificacion_str = trim($data_nota['calificacion']); $observaciones_profesor = trim($data_nota['observaciones']); $calificacion_db = null; 
                if ($calificacion_str !== '') { if (!is_numeric($calificacion_str)) { $errors_notas[] = "Calificación para matrícula ID $id_matricula debe ser numérica."; $registros_notas_error++; continue; } $calificacion_db = (float)$calificacion_str; if ($calificacion_db < 0 || $calificacion_db > $max_calif_actividad_check) { $errors_notas[] = "Calificación para matrícula ID $id_matricula fuera de rango (0 - $max_calif_actividad_check)."; $registros_notas_error++; continue; } }
                $sql_check_nota = "SELECT id_nota_actividad FROM NOTAS_ESTUDIANTE_ACTIVIDAD WHERE id_matricula_fk = ? AND id_actividad_fk = ?"; $stmt_check_nota = mysqli_prepare($conn, $sql_check_nota); mysqli_stmt_bind_param($stmt_check_nota, "ii", $id_matricula, $id_actividad_param); mysqli_stmt_execute($stmt_check_nota); $result_check_nota = mysqli_stmt_get_result($stmt_check_nota); $existing_nota = mysqli_fetch_assoc($result_check_nota); mysqli_stmt_close($stmt_check_nota);
                $stmt_upsert_nota = null; 
                if ($existing_nota) { $sql_update_nota = "UPDATE NOTAS_ESTUDIANTE_ACTIVIDAD SET calificacion_obtenida = ?, observaciones_profesor = ?, fecha_calificacion = NOW() WHERE id_nota_actividad = ?"; $stmt_upsert_nota = mysqli_prepare($conn, $sql_update_nota); mysqli_stmt_bind_param($stmt_upsert_nota, "dsi", $calificacion_db, $observaciones_profesor, $existing_nota['id_nota_actividad']);
                } else { if ($calificacion_db !== null || !empty($observaciones_profesor)) { $sql_insert_nota = "INSERT INTO NOTAS_ESTUDIANTE_ACTIVIDAD (id_matricula_fk, id_actividad_fk, calificacion_obtenida, observaciones_profesor, fecha_calificacion) VALUES (?, ?, ?, ?, NOW())"; $stmt_upsert_nota = mysqli_prepare($conn, $sql_insert_nota); mysqli_stmt_bind_param($stmt_upsert_nota, "iids", $id_matricula, $id_actividad_param, $calificacion_db, $observaciones_profesor); } else { $registros_notas_ok++; continue; } }
                if ($stmt_upsert_nota && mysqli_stmt_execute($stmt_upsert_nota)) { $registros_notas_ok++; } else { $registros_notas_error++; $db_error = $stmt_upsert_nota ? mysqli_stmt_error($stmt_upsert_nota) : mysqli_error($conn); error_log("Error al guardar/actualizar nota para matrícula ID $id_matricula: " . $db_error); } if ($stmt_upsert_nota) mysqli_stmt_close($stmt_upsert_nota); }
        } elseif (empty($notas_data) && empty($errors_notas)) { $errors_notas[] = "No se recibieron datos de notas para procesar."; }
        if (!empty($errors_notas)) { $_SESSION['message'] = implode("<br>", $errors_notas); $_SESSION['message_type'] = 'error';
        } else { $success_msg_notas = "Notas guardadas. Registros procesados: $registros_notas_ok."; if ($registros_notas_error > 0) { $success_msg_notas .= " Algunos registros individuales tuvieron error al guardarse (verifique los datos y el log)."; $_SESSION['message_type'] = 'warning'; } else { $_SESSION['message_type'] = 'success'; } $_SESSION['message'] = $success_msg_notas; }
        $redirect_id_grupo = $id_grupo_param ?? ''; $redirect_id_actividad = $id_actividad_param ?? '';
        header("Location: dashboard.php?action=cargar_modificar_notas&id_grupo=" . $redirect_id_grupo . "&id_actividad=" . $redirect_id_actividad); exit;
    } else { $_SESSION['message'] = "Acceso inválido para guardar notas."; $_SESSION['message_type'] = 'error'; header("Location: dashboard.php?action=cargar_modificar_notas"); exit; }
} elseif ($action == 'guardar_nuevo_informe') { // NUEVA ACCIÓN PARA GUARDAR INFORME
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_informe'])) {
        $id_grupo_fk_informe = isset($_POST['id_grupo_fk']) ? (int)$_POST['id_grupo_fk'] : null;
        // Si 'id_estudiante_destinatario_fk' no está en POST (ej. informe para grupo), será NULL.
        $id_estudiante_dest_fk_informe = isset($_POST['id_estudiante_destinatario_fk']) && !empty($_POST['id_estudiante_destinatario_fk']) ? (int)$_POST['id_estudiante_destinatario_fk'] : null;
        $titulo_informe = trim($_POST['titulo_informe'] ?? '');
        $tipo_informe = trim($_POST['tipo_informe'] ?? '');
        $contenido_informe = trim($_POST['contenido_informe'] ?? '');
        // id_profesor_autor_fk es $id_profesor_actual
        
        $errors_informe = [];

        if (empty($id_grupo_fk_informe)) $errors_informe[] = "Debe seleccionar un grupo para el informe.";
        if (empty($titulo_informe)) $errors_informe[] = "El título del informe es obligatorio.";
        if (empty($tipo_informe)) $errors_informe[] = "El tipo de informe es obligatorio.";
        $tipos_informe_validos = ['Académico', 'Comportamental', 'Disciplinario', 'General', 'Otro'];
        if (!in_array($tipo_informe, $tipos_informe_validos)) $errors_informe[] = "Tipo de informe no válido.";
        if (empty($contenido_informe)) $errors_informe[] = "El contenido del informe es obligatorio.";

        // Verificar propiedad del grupo
        if (empty($errors_informe)) {
            $sql_check_group_owner_inf = "SELECT id_grupo FROM GRUPOS_CURSO WHERE id_grupo = ? AND id_profesor_fk = ?";
            $stmt_check_owner_inf = mysqli_prepare($conn, $sql_check_group_owner_inf);
            if ($stmt_check_owner_inf) {
                mysqli_stmt_bind_param($stmt_check_owner_inf, "ii", $id_grupo_fk_informe, $id_profesor_actual);
                mysqli_stmt_execute($stmt_check_owner_inf);
                $result_check_owner_inf = mysqli_stmt_get_result($stmt_check_owner_inf);
                if (mysqli_num_rows($result_check_owner_inf) == 0) {
                    $errors_informe[] = "No tienes permiso para cargar informes a este grupo.";
                }
                mysqli_stmt_close($stmt_check_owner_inf);
            } else {
                $errors_informe[] = "Error de seguridad al verificar el grupo para el informe.";
                error_log("Error al preparar stmt_check_owner_inf: " . mysqli_error($conn));
            }
        }
        
        // (Opcional) Si es informe para estudiante, verificar que el estudiante pertenezca al grupo.
        // Por ahora, asumimos que la selección del UI es correcta.

        if (!empty($errors_informe)) {
            $_SESSION['message'] = implode("<br>", $errors_informe);
            $_SESSION['message_type'] = 'error';
            // Construir URL de redirección manteniendo selecciones previas
            $redirect_url_err_inf = "dashboard.php?action=cargar_informes_profesor&id_grupo=" . $id_grupo_fk_informe;
            if ($id_estudiante_dest_fk_informe) {
                 // Necesitamos saber si era tipo_destinatario=estudiante
                 // Asumimos que si id_estudiante_dest_fk_informe está, el tipo era estudiante
                $redirect_url_err_inf .= "&tipo_destinatario_informe=estudiante&id_estudiante_destinatario=" . $id_estudiante_dest_fk_informe;
            } elseif(isset($_POST['id_estudiante_destinatario_fk']) && empty($_POST['id_estudiante_destinatario_fk'])) { // Es decir, se seleccionó "Todo el grupo"
                 $redirect_url_err_inf .= "&tipo_destinatario_informe=grupo";
            }
            header("Location: " . $redirect_url_err_inf);
            exit;
        }
        
        $sql_insert_informe = "INSERT INTO INFORMES_ACADEMICOS 
                               (id_grupo_fk, id_profesor_autor_fk, id_estudiante_destinatario_fk, titulo_informe, contenido_informe, tipo_informe, fecha_creacion) 
                               VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt_insert_inf = mysqli_prepare($conn, $sql_insert_informe);
        if ($stmt_insert_inf) {
            mysqli_stmt_bind_param($stmt_insert_inf, "iiisss",
                $id_grupo_fk_informe,
                $id_profesor_actual, // id_profesor_autor_fk
                $id_estudiante_dest_fk_informe, // Puede ser NULL si es para grupo
                $titulo_informe,
                $contenido_informe,
                $tipo_informe
            );

            if (mysqli_stmt_execute($stmt_insert_inf)) {
                $_SESSION['message'] = "Informe '" . htmlspecialchars($titulo_informe) . "' guardado exitosamente.";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error al guardar el informe: " . mysqli_stmt_error($stmt_insert_inf);
                $_SESSION['message_type'] = 'error';
                error_log("Error al ejecutar insert informe: " . mysqli_stmt_error($stmt_insert_inf));
            }
            mysqli_stmt_close($stmt_insert_inf);
        } else {
            $_SESSION['message'] = "Error al preparar la consulta para guardar el informe: " . mysqli_error($conn);
            $_SESSION['message_type'] = 'error';
            error_log("Error al preparar insert informe: " . mysqli_error($conn));
        }
        // Redirigir manteniendo el contexto si es posible
        $redirect_url_inf = "dashboard.php?action=cargar_informes_profesor&id_grupo=" . $id_grupo_fk_informe;
        if ($id_estudiante_dest_fk_informe) {
            $redirect_url_inf .= "&tipo_destinatario_informe=estudiante&id_estudiante_destinatario=" . $id_estudiante_dest_fk_informe;
        } elseif(isset($_POST['id_estudiante_destinatario_fk']) && empty($_POST['id_estudiante_destinatario_fk'])) {
             $redirect_url_inf .= "&tipo_destinatario_informe=grupo";
        }
        header("Location: " . $redirect_url_inf);
        exit;
    } else {
        $_SESSION['message'] = "Acceso inválido para guardar informe.";
        $_SESSION['message_type'] = 'error';
        header("Location: dashboard.php?action=cargar_informes_profesor");
        exit;
    }
} elseif ($action == 'guardar_nuevo_mensaje') { // NUEVA ACCIÓN PARA GUARDAR MENSAJE
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_mensaje'])) {
        $id_grupo_contexto = isset($_POST['id_grupo_fk_mensaje']) ? (int)$_POST['id_grupo_fk_mensaje'] : null; // Grupo de contexto
        $id_estudiante_dest_msg = isset($_POST['id_estudiante_destinatario_fk_mensaje']) && !empty($_POST['id_estudiante_destinatario_fk_mensaje']) ? (int)$_POST['id_estudiante_destinatario_fk_mensaje'] : null;
        $tipo_dest_procesar = trim($_POST['tipo_destinatario_procesar'] ?? ''); // 'grupo_completo' o 'estudiante_especifico'
        
        $asunto_mensaje = trim($_POST['asunto_mensaje'] ?? '');
        $cuerpo_mensaje = trim($_POST['cuerpo_mensaje'] ?? '');
        $id_remitente_fk_msg = $id_profesor_actual;

        $errors_mensaje = [];
        $redirect_params = "&id_grupo=" . $id_grupo_contexto . "&tipo_destinatario_mensaje=" . $tipo_dest_procesar;
        if($id_estudiante_dest_msg) $redirect_params .= "&id_estudiante_mensaje=" . $id_estudiante_dest_msg;


        if (empty($id_grupo_contexto)) $errors_mensaje[] = "Debe seleccionar un grupo de contexto.";
        if (empty($tipo_dest_procesar)) $errors_mensaje[] = "Debe seleccionar un tipo de destinatario.";
        if ($tipo_dest_procesar == 'estudiante_especifico' && empty($id_estudiante_dest_msg)) {
            $errors_mensaje[] = "Si el mensaje es para un estudiante específico, debe seleccionar uno.";
        }
        if (empty($asunto_mensaje)) $errors_mensaje[] = "El asunto del mensaje es obligatorio.";
        if (empty($cuerpo_mensaje)) $errors_mensaje[] = "El cuerpo del mensaje es obligatorio.";

        // Verificar propiedad del grupo de contexto
        if (empty($errors_mensaje)) {
            $sql_check_group_owner_msg = "SELECT id_grupo FROM GRUPOS_CURSO WHERE id_grupo = ? AND id_profesor_fk = ?";
            $stmt_check_owner_msg = mysqli_prepare($conn, $sql_check_group_owner_msg);
            if ($stmt_check_owner_msg) {
                mysqli_stmt_bind_param($stmt_check_owner_msg, "ii", $id_grupo_contexto, $id_profesor_actual);
                mysqli_stmt_execute($stmt_check_owner_msg);
                $result_check_owner_msg = mysqli_stmt_get_result($stmt_check_owner_msg);
                if (mysqli_num_rows($result_check_owner_msg) == 0) {
                    $errors_mensaje[] = "No tienes permiso para enviar mensajes en este grupo de contexto.";
                }
                mysqli_stmt_close($stmt_check_owner_msg);
            } else {
                $errors_mensaje[] = "Error de seguridad al verificar el grupo para el mensaje.";
                error_log("Error al preparar stmt_check_owner_msg: " . mysqli_error($conn));
            }
        }
        
        // (Opcional) Si es mensaje para estudiante, verificar que pertenezca al grupo de contexto.
        // Esto ya se hace al poblar el dropdown, pero una doble verificación no está de más.

        if (!empty($errors_mensaje)) {
            $_SESSION['message'] = implode("<br>", $errors_mensaje);
            $_SESSION['message_type'] = 'error';
            header("Location: dashboard.php?action=enviar_mensajes_alertas" . $redirect_params);
            exit;
        }
        
        // Determinar destinatarios para la tabla MENSAJES_COMUNICACION
        $id_dest_usuario_db = NULL;
        $id_dest_grupo_db = NULL;

        if ($tipo_dest_procesar == 'estudiante_especifico') {
            $id_dest_usuario_db = $id_estudiante_dest_msg;
            // id_dest_grupo_db permanece NULL o podríamos poner $id_grupo_contexto si la lógica lo requiere.
            // Según la tabla, es mejor NULL si el destinatario principal es el usuario.
        } elseif ($tipo_dest_procesar == 'grupo_completo') {
            $id_dest_grupo_db = $id_grupo_contexto;
            // id_dest_usuario_db permanece NULL
        } else {
            // Tipo de destinatario no válido, ya debería haber sido capturado por la validación.
            $_SESSION['message'] = "Tipo de destinatario no válido para procesar.";
            $_SESSION['message_type'] = 'error';
            header("Location: dashboard.php?action=enviar_mensajes_alertas" . $redirect_params);
            exit;
        }

        $sql_insert_mensaje = "INSERT INTO MENSAJES_COMUNICACION 
                               (id_remitente_fk, id_destinatario_usuario_fk, id_destinatario_grupo_fk, asunto, cuerpo_mensaje, fecha_envio) 
                               VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt_insert_msg = mysqli_prepare($conn, $sql_insert_mensaje);
        if ($stmt_insert_msg) {
            // Los tipos son: i (remitente), i (dest_usr o NULL), i (dest_grp o NULL), s (asunto), s (cuerpo)
            mysqli_stmt_bind_param($stmt_insert_msg, "iiiss",
                $id_remitente_fk_msg,
                $id_dest_usuario_db, // Se pasa el valor de PHP (puede ser null)
                $id_dest_grupo_db,   // Se pasa el valor de PHP (puede ser null)
                $asunto_mensaje,
                $cuerpo_mensaje
            );

            if (mysqli_stmt_execute($stmt_insert_msg)) {
                $id_mensaje_insertado = mysqli_insert_id($conn); // Obtener el ID del mensaje recién insertado
                $_SESSION['message'] = "Mensaje '" . htmlspecialchars($asunto_mensaje) . "' enviado exitosamente.";
                $_SESSION['message_type'] = 'success';

                // Si el mensaje era para un grupo y tenemos la tabla MENSAJES_ESTADOS_DESTINATARIO
                // podríamos insertar un registro por cada estudiante del grupo aquí.
                // Por ahora, lo dejamos así. La tabla de estados es más para mensajes individuales leídos.

            } else {
                $_SESSION['message'] = "Error al enviar el mensaje: " . mysqli_stmt_error($stmt_insert_msg);
                $_SESSION['message_type'] = 'error';
                error_log("Error al ejecutar insert mensaje: " . mysqli_stmt_error($stmt_insert_msg));
            }
            mysqli_stmt_close($stmt_insert_msg);
        } else {
            $_SESSION['message'] = "Error al preparar la consulta para enviar el mensaje: " . mysqli_error($conn);
            $_SESSION['message_type'] = 'error';
            error_log("Error al preparar insert mensaje: " . mysqli_error($conn));
        }
        header("Location: dashboard.php?action=enviar_mensajes_alertas" . $redirect_params);
        exit;
    } else {
        $_SESSION['message'] = "Acceso inválido para enviar mensaje.";
        $_SESSION['message_type'] = 'error';
        header("Location: dashboard.php?action=enviar_mensajes_alertas");
        exit;
    }
}


// Mostrar mensajes de sesión
// ... (código HTML existente: bienvenida, menú <nav>) ...
if (isset($_SESSION['message'])) {
    $message_type = $_SESSION['message_type'] ?? 'info';
    echo '<div class="message ' . htmlspecialchars($message_type) . '" style="margin-bottom: 20px;">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<h1>Módulo Profesor</h1>
<p>Bienvenido/a, Profesor/a <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>.</p>
<nav class="module-menu">
    <ul>
        <li><a href="dashboard.php?action=cargar_modificar_notas">1. Cargar/modificar notas</a></li>
        <li><a href="dashboard.php?action=publicar_notas_definitivas">2. Publicar notas definitivas</a></li>
        <li><a href="dashboard.php?action=enviar_mensajes_alertas">3. Enviar mensajes/alertas</a></li>
        <li><a href="dashboard.php?action=registrar_asistencia">4. Registrar asistencia</a></li>
        <li><a href="dashboard.php?action=cargar_informes_profesor">5. Cargar informes</a></li>
        <li><a href="dashboard.php?action=asignar_tareas_trabajos">6. Asignar tareas/trabajos</a></li>
        <li><a href="dashboard.php?action=cargar_evaluaciones_profesor">7. Cargar evaluaciones</a></li>
    </ul>
</nav>
<hr style="margin: 20px 0;">

<?php
// Manejar la acción específica para mostrar contenido
switch ($action) {
    // ... (cases existentes: asignar_tareas_trabajos, cargar_evaluaciones_profesor, registrar_asistencia, cargar_modificar_notas) ...
    case 'asignar_tareas_trabajos':
        // ... (Código existente) ...
        echo "<h2>Asignar Nuevas Tareas o Trabajos</h2>";
        $sql_grupos_profesor_tareas = "SELECT gc.id_grupo, gc.nombre_grupo, gc.semestre, c.nombre_curso FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_profesor_fk = ? ORDER BY c.nombre_curso, gc.semestre DESC, gc.nombre_grupo";
        $stmt_grupos_tareas = mysqli_prepare($conn, $sql_grupos_profesor_tareas);
        if ($stmt_grupos_tareas) {
            mysqli_stmt_bind_param($stmt_grupos_tareas, "i", $id_profesor_actual); mysqli_stmt_execute($stmt_grupos_tareas); $result_grupos_tareas = mysqli_stmt_get_result($stmt_grupos_tareas);
            if (mysqli_num_rows($result_grupos_tareas) > 0) {
                echo "<form method='get' action='dashboard.php' style='margin-bottom:20px;'>"; echo "<input type='hidden' name='action' value='asignar_tareas_trabajos'>"; echo "<label for='id_grupo_select_tareas'>Seleccione un Grupo:</label>"; echo "<select name='id_grupo' id='id_grupo_select_tareas' onchange='this.form.submit()' style='padding: 8px; margin-left: 10px; border-radius: 4px;'>"; echo "<option value=''>-- Elija un grupo --</option>"; while ($grupo = mysqli_fetch_assoc($result_grupos_tareas)) { $selected = ($id_grupo_seleccionado == $grupo['id_grupo']) ? "selected" : ""; echo "<option value='" . htmlspecialchars($grupo['id_grupo']) . "' $selected>" . htmlspecialchars($grupo['nombre_curso'] . " - " . $grupo['nombre_grupo'] . " (" . $grupo['semestre'] . ")") . "</option>"; } echo "</select>"; echo "</form>";
                if ($id_grupo_seleccionado) {
                    $stmt_nombre_grupo_sel_tareas = mysqli_prepare($conn, "SELECT c.nombre_curso, gc.nombre_grupo, gc.semestre FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_grupo = ? AND gc.id_profesor_fk = ?"); $nombre_grupo_display_tareas = "Grupo no válido o no autorizado"; if($stmt_nombre_grupo_sel_tareas){ mysqli_stmt_bind_param($stmt_nombre_grupo_sel_tareas, "ii", $id_grupo_seleccionado, $id_profesor_actual); mysqli_stmt_execute($stmt_nombre_grupo_sel_tareas); $result_nombre_grupo_sel_tareas = mysqli_stmt_get_result($stmt_nombre_grupo_sel_tareas); if($data_grupo_sel_tareas = mysqli_fetch_assoc($result_nombre_grupo_sel_tareas)){ $nombre_grupo_display_tareas = htmlspecialchars($data_grupo_sel_tareas['nombre_curso'] . " - " . $data_grupo_sel_tareas['nombre_grupo'] . " (" . $data_grupo_sel_tareas['semestre'] . ")"); } mysqli_stmt_close($stmt_nombre_grupo_sel_tareas); }
                    echo "<hr style='margin: 25px 0;'>"; echo "<h3>Tareas/Trabajos Existentes para: " . $nombre_grupo_display_tareas . "</h3>"; $tipos_tareas_display = ['Tarea', 'Proyecto', 'Laboratorio']; $placeholders_display = implode(',', array_fill(0, count($tipos_tareas_display), '?')); $sql_tareas_existentes = "SELECT id_actividad, titulo_actividad, tipo_actividad, fecha_publicacion, fecha_entrega_limite, ponderacion, max_calificacion FROM ACTIVIDADES_EVALUABLES WHERE id_grupo_fk = ? AND tipo_actividad IN ($placeholders_display) ORDER BY fecha_publicacion DESC";
                    $stmt_tareas_existentes = mysqli_prepare($conn, $sql_tareas_existentes); if ($stmt_tareas_existentes) { $bind_types_display = "i" . str_repeat('s', count($tipos_tareas_display)); $bind_params_display = array_merge([$id_grupo_seleccionado], $tipos_tareas_display); $ref_params_display = []; foreach ($bind_params_display as $key => $value) { $ref_params_display[$key] = &$bind_params_display[$key]; } call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_tareas_existentes, $bind_types_display], $ref_params_display)); mysqli_stmt_execute($stmt_tareas_existentes); $result_tareas_existentes = mysqli_stmt_get_result($stmt_tareas_existentes);
                        if (mysqli_num_rows($result_tareas_existentes) > 0) { echo "<table border='1' style='width:100%; border-collapse: collapse; margin-bottom: 30px;'>"; echo "<thead><tr><th>Título</th><th>Tipo</th><th>Publicada</th><th>Límite Entrega</th><th>Ponderación</th><th>Máx. Calif.</th><th>Acciones</th></tr></thead>"; echo "<tbody>"; while($tarea_existente = mysqli_fetch_assoc($result_tareas_existentes)) { echo "<tr>"; echo "<td>" . htmlspecialchars($tarea_existente['titulo_actividad']) . "</td>"; echo "<td>" . htmlspecialchars($tarea_existente['tipo_actividad']) . "</td>"; echo "<td>" . htmlspecialchars(date("d/m/Y H:i", strtotime($tarea_existente['fecha_publicacion']))) . "</td>"; echo "<td>" . ($tarea_existente['fecha_entrega_limite'] ? htmlspecialchars(date("d/m/Y H:i", strtotime($tarea_existente['fecha_entrega_limite']))) : 'N/A') . "</td>"; echo "<td>" . ($tarea_existente['ponderacion'] ? htmlspecialchars($tarea_existente['ponderacion']*100).'%' : 'N/A') . "</td>"; echo "<td>" . htmlspecialchars($tarea_existente['max_calificacion']) . "</td>"; echo "<td><a href='#' style='font-size:0.9em;'>Editar</a> <a href='#' style='font-size:0.9em; color:red;'>Eliminar</a></td>"; echo "</tr>"; } echo "</tbody></table>";
                        } else { echo "<p>No hay tareas o trabajos asignados para este grupo todavía.</p>"; } mysqli_stmt_close($stmt_tareas_existentes);
                    } else { error_log("Error al preparar consulta de tareas existentes: " . mysqli_error($conn)); echo "<p>Error al cargar tareas existentes.</p>"; }
                    echo "<hr style='margin: 25px 0;'>"; echo "<h3>Crear Nueva Tarea/Trabajo para: " . $nombre_grupo_display_tareas . "</h3>";
                    echo "<form action='dashboard.php?action=guardar_nueva_tarea' method='post' style='max-width: 600px;'>"; echo "<input type='hidden' name='id_grupo_fk' value='" . htmlspecialchars($id_grupo_seleccionado) . "'>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='titulo_actividad_tarea' style='display:block; margin-bottom:5px;'>Título:</label><input type='text' name='titulo_actividad' id='titulo_actividad_tarea' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='descripcion_actividad_tarea' style='display:block; margin-bottom:5px;'>Descripción:</label><textarea name='descripcion_actividad' id='descripcion_actividad_tarea' rows='4' style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></textarea></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='tipo_actividad_tarea' style='display:block; margin-bottom:5px;'>Tipo:</label><select name='tipo_actividad' id='tipo_actividad_tarea' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'><option value='Tarea'>Tarea</option><option value='Proyecto'>Proyecto</option><option value='Laboratorio'>Laboratorio</option></select></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='fecha_entrega_limite_tarea' style='display:block; margin-bottom:5px;'>Fecha Límite (Opcional):</label><input type='datetime-local' name='fecha_entrega_limite' id='fecha_entrega_limite_tarea' style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='ponderacion_tarea' style='display:block; margin-bottom:5px;'>Ponderación (0-1, Opcional):</label><input type='number' name='ponderacion' id='ponderacion_tarea' step='0.01' min='0' max='1' style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='max_calificacion_tarea' style='display:block; margin-bottom:5px;'>Calificación Máxima:</label><input type='number' name='max_calificacion' id='max_calificacion_tarea' step='0.1' value='5.0' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>"; echo "<button type='submit' name='guardar_tarea' style='padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Guardar Tarea/Trabajo</button>"; echo "</form>";
                }
            } else { echo "<p>No tienes grupos asignados actualmente o no se pudieron cargar.</p>"; } mysqli_stmt_close($stmt_grupos_tareas);
        } else { error_log("Error al preparar la consulta de grupos del profesor (tareas): " . mysqli_error($conn)); echo "<p>Error al cargar tus grupos. Por favor, intenta más tarde.</p>";}
        break;
    case 'cargar_evaluaciones_profesor':
        // ... (Código existente) ...
        echo "<h2>Cargar Nuevas Evaluaciones</h2>";
        $sql_grupos_profesor_eval = "SELECT gc.id_grupo, gc.nombre_grupo, gc.semestre, c.nombre_curso FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_profesor_fk = ? ORDER BY c.nombre_curso, gc.semestre DESC, gc.nombre_grupo";
        $stmt_grupos_eval = mysqli_prepare($conn, $sql_grupos_profesor_eval);
        if ($stmt_grupos_eval) {
            mysqli_stmt_bind_param($stmt_grupos_eval, "i", $id_profesor_actual); mysqli_stmt_execute($stmt_grupos_eval); $result_grupos_eval = mysqli_stmt_get_result($stmt_grupos_eval);
            if (mysqli_num_rows($result_grupos_eval) > 0) {
                echo "<form method='get' action='dashboard.php' style='margin-bottom:20px;'>"; echo "<input type='hidden' name='action' value='cargar_evaluaciones_profesor'>"; echo "<label for='id_grupo_select_eval'>Seleccione un Grupo:</label>"; echo "<select name='id_grupo' id='id_grupo_select_eval' onchange='this.form.submit()' style='padding: 8px; margin-left: 10px; border-radius: 4px;'>"; echo "<option value=''>-- Elija un grupo --</option>"; while ($grupo = mysqli_fetch_assoc($result_grupos_eval)) { $selected = ($id_grupo_seleccionado == $grupo['id_grupo']) ? "selected" : ""; echo "<option value='" . htmlspecialchars($grupo['id_grupo']) . "' $selected>" . htmlspecialchars($grupo['nombre_curso'] . " - " . $grupo['nombre_grupo'] . " (" . $grupo['semestre'] . ")") . "</option>"; } echo "</select>"; echo "</form>";
                if ($id_grupo_seleccionado) {
                    $stmt_nombre_grupo_sel_eval = mysqli_prepare($conn, "SELECT c.nombre_curso, gc.nombre_grupo, gc.semestre FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_grupo = ? AND gc.id_profesor_fk = ?"); $nombre_grupo_display_eval = "Grupo no válido o no autorizado"; if($stmt_nombre_grupo_sel_eval){ mysqli_stmt_bind_param($stmt_nombre_grupo_sel_eval, "ii", $id_grupo_seleccionado, $id_profesor_actual); mysqli_stmt_execute($stmt_nombre_grupo_sel_eval); $result_nombre_grupo_sel_eval = mysqli_stmt_get_result($stmt_nombre_grupo_sel_eval); if($data_grupo_sel_eval = mysqli_fetch_assoc($result_nombre_grupo_sel_eval)){ $nombre_grupo_display_eval = htmlspecialchars($data_grupo_sel_eval['nombre_curso'] . " - " . $data_grupo_sel_eval['nombre_grupo'] . " (" . $data_grupo_sel_eval['semestre'] . ")"); } mysqli_stmt_close($stmt_nombre_grupo_sel_eval); }
                    echo "<hr style='margin: 25px 0;'>"; echo "<h3>Evaluaciones Existentes para: " . $nombre_grupo_display_eval . "</h3>"; $tipos_eval_display = ['Examen Parcial', 'Examen Final', 'Quiz']; $placeholders_eval_display = implode(',', array_fill(0, count($tipos_eval_display), '?')); $sql_eval_existentes = "SELECT id_actividad, titulo_actividad, tipo_actividad, fecha_publicacion, fecha_entrega_limite AS fecha_evaluacion, ponderacion, max_calificacion FROM ACTIVIDADES_EVALUABLES WHERE id_grupo_fk = ? AND tipo_actividad IN ($placeholders_eval_display) ORDER BY fecha_publicacion DESC";
                    $stmt_eval_existentes = mysqli_prepare($conn, $sql_eval_existentes); if ($stmt_eval_existentes) { $bind_types_eval_display = "i" . str_repeat('s', count($tipos_eval_display)); $bind_params_eval_display = array_merge([$id_grupo_seleccionado], $tipos_eval_display); $ref_params_eval_display = []; foreach ($bind_params_eval_display as $key => $value) { $ref_params_eval_display[$key] = &$bind_params_eval_display[$key]; } call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_eval_existentes, $bind_types_eval_display], $ref_params_eval_display)); mysqli_stmt_execute($stmt_eval_existentes); $result_eval_existentes = mysqli_stmt_get_result($stmt_eval_existentes);
                        if (mysqli_num_rows($result_eval_existentes) > 0) { echo "<table border='1' style='width:100%; border-collapse: collapse; margin-bottom: 30px;'>"; echo "<thead><tr><th>Título</th><th>Tipo</th><th>Publicada</th><th>Fecha Evaluación</th><th>Ponderación</th><th>Máx. Calif.</th><th>Acciones</th></tr></thead>"; echo "<tbody>"; while($eval_existente = mysqli_fetch_assoc($result_eval_existentes)) { echo "<tr>"; echo "<td>" . htmlspecialchars($eval_existente['titulo_actividad']) . "</td>"; echo "<td>" . htmlspecialchars($eval_existente['tipo_actividad']) . "</td>"; echo "<td>" . htmlspecialchars(date("d/m/Y H:i", strtotime($eval_existente['fecha_publicacion']))) . "</td>"; echo "<td>" . ($eval_existente['fecha_evaluacion'] ? htmlspecialchars(date("d/m/Y H:i", strtotime($eval_existente['fecha_evaluacion']))) : 'N/A') . "</td>"; echo "<td>" . ($eval_existente['ponderacion'] ? htmlspecialchars($eval_existente['ponderacion']*100).'%' : 'N/A') . "</td>"; echo "<td>" . htmlspecialchars($eval_existente['max_calificacion']) . "</td>"; echo "<td><a href='#' style='font-size:0.9em;'>Editar</a> <a href='#' style='font-size:0.9em; color:red;'>Eliminar</a></td>"; echo "</tr>"; } echo "</tbody></table>";
                        } else { echo "<p>No hay evaluaciones asignadas para este grupo todavía.</p>"; } mysqli_stmt_close($stmt_eval_existentes);
                    } else { error_log("Error al preparar consulta de evaluaciones existentes: " . mysqli_error($conn)); echo "<p>Error al cargar evaluaciones existentes.</p>"; }
                    echo "<hr style='margin: 25px 0;'>"; echo "<h3>Crear Nueva Evaluación para: " . $nombre_grupo_display_eval . "</h3>";
                    echo "<form action='dashboard.php?action=guardar_nueva_evaluacion' method='post' style='max-width: 600px;'>"; echo "<input type='hidden' name='id_grupo_fk' value='" . htmlspecialchars($id_grupo_seleccionado) . "'>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='titulo_actividad_eval' style='display:block; margin-bottom:5px;'>Título Evaluación:</label><input type='text' name='titulo_actividad' id='titulo_actividad_eval' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='descripcion_actividad_eval' style='display:block; margin-bottom:5px;'>Descripción:</label><textarea name='descripcion_actividad' id='descripcion_actividad_eval' rows='3' style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></textarea></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='tipo_actividad_eval' style='display:block; margin-bottom:5px;'>Tipo Evaluación:</label><select name='tipo_actividad' id='tipo_actividad_eval' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'><option value='Examen Parcial'>Examen Parcial</option><option value='Examen Final'>Examen Final</option><option value='Quiz'>Quiz</option></select></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='fecha_evaluacion' style='display:block; margin-bottom:5px;'>Fecha y Hora Realización (Opcional):</label><input type='datetime-local' name='fecha_evaluacion' id='fecha_evaluacion' style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='ponderacion_eval' style='display:block; margin-bottom:5px;'>Ponderación (0-1, Opcional):</label><input type='number' name='ponderacion' id='ponderacion_eval' step='0.01' min='0' max='1' style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>"; echo "<div class='form-group' style='margin-bottom: 15px;'><label for='max_calificacion_eval' style='display:block; margin-bottom:5px;'>Calificación Máxima:</label><input type='number' name='max_calificacion' id='max_calificacion_eval' step='0.1' value='5.0' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>"; echo "<button type='submit' name='guardar_evaluacion' style='padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Guardar Evaluación</button>"; echo "</form>";
                }
            } else { echo "<p>No tienes grupos asignados actualmente.</p>"; } mysqli_stmt_close($stmt_grupos_eval);
        } else { error_log("Error al preparar la consulta de grupos del profesor (eval): " . mysqli_error($conn)); echo "<p>Error al cargar tus grupos.</p>"; }
        break;
    case 'registrar_asistencia':
        // ... (Código existente) ...
        echo "<h2>Registrar Asistencia</h2>";
        $sql_grupos_asistencia = "SELECT gc.id_grupo, gc.nombre_grupo, gc.semestre, c.nombre_curso FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_profesor_fk = ? ORDER BY c.nombre_curso, gc.semestre DESC, gc.nombre_grupo";
        $stmt_grupos_asistencia = mysqli_prepare($conn, $sql_grupos_asistencia);
        if($stmt_grupos_asistencia) {
            mysqli_stmt_bind_param($stmt_grupos_asistencia, "i", $id_profesor_actual); mysqli_stmt_execute($stmt_grupos_asistencia); $result_grupos_asistencia = mysqli_stmt_get_result($stmt_grupos_asistencia);
            if (mysqli_num_rows($result_grupos_asistencia) > 0) {
                echo "<form method='get' action='dashboard.php' style='margin-bottom:20px;'>"; echo "<input type='hidden' name='action' value='registrar_asistencia'>"; echo "<label for='id_grupo_asistencia'>Seleccione Grupo:</label> "; echo "<select name='id_grupo' id='id_grupo_asistencia' style='padding: 8px; border-radius: 4px;'>"; echo "<option value=''>-- Elija un grupo --</option>"; while ($grupo = mysqli_fetch_assoc($result_grupos_asistencia)) { $selected = ($id_grupo_seleccionado == $grupo['id_grupo']) ? "selected" : ""; echo "<option value='" . htmlspecialchars($grupo['id_grupo']) . "' $selected>" . htmlspecialchars($grupo['nombre_curso'] . " - " . $grupo['nombre_grupo'] . " (" . $grupo['semestre'] . ")") . "</option>"; } echo "</select>";
                if ($id_grupo_seleccionado) { echo "<label for='fecha_asistencia' style='margin-left: 15px;'>Fecha de Clase:</label> "; echo "<input type='date' name='fecha_asistencia' id='fecha_asistencia' value='" . htmlspecialchars($fecha_asistencia_seleccionada) . "' required style='padding: 8px; border-radius: 4px;'>"; }
                echo "<button type='submit' style='padding: 8px 12px; margin-left: 10px; background-color: #007bff; color:white; border:none; border-radius:4px;'>Cargar Estudiantes</button>"; echo "</form>";
                if ($id_grupo_seleccionado && !empty($fecha_asistencia_seleccionada)) {
                    try { $fecha_obj = new DateTime($fecha_asistencia_seleccionada); $fecha_asistencia_db = $fecha_obj->format('Y-m-d'); } catch (Exception $e) { echo "<p class='message error'>Fecha de asistencia no válida.</p>"; $fecha_asistencia_db = null; }
                    if($fecha_asistencia_db) {
                        $stmt_nombre_grupo_asist = mysqli_prepare($conn, "SELECT c.nombre_curso, gc.nombre_grupo, gc.semestre FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_grupo = ? AND gc.id_profesor_fk = ?"); $nombre_grupo_display_asist = ""; if($stmt_nombre_grupo_asist){ mysqli_stmt_bind_param($stmt_nombre_grupo_asist, "ii", $id_grupo_seleccionado, $id_profesor_actual); mysqli_stmt_execute($stmt_nombre_grupo_asist); $result_nombre_grupo_asist = mysqli_stmt_get_result($stmt_nombre_grupo_asist); if($data_grupo_asist = mysqli_fetch_assoc($result_nombre_grupo_asist)){ $nombre_grupo_display_asist = htmlspecialchars($data_grupo_asist['nombre_curso'] . " - " . $data_grupo_asist['nombre_grupo'] . " (" . $data_grupo_asist['semestre'] . ")"); } mysqli_stmt_close($stmt_nombre_grupo_asist); }
                        echo "<hr style='margin:25px 0;'><h3>Registrar Asistencia para: " . $nombre_grupo_display_asist . "</h3>"; echo "<p><strong>Fecha Seleccionada:</strong> " . htmlspecialchars(date("d/m/Y", strtotime($fecha_asistencia_db))) . "</p>";
                        $sql_estudiantes_grupo = "SELECT u.id_usuario, u.nombres, u.apellidos, me.id_matricula, ac.estado_asistencia, ac.observaciones FROM USUARIOS u JOIN MATRICULAS_ESTUDIANTES me ON u.id_usuario = me.id_estudiante_fk LEFT JOIN ASISTENCIA_CLASE ac ON me.id_matricula = ac.id_matricula_fk AND ac.fecha_clase = ? WHERE me.id_grupo_fk = ? AND u.estado = 'activo' AND me.estado_matricula = 'Activa' ORDER BY u.apellidos, u.nombres";
                        $stmt_estudiantes = mysqli_prepare($conn, $sql_estudiantes_grupo);
                        if ($stmt_estudiantes) {
                            mysqli_stmt_bind_param($stmt_estudiantes, "si", $fecha_asistencia_db, $id_grupo_seleccionado); mysqli_stmt_execute($stmt_estudiantes); $result_estudiantes = mysqli_stmt_get_result($stmt_estudiantes);
                            if (mysqli_num_rows($result_estudiantes) > 0) {
                                echo "<form action='dashboard.php?action=guardar_asistencia_grupo' method='post'>"; echo "<input type='hidden' name='id_grupo_fk' value='" . htmlspecialchars($id_grupo_seleccionado) . "'>"; echo "<input type='hidden' name='fecha_asistencia' value='" . htmlspecialchars($fecha_asistencia_db) . "'>";
                                echo "<table border='1' style='width:100%; border-collapse: collapse; margin-bottom: 20px;'>"; echo "<thead><tr><th>Estudiante</th><th>Estado Asistencia</th><th>Observaciones</th></tr></thead>"; echo "<tbody>"; $estados_asistencia = ['Presente', 'Ausente', 'Justificado', 'Tardanza']; while($estudiante = mysqli_fetch_assoc($result_estudiantes)) { echo "<tr>"; echo "<td>" . htmlspecialchars($estudiante['apellidos'] . ", " . $estudiante['nombres']) . "</td>"; echo "<td><select name='asistencia[" . $estudiante['id_matricula'] . "][estado]' style='padding:5px;'>"; foreach($estados_asistencia as $estado) { $selected_estado = ($estudiante['estado_asistencia'] == $estado) ? "selected" : ""; echo "<option value='" . $estado . "' $selected_estado>" . $estado . "</option>"; } echo "</select></td>"; echo "<td><input type='text' name='asistencia[" . $estudiante['id_matricula'] . "][observaciones]' value='" . htmlspecialchars($estudiante['observaciones'] ?? '') . "' style='width:98%; padding:5px;'></td>"; echo "</tr>"; } echo "</tbody></table>";
                                echo "<button type='submit' name='guardar_asistencia' style='padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Guardar Asistencia</button>"; echo "</form>";
                            } else { echo "<p>No hay estudiantes activos matriculados en este grupo para la fecha seleccionada o no se pudieron cargar.</p>"; } mysqli_stmt_close($stmt_estudiantes);
                        } else { error_log("Error al preparar consulta de estudiantes para asistencia: " . mysqli_error($conn)); echo "<p>Error al cargar la lista de estudiantes.</p>"; }
                    }
                }
            } else { echo "<p>No tienes grupos asignados para registrar asistencia.</p>"; } mysqli_stmt_close($stmt_grupos_asistencia);
        } else { error_log("Error al preparar consulta de grupos para asistencia: " . mysqli_error($conn)); echo "<p>Error al cargar tus grupos.</p>"; }
        break;
    case 'cargar_modificar_notas':
        // ... (Código existente para mostrar el form de cargar_modificar_notas) ...
        echo "<h2>Cargar/Modificar Notas</h2>";
        $sql_grupos_notas = "SELECT gc.id_grupo, gc.nombre_grupo, gc.semestre, c.nombre_curso FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_profesor_fk = ? ORDER BY c.nombre_curso, gc.semestre DESC, gc.nombre_grupo";
        $stmt_grupos_notas = mysqli_prepare($conn, $sql_grupos_notas);
        if($stmt_grupos_notas) {
            mysqli_stmt_bind_param($stmt_grupos_notas, "i", $id_profesor_actual); mysqli_stmt_execute($stmt_grupos_notas); $result_grupos_notas = mysqli_stmt_get_result($stmt_grupos_notas);
            if (mysqli_num_rows($result_grupos_notas) > 0) {
                echo "<form method='get' action='dashboard.php' id='formSeleccionNotas' style='margin-bottom:20px;'>"; echo "<input type='hidden' name='action' value='cargar_modificar_notas'>";
                echo "<div style='margin-bottom:10px;'>"; echo "<label for='id_grupo_notas'>Seleccione Grupo:</label> "; echo "<select name='id_grupo' id='id_grupo_notas' style='padding: 8px; border-radius: 4px;' onchange='var activityDropdown = document.getElementById(\"id_actividad_notas\"); if(activityDropdown){ activityDropdown.value=\"\"; } this.form.submit();'>"; echo "<option value=''>-- Elija un grupo --</option>"; while ($grupo = mysqli_fetch_assoc($result_grupos_notas)) { $selected = ($id_grupo_seleccionado == $grupo['id_grupo']) ? "selected" : ""; echo "<option value='" . htmlspecialchars($grupo['id_grupo']) . "' $selected>" . htmlspecialchars($grupo['nombre_curso'] . " - " . $grupo['nombre_grupo'] . " (" . $grupo['semestre'] . ")") . "</option>"; } echo "</select>"; echo "</div>";
                if ($id_grupo_seleccionado) {
                    echo "<div style='margin-bottom:10px;'>"; echo "<label for='id_actividad_notas'>Seleccione Actividad Evaluable:</label> "; $sql_actividades_grupo = "SELECT id_actividad, titulo_actividad, tipo_actividad FROM ACTIVIDADES_EVALUABLES WHERE id_grupo_fk = ? ORDER BY fecha_publicacion DESC, titulo_actividad"; $stmt_actividades = mysqli_prepare($conn, $sql_actividades_grupo);
                    if ($stmt_actividades) { mysqli_stmt_bind_param($stmt_actividades, "i", $id_grupo_seleccionado); mysqli_stmt_execute($stmt_actividades); $result_actividades = mysqli_stmt_get_result($stmt_actividades); echo "<select name='id_actividad' id='id_actividad_notas' style='padding: 8px; border-radius: 4px;' onchange='this.form.submit();'>"; echo "<option value=''>-- Elija una actividad --</option>"; if (mysqli_num_rows($result_actividades) > 0) { while ($actividad = mysqli_fetch_assoc($result_actividades)) { $selected_act = ($id_actividad_seleccionada == $actividad['id_actividad']) ? "selected" : ""; echo "<option value='" . htmlspecialchars($actividad['id_actividad']) . "' $selected_act>" . htmlspecialchars($actividad['titulo_actividad'] . " (" . $actividad['tipo_actividad'] . ")") . "</option>"; } } else { echo "<option value=''>No hay actividades para este grupo</option>"; } echo "</select>"; mysqli_stmt_close($stmt_actividades);
                    } else { echo "<p>Error al cargar actividades.</p>"; } echo "</div>";
                } echo "</form>";
                if ($id_grupo_seleccionado && $id_actividad_seleccionada) {
                    $actividad_info_sql = "SELECT titulo_actividad, max_calificacion FROM ACTIVIDADES_EVALUABLES WHERE id_actividad = ? AND id_grupo_fk = ?"; $stmt_act_info = mysqli_prepare($conn, $actividad_info_sql); $max_calif_actividad = 5.0; $titulo_act_display = "Actividad no encontrada"; if($stmt_act_info){ mysqli_stmt_bind_param($stmt_act_info, "ii", $id_actividad_seleccionada, $id_grupo_seleccionado); mysqli_stmt_execute($stmt_act_info); $result_act_info = mysqli_stmt_get_result($stmt_act_info); if($act_data = mysqli_fetch_assoc($result_act_info)){ $max_calif_actividad = (float)$act_data['max_calificacion']; $titulo_act_display = htmlspecialchars($act_data['titulo_actividad']); } mysqli_stmt_close($stmt_act_info); }
                    echo "<hr style='margin:25px 0;'><h3>Ingresar/Modificar Notas para: " . $titulo_act_display . "</h3>"; echo "<p><strong>Calificación Máxima Permitida:</strong> " . $max_calif_actividad . "</p>";
                    $sql_estudiantes_notas = "SELECT u.id_usuario, u.nombres, u.apellidos, me.id_matricula, nea.calificacion_obtenida, nea.observaciones_profesor FROM USUARIOS u JOIN MATRICULAS_ESTUDIANTES me ON u.id_usuario = me.id_estudiante_fk LEFT JOIN NOTAS_ESTUDIANTE_ACTIVIDAD nea ON me.id_matricula = nea.id_matricula_fk AND nea.id_actividad_fk = ? WHERE me.id_grupo_fk = ? AND u.estado = 'activo' AND me.estado_matricula = 'Activa' ORDER BY u.apellidos, u.nombres";
                    $stmt_estudiantes_notas = mysqli_prepare($conn, $sql_estudiantes_notas);
                    if ($stmt_estudiantes_notas) { mysqli_stmt_bind_param($stmt_estudiantes_notas, "ii", $id_actividad_seleccionada, $id_grupo_seleccionado); mysqli_stmt_execute($stmt_estudiantes_notas); $result_estudiantes_notas = mysqli_stmt_get_result($stmt_estudiantes_notas);
                        if (mysqli_num_rows($result_estudiantes_notas) > 0) {
                            echo "<form action='dashboard.php?action=guardar_notas_actividad' method='post'>"; echo "<input type='hidden' name='id_grupo_fk' value='" . htmlspecialchars($id_grupo_seleccionado) . "'>"; echo "<input type='hidden' name='id_actividad_fk' value='" . htmlspecialchars($id_actividad_seleccionada) . "'>";
                            echo "<table border='1' style='width:100%; border-collapse: collapse; margin-bottom: 20px;'>"; echo "<thead><tr><th>Estudiante</th><th>Calificación (0 - " . $max_calif_actividad . ")</th><th>Observaciones</th></tr></thead>"; echo "<tbody>"; while($estudiante = mysqli_fetch_assoc($result_estudiantes_notas)) { echo "<tr>"; echo "<td>" . htmlspecialchars($estudiante['apellidos'] . ", " . $estudiante['nombres']) . "</td>"; echo "<td><input type='number' name='notas[" . $estudiante['id_matricula'] . "][calificacion]' value='" . htmlspecialchars($estudiante['calificacion_obtenida'] ?? '') . "' step='0.01' min='0' max='" . $max_calif_actividad . "' style='width:80px; padding:5px;'></td>"; echo "<td><input type='text' name='notas[" . $estudiante['id_matricula'] . "][observaciones]' value='" . htmlspecialchars($estudiante['observaciones_profesor'] ?? '') . "' style='width:98%; padding:5px;'></td>"; echo "</tr>"; } echo "</tbody></table>";
                            echo "<button type='submit' name='guardar_notas' style='padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Guardar Notas</button>"; echo "</form>";
                        } else { echo "<p>No hay estudiantes activos matriculados en este grupo para calificar esta actividad.</p>"; } mysqli_stmt_close($stmt_estudiantes_notas);
                    } else { error_log("Error al preparar consulta de estudiantes para notas: " . mysqli_error($conn)); echo "<p>Error al cargar la lista de estudiantes.</p>"; }
                }
            } else { echo "<p>No tienes grupos asignados.</p>"; } mysqli_stmt_close($stmt_grupos_notas);
        } else { error_log("Error al preparar consulta de grupos para notas: " . mysqli_error($conn)); echo "<p>Error al cargar tus grupos.</p>"; }
        break;
    case 'cargar_informes_profesor':
        // ... (Código existente para mostrar el form de cargar_informes_profesor) ...
        echo "<h2>Cargar Informes Académicos</h2>";
        $sql_grupos_informes = "SELECT gc.id_grupo, gc.nombre_grupo, gc.semestre, c.nombre_curso FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_profesor_fk = ? ORDER BY c.nombre_curso, gc.semestre DESC, gc.nombre_grupo";
        $stmt_grupos_informes = mysqli_prepare($conn, $sql_grupos_informes);
        if($stmt_grupos_informes) {
            mysqli_stmt_bind_param($stmt_grupos_informes, "i", $id_profesor_actual); mysqli_stmt_execute($stmt_grupos_informes); $result_grupos_informes = mysqli_stmt_get_result($stmt_grupos_informes);
            if (mysqli_num_rows($result_grupos_informes) > 0) {
                echo "<form method='get' action='dashboard.php' id='formSeleccionInformes' style='margin-bottom:20px;'>"; echo "<input type='hidden' name='action' value='cargar_informes_profesor'>";
                echo "<div style='margin-bottom:10px;'>"; echo "<label for='id_grupo_informe'>Seleccione Grupo:</label> "; echo "<select name='id_grupo' id='id_grupo_informe' style='padding: 8px; border-radius: 4px;' onchange='var tipoDestinatario = this.form.elements[\"tipo_destinatario_informe\"]; if (tipoDestinatario) { tipoDestinatario.value = \"\"; } var estudianteDestinatario = this.form.elements[\"id_estudiante_destinatario\"]; if (estudianteDestinatario) { estudianteDestinatario.value = \"\"; } this.form.submit();'>"; echo "<option value=''>-- Elija un grupo --</option>"; while ($grupo = mysqli_fetch_assoc($result_grupos_informes)) { $selected_grupo = ($id_grupo_seleccionado == $grupo['id_grupo']) ? "selected" : ""; echo "<option value='" . htmlspecialchars($grupo['id_grupo']) . "' $selected_grupo>" . htmlspecialchars($grupo['nombre_curso'] . " - " . $grupo['nombre_grupo'] . " (" . $grupo['semestre'] . ")") . "</option>"; } echo "</select>"; echo "</div>";
                if ($id_grupo_seleccionado) {
                    echo "<div style='margin-bottom:10px;'>"; echo "<label for='tipo_destinatario_informe'>Informe Para:</label> "; echo "<select name='tipo_destinatario_informe' id='tipo_destinatario_informe' style='padding: 8px; border-radius: 4px;' onchange='var estudianteSelect = this.form.elements[\"id_estudiante_destinatario\"]; if(estudianteSelect) { estudianteSelect.value=\"\"; } this.form.submit();'>"; echo "<option value=''>-- Seleccione tipo --</option>"; echo "<option value='grupo'" . ($tipo_destinatario_informe == 'grupo' ? ' selected' : '') . ">Todo el Grupo</option>"; echo "<option value='estudiante'" . ($tipo_destinatario_informe == 'estudiante' ? ' selected' : '') . ">Estudiante Específico</option>"; echo "</select>"; echo "</div>";
                    if ($tipo_destinatario_informe == 'estudiante') {
                        echo "<div style='margin-bottom:10px;'>"; echo "<label for='id_estudiante_destinatario'>Seleccione Estudiante:</label> "; $sql_estudiantes_grupo_inf = "SELECT u.id_usuario, u.nombres, u.apellidos FROM USUARIOS u JOIN MATRICULAS_ESTUDIANTES me ON u.id_usuario = me.id_estudiante_fk WHERE me.id_grupo_fk = ? AND u.estado = 'activo' AND me.estado_matricula = 'Activa' ORDER BY u.apellidos, u.nombres"; $stmt_estudiantes_inf = mysqli_prepare($conn, $sql_estudiantes_grupo_inf);
                        if ($stmt_estudiantes_inf) { mysqli_stmt_bind_param($stmt_estudiantes_inf, "i", $id_grupo_seleccionado); mysqli_stmt_execute($stmt_estudiantes_inf); $result_estudiantes_inf = mysqli_stmt_get_result($stmt_estudiantes_inf); echo "<select name='id_estudiante_destinatario' id='id_estudiante_destinatario' style='padding: 8px; border-radius: 4px;' onchange='this.form.submit();'>"; echo "<option value=''>-- Elija un estudiante --</option>"; if (mysqli_num_rows($result_estudiantes_inf) > 0) { while ($estudiante_inf = mysqli_fetch_assoc($result_estudiantes_inf)) { $selected_est_inf = ($id_estudiante_destinatario_informe == $estudiante_inf['id_usuario']) ? "selected" : ""; echo "<option value='" . htmlspecialchars($estudiante_inf['id_usuario']) . "' $selected_est_inf>" . htmlspecialchars($estudiante_inf['apellidos'] . ", " . $estudiante_inf['nombres']) . "</option>"; } } else { echo "<option value=''>No hay estudiantes en este grupo</option>"; } echo "</select>"; mysqli_stmt_close($stmt_estudiantes_inf);
                        } else { error_log("Error al preparar consulta de estudiantes para informe: " . mysqli_error($conn)); echo "<p>Error al cargar estudiantes del grupo.</p>"; } echo "</div>";
                    }
                } echo "</form>";
                $mostrar_formulario_informe = false; if ($id_grupo_seleccionado) { if ($tipo_destinatario_informe == 'grupo') { $mostrar_formulario_informe = true; } elseif ($tipo_destinatario_informe == 'estudiante' && $id_estudiante_destinatario_informe) { $mostrar_formulario_informe = true; } }
                if ($mostrar_formulario_informe) {
                    $nombre_destinatario_display = "Todo el Grupo"; if ($tipo_destinatario_informe == 'estudiante' && $id_estudiante_destinatario_informe) { $stmt_nombre_est_inf = mysqli_prepare($conn, "SELECT nombres, apellidos FROM USUARIOS WHERE id_usuario = ?"); if ($stmt_nombre_est_inf) { mysqli_stmt_bind_param($stmt_nombre_est_inf, "i", $id_estudiante_destinatario_informe); mysqli_stmt_execute($stmt_nombre_est_inf); $res_nombre_est_inf = mysqli_stmt_get_result($stmt_nombre_est_inf); if($data_nombre_est_inf = mysqli_fetch_assoc($res_nombre_est_inf)){ $nombre_destinatario_display = htmlspecialchars($data_nombre_est_inf['apellidos'] . ", " . $data_nombre_est_inf['nombres']); } mysqli_stmt_close($stmt_nombre_est_inf); } }
                    echo "<hr style='margin:25px 0;'><h3>Crear Informe para: " . $nombre_destinatario_display . "</h3>";
                    echo "<form action='dashboard.php?action=guardar_nuevo_informe' method='post' style='max-width: 700px;'>"; echo "<input type='hidden' name='id_grupo_fk' value='" . htmlspecialchars($id_grupo_seleccionado) . "'>"; if ($tipo_destinatario_informe == 'estudiante' && $id_estudiante_destinatario_informe) { echo "<input type='hidden' name='id_estudiante_destinatario_fk' value='" . htmlspecialchars($id_estudiante_destinatario_informe) . "'>"; }
                    echo "<div class='form-group' style='margin-bottom: 15px;'><label for='titulo_informe' style='display:block; margin-bottom:5px;'>Título del Informe:</label><input type='text' name='titulo_informe' id='titulo_informe' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></div>";
                    echo "<div class='form-group' style='margin-bottom: 15px;'><label for='tipo_informe' style='display:block; margin-bottom:5px;'>Tipo de Informe:</label><select name='tipo_informe' id='tipo_informe' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'><option value='Académico'>Académico</option><option value='Comportamental'>Comportamental</option><option value='Disciplinario'>Disciplinario</option><option value='General'>General</option><option value='Otro'>Otro</option></select></div>";
                    echo "<div class='form-group' style='margin-bottom: 15px;'><label for='contenido_informe' style='display:block; margin-bottom:5px;'>Contenido del Informe:</label><textarea name='contenido_informe' id='contenido_informe' rows='10' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></textarea></div>";
                    echo "<button type='submit' name='guardar_informe' style='padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Guardar Informe</button>"; echo "</form>";
                }
            } else { echo "<p>No tienes grupos asignados.</p>"; } mysqli_stmt_close($stmt_grupos_informes);
        } else { error_log("Error al preparar consulta de grupos para informes: " . mysqli_error($conn)); echo "<p>Error al cargar tus grupos.</p>"; }
        break;

    case 'enviar_mensajes_alertas': // NUEVO CASE PARA MENSAJES Y ALERTAS
        echo "<h2>Enviar Mensajes o Alertas</h2>";

        // Paso 1: Selección de Grupo
        $sql_grupos_mensajes = "SELECT gc.id_grupo, gc.nombre_grupo, gc.semestre, c.nombre_curso FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_profesor_fk = ? ORDER BY c.nombre_curso, gc.semestre DESC, gc.nombre_grupo";
        $stmt_grupos_mensajes = mysqli_prepare($conn, $sql_grupos_mensajes);
        if($stmt_grupos_mensajes) {
            mysqli_stmt_bind_param($stmt_grupos_mensajes, "i", $id_profesor_actual);
            mysqli_stmt_execute($stmt_grupos_mensajes);
            $result_grupos_mensajes = mysqli_stmt_get_result($stmt_grupos_mensajes);

            if (mysqli_num_rows($result_grupos_mensajes) > 0) {
                // Formulario para seleccionar grupo, tipo de destinatario y estudiante (si aplica)
                echo "<form method='get' action='dashboard.php' id='formSeleccionMensajes' style='margin-bottom:20px;'>";
                echo "<input type='hidden' name='action' value='enviar_mensajes_alertas'>";
                
                // Selector de Grupo
                echo "<div style='margin-bottom:10px;'>";
                echo "<label for='id_grupo_mensaje'>Seleccione Grupo:</label> ";
                echo "<select name='id_grupo' id='id_grupo_mensaje' style='padding: 8px; border-radius: 4px;' onchange='
                var tipoDestMsg = this.form.elements[\"tipo_destinatario_mensaje\"];
                if (tipoDestMsg) { tipoDestMsg.value = \"\"; }
                var estMsg = this.form.elements[\"id_estudiante_mensaje\"];
                if (estMsg) { estMsg.value = \"\"; }
                this.form.submit();
                '>";
                // Mostrar opciones de grupos
                echo "<option value=''>-- Elija un grupo --</option>";
                while ($grupo = mysqli_fetch_assoc($result_grupos_mensajes)) {
                    $selected_grupo = ($id_grupo_seleccionado == $grupo['id_grupo']) ? "selected" : "";
                    echo "<option value='" . htmlspecialchars($grupo['id_grupo']) . "' $selected_grupo>" . htmlspecialchars($grupo['nombre_curso'] . " - " . $grupo['nombre_grupo'] . " (" . $grupo['semestre'] . ")") . "</option>";
                }
                echo "</select>";
                echo "</div>";

                // Selector de Tipo de Destinatario (solo si ya hay un grupo seleccionado)
                if ($id_grupo_seleccionado) {
                    echo "<div style='margin-bottom:10px;'>";
                    echo "<label for='tipo_destinatario_mensaje'>Mensaje Para:</label> ";
                    echo "<select name='tipo_destinatario_mensaje' id='tipo_destinatario_mensaje' style='padding: 8px; border-radius: 4px;' onchange='if(this.form.elements[\"id_estudiante_mensaje\"]) { this.form.elements[\"id_estudiante_mensaje\"].value=\"\";} this.form.submit();'>";
                    echo "<option value=''>-- Seleccione tipo de destinatario --</option>";
                    echo "<option value='grupo_completo'" . ($tipo_destinatario_mensaje == 'grupo_completo' ? ' selected' : '') . ">Todo el Grupo</option>";
                    echo "<option value='estudiante_especifico'" . ($tipo_destinatario_mensaje == 'estudiante_especifico' ? ' selected' : '') . ">Estudiante Específico</option>";
                    echo "</select>";
                    echo "</div>";

                    // Selector de Estudiante (solo si se seleccionó "Estudiante Específico")
                    if ($tipo_destinatario_mensaje == 'estudiante_especifico') {
                        echo "<div style='margin-bottom:10px;'>";
                        echo "<label for='id_estudiante_mensaje'>Seleccione Estudiante:</label> ";
                        $sql_estudiantes_grupo_msg = "SELECT u.id_usuario, u.nombres, u.apellidos FROM USUARIOS u JOIN MATRICULAS_ESTUDIANTES me ON u.id_usuario = me.id_estudiante_fk WHERE me.id_grupo_fk = ? AND u.estado = 'activo' AND me.estado_matricula = 'Activa' ORDER BY u.apellidos, u.nombres";
                        $stmt_estudiantes_msg = mysqli_prepare($conn, $sql_estudiantes_grupo_msg);
                        if ($stmt_estudiantes_msg) {
                            mysqli_stmt_bind_param($stmt_estudiantes_msg, "i", $id_grupo_seleccionado);
                            mysqli_stmt_execute($stmt_estudiantes_msg);
                            $result_estudiantes_msg = mysqli_stmt_get_result($stmt_estudiantes_msg);
                            echo "<select name='id_estudiante_mensaje' id='id_estudiante_mensaje' style='padding: 8px; border-radius: 4px;' onchange='this.form.submit();'>"; // submit para actualizar el contexto
                            echo "<option value=''>-- Elija un estudiante --</option>";
                            if (mysqli_num_rows($result_estudiantes_msg) > 0) {
                                while ($estudiante_msg = mysqli_fetch_assoc($result_estudiantes_msg)) {
                                    $selected_est_msg = ($id_estudiante_destinatario_mensaje == $estudiante_msg['id_usuario']) ? "selected" : "";
                                    echo "<option value='" . htmlspecialchars($estudiante_msg['id_usuario']) . "' $selected_est_msg>" . htmlspecialchars($estudiante_msg['apellidos'] . ", " . $estudiante_msg['nombres']) . "</option>";
                                }
                            } else {
                                echo "<option value=''>No hay estudiantes en este grupo</option>";
                            }
                            echo "</select>";
                            mysqli_stmt_close($stmt_estudiantes_msg);
                        } else {
                            error_log("Error al preparar consulta de estudiantes para mensaje: " . mysqli_error($conn));
                            echo "<p>Error al cargar estudiantes del grupo.</p>";
                        }
                        echo "</div>";
                    }
                }
                echo "</form>"; // Fin del formulario de selección

                // Formulario para redactar el mensaje
                $mostrar_formulario_mensaje = false;
                if ($id_grupo_seleccionado) {
                    if ($tipo_destinatario_mensaje == 'grupo_completo') {
                        $mostrar_formulario_mensaje = true;
                    } elseif ($tipo_destinatario_mensaje == 'estudiante_especifico' && $id_estudiante_destinatario_mensaje) {
                        $mostrar_formulario_mensaje = true;
                    }
                }

                if ($mostrar_formulario_mensaje) {
                    $destinatario_display_msg = "Todo el Grupo";
                    if ($tipo_destinatario_mensaje == 'estudiante_especifico' && $id_estudiante_destinatario_mensaje) {
                        // Obtener nombre del estudiante para mostrar
                        $stmt_nombre_est_msg = mysqli_prepare($conn, "SELECT nombres, apellidos FROM USUARIOS WHERE id_usuario = ?");
                        if ($stmt_nombre_est_msg) {
                            mysqli_stmt_bind_param($stmt_nombre_est_msg, "i", $id_estudiante_destinatario_mensaje);
                            mysqli_stmt_execute($stmt_nombre_est_msg);
                            $res_nombre_est_msg = mysqli_stmt_get_result($stmt_nombre_est_msg);
                            if($data_nombre_est_msg = mysqli_fetch_assoc($res_nombre_est_msg)){
                                $destinatario_display_msg = htmlspecialchars($data_nombre_est_msg['apellidos'] . ", " . $data_nombre_est_msg['nombres']);
                            }
                            mysqli_stmt_close($stmt_nombre_est_msg);
                        }
                    }
                     // Nombre del grupo
                    $stmt_nombre_grupo_msg = mysqli_prepare($conn, "SELECT c.nombre_curso, gc.nombre_grupo, gc.semestre FROM GRUPOS_CURSO gc JOIN CURSOS c ON gc.id_curso_fk = c.id_curso WHERE gc.id_grupo = ?");
                    $nombre_grupo_contexto = "";
                    if($stmt_nombre_grupo_msg){
                        mysqli_stmt_bind_param($stmt_nombre_grupo_msg, "i", $id_grupo_seleccionado);
                        mysqli_stmt_execute($stmt_nombre_grupo_msg);
                        $res_nombre_grupo_msg = mysqli_stmt_get_result($stmt_nombre_grupo_msg);
                        if($data_grupo_contexto = mysqli_fetch_assoc($res_nombre_grupo_msg)){
                            $nombre_grupo_contexto = htmlspecialchars($data_grupo_contexto['nombre_curso'] . " - " . $data_grupo_contexto['nombre_grupo'] . " (" . $data_grupo_contexto['semestre'] . ")");
                        }
                        mysqli_stmt_close($stmt_nombre_grupo_msg);
                    }

                    echo "<hr style='margin:25px 0;'><h3>Redactar Mensaje para: " . $destinatario_display_msg . " (Grupo: " . $nombre_grupo_contexto . ")</h3>";
                    
                    echo "<form action='dashboard.php?action=guardar_nuevo_mensaje' method='post' style='max-width: 700px;'>";
                    echo "<input type='hidden' name='id_grupo_fk_mensaje' value='" . htmlspecialchars($id_grupo_seleccionado) . "'>"; // Para contexto, y si el destinatario es el grupo
                    if ($tipo_destinatario_mensaje == 'estudiante_especifico' && $id_estudiante_destinatario_mensaje) {
                        echo "<input type='hidden' name='id_estudiante_destinatario_fk_mensaje' value='" . htmlspecialchars($id_estudiante_destinatario_mensaje) . "'>";
                    }
                    // Campo para saber si es a grupo o estudiante al procesar
                    echo "<input type='hidden' name='tipo_destinatario_procesar' value='" . htmlspecialchars($tipo_destinatario_mensaje) . "'>";


                    echo "<div class='form-group' style='margin-bottom: 15px;'>";
                    echo "<label for='asunto_mensaje' style='display:block; margin-bottom:5px;'>Asunto:</label>";
                    echo "<input type='text' name='asunto_mensaje' id='asunto_mensaje' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'>";
                    echo "</div>";
                    
                    echo "<div class='form-group' style='margin-bottom: 15px;'>";
                    echo "<label for='cuerpo_mensaje' style='display:block; margin-bottom:5px;'>Cuerpo del Mensaje:</label>";
                    echo "<textarea name='cuerpo_mensaje' id='cuerpo_mensaje' rows='10' required style='width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;'></textarea>";
                    echo "</div>";
                                        
                    echo "<button type='submit' name='enviar_mensaje' style='padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>Enviar Mensaje</button>";
                    echo "</form>";
                }

            } else {
                echo "<p>No tienes grupos asignados para enviar mensajes.</p>";
            }
            mysqli_stmt_close($stmt_grupos_mensajes);
        } else {
            error_log("Error al preparar consulta de grupos para mensajes: " . mysqli_error($conn));
            echo "<p>Error al cargar tus grupos.</p>";
        }
        break;
    
    // ... (otros cases del switch) ...
    case 'publicar_notas_definitivas': echo "<h2>Publicar Notas Definitivas</h2><p>(Funcionalidad en desarrollo)</p>"; break;
    case 'inicio_profesor':
    default:
        echo "<h2>Panel Principal del Profesor</h2>";
        echo "<p>Selecciona una opción del menú para comenzar.</p>";
        break;
}
?>