<?php
//Metodo que calcula y genera archivos JSONS ubicados en /resultados_json/$fecha
//con datos de resultados rector por cada unidad electoral
function datos_ue($fecha, $tabla_voto, $tabla_lista, $sigla_cat){
    $sql = "
        select datos.*, 
        case when m_enviadas is null then 0 else m_enviadas end as m_enviadas, 
        case when m_confirmadas is null then 0 else m_confirmadas end as m_confirmadas, 
        m_total, empadronados.empadronados
        from (
            select ue.nombre as unidad_electoral, ue.sigla as sigla_ue,
                ue.id_nro_ue as id_ue,
                trim(cl.descripcion) as claustro, 
                l.id_nro_lista, trim(l.nombre) as lista,
                s.sigla as sede, m.nro_mesa,
                trim(l.sigla) as sigla_lista, vl.cant_votos, total.total,
                total.votos_blancos, total.votos_nulos, total.votos_recurridos,
                cast(total.total as real)/vv.votos_validos*ponderacion as ponderado
            from acta a 
            inner join mesa m on m.id_mesa = a.de
            inner join claustro cl on cl.id = m.id_claustro
            inner join $tabla_voto vl on vl.id_acta = a.id_acta
            inner join $tabla_lista l on l.id_nro_lista = vl.id_lista
            inner join sede s on s.id_sede = a.id_sede
            inner join unidad_electoral ue on ue.id_nro_ue = s.id_ue
            inner join (
                select m.id_claustro, s.id_ue,
                        vl.id_lista,  
                        sum(vl.cant_votos) total, sum(total_votos_blancos) votos_blancos,
                        sum(total_votos_nulos) votos_nulos, sum(total_votos_recurridos) votos_recurridos
                from acta a 
                inner join mesa m on m.id_mesa = a.de
                inner join $tabla_voto vl on vl.id_acta = a.id_acta
                inner join sede s on s.id_sede = a.id_sede
                where m.fecha = '$fecha'
                group by s.id_ue, m.id_claustro, vl.id_lista 
            ) total on total.id_claustro = cl.id
                    and total.id_lista = l.id_nro_lista
                    and total.id_ue = s.id_ue
            inner join (
                    select ue.sigla as ue,c.descripcion claustro, 
                    cargos_cdirectivo as ponderacion, sum(cant_votos) as votos_validos
                    from acta a inner join $tabla_voto vl on a.id_acta=vl.id_acta
                            inner join $tabla_lista l on vl.id_lista=l.id_nro_lista
                            inner join mesa m on a.de=m.id_mesa
                            inner join claustro c on m.id_claustro=c.id
                            inner join sede s on a.id_sede=s.id_sede
                            right join unidad_electoral ue on s.id_ue=ue.id_nro_ue
                    where m.fecha = '$fecha' and m.estado > 1
                    group by ue,claustro,ponderacion
                    )vv on vv.ue=ue.sigla and vv.claustro=cl.descripcion
            where l.fecha = '$fecha'
            order by unidad_electoral, claustro, lista, sede
        ) datos  
        inner join (select sum(cant_empadronados) empadronados, cl.descripcion claustro, s.id_ue 
            from mesa m
            inner join claustro cl on cl.id = m.id_claustro
            inner join sede s on s.id_sede = m.id_sede
            where m.fecha='$fecha'
            group by cl.descripcion, s.id_ue
        ) empadronados on empadronados.id_ue = datos.id_ue and empadronados.claustro = datos.claustro      
        inner join (select count(distinct(m.id_mesa))  as m_total, s.id_ue from mesa m
            inner join acta a on a.de = m.id_mesa
            inner join sede s on s.id_sede = a.id_sede
            where m.fecha = '$fecha'
            group by s.id_ue) t on t.id_ue = datos.id_ue
        left join (select count(distinct(m.id_mesa)) as m_confirmadas, s.id_ue from mesa m
            inner join acta a on a.de = m.id_mesa
            inner join sede s on s.id_sede = a.id_sede
            where m.fecha = '$fecha' and m.estado>2
            group by s.id_ue) m2 on m2.id_ue = t.id_ue
        left join (select count(distinct(m.id_mesa)) as m_enviadas, s.id_ue from mesa m
            inner join acta a on a.de = m.id_mesa
            inner join sede s on s.id_sede = a.id_sede
            where m.fecha = '$fecha' and m.estado>1
            group by s.id_ue) m on m.id_ue = t.id_ue
    order by datos.sigla_ue, datos.claustro, datos.sigla_lista
            ";print_r($sql.'----------------------');
    $datos = toba::db('gu_kena')->consultar($sql);

    $nom_ue = null;
    $data = array();//Datos de cuadro de votos finales por claustro
    $data2 = array();//Datos de cuadro de ponderados 

    $columns2 = array();
    $columns2[] = array('field' => 'lista', 'title' => 'Listas');
    $columns2[] = array('field' => 'sigla_lista', 'title' => 'Sigla Listas');
    $columns2[] = array('field' => 'ponderado', 'title' => 'Ponderado');

    $claustros = array();
    $empadronados = array();
    $empadronados['lista'] = 'Empadronados';

    //Datos de mesas
    $m_enviadas = null;
    $m_confirmadas = null;
    $m_total = null;

    $bnr = array();
    $bnr['blancos']['lista'] = 'Blancos';
    $bnr['nulos']['lista'] = 'Nulos';
    $bnr['recurridos']['lista'] = 'Recurridos';

    foreach($datos as $un_registro){
        if($nom_ue != null && $nom_ue != $un_registro['sigla_ue']){
            crear_json_ue($fecha, $sigla_cat, $claustros, $columns2, $data, $ponderados, $empadronados, $bnr, $nom_ue, $m_enviadas, $m_confirmadas, $m_total);

            $data = array();
            $claustros = array();
            $ponderados = array();
            
            $nom_ue = $un_registro['sigla_ue'];                
        }elseif($nom_ue == null)
            $nom_ue = $un_registro['sigla_ue'];

        $data[$un_registro['sigla_lista']]['lista'] = utf8_encode($un_registro['lista']);
        $data[$un_registro['sigla_lista']]['sigla_lista'] = utf8_encode($un_registro['sigla_lista']);

        if(isset($ponderados[$un_registro['sigla_lista']]))
            $ponderados[$un_registro['sigla_lista']] += $un_registro['ponderado'];
        else
            $ponderados[$un_registro['sigla_lista']] = $un_registro['ponderado'];

        if(isset($data[$un_registro['sigla_lista']]['total']))
            $data[$un_registro['sigla_lista']]['total'] += $un_registro['total'];
        else
            $data[$un_registro['sigla_lista']]['total'] = $un_registro['total'];

        $data[$un_registro['sigla_lista']][$un_registro['claustro']] = $un_registro['cant_votos'];

        $claustros[$un_registro['claustro']] = $un_registro['claustro'];
        $empadronados[$un_registro['claustro']] = $un_registro['empadronados'];
        //Datos de mesas
        $m_enviadas = $un_registro['m_enviadas'];
        $m_confirmadas = $un_registro['m_confirmadas'];
        $m_total = $un_registro['m_total'];

        $bnr['blancos'][$un_registro['claustro']] = $un_registro['votos_blancos'];
        $bnr['nulos'][$un_registro['claustro']] = $un_registro['votos_nulos'];
        $bnr['recurridos'][$un_registro['claustro']] = $un_registro['votos_recurridos'];
    }

    if(isset($data) && $nom_ue != null){//Quedo un ultimo claustro sin guardar
        crear_json_ue($fecha, $sigla_cat, $claustros, $columns2, $data, $ponderados, $empadronados, $bnr, $nom_ue, $m_enviadas, $m_confirmadas, $m_total);
    }
}

