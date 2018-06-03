<?php

//Metodo que calcula y genera archivos JSONS ubicados en /resultados_json/$fecha
//con datos de resultados rector por cada claustro
function datos_rector_claustro($fecha) {
    $sql = "
                        select datos.*,
            case when m_enviadas is null then 0 else m_enviadas end as m_enviadas,
            case when m_confirmadas is null then 0 else m_confirmadas end as m_confirmadas,                         
m_total, t.empadronados 
            from (
								select claustro, t.id_claustro,
								    trim(lista) as lista, trim(sigla_lista) as sigla_lista, 
								    sum(ponderado) ponderado,
									sum(total) votos_lista,sum(votos_blancos) votos_blancos,
								    sum(votos_nulos) votos_nulos, sum(votos_recurridos) votos_recurridos
								from(
										select votos_totales.id_tipo, votos_totales.id_nro_ue, 
											votos_totales.sigla as sigla_ue, votos_totales.id_claustro, votos_totales.claustro, 
											votos_totales.id_nro_lista, votos_totales.lista, votos_totales.sigla_lista, 
											votos_totales.total, validos.validos, 
											case when validos.validos <> 0 then 
													votos_totales.total/cast(validos.validos as decimal) * validos.mult 
											end ponderado,
											votos_blancos, votos_nulos, votos_recurridos
										from (select a.id_tipo, ue.id_nro_ue, ue.sigla, 
											m.id_claustro as id_claustro, c.descripcion claustro, l.id_nro_lista, l.nombre lista, 
											l.sigla sigla_lista, sum(cant_votos) total, sum(total_votos_blancos) votos_blancos,
											sum(total_votos_nulos) votos_nulos, sum(total_votos_recurridos) votos_recurridos 
										  from acta a 
										  inner join mesa m on m.id_mesa = a.de 
										  inner join sede s on s.id_sede = a.id_sede 
										  inner join unidad_electoral ue on ue.id_nro_ue = s.id_ue 
										  inner join claustro c on c.id = m.id_claustro 
										  inner join voto_lista_rector vl on vl.id_acta = a.id_acta 
										  inner join lista_rector l on l.id_nro_lista = vl.id_lista 
											where m.estado > 1 and m.fecha = '$fecha' 
											group by ue.id_nro_ue, ue.sigla, c.descripcion, l.nombre, l.id_nro_lista, l.sigla, s.id_ue, 
													  m.id_claustro, a.id_tipo 
											order by s.id_ue,m.id_claustro, l.nombre 
                						) votos_totales
											inner join (select id_ue, id_claustro,
                                                                                        
                                                                                --siempre debería ser por cdirectivo sin importar el nivel
										--case ue.nivel when 2 then cargos_cdirectivo
										--	when 3 then cargos_cdiras
										--end
                                                                                cargos_cdirectivo as mult, 
										sum(cant_votos) validos 
											from sede s 
											inner join acta a on a.id_sede = s.id_sede 
											inner join mesa m on m.id_mesa = a.de 
											inner join voto_lista_rector vl on vl.id_acta = a.id_acta 
											inner join claustro cl on cl.id = m.id_claustro
											inner join unidad_electoral ue on ue.id_nro_ue = s.id_ue
											where m.estado > 1 and m.fecha = '$fecha' 
											group by id_ue, id_claustro, cargos_csuperior, ue.nivel, 
														cargos_cdirectivo, cargos_cdiras  
                						) validos on validos.id_ue = votos_totales.id_nro_ue 
                            			and validos.id_claustro = votos_totales.id_claustro
									) t
									group by id_tipo, claustro, lista, sigla_lista, id_claustro
									
								) datos
           inner join (
                select count(*)  as m_total ,sum(cant_empadronados) as empadronados, m.id_claustro from mesa m
                where m.fecha = '$fecha'
                group by m.id_claustro) t on t.id_claustro = datos.id_claustro
			left outer join (
                select count(*) as m_enviadas, m.id_claustro from mesa m
                where m.fecha = '$fecha' and m.estado>1
                group by m.id_claustro) m on m.id_claustro = datos.id_claustro
            left outer join (
                select count(*) as m_confirmadas, m.id_claustro from mesa m
                where m.fecha = '$fecha' and m.estado>2
                group by m.id_claustro) m2 on m2.id_claustro = datos.id_claustro
                order by claustro, lista
            

";

    $datos = toba::db('gu_kena')->consultar($sql);

    $columns = array();
    $columns[] = array('field' => 'lista', 'title' => 'Listas');
    $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla Listas');
    $columns[] = array('field' => 'votos', 'title' => 'Votos');
    $columns[] = array('field' => 'ponderado', 'title' => 'Ponderado');
    $columns[] = array('field' => 'porcentaje', 'title' => 'Porcentaje');

    $nom_claustro = null;

    //Datos de mesas
    $m_enviadas = null;
    $m_confirmadas = null;
    $m_total = null;
    $total['total'] = 0;
    $total_votos = 0;
    $empadronados = null;
    $blancos = null;
    $nulos = null;
    $recurridos = null;

    $data = array();
    foreach ($datos as $un_registro) {
        if ($nom_claustro != null && $nom_claustro != $un_registro['claustro']) {
            $json = array();
            
            $porcentajes = array();
            for($pos = 0; $pos <sizeof($data); $pos++){
                $porcentaje = round($data[$pos]['ponderado']*100/$total['total'], 2);
                $labels[$pos] .= ' ('.$porcentaje.'%)';
                $porcentajes[] = $porcentaje;//utf8_encode($porcentaje.'%');
                $data[$pos]['porcentaje'] = utf8_encode($porcentaje.'%');
            }
        
            $data[] = array('lista' => 'Blancos', 'votos' => $blancos);
            $data[] = array('lista' => 'Nulos', 'votos' => $nulos);
            $data[] = array('lista' => 'Recurridos', 'votos' => $recurridos);
            $data[] = array('lista' => 'Votantes', 'votos' => $total_votos + $blancos + $nulos + $recurridos);
            $data[] = array('lista' => 'Empadronados', 'votos' => $empadronados);

            $json['columns'] = $columns;
            $json['data'] = $data;
            $json['labels'] = $labels;
            $json['total'] = $porcentajes;
            $json['fecha'] = date('d/m/Y G:i:s');
            $json['titulo'] = 'Votos Ponderados Universidad Rector ' . $nom_claustro;
            $json['titulo_grafico'] = 'PORCENTAJE DE VOTOS PONDERADOS SOBRE MESAS CARGADAS';
            $data2 = votos_por_ue($fecha, 'voto_lista_rector', 'lista_rector', 4, $nom_claustro);
            $json['titulo2'] = 'Votos Universidad Rector ' . $nom_claustro . ' por Unidad Electoral';
            $json['columns2'] = $data2[1];
            $json['data2'] = $data2[0];


            $json['enviadas'] = round($m_enviadas * 100 / $m_total, 2) . '% (' . $m_enviadas . " de " . $m_total . ')';
            $json['confirmadas'] = round($m_confirmadas * 100 / $m_total, 2) . '% (' . $m_confirmadas . " de " . $m_total . ')';

            $data = array();
            $labels = array();
            $total = array();
            $total['total'] = 0;
            $total_votos = 0;

            $string_json = json_encode($json);
            $nom_archivo = 'e' . str_replace('-', '', $fecha) . '/R_TODO_' . strtoupper($nom_claustro[0]) . '.json';
            file_put_contents('resultados_json/' . $nom_archivo, $string_json);

            $nom_claustro = $un_registro['claustro'];
        } elseif ($nom_claustro == null)
            $nom_claustro = $un_registro['claustro'];

        $r['lista'] = utf8_encode($un_registro['lista']);
        $r['sigla_lista'] = $un_registro['sigla_lista'];
        $r['votos'] = $un_registro['votos_lista'];
        $total_votos+=$r['votos'];
        $r['ponderado'] = $un_registro['ponderado'];

        $labels[] = $un_registro['sigla_lista'];
        $total[] = $un_registro['ponderado'];
        $total['total'] += $un_registro['ponderado'];

        //Datos de mesas
        $m_enviadas = $un_registro['m_enviadas'];
        $m_confirmadas = $un_registro['m_confirmadas'];
        $m_total = $un_registro['m_total'];

        $blancos = $un_registro['votos_blancos'];
        $nulos = $un_registro['votos_nulos'];
        $recurridos = $un_registro['votos_recurridos'];
        $empadronados = $un_registro['empadronados'];
        $data[] = $r;
    }
    if (isset($data) && $nom_claustro != null) {//Quedo un ultimo claustro sin guardar
        $json = array();

        $porcentajes = array();
        for($pos = 0; $pos <sizeof($data); $pos++){
            $porcentaje = round($data[$pos]['ponderado']*100/$total['total'], 2);
            $labels[$pos] .= ' ('.$porcentaje.'%)';
            $porcentajes[] = $porcentaje;//utf8_encode($porcentaje.'%');
            $data[$pos]['porcentaje'] = utf8_encode($porcentaje.'%');
        }
        
        $data[] = array('lista' => 'Blancos', 'votos' => $blancos);
        $data[] = array('lista' => 'Nulos', 'votos' => $nulos);
        $data[] = array('lista' => 'Recurridos', 'votos' => $recurridos);
        $data[] = array('lista' => 'Votantes', 'votos' => $total_votos + $blancos + $nulos + $recurridos);
        $data[] = array('lista' => 'Empadronados', 'votos' => $empadronados);

        $json['columns'] = $columns;
        $json['data'] = $data;
        $json['labels'] = $labels;
        $json['total'] = $porcentajes;
        $json['fecha'] = date('d/m/Y G:i:s');
        $json['titulo'] = 'Votos Ponderados Universidad Rector ' . $nom_claustro;
        $json['titulo_grafico'] = 'PORCENTAJE DE VOTOS PONDERADOS SOBRE MESAS CARGADAS';
        $data2 = votos_por_ue($fecha, 'voto_lista_rector', 'lista_rector', 4, $nom_claustro);
        $json['titulo2'] = 'Votos Universidad Rector ' . $nom_claustro . ' por Unidad Electoral';
        $json['columns2'] = $data2[1];
        $json['data2'] = $data2[0];


        $json['enviadas'] = round($m_enviadas * 100 / $m_total, 2) . '% (' . $m_enviadas . " de " . $m_total . ')';
        $json['confirmadas'] = round($m_confirmadas * 100 / $m_total, 2) . '% (' . $m_confirmadas . " de " . $m_total . ')';

        $string_json = json_encode($json);

        $nom_archivo = 'e' . str_replace('-', '', $fecha) . '/R_TODO_' . strtoupper($nom_claustro[0]);
        file_put_contents('resultados_json/' . $nom_archivo . '.json', $string_json);
    }
}

