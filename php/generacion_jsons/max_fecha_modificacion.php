<?php
//Calcula la fecha maxima de modificacion siempre que existan mesas sin estado definitivo
// para la fecha ingresada
function max_fecha_modificacion($fecha) {
    $sql = "select case when fechamax_modificacion is not null 
                            then case when fechamax_generacion is not null 
                                            then case when fechamax_modificacion > fechamax_generacion 
                                                      then 1
                                                      else 0
                                                 end
                                      else 1
                                 end
                            else 0
                   end existe_mod
            from (
                    select max(fechamax) fechamax_modificacion
                    from (
                            select case when pa.fmod is not null 
                                            then case when pv.fmod is not null 
                                                            then case when pv.fmod > pa.fmod 
                                                                            then pv.fmod 
                                                                            else pa.fmod 
                                                                    end
                                                            else pv.fmod
                                                 end
                                        when pv.fmod is not null
                                            then pv.fmod
                                        else pm.auditoria_fecha
                                    end as fechamax, m.fecha
                            from mesa m
                            inner join public_auditoria.logs_mesa pm on pm.id_mesa = m.id_mesa
                            left join (
                                            select max(auditoria_fecha) fmod 
                                            from public_auditoria.logs_acta
                                    ) pa on pa.fmod > pm.auditoria_fecha
                            left join (
                                            select max(auditoria_fecha) fmod 
                                            from public_auditoria.logs_voto_lista_rector
                                            UNION
                                            select max(auditoria_fecha) fmod 
                                            from public_auditoria.logs_voto_lista_decano
                                            UNION
                                            select max(auditoria_fecha) fmod 
                                            from public_auditoria.logs_voto_lista_cdirectivo
                                            UNION
                                            select max(auditoria_fecha) fmod
                                            from public_auditoria.logs_voto_lista_csuperior
                                    ) pv on pv.fmod > pm.auditoria_fecha or pv.fmod > pa.fmod
                            where m.estado < 4
                            and m.fecha = '$fecha'
                    ) t
            ) t,
            (select max(generacion_json_fecha) fechamax_generacion 
            from acto_electoral 
            where id_fecha = '$fecha') x
            ";
    $max = toba::db('gu_kena')->consultar($sql);
    if(sizeof($max)>0 && !is_null($max[0]['existe_mod']))
        return $max[0]['existe_mod'];
    else
        return null;
}

