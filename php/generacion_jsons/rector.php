<?php

 // Resultado general de rector con lista | ponderado
    function datos_rector($fecha) {
        $sql = "
            select datos.*, m_enviadas, m_confirmadas, m_total, empadronados.empadronados
            from(
                select claustro, id_nro_lista, trim(lista) as lista, 
                    trim(sigla_lista) as sigla_lista, sum(pond) as ponderado,
                    sum(votos) votos, sum(empadronados) empadronados,
                    total_votos_blancos, total_votos_nulos, total_votos_recurridos
                from (
                    select id_nro_lista, lista as Lista, sigla_lista, vl.claustro, 
                    sum(cast(votos_lista as real)/votos_validos)*ponderacion pond,
                    sum(votos_lista) votos, sum(empadronados) empadronados,
                    sum(total_votos_blancos) total_votos_blancos, 
                    sum(total_votos_nulos) total_votos_nulos, sum(total_votos_recurridos) total_votos_recurridos
                    from(
                    select ue.sigla as ue,c.descripcion claustro, l.id_nro_lista, 
                    l.nombre as lista, l.sigla as sigla_lista,
                    sum(cant_votos) as votos_lista, a.total_votos_blancos, 
                    sum(cant_empadronados) empadronados,
                    a.total_votos_nulos, a.total_votos_recurridos
                    from acta a inner join voto_lista_rector vl on a.id_acta=vl.id_acta and a.id_tipo=4
                            inner join lista_rector l on vl.id_lista=l.id_nro_lista
                            inner join mesa m on a.de=m.id_mesa
                            inner join claustro c on m.id_claustro=c.id
                            inner join sede s on a.id_sede=s.id_sede
                            inner join unidad_electoral ue on s.id_ue=ue.id_nro_ue
                    where m.fecha = '$fecha' and m.estado > 1
                    group by ue,claustro, lista, l.id_nro_lista, a.total_votos_blancos, 
                    a.total_votos_nulos, a.total_votos_recurridos
                    )vl inner join 
                    (
                    select ue.sigla as ue,c.descripcion claustro, cargos_cdirectivo as ponderacion, sum(cant_votos) as votos_validos
                    from acta a inner join voto_lista_rector vl on a.id_acta=vl.id_acta
                            inner join lista_rector l on vl.id_lista=l.id_nro_lista
                            inner join mesa m on a.de=m.id_mesa
                            inner join claustro c on m.id_claustro=c.id
                            inner join sede s on a.id_sede=s.id_sede
                            inner join unidad_electoral ue on s.id_ue=ue.id_nro_ue
                    where m.fecha = '$fecha' and m.estado > 1
                    group by ue,claustro,ponderacion

                    )vv on vl.ue=vv.ue and vl.claustro=vv.claustro
                    group by lista,vl.claustro,ponderacion, id_nro_lista, sigla_lista
                    order by vl.claustro, lista
                )a group by lista, claustro, id_nro_lista, sigla_lista, 
                    total_votos_blancos, total_votos_nulos, total_votos_recurridos
                order by lista, claustro, ponderado
            ) datos, 
            (select count(*) as m_enviadas from mesa m
                           where m.fecha = '$fecha' and m.estado>1) m,
            (select count(*) as m_confirmadas from mesa m
                        where m.fecha = '$fecha' and m.estado>2) m2,
            (select count(*) as m_total from mesa m
                        where m.fecha = '$fecha') t,
            (select sum(cant_empadronados) empadronados, cl.descripcion claustro 
		from mesa m
		inner join claustro cl on cl.id = m.id_claustro
		where m.fecha='$fecha'
		group by cl.descripcion
            ) empadronados
      where empadronados.claustro = datos.claustro
      order by id_nro_lista, claustro
                    ";
        $datos = toba::db('gu_kena')->consultar($sql);

        if(sizeof($datos) > 0){
            $nom_lista = null;
            $total = array();//Contiene la ultima fila de total por columna
            $total2 = array();//Contiene la ultima fila de total votantes por columna
            $empadronados = array();
            $data = array();//Coleccion de filas que forma el cuadro ponderado final
            $data2 = array();//Coleccion de filas que forma el cuadro votos final
            $r = array();//Recorre y arma una fila del cuadro final
            
            $labels = array();
            $totales = array();
            
            $m_enviadas = $datos[0]['m_enviadas'];
            $m_confirmadas = $datos[0]['m_confirmadas'];
            $m_total = $datos[0]['m_total'];
            
            $bnr = array();//Registros de blancos, nulos y recurridos
            $bnr['Blancos']['total'] = 0;
            $bnr['Nulos']['total'] = 0;
            $bnr['Recurridos']['total'] = 0;
            foreach($datos as $un_registro){
                if($nom_lista == null){
                    $nom_lista = $un_registro['sigla_lista'];                
                }elseif($nom_lista != $un_registro['sigla_lista']){
                    $r['sigla_lista'] = $nom_lista;
                    $r2['sigla_lista'] = $nom_lista;
                    
                    $labels[] = $nom_lista;
                    $totales[] = $r['ponderado'];
                    
                    $data[] = $r;
                    $data2[] = $r2;
                    
                    $r = array();
                    $r2 = array();
                    $nom_lista = $un_registro['sigla_lista'];
                }
                $r['lista'] = utf8_encode($un_registro['lista']);
                //$r['ponderado'] = $un_registro['ponderado'];
                $r2[$un_registro['claustro']] = $un_registro['votos'];
                
                $bnr['Blancos'][$un_registro['claustro']] = $un_registro['total_votos_blancos'];
                $bnr['Nulos'][$un_registro['claustro']] = $un_registro['total_votos_nulos'];
                $bnr['Recurridos'][$un_registro['claustro']] = $un_registro['total_votos_recurridos'];
                 
                if(isset($r['ponderado']))
                    $r['ponderado'] += $un_registro['ponderado'];
                else
                    $r['ponderado'] = $un_registro['ponderado'];
                
                if(isset($r2['total']))
                    $r2['total'] += $un_registro['votos'];
                else
                    $r2['total'] = $un_registro['votos'];
                
                if(isset($total['ponderado']))
                    $total['ponderado'] += $un_registro['ponderado'];
                else
                    $total['ponderado'] = $un_registro['ponderado'];
                
                if(isset($total2[$un_registro['claustro']]))
                    $total2[$un_registro['claustro']] += $un_registro['votos'];
                else
                    $total2[$un_registro['claustro']] = $un_registro['votos'];
                
                $empadronados[$un_registro['claustro']] = $un_registro['empadronados'];
            }
            
            //Guardar Ultima lista no guardada
            $r['sigla_lista'] = $nom_lista;
            $r2['sigla_lista'] = $nom_lista;
            $data[] = $r;
            $data2[] = $r2;

            $columns = array();
            $columns[] = array('field' => 'lista', 'title' => 'Lista');
            $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla');
            $columns2 = array();
            $columns2[] = array('field' => 'sigla_lista', 'title' => 'Lista');
            foreach($total2 as $key => $value){
                 $columns2[] = array('field' => $key, 'title' => $key);
                 
                 $total2[$key] = $value+$bnr['Blancos'][$key]+
                                    $bnr['Nulos'][$key]+
                                    $bnr['Recurridos'][$key];
                 
                 $bnr['Blancos']['total'] += $bnr['Blancos'][$key];
                 $bnr['Nulos']['total'] += $bnr['Nulos'][$key];
                 $bnr['Recurridos']['total'] += $bnr['Recurridos'][$key];
                  
                 if(isset($total2['total']))
                    $total2['total'] += $value;
                 else
                     $total2['total'] = $value;
            }
            foreach($empadronados as $key => $value){
                 if(isset($empadronados['total']))
                    $empadronados['total'] += $value;
                 else
                     $empadronados['total'] = $value;
            }
            $columns[] = array('field' => 'ponderado', 'title' => 'Ponderado');
            $columns2[] = array('field' => 'total', 'title' => 'Total');
            //print_r($bnr);
           //Armado del json
            $json = array();
            $total['lista'] = 'Total';
            $data[] = $total;
            $json['data'] = $data;
            $json['columns'] = $columns;
            //print_r($total);
            
            $total2['sigla_lista'] = 'Votantes';
            $bnr['Blancos']['sigla_lista'] = 'Blancos';
            $bnr['Nulos']['sigla_lista'] = 'Nulos';
            $bnr['Recurridos']['sigla_lista'] = 'Recurridos';
            $data2[] = $bnr['Blancos'];
            $data2[] = $bnr['Nulos'];
            $data2[] = $bnr['Recurridos'];
            $data2[] = $total2;
            $empadronados['sigla_lista'] = 'Empadronados';
            $data2[] = $empadronados;
            $json['data2'] = $data2;
            $json['columns2'] = $columns2;

            $labels[] = $nom_lista;
            $totales[] = $r['ponderado'];
            
            $json['labels'] = $labels;
            $json['total'] = $totales;
            $json['fecha'] = date('d/m/Y G:i:s');
            $json['titulo'] = 'Votos Ponderados Universidad Rector';
            $json['titulo2'] = 'Votos Universidad Rector';
            $json['enviadas'] = round($m_enviadas*100/$m_total, 2).'% ('.$m_enviadas." de ".$m_total.')';
            $json['confirmadas'] = round($m_confirmadas*100/$m_total, 2).'% ('.$m_confirmadas." de ".$m_total.')';
                        
            $string_json = json_encode($json);

            $nom_archivo = 'e'.str_replace('-','',$fecha).'/R_TODO_T';
            file_put_contents('resultados_json/'.$nom_archivo . '.json', $string_json);
        }
    }