function crear_json_ue($fecha, $sigla_cat, $claustros, $columns2, $data, $ponderados, $empadronados, $bnr, $nom_ue, $m_enviadas, $m_confirmadas, $m_total){
    $json = array();
                
        $columns = array();
        $columns[] = array('field' => 'lista', 'title' => 'Listas');
        $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla Listas');
        foreach($claustros as $key => $value){
            $columns[] = array('field' => $key, 'title' => $key);
        }
        $columns[] = array('field' => 'total', 'title' => 'Total');

        $fila_total = array();//Ultima fila que contiene los totales de cada columna
        $fila_total2 = array(); //Ultima fila que contiene los totales de ponderados
        $fila_total['lista'] = 'Votantes'; 
        
        $fila_total2['lista'] = 'Total'; 
        $fila_total2['ponderado'] = 'Ponderado'; 
        $data2 = array();//cuadro de ponderados
        
        //Carga valores de blancos, nulos y recurridos
        foreach($claustros as $k => $v){
            $bnr['blancos']['total'] += $bnr['blancos'][$k];
            $bnr['nulos']['total'] += $bnr['nulos'][$k];
            $bnr['recurridos']['total'] += $bnr['recurridos'][$k];

            $empadronados['total'] += $empadronados[$k];
            
            $fila_total[$k] = $bnr['blancos'][$k]+$bnr['nulos'][$k]+$bnr['recurridos'][$k];
        }
        $fila_total['total'] = $bnr['blancos']['total']+$bnr['nulos']['total']+$bnr['recurridos']['total'];
        
        //calcula valores totales
        foreach($data as $key => $value){
            //calcula valores totales de cada fila
            foreach($claustros as $k => $v)
                $fila_total[$k] += $value[$k];
            
            $fila_total['total'] += $value['total'];

            $r_data['lista'] = $value['lista'];
            $r_data['sigla_lista'] = $value['sigla_lista'];
            $r_data['ponderado'] = $ponderados[$value['sigla_lista']];
            $json['data'][] = $r_data;
            
            $fila_total2['ponderado'] += $ponderados[$value['sigla_lista']];

            $json['data2'][] = $value;
            $json['labels'][] = $value['sigla_lista'];
            $json['total'][] = $ponderados[$value['sigla_lista']];
        }
        $json['data2'][] = $bnr['blancos'];
        $json['data2'][] = $bnr['nulos'];
        $json['data2'][] = $bnr['recurridos'];
        $json['data2'][] = $fila_total;
        $json['data2'][] = $empadronados;
        $json['columns2'] = $columns;
        
        $json['data'][] = $fila_total2;
        $json['columns'] = $columns2;
        
        $json['fecha'] = date('d/m/Y G:i:s');
        if(strtoupper($nom_ue) == 'RECT'){
            $json['titulo'] = 'Votos Ponderados Adm. Central '.($sigla_cat=='R'?'Rector':'Decano');
            $json['titulo2'] = 'Votos Adm. Central '.($sigla_cat=='R'?'Rector':'Decano');
        }else{
            $json['titulo'] = 'Votos Ponderados '.$nom_ue.' '.($sigla_cat=='R'?'Rector':'Decano');
            $json['titulo2'] = 'Votos '.$nom_ue.' '.($sigla_cat=='R'?'Rector':'Decano');
        }
        $json['enviadas'] = round($m_enviadas*100/$m_total, 2).'% ('.$m_enviadas." de ".$m_total.')';
        $json['confirmadas'] = round($m_confirmadas*100/$m_total, 2).'% ('.$m_confirmadas." de ".$m_total.')';

        $string_json = json_encode($json);
        $nom_archivo = 'e'.str_replace('-','',$fecha).'/'.$sigla_cat.'_'.strtoupper($nom_ue).'_T.json';
        file_put_contents('resultados_json/'. $nom_archivo , $string_json);
        
        $bnr = array();
        $bnr['blancos']['lista'] = 'Blancos';
        $bnr['nulos']['lista'] = 'Nulos';
        $bnr['recurridos']['lista'] = 'Recurridos';
        $bnr['blancos']['total'] = 0;
        $bnr['nulos']['total'] = 0;
        $bnr['recurridos']['total'] = 0;
        
        $empadronados = array();
        $empadronados['lista'] = 'Empadronados';
        $empadronados['total'] = 0;
}
