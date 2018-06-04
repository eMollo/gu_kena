<?php

//Metodo que calcula y genera JSONS de la categoria $categoria para cada unidad
//electoral y cada claustro
function datos_ue_claustro($fecha, $tabla_voto, $tabla_lista, $categoria, $sigla_cat) {
    $cargos = '';
    if ($sigla_cat == 'CS') {
        $cargos = ", cl.cargos_csuperior as cant_cargos";
    } elseif ($sigla_cat == 'CD') {
        $cargos = ", case ue.nivel when 2 then cl.cargos_cdirectivo 
                                       when 3 then cl.cargos_cdiras end as cant_cargos";
    }

    $sql = "
            select datos.*, 
            case when m_enviadas is null then 0 else m_enviadas end as m_enviadas, 
            case when m_confirmadas is null then 0 else m_confirmadas end as m_confirmadas, 
            m_total
            from (
                select ue.nombre as unidad_electoral, ue.sigla as sigla_ue, 
                    ue.id_nro_ue as id_ue,
                    cl.descripcion as claustro, cl.id id_claustro,
                    l.id_nro_lista, l.nombre as lista,
                    s.sigla as sede, m.nro_mesa,m.estado,
                    trim(l.sigla) as sigla_lista, vl.cant_votos,
                    a.total_votos_blancos as votos_blancos, a.total_votos_nulos as votos_nulos,
                    a.total_votos_recurridos as votos_recurridos, m.cant_empadronados

                    $cargos
                from acta a 
                inner join mesa m on m.id_mesa = a.de
                inner join claustro cl on cl.id = m.id_claustro
                inner join $tabla_voto vl on vl.id_acta = a.id_acta
                inner join $tabla_lista l on l.id_nro_lista = vl.id_lista
                inner join sede s on s.id_sede = a.id_sede
                inner join unidad_electoral ue on ue.id_nro_ue = s.id_ue

                where l.fecha = '" . $fecha . "' and m.estado>1
                 
            ) datos
            inner join (select count(distinct(m.id_mesa)) as m_total, s.id_ue, m.id_claustro 
                from mesa m
                inner join acta a on a.de = m.id_mesa
                inner join sede s on s.id_sede = a.id_sede
                where m.fecha = '$fecha'
                group by s.id_ue, m.id_claustro) t on t.id_ue = datos.id_ue 
                                        and t.id_claustro = datos.id_claustro
            left join (select count(distinct(m.id_mesa)) as m_confirmadas, s.id_ue, m.id_claustro from mesa m
                inner join acta a on a.de = m.id_mesa
                inner join sede s on s.id_sede = a.id_sede
                where m.fecha = '$fecha' and m.estado>2
                group by s.id_ue, m.id_claustro) m2 on m2.id_ue = t.id_ue
                                        and m2.id_claustro = t.id_claustro
            left join (select count(distinct(m.id_mesa)) as m_enviadas, s.id_ue, m.id_claustro from mesa m
                inner join acta a on a.de = m.id_mesa
                inner join sede s on s.id_sede = a.id_sede
                where m.fecha = '$fecha' and m.estado>1
                group by s.id_ue, m.id_claustro) m on m.id_ue = t.id_ue
                                        and m.id_claustro = t.id_claustro
                order by unidad_electoral, claustro,  lista, sede, nro_mesa
                ";
    //print_r($sql . '///////');
    $datos = toba::db('gu_kena')->consultar($sql);

    $nro_lista = null;
    $nom_ue = null;
    $nom_claustro = null;
    $data = array();
    $sedes = array();
    $labels = array();
    $total = array();
    $blancos = array('lista' => 'Blancos', 'total' => 0);
    $nulos = array('lista' => 'Nulos', 'total' => 0);
    $recurridos = array('lista' => 'Recurridos', 'total' => 0);
    $votantes = array('lista' => 'Votantes', 'total' => 0);
    $empadronados = array('lista' => 'Empadronados', 'total' => 0);
    foreach ($datos as $un_registro) {

        $sigla_sede = $un_registro['sede'] . ' M' . $un_registro['nro_mesa'];
        if ($un_registro['estado'] > 2)
            $sigla_sede.='*';
        if (($nro_lista != $un_registro['id_nro_lista']) || ($nom_ue != $un_registro['sigla_ue']) || ( $nom_claustro != $un_registro['claustro'])) { //nueva fila
            if (!is_null($nro_lista)) {
                $data[] = $lista;
                $total[] = $lista['total'];

                //cambia ue o claustro hay que crear los archivos
                if (($nom_ue != $un_registro['sigla_ue']) || ( $nom_claustro != $un_registro['claustro'])) {
                    $json = array();
                    $data[] = $blancos;
                    $data[] = $nulos;
                    $data[] = $recurridos;
                    $data[] = $votantes;
                    $data[] = $empadronados;
                    $json['data'] = $data;
                    
                    $columns = array();
                    $columns[] = array('field' => 'lista', 'title' => 'Listas');
                    $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla');
                    foreach ($sedes as $sede) {
                        $columns[] = array('field' => $sede, 'title' => $sede);
                    }
                    $columns[] = array('field' => 'total', 'title' => 'Total');
                    $json['columns'] = $columns;
                    if ($sigla_cat == 'CD') {
                        $res = dhont($labels, $total, $cant_cargos, $sigla_cat);
                        $json['data2'] = $res[1];
                        $json['columns2'] = $res[0];
                        $json['titulo2'] = 'Distribución de cargos a ocupar';
                    }
                    $categoria2 = $categoria;
                    if (strtoupper($nom_ue) == 'AUZA' || strtoupper($nom_ue) == 'ASMA') {
                        if ($sigla_cat == 'D') {
                            $categoria2 = 'Director de Asentamiento';
                        } elseif ($sigla_cat == 'CD') {
                            $categoria2 = 'Consejero Directivo Asentamiento';
                        }
                    }
                    foreach($total as $pos => $votos){
                        $labels[$pos] .= ' ('.$votos.' votos)'; 
                    }
                    $json['labels'] = $labels;
                    $json['total'] = $total;
                    
                    $json['fecha'] = date('d/m/Y G:i:s');
                    $json['titulo'] = 'Votos ' . $nom_ue . ' ' . $categoria2 . ' ' . $nom_claustro;
                    $json['enviadas'] = round($m_enviadas * 100 / $m_total, 2) . '% (' . $m_enviadas . " de " . $m_total . ')';
                    $json['confirmadas'] = round($m_confirmadas * 100 / $m_total, 2) . '% (' . $m_confirmadas . " de " . $m_total . ')';
                    $json['titulo_grafico'] = 'VOTOS SOBRE MESAS CARGADAS';
                    $string_json = json_encode($json);
                    $nom_archivo = 'e' . str_replace('-', '', $fecha) . '/' . $sigla_cat . '_' . strtoupper($nom_ue) . '_' . strtoupper($nom_claustro[0]) . '.json';
                    file_put_contents('resultados_json/' . $nom_archivo, $string_json);
                    //inicializo data
                    $nom_ue = $un_registro['sigla_ue'];
                    $nom_claustro = $un_registro['claustro'];
                    $data = array();
                    $sedes = array();
                    $labels = array();
                    $total = array();
                    $blancos = array('lista' => 'Blancos', 'total' => 0);
                    $nulos = array('lista' => 'Nulos', 'total' => 0);
                    $recurridos = array('lista' => 'Recurridos', 'total' => 0);
                    $votantes = array('lista' => 'Votantes', 'total' => 0);
                    $empadronados = array('lista' => 'Empadronados', 'total' => 0);
                    if (isset($un_registro['cant_cargos'])) {
                        $cant_cargos = $un_registro['cant_cargos'];
                    }
                }
            } else { //si es la primera ve que entre id_nro_lista == null
                $nom_ue = $un_registro['sigla_ue'];
                $nom_claustro = $un_registro['claustro'];
                if (isset($un_registro['cant_cargos'])) {
                    $cant_cargos = $un_registro['cant_cargos'];
                }
            }

            //inicializo lista
            $nro_lista = $un_registro['id_nro_lista'];
            $lista = array();
            $lista['lista'] = utf8_encode($un_registro['lista']);
            $lista['sigla_lista'] = $un_registro['sigla_lista'];
            $lista['total'] = 0;
            $labels[] = $un_registro['sigla_lista'];
        }
        //nueva columna
        if (!in_array($sigla_sede, $sedes)) {
            $sedes[] = $sigla_sede;
            $blancos[$sigla_sede] = $un_registro['votos_blancos'];
            $blancos['total']+=$un_registro['votos_blancos'];
            $nulos[$sigla_sede] = $un_registro['votos_nulos'];
            $nulos['total']+=$un_registro['votos_nulos'];
            $recurridos[$sigla_sede] = $un_registro['votos_recurridos'];
            $recurridos['total']+=$un_registro['votos_recurridos'];
            $votantes[$sigla_sede] = $un_registro['votos_blancos'] + $un_registro['votos_nulos'] + $un_registro['votos_recurridos'];
            $votantes['total'] += $votantes[$sigla_sede];
            $empadronados[$sigla_sede] = $un_registro['cant_empadronados'];
            $empadronados['total']+=$un_registro['cant_empadronados'];
        }
        //Datos de mesas
        $m_enviadas = $un_registro['m_enviadas'];
        $m_confirmadas = $un_registro['m_confirmadas'];
        $m_total = $un_registro['m_total'];

        $lista[$sigla_sede] = $un_registro['cant_votos'];
        $lista['total']+=$un_registro['cant_votos'];
        $votantes[$sigla_sede]+=$un_registro['cant_votos'];
        $votantes['total']+=$un_registro['cant_votos'];
    }

    if (isset($lista)&&sizeof($lista) > 0) {//Solo si existen datos finales ent crea el json
        $data[] = $lista;
        $total[] = $lista['total'];
        //print_r($data);exit;
        $json = array();
        $columns = array();
        $columns[] = array('field' => 'lista', 'title' => 'Listas');
        $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla Listas');
        foreach ($sedes as $sede) {
            $columns[] = array('field' => $sede, 'title' => $sede);
        }
        $columns[] = array('field' => 'total', 'title' => 'Total');

        $json['columns'] = $columns;

        $data[] = $blancos;
        $data[] = $nulos;
        $data[] = $recurridos;
        $data[] = $votantes;
        $data[] = $empadronados;
        $json['data'] = $data;
        
        //Calculo dhont solo para cons.  directivo, los 
        // unicos que tendran este campo cargado
        if ($sigla_cat == 'CD') {
            $res = dhont($labels, $total, $cant_cargos, $sigla_cat);
            $json['data2'] = $res[1];
            $json['columns2'] = $res[0];
            $json['titulo2'] = 'Distribución de cargos a ocupar';
        }
        foreach($total as $pos => $votos){
            $labels[$pos] .= ' ('.$votos.' votos)'; 
        }
        $json['labels'] = $labels;
        $json['total'] = $total;
        $json['fecha'] = date('d/m/Y G:i:s');
        $categoria2 = $categoria;
        if (strtoupper($nom_ue) == 'AUZA' || strtoupper($nom_ue) == 'ASMA') {
            if ($sigla_cat == 'D') {
                $categoria2 = 'Director de Asentamiento';
            } elseif ($sigla_cat == 'CD') {
                $categoria2 = 'Consejero Directivo Asentamiento';
            }
        }

        $json['titulo'] = 'Votos ' . $nom_ue . ' ' . $categoria2 . ' ' . $nom_claustro;
        $json['titulo_grafico'] = 'VOTOS SOBRE MESAS CARGADAS';
        $json['enviadas'] = round($m_enviadas * 100 / $m_total, 2) . '% (' . $m_enviadas . " de " . $m_total . ')';
        $json['confirmadas'] = round($m_confirmadas * 100 / $m_total, 2) . '% (' . $m_confirmadas . " de " . $m_total . ')';

        $string_json = json_encode($json);
        $nom_archivo = 'e' . str_replace('-', '', $fecha) . '/' . $sigla_cat . '_' . strtoupper($nom_ue) . '_' . strtoupper($nom_claustro[0]) . '.json';
        file_put_contents('resultados_json/' . $nom_archivo, $string_json);
    }
}
