<?php

    // Resultado general de rector con lista | ponderado
    function datos_rector($fecha) {
        $sql = "
            select datos.*, m_enviadas, m_confirmadas, m_total, empadronados.empadronados
            from (
                    select id_nro_lista, trim(lista) as lista, 
                    trim(sigla_lista) as sigla_lista, vl.claustro, 
                    sum(votos_lista) as votos,
                    sum(case votos_validos when 0 then 0 
                    else cast(votos_lista as real)/votos_validos*ponderacion end) ponderado,
                    sum(total_votos_blancos) total_votos_blancos, 
                    sum(total_votos_nulos) total_votos_nulos, 
                    sum(total_votos_recurridos) total_votos_recurridos
                    from(
			    select ue.sigla as ue,c.descripcion claustro, l.id_nro_lista, 
			    l.nombre as lista, l.sigla as sigla_lista,
			    sum(cant_votos) as votos_lista, 
				sum(a.total_votos_blancos) as total_votos_blancos,
				sum(a.total_votos_nulos) as total_votos_nulos,
				sum(a.total_votos_recurridos) as total_votos_recurridos

			    from acta a inner join voto_lista_rector vl on a.id_acta=vl.id_acta and a.id_tipo=4
				    inner join lista_rector l on vl.id_lista=l.id_nro_lista
				    inner join mesa m on a.de=m.id_mesa
				    inner join claustro c on m.id_claustro=c.id
				    inner join sede s on a.id_sede=s.id_sede
				    inner join unidad_electoral ue on s.id_ue=ue.id_nro_ue
			    where m.fecha = '$fecha' and m.estado > 1
			    group by ue,claustro, lista, l.id_nro_lista 
		    )vl inner join (
			    select ue.sigla as ue,c.descripcion claustro, cargos_cdirectivo as ponderacion, 
			    sum(cant_votos) as votos_validos
			    from acta a inner join voto_lista_rector vl on a.id_acta=vl.id_acta
				    inner join lista_rector l on vl.id_lista=l.id_nro_lista
				    inner join mesa m on a.de=m.id_mesa
				    inner join claustro c on m.id_claustro=c.id
				    inner join sede s on a.id_sede=s.id_sede
				    inner join unidad_electoral ue on s.id_ue=ue.id_nro_ue
			    where m.fecha = '$fecha' and m.estado > 1
			    group by ue,claustro,ponderacion
                    )vv on vl.ue=vv.ue and vl.claustro=vv.claustro
                    group by id_nro_lista, lista, sigla_lista, vl.claustro  
                    order by vl.claustro, lista
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
        //echo $sql; exit;
        $datos = toba::db('gu_kena')->consultar($sql);

        if(sizeof($datos) > 0){
            $nom_lista = null;
            $total = array();//Contiene la ultima fila de total ponderado
            $total['ponderado'] = 0;
            $total2 = array();//Contiene la ultima fila de total votantes por columna
            $total2['total'] = 0;
            $empadronados = array();
            $empadronados['total'] = 0;
            $data = array();//Coleccion de filas que forma el cuadro ponderado final
            $data2 = array();//Coleccion de filas que forma el cuadro votos final
            $r = array();//Recorre y arma una fila del cuadro final de ponderado
            $r['ponderado'] = 0;
            $r2 = array();
            $r2['total'] = 0;
            
            $labels = array();//Contiene los datos de ponderados por lista
            //$totales = array();
            
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
                    //$totales[] = $r['ponderado'];
                    
                    $data[] = $r;
                    $data2[] = $r2;
                    
                    $r = array();//Recorre y arma una fila del cuadro final de ponderado
                    $r['ponderado'] = 0;
                    $r2 = array();//Recorre y arma una fila del cuadro final de votos
                    $r2['total'] = 0;
                    $nom_lista = $un_registro['sigla_lista'];
                }
                $r['lista'] = utf8_encode($un_registro['lista']);
                $r['ponderado'] += $un_registro['ponderado'];
                $total['ponderado'] += $un_registro['ponderado'];
                
                $r2[$un_registro['claustro']] = $un_registro['votos'];
                $r2['total'] += $un_registro['votos'];
                
                $bnr['Blancos'][$un_registro['claustro']] = $un_registro['total_votos_blancos'];
                $bnr['Nulos'][$un_registro['claustro']] = $un_registro['total_votos_nulos'];
                $bnr['Recurridos'][$un_registro['claustro']] = $un_registro['total_votos_recurridos'];
                
                if(isset($total2[$un_registro['claustro']]))
                    $total2[$un_registro['claustro']] += $un_registro['votos'];
                else
                    $total2[$un_registro['claustro']] = $un_registro['votos'];
                
                $total2['total'] += $un_registro['votos'];
                $empadronados[$un_registro['claustro']] = $un_registro['empadronados'];
            }
            //Guardar Ultima lista no guardada de ponderado
            $labels[] = $nom_lista;
            //$totales[] = $r['ponderado'];
            
            $r['sigla_lista'] = $nom_lista;
            $r2['sigla_lista'] = $nom_lista;
            $data[] = $r;
            //Guardar ultima fila con total de ponderado, 
            $total['lista'] = 'Total';
            $data[] = $total;
            
            $porcentajes = array();
            for($pos = 0; $pos <sizeof($data); $pos++){
                $porcentaje = round($data[$pos]['ponderado']*100/$total['ponderado'], 2);
                if($data[$pos]['lista'] != 'Total'){
                    $labels[$pos] .= ' ('.$porcentaje.'%)';
                    $porcentajes[] = $porcentaje;//utf8_encode($porcentaje.'%');
                
                    $data[$pos]['porcentaje'] = utf8_encode($porcentaje.'%');
                }
            }
            
            //Datos de columnas de primer cuadro (ponderado)
            $columns = array();
            $columns[] = array('field' => 'lista', 'title' => 'Lista');
            $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla');
            $columns[] = array('field' => 'ponderado', 'title' => 'Ponderado');
            $columns[] = array('field' => 'porcentaje', 'title' => 'Porcentaje');
            
            //Datos de segundo cuadro (votos)
            //Guardar ultima lista no guardada de votos
            $data2[] = $r2;

            $columns2 = array();
            $columns2[] = array('field' => 'sigla_lista', 'title' => 'Lista');
            foreach($total2 as $key => $value){
                if($key != 'total'){ 
                    $columns2[] = array('field' => $key, 'title' => $key);

                     $total2[$key] = $value+$bnr['Blancos'][$key]+
                                        $bnr['Nulos'][$key]+
                                        $bnr['Recurridos'][$key];

                     $bnr['Blancos']['total'] += $bnr['Blancos'][$key];
                     $bnr['Nulos']['total'] += $bnr['Nulos'][$key];
                     $bnr['Recurridos']['total'] += $bnr['Recurridos'][$key];

                     $empadronados['total'] += $empadronados[$key];
                }
            }
            //Guardar filas de blancos, nulos y recurridos
            $bnr['Blancos']['sigla_lista'] = 'Blancos';
            $bnr['Nulos']['sigla_lista'] = 'Nulos';
            $bnr['Recurridos']['sigla_lista'] = 'Recurridos';
            $data2[] = $bnr['Blancos'];
            $data2[] = $bnr['Nulos'];
            $data2[] = $bnr['Recurridos'];
            
            //Guardar fila de cantidad de votantes totales
            $total2['sigla_lista'] = 'Votantes';
            $total2['total'] += $bnr['Blancos']['total']+$bnr['Nulos']['total']+$bnr['Recurridos']['total'];
            $data2[] = $total2;
            
            $empadronados['sigla_lista'] = 'Empadronados';
            $data2[] = $empadronados;
            
            $columns2[] = array('field' => 'total', 'title' => 'Total');
            
           //Armado del json
            $json = array();            
            $json['data'] = $data;
            $json['columns'] = $columns;
            
            $json['data2'] = $data2;
            $json['columns2'] = $columns2;

            $json['titulo_grafico'] = 'PORCENTAJE DE VOTOS PONDERADOS SOBRE MESAS CARGADAS';
            $json['labels'] = $labels;
            $json['total'] = $porcentajes;
            $json['fecha'] = date('d/m/Y G:i:s');
            $json['titulo'] = 'Votos Universidad Rector';
            $json['titulo2'] = 'Votos Universidad Rector';
            $json['enviadas'] = round($m_enviadas*100/$m_total, 2).'% ('.$m_enviadas." de ".$m_total.')';
            $json['confirmadas'] = round($m_confirmadas*100/$m_total, 2).'% ('.$m_confirmadas." de ".$m_total.')';
            
            //Crea JSON con todos los datos recolectados
            $string_json = json_encode($json);

            $nom_archivo = 'e'.str_replace('-','',$fecha).'/R_TODO_T';
            file_put_contents('resultados_json/'.$nom_archivo . '.json', $string_json);
        }
    }