function votos_por_ue($fecha, $tabla_voto, $tabla_lista, $id_tipo, $nom_claustro) {
    $sql = "select ue.sigla as sigla_ue, 
                    ue.id_nro_ue as id_ue,
                    cl.descripcion as claustro, cl.id id_claustro,
                    l.id_nro_lista, l.nombre as lista,
                    l.sigla as sigla_lista,
                    sum(vl.cant_votos) votos_lista,
                    sum(a.total_votos_blancos) as votos_blancos,
                    sum(a.total_votos_nulos) as votos_nulos,
                    sum(a.total_votos_recurridos) as votos_recurridos,
                    sum(m.cant_empadronados) as cant_empadronados,
                    count(distinct m.id_mesa) as mesas,
                    sum(case when m.estado>1 then 1 else 0 end) as mesas_cargadas,
                    sum(case when m.estado>2 then 1 else 0 end) as mesas_confirmadas

                from acta a 
                inner join mesa m on m.id_mesa = a.de
                inner join claustro cl on cl.id = m.id_claustro
                inner join $tabla_voto vl on vl.id_acta = a.id_acta
                inner join $tabla_lista l on l.id_nro_lista = vl.id_lista
                inner join sede s on s.id_sede = a.id_sede
                inner join unidad_electoral ue on ue.id_nro_ue = s.id_ue

                where a.id_tipo=$id_tipo and l.fecha = '" . $fecha . "' and m.estado>1 and cl.descripcion='$nom_claustro'
                
                group by ue.sigla, ue.id_nro_ue, cl.descripcion, cl.id,
                    l.id_nro_lista, l.nombre, l.sigla 
                    order by l.sigla,ue.sigla";
    $datos = toba::db('gu_kena')->consultar($sql);
    $data = array();

    $sigla_lista = null;
    $r_total = array('sigla_lista' => 'Validos', 'total' => 0);
    $r_blancos = array('sigla_lista' => 'Blancos', 'total' => 0);
    $r_nulos = array('sigla_lista' => 'Nulos', 'total' => 0);
    $r_recurridos = array('sigla_lista' => 'Recurridos', 'total' => 0);
    $r_votantes = array('sigla_lista' => 'Votantes', 'total' => 0);
    $r_empadronados = array('sigla_lista' => 'Empadronados', 'total' => 0);
    $r_mesas_confirmadas = array('sigla_lista' => 'Mesas Confirmadas', 'total' => 0);
    $r_mesas_cargadas = array('sigla_lista' => 'Mesas Cargadas', 'total' => 0);

    $columns = array();
    $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla Listas');

    foreach ($datos as $un_registro) {
        //si cambia la lista nueva fila
        if ($sigla_lista != $un_registro['sigla_lista']) {
            if ($sigla_lista != null) {

                $data[] = $r;
            }
            $sigla_lista = $un_registro['sigla_lista'];
            $r = array();

            $r['lista'] = utf8_encode($un_registro['lista']);
            $r['sigla_lista'] = $un_registro['sigla_lista'];
            $r['total']=0;
        }
        //agrego columna
        $r[$un_registro['sigla_ue']] = $un_registro['votos_lista'];
        $r['total'] += $un_registro['votos_lista'];
        //si ya cree la columna actualizo totales
        if (isset($r_total[$un_registro['sigla_ue']])) {
            $r_total[$un_registro['sigla_ue']]+=$un_registro['votos_lista'];
            $r_total['total']+=$un_registro['votos_lista'];
            $r_votantes[$un_registro['sigla_ue']]+=$un_registro['votos_lista'];
            $r_votantes['total']+=$un_registro['votos_lista'];
        } else {
            //sino actualizo resto de filas
            $columns[] = array('field' => $un_registro['sigla_ue'], 'title' => $un_registro['sigla_ue']);
            $r_total[$un_registro['sigla_ue']] = $un_registro['votos_lista'];
            $r_total['total']+=$un_registro['votos_lista'];
            $r_blancos[$un_registro['sigla_ue']] = $un_registro['votos_blancos'];
            $r_blancos['total']+=$un_registro['votos_blancos'];
            $r_nulos[$un_registro['sigla_ue']] = $un_registro['votos_nulos'];
            $r_nulos['total']+=$un_registro['votos_nulos'];
            $r_recurridos[$un_registro['sigla_ue']] = $un_registro['votos_recurridos'];
            $r_recurridos['total']+=$un_registro['votos_recurridos'];
            $r_votantes[$un_registro['sigla_ue']] = $un_registro['votos_lista']+$un_registro['votos_blancos']+$un_registro['votos_nulos']+$un_registro['votos_recurridos'];
            $r_votantes['total']+=$r_votantes[$un_registro['sigla_ue']];
            $r_empadronados[$un_registro['sigla_ue']] = $un_registro['cant_empadronados'];
            $r_empadronados['total']+=$un_registro['cant_empadronados'];
            $r_mesas_confirmadas[$un_registro['sigla_ue']] = $un_registro['mesas_confirmadas'];
            $r_mesas_confirmadas['total']+=$un_registro['mesas_confirmadas'];
            $r_mesas_cargadas[$un_registro['sigla_ue']] = $un_registro['mesas_cargadas'];
            $r_mesas_cargadas['total']+=$un_registro['mesas_cargadas'];
        }
    }
    if (count($r) > 0) {

        $data[] = $r;
        $data[] = $r_total;
        $data[] = $r_blancos;
        $data[] = $r_nulos;
        $data[] = $r_recurridos;
        $data[] = $r_votantes;
        $data[] = $r_empadronados;
        
        
        $data[] = $r_mesas_cargadas;
        $data[] = $r_mesas_confirmadas;
        $columns[] = array('field' => 'total', 'title' => 'Total');
    }
    return array($data, $columns);
}
