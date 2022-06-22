<?php

// Resultado general de rector con lista | ponderado
function estado_mesas($fecha) {
    $sql = "select ue.sigla ue, s.sigla sede,c.descripcion claustro,m.nro_mesa,e.descripcion estado, e.id_estado, cant_empadronados  from mesa m inner join sede s on m.id_sede=s.id_sede
inner join unidad_electoral ue on ue.id_nro_ue=s.id_ue
inner join claustro c on m.id_claustro=c.id
inner join estado e on m.estado = e.id_estado
where fecha='$fecha'
order by ue.sigla,  s.sigla , c.descripcion, m.nro_mesa";
    //echo $sql; exit;
    $datos = toba::db('gu_kena')->consultar($sql);

    if (sizeof($datos) > 0) {
        $total = array(); //Contiene la ultima fila de total ponderado
        $total['total'] = 0;
        $total2 = array(); //Contiene la ultima fila de total votantes por columna
        $total2['enviadas'] = 0;
        $total2['confirmadas'] = 0;
        $total['empadronados'] = 0;
        $total2['empadronados'] = 0;
        $data = array(); //Coleccion de filas que forma el cuadro ponderado final
        $data2 = array(); //Coleccion de filas que forma el cuadro votos final
        $r = array(); //Recorre y arma una fila del cuadro final de ponderado
        $r['ponderado'] = 0;
        $r2 = array();
        $r2['total'] = 0;


        //$totales = array();

        foreach ($datos as $un_registro) {
            if ($un_registro['id_estado'] < 2) {
                $data[] = $un_registro;
                $total['empadronados'] += $un_registro['cant_empadronados'];
                $total['total']++;
            } else {
                $total2['empadronados'] += $un_registro['cant_empadronados'];
                if ($un_registro['id_estado']==2) {
                    $total2['enviadas']++;
                    $un_registro['estado']='Enviado Autoridad de Mesa';
                } else {
                    $total2['confirmadas']++;
                    if($un_registro['id_estado']==4){
                        $un_registro['estado']='Confirmado Junta Electoral*2';
                    }else{
                        $un_registro['estado']='Confirmado Junta Electoral';
                    }
                }
                $data2[] = $un_registro;
            }
        }

        $m_enviadas = $total2['enviadas']+$total2['confirmadas'];
        $m_confirmadas = $total2['confirmadas'];
        $m_sin_datos= $total['total'];
        $m_total = sizeof($datos);

        //Guardar Ultima lista no guardada de ponderado
        $labels = ['SIN DATOS '.$m_sin_datos,'CARGADAS '.$m_enviadas,'CONFIRMADAS '.$m_confirmadas];
        $totales = [$m_sin_datos,$m_enviadas,$m_confirmadas];
        $porcentajes = [round($m_sin_datos * 100 / $m_total, 2),
            round($m_enviadas * 100 / $m_total, 2),
            round($m_confirmadas * 100 / $m_total, 2)];


        //Datos de columnas de primer cuadro (ponderado)
        $columns = array();
        $columns[] = array('field' => 'ue', 'title' => 'UE');
        $columns[] = array('field' => 'sede', 'title' => 'Sede');
        $columns[] = array('field' => 'claustro', 'title' => 'Claustro');
        $columns[] = array('field' => 'nro_mesa', 'title' => 'Nro');
        $columns[] = array('field' => 'cant_empadronados', 'title' => 'Empadronados');
        //$columns[] = array('field' => 'estado', 'title' => 'Estado');


        $columns2 = $columns;
        $columns2[] = array('field' => 'estado', 'title' => 'Estado');
        
        //Armado del json
        $json = array();
        $json['data2'] = $data;
        $json['columns2'] = $columns;

        $json['data'] = $data2;
        $json['columns'] = $columns2;

        $json['titulo_grafico'] = 'MESAS UNIVERSIDAD SEGUN ESTADO';
        $json['labels'] = $labels;
        $json['total'] = $porcentajes;
        $json['fecha'] = date('d/m/Y G:i:s');
        $json['titulo'] = 'Estado mesas cargadas Universidad';
        $json['titulo2'] = 'Mesas sin datos';
        $json['enviadas'] = round($m_enviadas * 100 / $m_total, 2) . '% (' . $m_enviadas . " de " . $m_total . ')';
        $json['confirmadas'] = round($m_confirmadas * 100 / $m_total, 2) . '% (' . $m_confirmadas . " de " . $m_total . ')';

        //Crea JSON con todos los datos recolectados
        $string_json = json_encode($json);

        $nom_archivo = 'e' . str_replace('-', '', $fecha) . '/TODO_MESAS';
        file_put_contents('resultados_json/' . $nom_archivo . '.json', $string_json);
    }
}
