<?php
// php/paneles/estudiante_panel_contenido.php
// (Inicio del archivo igual que antes, con la protección de rol y obtención de $id_estudiante_actual y $action)
// ... (código existente para protección de rol, $id_estudiante_actual, $action, <h1>, <p> bienvenida, <nav>) ...
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol_id']) || $_SESSION['user_rol_id'] != 1) {
    echo "<p>Acceso restringido. Este panel es solo para estudiantes.</p>";
    return;
}
$id_estudiante_actual = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'inicio_estudiante';
?>

<h1>Módulo Estudiante</h1>
<p>Bienvenido/a, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>.</p>

<nav class="module-menu">
    <ul>
        <li><a href="dashboard.php?action=consultar_notas">1. Consultar notas</a></li>
        <li><a href="dashboard.php?action=ver_asistencia">2. Ver asistencia</a></li>
        <li><a href="dashboard.php?action=ver_informes_estudiante">3. Ver informes</a></li>
        <li><a href="dashboard.php?action=ver_tareas_estudiante">4. Ver tareas asignadas</a></li>
        <li><a href="dashboard.php?action=ver_evaluaciones_estudiante">5. Ver evaluaciones</a></li>
    </ul>
</nav>
<hr style="margin: 20px 0;">

<?php
// Manejar la acción específica
switch ($action) {
    case 'consultar_notas':
        echo "<h2>Consultar Notas</h2>";
        // ... (código de consultar notas que ya tienes y funciona) ...
        $sql_notas = "SELECT c.nombre_curso, gc.nombre_grupo, gc.semestre, ae.titulo_actividad, ae.tipo_actividad, ae.max_calificacion AS calificacion_maxima_actividad, nea.calificacion_obtenida, nea.observaciones_profesor, me.nota_final_numerica AS nota_final_curso_matriculado, me.estado_matricula FROM MATRICULAS_ESTUDIANTES me JOIN GRUPOS_CURSO gc ON me.id_grupo_fk = gc.id_grupo JOIN CURSOS c ON gc.id_curso_fk = c.id_curso LEFT JOIN ACTIVIDADES_EVALUABLES ae ON gc.id_grupo = ae.id_grupo_fk LEFT JOIN NOTAS_ESTUDIANTE_ACTIVIDAD nea ON me.id_matricula = nea.id_matricula_fk AND ae.id_actividad = nea.id_actividad_fk WHERE me.id_estudiante_fk = ? ORDER BY c.nombre_curso, gc.semestre DESC, gc.nombre_grupo, ae.fecha_publicacion, ae.titulo_actividad";
        $stmt_notas = mysqli_prepare($conn, $sql_notas);
        if ($stmt_notas) {
            mysqli_stmt_bind_param($stmt_notas, "i", $id_estudiante_actual);
            mysqli_stmt_execute($stmt_notas);
            $result_notas = mysqli_stmt_get_result($stmt_notas);
            if (mysqli_num_rows($result_notas) > 0) {
                $notas_agrupadas = [];
                while ($fila = mysqli_fetch_assoc($result_notas)) {
                    $key_grupo = $fila['nombre_curso'] . " - " . $fila['nombre_grupo'] . " (" . $fila['semestre'] . ")";
                    if (!isset($notas_agrupadas[$key_grupo])) {
                        $notas_agrupadas[$key_grupo] = ['nombre_curso_completo' => $key_grupo, 'nota_final_curso' => $fila['nota_final_curso_matriculado'], 'estado_matricula' => $fila['estado_matricula'], 'actividades' => []];
                    }
                    if (!empty($fila['titulo_actividad'])) {
                        $notas_agrupadas[$key_grupo]['actividades'][] = $fila;
                    }
                }
                if (empty($notas_agrupadas) && mysqli_num_rows($result_notas) > 0){ 
                    mysqli_data_seek($result_notas, 0); 
                    $fila_temp = mysqli_fetch_assoc($result_notas);
                     if($fila_temp){
                        $key_grupo = $fila_temp['nombre_curso'] . " - " . $fila_temp['nombre_grupo'] . " (" . $fila_temp['semestre'] . ")";
                        if(!isset($notas_agrupadas[$key_grupo])){
                              $notas_agrupadas[$key_grupo] = ['nombre_curso_completo' => $key_grupo, 'nota_final_curso' => $fila_temp['nota_final_curso_matriculado'], 'estado_matricula' => $fila_temp['estado_matricula'], 'actividades' => []];
                        }
                     }
                }
                if (empty($notas_agrupadas)) {
                     echo "<p>Aún no tienes calificaciones registradas o no estás matriculado en cursos con actividades.</p>";
                } else {
                    foreach ($notas_agrupadas as $grupo_info) {
                        echo "<h3>" . htmlspecialchars($grupo_info['nombre_curso_completo']) . "</h3>";
                        echo "<p><strong>Estado Matrícula:</strong> " . htmlspecialchars($grupo_info['estado_matricula']) . "</p>";
                        if (!empty($grupo_info['actividades'])) {
                            echo "<table border='1' style='width:100%; border-collapse: collapse; margin-bottom: 20px;'>";
                            echo "<thead><tr><th>Actividad</th><th>Tipo</th><th>Calificación Obtenida</th><th>Sobre (Máx.)</th><th>Observaciones del Profesor</th></tr></thead>";
                            echo "<tbody>";
                            foreach ($grupo_info['actividades'] as $actividad) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($actividad['titulo_actividad']) . "</td>";
                                echo "<td>" . htmlspecialchars($actividad['tipo_actividad']) . "</td>";
                                echo "<td>" . (isset($actividad['calificacion_obtenida']) ? htmlspecialchars($actividad['calificacion_obtenida']) : 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($actividad['calificacion_maxima_actividad']) . "</td>";
                                echo "<td>" . (!empty($actividad['observaciones_profesor']) ? nl2br(htmlspecialchars($actividad['observaciones_profesor'])) : '-') . "</td>";
                                echo "</tr>";
                            }
                            echo "</tbody></table>";
                        } else {
                            echo "<p>No hay actividades calificadas para este curso/grupo todavía.</p>";
                        }
                        if (isset($grupo_info['nota_final_curso'])) {
                            echo "<p><strong>Nota Final del Curso/Grupo: " . htmlspecialchars($grupo_info['nota_final_curso']) . "</strong></p>";
                        } else {
                            echo "<p><strong>Nota Final del Curso/Grupo:</strong> Aún no calculada.</p>";
                        }
                        echo "<hr style='margin: 15px 0;'>";
                    }
                }
            } else {
                echo "<p>No se encontraron notas registradas para ti o no estás matriculado en ningún curso.</p>";
            }
            mysqli_stmt_close($stmt_notas);
        } else {
            error_log("Error al preparar la consulta de notas: " . mysqli_error($conn));
            echo "<p>Error al consultar tus notas. Por favor, intenta más tarde.</p>";
        }
        break;

    case 'ver_asistencia':
        echo "<h2>Ver Asistencia</h2>";
        // ... (código de ver asistencia que ya tienes y funciona) ...
        $sql_asistencia = "SELECT c.nombre_curso, gc.nombre_grupo, gc.semestre, ac.fecha_clase, ac.estado_asistencia, ac.observaciones AS observaciones_asistencia FROM MATRICULAS_ESTUDIANTES me JOIN GRUPOS_CURSO gc ON me.id_grupo_fk = gc.id_grupo JOIN CURSOS c ON gc.id_curso_fk = c.id_curso JOIN ASISTENCIA_CLASE ac ON me.id_matricula = ac.id_matricula_fk WHERE me.id_estudiante_fk = ? ORDER BY c.nombre_curso, gc.semestre DESC, gc.nombre_grupo, ac.fecha_clase DESC";
        $stmt_asistencia = mysqli_prepare($conn, $sql_asistencia);
        if ($stmt_asistencia) {
            mysqli_stmt_bind_param($stmt_asistencia, "i", $id_estudiante_actual);
            mysqli_stmt_execute($stmt_asistencia);
            $result_asistencia = mysqli_stmt_get_result($stmt_asistencia);
            if (mysqli_num_rows($result_asistencia) > 0) {
                $asistencia_agrupada = [];
                while ($fila = mysqli_fetch_assoc($result_asistencia)) {
                    $key_grupo = $fila['nombre_curso'] . " - " . $fila['nombre_grupo'] . " (" . $fila['semestre'] . ")";
                    if (!isset($asistencia_agrupada[$key_grupo])) {
                        $asistencia_agrupada[$key_grupo] = ['nombre_curso_completo' => $key_grupo, 'registros' => []];
                    }
                    $asistencia_agrupada[$key_grupo]['registros'][] = $fila;
                }
                foreach ($asistencia_agrupada as $grupo_info) {
                    echo "<h3>" . htmlspecialchars($grupo_info['nombre_curso_completo']) . "</h3>";
                    echo "<table border='1' style='width:100%; border-collapse: collapse; margin-bottom: 20px;'>";
                    echo "<thead><tr><th>Fecha Clase</th><th>Estado Asistencia</th><th>Observaciones</th></tr></thead>";
                    echo "<tbody>";
                    foreach ($grupo_info['registros'] as $registro) {
                        $fecha_formateada = date("d/m/Y", strtotime($registro['fecha_clase']));
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($fecha_formateada) . "</td>";
                        echo "<td>" . htmlspecialchars($registro['estado_asistencia']) . "</td>";
                        echo "<td>" . (!empty($registro['observaciones_asistencia']) ? nl2br(htmlspecialchars($registro['observaciones_asistencia'])) : '-') . "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                }
            } else {
                echo "<p>No se encontraron registros de asistencia para ti.</p>";
            }
            mysqli_stmt_close($stmt_asistencia);
        } else {
            error_log("Error al preparar la consulta de asistencia: " . mysqli_error($conn));
            echo "<p>Error al consultar tu asistencia. Por favor, intenta más tarde.</p>";
        }
        break;

    case 'ver_informes_estudiante':
        echo "<h2>Ver Informes Académicos</h2>";
        // ... (código de ver informes que ya tienes y funciona) ...
        $sql_informes = "SELECT ia.titulo_informe, ia.contenido_informe, ia.tipo_informe, ia.fecha_creacion, ia.archivo_adjunto_ruta, u_prof.nombres AS profesor_nombres, u_prof.apellidos AS profesor_apellidos, c.nombre_curso, gc.nombre_grupo, gc.semestre FROM INFORMES_ACADEMICOS ia JOIN GRUPOS_CURSO gc ON ia.id_grupo_fk = gc.id_grupo JOIN CURSOS c ON gc.id_curso_fk = c.id_curso JOIN USUARIOS u_prof ON ia.id_profesor_autor_fk = u_prof.id_usuario WHERE ia.id_estudiante_destinatario_fk = ? OR (ia.id_estudiante_destinatario_fk IS NULL AND ia.id_grupo_fk IN (SELECT me.id_grupo_fk FROM MATRICULAS_ESTUDIANTES me WHERE me.id_estudiante_fk = ?)) ORDER BY ia.fecha_creacion DESC, c.nombre_curso, gc.nombre_grupo";
        $stmt_informes = mysqli_prepare($conn, $sql_informes);
        if ($stmt_informes) {
            mysqli_stmt_bind_param($stmt_informes, "ii", $id_estudiante_actual, $id_estudiante_actual);
            mysqli_stmt_execute($stmt_informes);
            $result_informes = mysqli_stmt_get_result($stmt_informes);
            if (mysqli_num_rows($result_informes) > 0) {
                echo "<div class='lista-informes'>";
                while ($informe = mysqli_fetch_assoc($result_informes)) {
                    echo "<div class='informe-item' style='border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;'>";
                    echo "<h4>" . htmlspecialchars($informe['titulo_informe']) . "</h4>";
                    echo "<p style='font-size: 0.9em; color: #555;'>";
                    echo "<strong>Curso/Grupo:</strong> " . htmlspecialchars($informe['nombre_curso']) . " - " . htmlspecialchars($informe['nombre_grupo']) . " (" . htmlspecialchars($informe['semestre']) . ")<br>";
                    echo "<strong>Autor:</strong> Prof. " . htmlspecialchars($informe['profesor_nombres'] . " " . $informe['profesor_apellidos']) . "<br>";
                    $fecha_informe_formateada = date("d/m/Y \a \l\a\s H:i", strtotime($informe['fecha_creacion']));
                    echo "<strong>Fecha:</strong> " . htmlspecialchars($fecha_informe_formateada) . "<br>";
                    echo "<strong>Tipo:</strong> " . htmlspecialchars($informe['tipo_informe']);
                    echo "</p>";
                    echo "<div class='contenido-informe' style='margin-top:10px; padding-top:10px; border-top: 1px dashed #eee;'>";
                    echo nl2br(htmlspecialchars($informe['contenido_informe']));
                    echo "</div>";
                    if (!empty($informe['archivo_adjunto_ruta'])) {
                        echo "<p style='margin-top:10px;'><strong>Adjunto:</strong> <a href='" . htmlspecialchars($informe['archivo_adjunto_ruta']) . "' target='_blank'>Descargar archivo</a></p>";
                    }
                    echo "</div>"; 
                }
                echo "</div>"; 
            } else {
                echo "<p>No tienes informes académicos disponibles en este momento.</p>";
            }
            mysqli_stmt_close($stmt_informes);
        } else {
            error_log("Error al preparar la consulta de informes: " . mysqli_error($conn));
            echo "<p>Error al consultar tus informes. Por favor, intenta más tarde.</p>";
        }
        break;

    case 'ver_tareas_estudiante':
        echo "<h2>Ver Tareas y Trabajos Asignados</h2>";
        // ... (código de ver tareas que ya tienes y funciona) ...
        $tipos_tareas = ['Tarea', 'Proyecto', 'Laboratorio'];
        $placeholders = implode(',', array_fill(0, count($tipos_tareas), '?'));
        $sql_tareas = "SELECT c.nombre_curso, gc.nombre_grupo, gc.semestre, ae.id_actividad, ae.titulo_actividad, ae.descripcion_actividad, ae.tipo_actividad, ae.fecha_publicacion, ae.fecha_entrega_limite, ae.max_calificacion, nea.calificacion_obtenida, nea.archivo_entrega_ruta, nea.fecha_calificacion FROM MATRICULAS_ESTUDIANTES me JOIN GRUPOS_CURSO gc ON me.id_grupo_fk = gc.id_grupo JOIN CURSOS c ON gc.id_curso_fk = c.id_curso JOIN ACTIVIDADES_EVALUABLES ae ON gc.id_grupo = ae.id_grupo_fk LEFT JOIN NOTAS_ESTUDIANTE_ACTIVIDAD nea ON me.id_matricula = nea.id_matricula_fk AND ae.id_actividad = nea.id_actividad_fk WHERE me.id_estudiante_fk = ? AND ae.tipo_actividad IN ($placeholders) ORDER BY gc.semestre DESC, c.nombre_curso, gc.nombre_grupo, ae.fecha_entrega_limite DESC, ae.fecha_publicacion DESC";
        $stmt_tareas = mysqli_prepare($conn, $sql_tareas);
        if ($stmt_tareas) {
            $bind_types = "i" . str_repeat('s', count($tipos_tareas));
            $bind_params = array_merge([$id_estudiante_actual], $tipos_tareas);
            $ref_params = []; foreach ($bind_params as $key => $value) { $ref_params[$key] = &$bind_params[$key]; }
            call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_tareas, $bind_types], $ref_params));
            mysqli_stmt_execute($stmt_tareas);
            $result_tareas = mysqli_stmt_get_result($stmt_tareas);
            if (mysqli_num_rows($result_tareas) > 0) {
                $tareas_agrupadas = [];
                while ($fila = mysqli_fetch_assoc($result_tareas)) {
                    $key_grupo = $fila['nombre_curso'] . " - " . $fila['nombre_grupo'] . " (" . $fila['semestre'] . ")";
                    if (!isset($tareas_agrupadas[$key_grupo])) {
                        $tareas_agrupadas[$key_grupo] = ['nombre_curso_completo' => $key_grupo, 'tareas' => []];
                    }
                    $tareas_agrupadas[$key_grupo]['tareas'][] = $fila;
                }
                foreach ($tareas_agrupadas as $grupo_info) {
                    echo "<h3>" . htmlspecialchars($grupo_info['nombre_curso_completo']) . "</h3>";
                    if (empty($grupo_info['tareas'])) {
                        echo "<p>No hay tareas o trabajos asignados para este curso/grupo.</p>"; continue;
                    }
                    echo "<table border='1' style='width:100%; border-collapse: collapse; margin-bottom: 20px;'>";
                    echo "<thead><tr><th>Tarea/Trabajo</th><th>Tipo</th><th>Publicada</th><th>Fecha Límite</th><th>Estado Entrega</th><th>Calificación</th><th>Acción</th></tr></thead>";
                    echo "<tbody>";
                    foreach ($grupo_info['tareas'] as $tarea) {
                        $fecha_publicacion_f = date("d/m/Y", strtotime($tarea['fecha_publicacion']));
                        $fecha_limite_f = $tarea['fecha_entrega_limite'] ? date("d/m/Y H:i", strtotime($tarea['fecha_entrega_limite'])) : 'N/A';
                        $estado_entrega = 'Pendiente';
                        if (!empty($tarea['archivo_entrega_ruta'])) {
                            $estado_entrega = 'Entregado';
                            if (isset($tarea['calificacion_obtenida'])) { $estado_entrega .= ' - Calificado'; } else { $estado_entrega .= ' - Sin Calificar'; }
                        } elseif ($tarea['fecha_entrega_limite'] && new DateTime() > new DateTime($tarea['fecha_entrega_limite'])) { $estado_entrega = 'Vencida (Sin entrega)'; }
                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($tarea['titulo_actividad']) . "</strong><br><small>" . nl2br(htmlspecialchars(substr($tarea['descripcion_actividad'], 0, 100))) . (strlen($tarea['descripcion_actividad']) > 100 ? '...' : '') . "</small></td>";
                        echo "<td>" . htmlspecialchars($tarea['tipo_actividad']) . "</td>";
                        echo "<td>" . htmlspecialchars($fecha_publicacion_f) . "</td>";
                        echo "<td>" . htmlspecialchars($fecha_limite_f) . "</td>";
                        echo "<td>" . htmlspecialchars($estado_entrega) . "</td>";
                        echo "<td>" . (isset($tarea['calificacion_obtenida']) ? htmlspecialchars($tarea['calificacion_obtenida']) . '/' . htmlspecialchars($tarea['max_calificacion']) : 'N/A') . "</td>";
                        echo "<td>";
                        if (!empty($tarea['archivo_entrega_ruta'])) { echo "<a href='" . htmlspecialchars($tarea['archivo_entrega_ruta']) . "' target='_blank'>Ver Entrega</a><br>"; }
                        echo "<a href='dashboard.php?action=subir_entrega&id_actividad=" . $tarea['id_actividad'] . "' style='font-size:0.9em;'>Subir/Modificar Entrega</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                }
            } else {
                echo "<p>No tienes tareas o trabajos asignados en este momento.</p>";
            }
            mysqli_stmt_close($stmt_tareas);
        } else {
            error_log("Error al preparar la consulta de tareas: " . mysqli_error($conn));
            echo "<p>Error al consultar tus tareas. Por favor, intenta más tarde.</p>";
        }
        break;

    case 'ver_evaluaciones_estudiante':
        echo "<h2>Ver Evaluaciones</h2>";

        // Tipos de actividades que consideramos "evaluaciones"
        $tipos_evaluaciones = ['Examen Parcial', 'Examen Final', 'Quiz']; // Puedes ajustar esto
        $placeholders_eval = implode(',', array_fill(0, count($tipos_evaluaciones), '?'));

        $sql_evaluaciones = "SELECT
                                c.nombre_curso,
                                gc.nombre_grupo,
                                gc.semestre,
                                ae.id_actividad,
                                ae.titulo_actividad,
                                ae.descripcion_actividad,
                                ae.tipo_actividad,
                                ae.fecha_publicacion,
                                ae.fecha_entrega_limite AS fecha_evaluacion,
                                ae.max_calificacion,
                                nea.calificacion_obtenida,
                                nea.observaciones_profesor
                            FROM MATRICULAS_ESTUDIANTES me
                            JOIN GRUPOS_CURSO gc ON me.id_grupo_fk = gc.id_grupo
                            JOIN CURSOS c ON gc.id_curso_fk = c.id_curso
                            JOIN ACTIVIDADES_EVALUABLES ae ON gc.id_grupo = ae.id_grupo_fk
                            LEFT JOIN NOTAS_ESTUDIANTE_ACTIVIDAD nea ON me.id_matricula = nea.id_matricula_fk AND ae.id_actividad = nea.id_actividad_fk
                            WHERE me.id_estudiante_fk = ?
                              AND ae.tipo_actividad IN ($placeholders_eval)
                            ORDER BY gc.semestre DESC, c.nombre_curso, gc.nombre_grupo, ae.fecha_entrega_limite DESC, ae.fecha_publicacion DESC";

        $stmt_evaluaciones = mysqli_prepare($conn, $sql_evaluaciones);

        if ($stmt_evaluaciones) {
            $bind_types_eval = "i" . str_repeat('s', count($tipos_evaluaciones));
            $bind_params_eval = array_merge([$id_estudiante_actual], $tipos_evaluaciones);
            $ref_params_eval = [];
            foreach ($bind_params_eval as $key => $value) {
                $ref_params_eval[$key] = &$bind_params_eval[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_evaluaciones, $bind_types_eval], $ref_params_eval));
            
            mysqli_stmt_execute($stmt_evaluaciones);
            $result_evaluaciones = mysqli_stmt_get_result($stmt_evaluaciones);

            if (mysqli_num_rows($result_evaluaciones) > 0) {
                $evaluaciones_agrupadas = [];
                while ($fila = mysqli_fetch_assoc($result_evaluaciones)) {
                    $key_grupo = $fila['nombre_curso'] . " - " . $fila['nombre_grupo'] . " (" . $fila['semestre'] . ")";
                    if (!isset($evaluaciones_agrupadas[$key_grupo])) {
                        $evaluaciones_agrupadas[$key_grupo] = [
                            'nombre_curso_completo' => $key_grupo,
                            'evaluaciones' => []
                        ];
                    }
                    $evaluaciones_agrupadas[$key_grupo]['evaluaciones'][] = $fila;
                }

                foreach ($evaluaciones_agrupadas as $grupo_info) {
                    echo "<h3>" . htmlspecialchars($grupo_info['nombre_curso_completo']) . "</h3>";
                    if (empty($grupo_info['evaluaciones'])) {
                        echo "<p>No hay evaluaciones programadas o realizadas para este curso/grupo.</p>";
                        continue;
                    }
                    echo "<table border='1' style='width:100%; border-collapse: collapse; margin-bottom: 20px;'>";
                    echo "<thead><tr>
                            <th>Evaluación</th>
                            <th>Tipo</th>
                            <th>Fecha Anuncio</th>
                            <th>Fecha Realización</th>
                            <th>Calificación</th>
                            <th>Observaciones</th>
                          </tr></thead>";
                    echo "<tbody>";
                    foreach ($grupo_info['evaluaciones'] as $evaluacion) {
                        $fecha_publicacion_f = date("d/m/Y", strtotime($evaluacion['fecha_publicacion']));
                        $fecha_evaluacion_f = $evaluacion['fecha_evaluacion'] ? date("d/m/Y H:i", strtotime($evaluacion['fecha_evaluacion'])) : 'N/A';
                        
                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($evaluacion['titulo_actividad']) . "</strong><br><small>" . nl2br(htmlspecialchars(substr($evaluacion['descripcion_actividad'], 0, 100))) . (strlen($evaluacion['descripcion_actividad']) > 100 ? '...' : '') . "</small></td>";
                        echo "<td>" . htmlspecialchars($evaluacion['tipo_actividad']) . "</td>";
                        echo "<td>" . htmlspecialchars($fecha_publicacion_f) . "</td>";
                        echo "<td>" . htmlspecialchars($fecha_evaluacion_f) . "</td>";
                        echo "<td>" . (isset($evaluacion['calificacion_obtenida']) ? htmlspecialchars($evaluacion['calificacion_obtenida']) . '/' . htmlspecialchars($evaluacion['max_calificacion']) : 'Pendiente / N/A') . "</td>";
                        echo "<td>" . (!empty($evaluacion['observaciones_profesor']) ? nl2br(htmlspecialchars($evaluacion['observaciones_profesor'])) : '-') . "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                }

            } else {
                echo "<p>No tienes evaluaciones programadas o realizadas en este momento.</p>";
            }
            mysqli_stmt_close($stmt_evaluaciones);
        } else {
            error_log("Error al preparar la consulta de evaluaciones: " . mysqli_error($conn));
            echo "<p>Error al consultar tus evaluaciones. Por favor, intenta más tarde.</p>";
        }
        break;

    case 'inicio_estudiante':
    default:
        echo "<h2>Panel Principal del Estudiante</h2>";
        echo "<p>Selecciona una opción del menú para comenzar.</p>";
        break;
}
?>