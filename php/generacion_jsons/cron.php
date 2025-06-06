<?php
include_once 'rector.php';
include_once 'rector_claustro.php';
include_once 'superior_claustro.php';
include_once 'datos_ue.php';
include_once 'ue_claustro.php';
include_once 'dhont.php';
include_once 'max_fecha_modificacion.php';
include_once 'estado_mesas.php';

// ubicarse www
// para ejecutar toba item ejecutar -p gu_kena -t 10000050

$fecha = '2025-06-10';
//$fecha = '2022-06-14';
// $fecha = '2019-05-14';
//$fecha = '2018-10-05';
//$fecha = '2018-08-22';
//$fecha = '2018-06-05';
//$fecha = '2018-05-22';
//$fecha = '2017-05-16';
//$fecha = '2016-05-17';
//$fecha = '2015-06-16';
//
$res = max_fecha_modificacion($fecha);
if ($res['existe_mod']) { //Existen modificaciones ent generar jsons

    //Genera un JSON del estado de las mesas
    estado_mesas($fecha);
    
    //Genera un JSON de total rector
    datos_rector($fecha);

    //Genera 4 JSONS de total rector por claustro
    datos_rector_claustro($fecha);
    //Genera 4 JSONS de total consejero superior por claustro
    datos_sup_claustro($fecha);

    //Genera 18 JSONS de total rector por unidad electoral
    datos_ue($fecha, 'voto_lista_rector', 'lista_rector', 'R');
    //Genera 17 JSONS de total decano por unidad electoral
    datos_ue($fecha, 'voto_lista_decano', 'lista_decano', 'D');

    //Genera 17*4 + 1 = 69 JSONS de total rector por claustro y por unidad electoral
    datos_ue_claustro($fecha, 'voto_lista_rector', 'lista_rector', 'Rector/a', 'R');
    //Genera 17*4 = 68 JSONS de total decano por claustro y por unidad electoral
    datos_ue_claustro($fecha, 'voto_lista_decano', 'lista_decano', 'Decano/a', 'D');
    //Genera 17*4 = 68 JSONS de total consejo superior por claustro y por unidad electoral
    datos_ue_claustro($fecha, 'voto_lista_csuperior', 'lista_csuperior', 'Consejero/a Superior', 'CS');
    //Genera 17*4 = 68 JSONS de total consejo directivo por claustro y por unidad electoral
    datos_ue_claustro($fecha, 'voto_lista_cdirectivo', 'lista_cdirectivo', 'Consejero/a Directivo', 'CD');

    if (!is_null($res['fechamax'])) {
        $sql_update = "update acto_electoral 
                            set generacion_json_fecha = '" . $res['fechamax'] . "' 
                            where id_fecha = '$fecha'";
        print_r($sql_update);
        toba::db('gu_kena')->consultar($sql_update);
    }
}
