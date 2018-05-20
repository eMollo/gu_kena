
<?php

class ci_rector extends toba_ci {

    protected $s__votos_e;
    protected $s__votos_g;
    protected $s__votos_nd;
    protected $s__votos_d;
    protected $s__total_emp;
    protected $s__fecha = '2016-05-17';
    
    //---- Cuadro -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    //---- cuadro_rector_e ------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__cuadro_rector_e(gu_kena_ei_cuadro $cuadro) {
        $res = $this->cargar_cuadro_rector('cuadro_rector_e', 3); // 3 = Estudiantes 
        return $res;
    }

    //-----------------------------------------------------------------------------------
    //---- cuadro_rector_g ------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__cuadro_rector_g(gu_kena_ei_cuadro $cuadro) {
        $res = $this->cargar_cuadro_rector('cuadro_rector_g', 4); // 4 = Graduados
        return $res;
    }

    //-----------------------------------------------------------------------------------
    //---- cuadro_rector_nd -----------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__cuadro_rector_nd(gu_kena_ei_cuadro $cuadro) {
        $res = $this->cargar_cuadro_rector('cuadro_rector_nd', 1); // 1 = No docente
        return $res;
    }

    //-----------------------------------------------------------------------------------
    //---- cuadro_rector_d ------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__cuadro_rector_d(gu_kena_ei_cuadro $cuadro) {
        $res = $this->cargar_cuadro_rector('cuadro_rector_d', 2); // 2 = Docente
        return $res;
    }

    function cargar_cuadro_rector($cuadro, $id_claustro) {
        //Obtengo todas las unidades electorales
        $unidades = $this->dep('datos')->tabla('unidad_electoral')->get_descripciones();

        //Cargar la cantidad de empadronados para el claustro $id_claustro
        // en cada unidad como segunda columna
        $ar = $this->cargar_cant_empadronados($unidades, $id_claustro);
        //Cargar la cantidad de votantes para el claustro estudiantes=3
        // en cada unidad como tercer columna
        $ar = $this->cargar_cant_votantes($ar, $listas);
        //Ante ultima fila carga los votos totales de cada lista
        $pos = sizeof($ar);
        $ar[$pos]['sigla'] = "<span style='color:blue; font-weight:bold'>TOTAL</span>";
        $ar[$pos]['cant_empadronados'] = "<span style='color:blue; font-weight:bold'>" . $this->s__total_emp . "</span>";

        //Ultima fila carga los votos ponderados de cada lista
        $pos = sizeof($ar);
        $ar[$pos]['sigla'] = "<span style='color:red; font-weight:bold'>PONDERADOS</span>";

        //Obtener las listas del claustro estudiantes=3
        $listas = $this->dep('datos')->tabla('lista_rector')->get_listas($this->s__fecha);

        //Agregar las etiquetas de todas las listas (columnas dinámicas)
        $i = 1;
        foreach ($listas as $lista) {
            $l['clave'] = $lista['id_nro_lista'];
            $l['titulo'] = substr($lista['nombre'], 0, 11) . $lista['sigla'];
            $l['estilo'] = 'col-cuadro-resultados';
            $l['estilo_titulo'] = 'tit-cuadro-resultados';

            $l['permitir_html'] = true;

            $grupo[$i] = $lista['id_nro_lista'];

            $columnas[$i] = $l;
            $this->dep($cuadro)->agregar_columnas($columnas);

            //Cargar la cantidad de votos para cada lista de claustro $id_claustro 
            //en cada unidad
            $ar = $this->cargar_cant_votos($lista['id_nro_lista'], $ar, $id_claustro);

            //Cargar los votos totales/ponderados para cada lista agregado como ante/última fila
            //para claustro estudiantes=3
            $ar[$pos - 1][$lista['id_nro_lista']] = 0;
            $ar[$pos][$lista['id_nro_lista']] = 0;
            $ar = $this->cargar_votos_totales_ponderados($lista['id_nro_lista'], $ar, $id_claustro);

            $i++;
        }
        $this->dep($cuadro)->set_grupo_columnas('Listas', $grupo);


        $this->s__votos_e = $ar; //Guardar los votos para el calculo dhondt
        //Agregar datos totales de blancos, nulos y recurridos
        $b['clave'] = 'total_votos_blancos';
        $b['titulo'] = 'Blancos';
        $b['estilo'] = 'col-cuadro-resultados';
        $b['estilo_titulo'] = 'tit-cuadro-resultados';
        $bnr[0] = $b;

        $n['clave'] = 'total_votos_nulos';
        $n['titulo'] = 'Nulos';
        $n['estilo'] = 'col-cuadro-resultados';
        $n['estilo_titulo'] = 'tit-cuadro-resultados';
        $bnr[1] = $n;

        $r['clave'] = 'total_votos_recurridos';
        $r['titulo'] = 'Recurridos';
        $r['estilo'] = 'col-cuadro-resultados';
        $r['estilo_titulo'] = 'tit-cuadro-resultados';
        $bnr[2] = $r;

        $this->dep($cuadro)->agregar_columnas($bnr);


        $ar = $this->cargar_cant_b_n_r($ar, $id_claustro);

        //$this->cambiar_estilo_total($ar); //ver porqué no agrega estilo
        //print_r($ar);
        return $ar;
    }

    //Metodo responsable de cargar los votos blancos, nulos y recurridos de cada unidad electoral
    function cargar_cant_b_n_r($unidades, $id_claustro) {
        $p = sizeof($unidades) - 2;
        //Inicializo para realizar la sumatoria
        $unidades[$p]['total_votos_blancos'] = 0;
        $unidades[$p]['total_votos_nulos'] = 0;
        $unidades[$p]['total_votos_recurridos'] = 0;
        for ($i = 0; $i < $p; $i++) {//Recorro las unidades
            //Agrega la cantidad de votos blancos,nulos y recurridos calculado en acta para cada unidad con claustro y tipo rector=4            
            $ar = $this->dep('datos')->tabla('acta')->cant_b_n_r($unidades[$i]['id_nro_ue'], $id_claustro, 4);
            if (sizeof($ar) > 0) {
                $unidades[$i]['total_votos_blancos'] = $ar[0]['blancos'];
                $unidades[$i]['total_votos_nulos'] = $ar[0]['nulos'];
                $unidades[$i]['total_votos_recurridos'] = $ar[0]['recurridos'];

                //Agrego en la anteultima fila la sumatoria total

                $unidades[$p]['total_votos_blancos'] += $ar[0]['blancos'];
                $unidades[$p]['total_votos_nulos'] += $ar[0]['nulos'];
                $unidades[$p]['total_votos_recurridos'] += $ar[0]['recurridos'];
            }
        }
        return $unidades;
    }

    //Metodo responsable de cargar la segunda columna con la cantidad de empadronados
    // en cada unidad electoral
    function cargar_cant_empadronados($unidades, $id_claustro) {
        $this->s__total_emp = 0;
        for ($i = 0; $i < sizeof($unidades); $i++) {//Recorro las unidades
            //Agrega la cantidad de empadronados calculado en acta para cada unidad con claustro
            $unidades[$i]['cant_empadronados'] = $this->dep('datos')->tabla('mesa')->cant_empadronados($unidades[$i]['id_nro_ue'], $id_claustro);
            $this->s__total_emp += $unidades[$i]['cant_empadronados'];
        }
        return $unidades;
    }

    function cargar_cant_votos($id_lista, $unidades, $id_claustro) {
        for ($i = 0; $i < sizeof($unidades) - 2; $i++) {//Recorro las unidades
            //Agrega la cantidad de empadronados calculado en acta para cada unidad con claustro  y tipo 'rector'
            $unidades[$i][$id_lista] = $this->dep('datos')->tabla('voto_lista_rector')->cant_votos($id_lista, $unidades[$i]['id_nro_ue'], $id_claustro);
        }
        return $unidades;
    }

    function cargar_cant_votantes($unidades, $listas) {
        //realiza la suma de los votos que hay en las distintas columnas
        $p = sizeof($unidades) - 2;
        $cant_columnas = sizeof($listas);
        $unidades[$p]['cant_votantes'] = 0;
        //Inicializo para realizar la sumatoria
        $cant_total = 0;
        for ($i = 0; $i < $p; $i++) { //Recorre las unidades
            $votantes_ue = 0;
            for ($c = 0; $c < $cant_columnas; $c++) {
                $votantes_ue += $unidades[$i][($listas[$c]['id_nro_lista'])];
            }
            $votantes_ue += $unidades[$i]['total_votos_blancos'];
            $votantes_ue += $unidades[$i]['total_votos_nulos'];
            $votantes_ue += $unidades[$i]['total_votos_recurridos'];
            $unidades[$i]['cant_votantes'] = $votantes_ue;
            $cant_total += $votantes_ue;
        }
        $unidades[$p]['cant_votantes'] = $cant_total;
        return $unidades;
    }

    function cargar_votos_totales_ponderados($id_lista, $unidades, $id_claustro) {
        $pos_total = sizeof($unidades) - 2; //Fila que contiene los votos totales
        $pos_pond = sizeof($unidades) - 1; //Fila que contiene los votos ponderados
        //Recorro las unidades exluyendo las dos últimas filas que tiene los votos totales y ponderados
        for ($i = 0; $i < $pos_total; $i++) {
            if (isset($unidades[$i][$id_lista]) && isset($unidades[$i]['cant_validos'])) {
                //Suma el cociente entre cant de votos de la 
                //lista en la UEn / cant votos validos del claustro en la UEn
                $cociente = $unidades[$i][$id_lista] / $unidades[$i]['cant_votantes'];
                $unidades[$pos_pond][$id_lista] += $cociente;
            }

            if (isset($unidades[$i][$id_lista])) {
                //Suma los votos 
                $unidades[$pos_total][$id_lista] += $unidades[$i][$id_lista];
            }
        }
        $unidades[$pos_pond][$id_lista] = "<span style='color:red'>" . round($unidades[$pos_pond][$id_lista], 6) . "</span>";

        return $unidades;
    }

    //-----------------------------------------------------------------------------------
    //---- Configuraciones --------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf() {
        $claustros = $this->dep('datos')->tabla('mesa')->get_claustro_novota($this->s__fecha);

        foreach ($claustros as $key => $claustro) {
            switch ($claustro['id']) {
                case 1: //No hay votacion de no docentes, ent ocultar pantalla
                    $this->pantalla()->tab('pant_no_docente')->ocultar();
                    break;
                case 2: //No hay votacion de docentes, ent ocultar pantalla
                    $this->pantalla()->tab('pant_docente')->ocultar();
                    break;
                case 3: //No hay votacion de estudiantes, ent ocultar pantalla
                    $this->pantalla()->tab('pant_estudiantes')->ocultar();
                    break;
                case 4: //No hay votacion de graduados, ent ocultar pantalla
                    $this->pantalla()->tab('pant_graduados')->ocultar();
                    break;
            }
        }

        $this->generar_json('2018-05-22');
        
    }

    function generar_json($fecha) {
        //Genera un JSON de total rector
        $this->datos_rector($fecha);
        
        //Genera 4 JSONS de total rector por claustro
        $this->datos_rector_claustro($fecha);
        //Genera 4 JSONS de total consejero superior por claustro
        $this->datos_sup_claustro($fecha);
        
        //Genera 18 JSONS de total rector por unidad electoral
        $this->datos_ue($fecha, 'voto_lista_rector', 'lista_rector', 'R');
        //Genera 17 JSONS de total decano por unidad electoral
        $this->datos_ue($fecha, 'voto_lista_decano', 'lista_decano', 'D');
        
        //Genera 17*4 + 1 = 69 JSONS de total rector por claustro y por unidad electoral
        $this->datos_ue_claustro($fecha, 'voto_lista_rector', 'lista_rector', 'Rector', 'R');
        //Genera 17*4 = 68 JSONS de total decano por claustro y por unidad electoral
        $this->datos_ue_claustro($fecha, 'voto_lista_decano', 'lista_decano', 'Decano', 'D');
        //Genera 17*4 = 68 JSONS de total consejo superior por claustro y por unidad electoral
        $this->datos_ue_claustro($fecha, 'voto_lista_csuperior', 'lista_csuperior', 'Consejero Superior', 'CS');
        //Genera 17*4 = 68 JSONS de total consejo directivo por claustro y por unidad electoral
        $this->datos_ue_claustro($fecha, 'voto_lista_cdirectivo', 'lista_cdirectivo', 'Consejero Directivo', 'CD');
    }
    
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
                    l.sigla as sigla_lista, vl.cant_votos,
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
        if($un_registro['estado']>2) $sigla_sede.='*';
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
                    $json['labels'] = $labels;
                    $json['total'] = $total;
                    $columns = array();
                    $columns[] = array('field' => 'lista', 'title' => 'Listas');
                    $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla');
                    foreach ($sedes as $sede) {
                        $columns[] = array('field' => $sede, 'title' => $sede);
                    }
                    $columns[] = array('field' => 'total', 'title' => 'Total');
                    $json['columns'] = $columns;
                    if ($sigla_cat == 'CD') {
                        $res = $this->dhont($labels, $total, $cant_cargos, $sigla_cat);
                        $json['data2'] = $res[1];
                        $json['columns2'] = $res[0];
                        $json['titulo2'] = 'Distribución de cargos a ocupar';
                    }
                    $json['fecha'] = date('d/m/Y G:i:s');
                    $json['titulo'] = 'Votos ' . $nom_ue . ' ' . $categoria . ' ' . $nom_claustro;
                    $json['enviadas'] = round($m_enviadas * 100 / $m_total, 2) . '% (' . $m_enviadas . " de " . $m_total . ')';
                    $json['confirmadas'] = round($m_confirmadas * 100 / $m_total, 2) . '% (' . $m_confirmadas . " de " . $m_total . ')';
                    //print_r($json);exit;
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

    if (sizeof($data) > 0) {//Solo si existen datos finales ent crea el json
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
        $json['labels'] = $labels;
        $json['total'] = $total;
        //Calculo dhont solo para cons.  directivo, los 
        // unicos que tendran este campo cargado
        if ($sigla_cat == 'CD') {
            $res = $this->dhont($labels, $total, $cant_cargos, $sigla_cat);
            $json['data2'] = $res[1];
            $json['columns2'] = $res[0];
            $json['titulo2'] = 'Distribución de cargos a ocupar';
        }

        $json['fecha'] = date('d/m/Y G:i:s');
        $json['titulo'] = 'Votos ' . $nom_ue . ' ' . $categoria . ' ' . $nom_claustro;

        $json['enviadas'] = round($m_enviadas * 100 / $m_total, 2) . '% (' . $m_enviadas . " de " . $m_total . ')';
        $json['confirmadas'] = round($m_confirmadas * 100 / $m_total, 2) . '% (' . $m_confirmadas . " de " . $m_total . ')';

        $string_json = json_encode($json);
        $nom_archivo = 'e' . str_replace('-', '', $fecha) . '/' . $sigla_cat . '_' . strtoupper($nom_ue) . '_' . strtoupper($nom_claustro[0]) . '.json';
        file_put_contents('resultados_json/' . $nom_archivo, $string_json);
    }
}
    
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
                trim(l.sigla) as sigla_lista, sum(vl.cant_votos) cant_votos, total.total,
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
            group by ue.nombre, ue.sigla,
                ue.id_nro_ue, cl.descripcion, 
                l.id_nro_lista, l.nombre,
                l.sigla, total.total,
                total.votos_blancos, total.votos_nulos, total.votos_recurridos,
                vv.votos_validos, ponderacion
            order by unidad_electoral, claustro, lista
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
                ";
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
                $this->crear_json_ue($fecha, $sigla_cat, $claustros, $columns2, $data, $ponderados, $empadronados, $bnr, $nom_ue, $m_enviadas, $m_confirmadas, $m_total);
                
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
            
            if(isset($data[$un_registro['sigla_lista']][$un_registro['claustro']]))
                $data[$un_registro['sigla_lista']][$un_registro['claustro']] += $un_registro['cant_votos'];
            else
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
            $this->crear_json_ue($fecha, $sigla_cat, $claustros, $columns2, $data, $ponderados, $empadronados, $bnr, $nom_ue, $m_enviadas, $m_confirmadas, $m_total);
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
    
    //Metodo que calcula y genera archivos JSONS ubicados en /resultados_json/$fecha
    //con datos de resultados consejero superior por cada claustro
    function datos_sup_claustro($fecha){
        $sql = "
             select datos.*, t.empadronados,
            case when m_enviadas is null then 0 else m_enviadas end as m_enviadas,
            case when m_confirmadas is null then 0 else m_confirmadas end as m_confirmadas, 
            m_total
            from (
                select claustro, id_claustro,
                    trim(lista) as lista, trim(sigla_lista) as sigla_lista, 
                    cargos_csuperior as cant_cargos,
                    
                    sum(ponderado) ponderado,
                    sum(total) votos_lista, sum(votos_blancos) votos_blancos,
                    sum(votos_nulos) votos_nulos, sum(votos_recurridos) votos_recurridos
                from(
                    select votos_totales.id_tipo, votos_totales.id_nro_ue, 
                        votos_totales.sigla as sigla_ue, votos_totales.id_claustro, votos_totales.claustro, 
                        votos_totales.id_nro_lista, votos_totales.lista, votos_totales.sigla_lista, 
                        votos_totales.total, empadronados.empadronados, votos_totales.cargos_csuperior,
                        case when empadronados.empadronados <> 0 then 
                                votos_totales.total/cast(empadronados.empadronados as decimal) 
                        end ponderado,
                        votos_blancos, votos_nulos, votos_recurridos 
                    from (select a.id_tipo, ue.id_nro_ue, ue.sigla, 
                        m.id_claustro as id_claustro, c.descripcion claustro, 
                        l.id_nro_lista, l.nombre lista, c.cargos_csuperior,
                        l.sigla sigla_lista, sum(cant_votos) total,
                        sum(total_votos_blancos) votos_blancos, sum(total_votos_nulos) votos_nulos,
                        sum(total_votos_recurridos) votos_recurridos
                        from acta a 
                        inner join mesa m on m.id_mesa = a.de 
                        inner join sede s on s.id_sede = a.id_sede 
                        inner join unidad_electoral ue on ue.id_nro_ue = s.id_ue 
                        inner join claustro c on c.id = m.id_claustro 
                        inner join voto_lista_csuperior vl on vl.id_acta = a.id_acta 
                        inner join lista_csuperior l on l.id_nro_lista = vl.id_lista 
                        where m.estado > 1 and m.fecha = '$fecha' 
                        group by ue.id_nro_ue, ue.sigla, c.descripcion, l.nombre, l.id_nro_lista, l.sigla, s.id_ue, 
                            m.id_claustro, a.id_tipo, c.cargos_csuperior  
                    order by s.id_ue,m.id_claustro, l.nombre 
                ) votos_totales
                inner join (select id_tipo, id_ue, ue.sigla, id_claustro, sum(cant_empadronados) empadronados 
                        from sede s 
                        inner join acta a on a.id_sede = s.id_sede
                        inner join mesa m on m.id_mesa = a.de 
                        inner join unidad_electoral ue on ue.id_nro_ue = s.id_ue 
                        where m.fecha = '$fecha' 
                        group by id_ue, id_claustro, ue.sigla, id_tipo 
                ) empadronados on empadronados.id_ue = votos_totales.id_nro_ue 
                            and empadronados.id_claustro = votos_totales.id_claustro
                            and empadronados.id_tipo = votos_totales.id_tipo
                ) t
                group by id_tipo, claustro, id_claustro, lista, sigla_lista, cargos_csuperior
                
            ) datos
            inner join (
                select count(*)  as m_total,sum(cant_empadronados) as empadronados, m.id_claustro from mesa m
                where m.fecha = '$fecha'
                group by m.id_claustro) t on t.id_claustro = datos.id_claustro
            left join (
                select count(*) as m_confirmadas, m.id_claustro from mesa m
                where m.fecha = '$fecha' and m.estado>2
                group by m.id_claustro) m2 on m2.id_claustro = t.id_claustro
            left join (
                select count(*) as m_enviadas, m.id_claustro from mesa m
                where m.fecha = '$fecha' and m.estado>1
                group by m.id_claustro) m on m.id_claustro = t.id_claustro
                order by claustro, lista";
        //echo $sql; exit;


        $datos = toba::db('gu_kena')->consultar($sql);

        $columns = array();
        $columns[] = array('field' => 'lista', 'title' => 'Listas');
        $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla Listas');
        $columns[] = array('field' => 'votos', 'title' => 'Votos');
        $columns[] = array('field' => 'ponderado', 'title' => 'Ponderado');
        
        $nom_claustro = null;
        $cant_cargos =null;
        $data = array();
        $data2 = array();
        $columns2 = array();
        $total_votos=0;
        $empadronados=null;
        //Datos de mesas
        $m_enviadas = null;
        $m_confirmadas = null;
        $m_total = null;
        
        $blancos = null;
        $nulos = null;
        $recurridos = null;
        foreach($datos as $un_registro){
            if($nom_claustro != null && $nom_claustro != $un_registro['claustro']){
                $json = array();
                
                $data[] = array('lista' => 'Blancos', 'votos' => $blancos);
                $data[] = array('lista' => 'Nulos', 'votos' => $nulos);
                $data[] = array('lista' => 'Recurridos', 'votos' => $recurridos);
                $data[] = array('lista' => 'Votantes', 'votos' => $total_votos+$blancos+$nulos+$recurridos);
                $data[] = array('lista' => 'Empadronados', 'votos' => $empadronados);
                $json['columns'] = $columns;
                $json['data'] = $data;
                $json['labels'] = $labels;
                $json['total'] = $total;
                $json['fecha'] = date('d/m/Y G:i:s');
                $json['titulo'] = 'Votos Ponderados Universidad Consejero Superior '.$nom_claustro;
                
                //Formula dhont
                $res = $this->dhont($labels, $total, $cant_cargos);
                $json['titulo2']='Distribución de cargos a ocupar';
                $json['data2'] = $res[1];
                $json['columns2'] = $res[0];
                
                $json['enviadas'] = round($m_enviadas*100/$m_total, 2).'% ('.$m_enviadas." de ".$m_total.')';
                $json['confirmadas'] = round($m_confirmadas*100/$m_total, 2).'% ('.$m_confirmadas." de ".$m_total.')';
                
                $data = array();
                $labels = array();
                $total = array();
                $total_votos=0;
                
                $string_json = json_encode($json);
                $nom_archivo = 'e'.str_replace('-','',$fecha).'/CS_TODO_'.strtoupper($nom_claustro[0]).'.json';
                file_put_contents('resultados_json/'.$nom_archivo , $string_json);
                
                $nom_claustro = $un_registro['claustro'];                
            }elseif($nom_claustro == null)
                $nom_claustro = $un_registro['claustro'];
            
            $r['lista'] = utf8_encode($un_registro['lista']);
            $r['sigla_lista'] = $un_registro['sigla_lista'];
            $r['ponderado'] = $un_registro['ponderado'];
            $r['votos'] = $un_registro['votos_lista'];
            $total_votos+=$r['votos'];
            $labels[] = $un_registro['sigla_lista'];
            $total[] = $un_registro['ponderado'];
            $cant_cargos = $un_registro['cant_cargos'];
            
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
        
        if(isset($data) && $nom_claustro != null){//Quedo un ultimo claustro sin guardar
            $json = array();
            
            $data[] = array('lista' => 'Blancos', 'votos' => $blancos);
            $data[] = array('lista' => 'Nulos', 'votos' => $nulos);
            $data[] = array('lista' => 'Recurridos', 'votos' => $recurridos);
            $data[] = array('lista' => 'Votantes', 'votos' => $total_votos+$blancos+$nulos+$recurridos);
            $data[] = array('lista' => 'Empadronados', 'votos' => $empadronados);    
            $json['columns'] = $columns;
            $json['data'] = $data;
            $json['labels'] = $labels;
            $json['total'] = $total;
            
            //Formula dhont
            $res = $this->dhont($labels, $total, $cant_cargos);
            $json['data2'] = $res[1];
            $json['columns2'] = $res[0];
            $json['titulo2']='Distribución de cargos a ocupar';
            
            $json['fecha'] = date('d/m/Y G:i:s');
            $json['titulo'] = 'Votos Ponderados Universidad Consejero Superior '.$nom_claustro;
            
            $json['enviadas'] = round($m_enviadas*100/$m_total, 2).'% ('.$m_enviadas." de ".$m_total.')';
            $json['confirmadas'] = round($m_confirmadas*100/$m_total, 2).'% ('.$m_confirmadas." de ".$m_total.')';
                
            $string_json = json_encode($json);

            $nom_archivo = 'e'.str_replace('-','',$fecha).'/CS_TODO_'.strtoupper($nom_claustro[0]);
            file_put_contents('resultados_json/'.$nom_archivo . '.json', $string_json);
        }
    }
    
    function dhont($listas, $valores, $escanos) {
        /*
         * Se multiplicarán por diez (10.000) los votos ponderados obtenidos por cada lista y se los
          dividirá desde uno (1) y hasta el total de cargos a ocupar.
          Luego, se agruparán en forma decreciente tantos cocientes como cargos a 
         * ocupar, sin considerar en que Listas se han obtenido. De esta manera 
         * se establecerá el "número repartidor", que es el menor de los cocientes 
         * citados.
          A continuación se dividirá la cantidad de votos lograda por cada Lista por 
         * el "número repartidor". El resultado obtenido dará el número de cargos 
         * que se adjudicará a cada una de ellas.

         */
        $escano_max = 0;
        $datos = array();
        if (count($listas) > 0 && count($listas) == count($valores)) {
            $cocientes = array();
            for ($index1 = 0; $index1 < count($valores); $index1++) {
                $valores[$index1] = $valores[$index1] * 10000;
            }
            foreach ($valores as $value) {
                for ($index = 1; $index <= $escanos; $index++) {
                    $cocientes[] = $value / $index;
                }
            }
            sort($cocientes);
            //print_r($cocientes);
            $repartidor = $cocientes[count($cocientes) - $escanos];

            $datos = array();
            foreach ($listas as $key => $lista) {
                $fila = array('lista' => $lista,
                    'escanos' => floor($valores[$key] / $repartidor));
                for ($index2 = 1; $index2 <= $escanos; $index2++) {
                    $fila[$index2] = floor($valores[$key] / $index2);
                    if ($index2 <= $fila['escanos']) {
                        if ($index2 > $escano_max)
                            $escano_max = $index2;
                        $orden = count($cocientes) - array_search($valores[$key] / $index2, $cocientes);
                        $fila[$index2] = "($orden)" . $fila[$index2];
                    }
                }
                $datos[] = $fila;
            }
        }
        $columns = array();
        $columns[] = array('field' => 'lista', 'title' => 'Listas');
        $columns[] = array('field' => 'escanos', 'title' => 'Escaños');
        for ($index3 = 1; $index3 <= $escano_max; $index3++) {
            $columns[] = array('field' => $index3, 'title' => $index3);
        }
        return array($columns, $datos);
    }
    
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
											where m.estado > 1 and m.fecha = '2018-05-22' 
											group by ue.id_nro_ue, ue.sigla, c.descripcion, l.nombre, l.id_nro_lista, l.sigla, s.id_ue, 
													  m.id_claustro, a.id_tipo 
											order by s.id_ue,m.id_claustro, l.nombre 
                						) votos_totales
											inner join (select id_ue, id_claustro, 
										case ue.nivel when 2 then cargos_cdirectivo
											when 3 then cargos_cdiras
										end as mult, 
										sum(cant_votos) validos 
											from sede s 
											inner join acta a on a.id_sede = s.id_sede 
											inner join mesa m on m.id_mesa = a.de 
											inner join voto_lista_rector vl on vl.id_acta = a.id_acta 
											inner join claustro cl on cl.id = m.id_claustro
											inner join unidad_electoral ue on ue.id_nro_ue = s.id_ue
											where m.estado > 1 and m.fecha = '2018-05-22' 
											group by id_ue, id_claustro, cargos_csuperior, ue.nivel, 
														cargos_cdirectivo, cargos_cdiras  
                						) validos on validos.id_ue = votos_totales.id_nro_ue 
                            			and validos.id_claustro = votos_totales.id_claustro
									) t
									group by id_tipo, claustro, lista, sigla_lista, id_claustro
									
								) datos
           inner join (
                select count(*)  as m_total ,sum(cant_empadronados) as empadronados, m.id_claustro from mesa m
                where m.fecha = '2018-05-22'
                group by m.id_claustro) t on t.id_claustro = datos.id_claustro
			left outer join (
                select count(*) as m_enviadas, m.id_claustro from mesa m
                where m.fecha = '2018-05-22' and m.estado>1
                group by m.id_claustro) m on m.id_claustro = datos.id_claustro
            left outer join (
                select count(*) as m_confirmadas, m.id_claustro from mesa m
                where m.fecha = '2018-05-22' and m.estado>2
                group by m.id_claustro) m2 on m2.id_claustro = datos.id_claustro
                order by claustro, lista
            

";

        $datos = toba::db('gu_kena')->consultar($sql);

        $columns = array();
        $columns[] = array('field' => 'lista', 'title' => 'Listas');
        $columns[] = array('field' => 'sigla_lista', 'title' => 'Sigla Listas');
        $columns[] = array('field' => 'votos', 'title' => 'Votos');
        $columns[] = array('field' => 'ponderado', 'title' => 'Ponderado');
        
        $nom_claustro = null;
        
        //Datos de mesas
        $m_enviadas = null;
        $m_confirmadas = null;
        $m_total = null;
        $total_votos=0;
        $empadronados=null;
        $blancos = null;
        $nulos = null;
        $recurridos = null;
        
        $data = array();
        foreach($datos as $un_registro){
            if($nom_claustro != null && $nom_claustro != $un_registro['claustro']){
                $json = array();
                $data[] = array('lista' => 'Blancos', 'votos' => $blancos);
                $data[] = array('lista' => 'Nulos', 'votos' => $nulos);
                $data[] = array('lista' => 'Recurridos', 'votos' => $recurridos);
                $data[] = array('lista' => 'Votantes', 'votos' => $total_votos+$blancos+$nulos+$recurridos);
                $data[] = array('lista' => 'Empadronados', 'votos' => $empadronados);
            
                $json['columns'] = $columns;
                $json['data'] = $data;
                $json['labels'] = $labels;
                $json['total'] = $total;
                $json['fecha'] = date('d/m/Y G:i:s');
                $json['titulo'] = 'Votos Ponderados Universidad Rector '.$nom_claustro;
                
                $json['enviadas'] = round($m_enviadas*100/$m_total, 2).'% ('.$m_enviadas." de ".$m_total.')';
                $json['confirmadas'] = round($m_confirmadas*100/$m_total, 2).'% ('.$m_confirmadas." de ".$m_total.')';
            
                $data = array();
                $labels = array();
                $total = array();
                $total_votos=0;
                
                $string_json = json_encode($json);
                $nom_archivo = 'e'.str_replace('-','',$fecha).'/R_TODO_'.strtoupper($nom_claustro[0]).'.json';
                file_put_contents('resultados_json/'.$nom_archivo , $string_json);
                
                $nom_claustro = $un_registro['claustro'];                
            }elseif($nom_claustro == null)
                $nom_claustro = $un_registro['claustro'];
            
            $r['lista'] = utf8_encode($un_registro['lista']);
            $r['sigla_lista'] = $un_registro['sigla_lista'];
            $r['votos'] = $un_registro['votos_lista'];
            $total_votos+=$r['votos'];
            $r['ponderado'] = $un_registro['ponderado'];
            
            $labels[] = $un_registro['sigla_lista'];
            $total[] = $un_registro['ponderado'];
            
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
        if(isset($data) && $nom_claustro != null){//Quedo un ultimo claustro sin guardar
            $json = array();
            
            $data[] = array('lista' => 'Blancos', 'votos' => $blancos);
            $data[] = array('lista' => 'Nulos', 'votos' => $nulos);
            $data[] = array('lista' => 'Recurridos', 'votos' => $recurridos);
            $data[] = array('lista' => 'Votantes', 'votos' => $total_votos+$blancos+$nulos+$recurridos);
            $data[] = array('lista' => 'Empadronados', 'votos' => $empadronados);
            
            
            $json['columns'] = $columns;
            $json['data'] = $data;
            $json['labels'] = $labels;
            $json['total'] = $total;
            $json['fecha'] = date('d/m/Y G:i:s');
            $json['titulo'] = 'Votos Ponderados Universidad Rector '.$nom_claustro;
            
            $json['enviadas'] = round($m_enviadas*100/$m_total, 2).'% ('.$m_enviadas." de ".$m_total.')';
            $json['confirmadas'] = round($m_confirmadas*100/$m_total, 2).'% ('.$m_confirmadas." de ".$m_total.')';
            
            $string_json = json_encode($json);

            $nom_archivo = 'e'.str_replace('-','',$fecha).'/R_TODO_'.strtoupper($nom_claustro[0]);
            file_put_contents('resultados_json/'.$nom_archivo . '.json', $string_json);
        }
    }
    
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
		where m.fecha='2018-05-22'
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
    
    //-----------------------------------------------------------------------------------
    //---- formulario que muestra datos de mesas enviadas, confirmadas y definitivas -----------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    //---- form_mesas_e -----------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_mesas_e(gu_kena_ei_formulario $form) {
        /* $cargadas = $this->dep('datos')->tabla('mesa')->get_cant_cargadas(3);
          $confirmadas = $this->dep('datos')->tabla('mesa')->get_cant_confirmadas(3);
          $definitivas = $this->dep('datos')->tabla('mesa')->get_cant_definitivas(3);

          $total = $this->dep('datos')->tabla('mesa')->get_total_mesas(3);
          if ($total != 0){
          $datos['cargadas'] = ($cargadas * 100 / $total);
          $datos['cargadas'] = round($datos['cargadas'], 2). " % ($cargadas de $total)";
          $datos['confirmadas'] = ($confirmadas * 100 / $total);
          $datos['confirmadas'] = round($datos['confirmadas'],2). " % ($confirmadas de $total)";
          $datos['definitivas'] = ($definitivas * 100 / $total);
          $datos['definitivas'] = round($datos['definitivas'],2). " % ($definitivas de $total)";
          }
          else {
          $datos['cargadas'] = $cargadas . " % ($cargadas de $total)";
          $datos['confirmadas'] = $confirmadas . " % ($confirmadas de $total)";
          $datos['definitivas'] = $definitivas . " % ($definitivas de $total)";
          }
          return $datos; */
    }

    //-----------------------------------------------------------------------------------
    //---- form_mesas_g -----------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_mesas_g(gu_kena_ei_formulario $form) {
        /* $cargadas = $this->dep('datos')->tabla('mesa')->get_cant_cargadas(4);
          $confirmadas = $this->dep('datos')->tabla('mesa')->get_cant_confirmadas(4);
          $definitivas = $this->dep('datos')->tabla('mesa')->get_cant_definitivas(4);

          $total = $this->dep('datos')->tabla('mesa')->get_total_mesas(4);
          if ($total != 0) {
          $datos['cargadas'] = ($cargadas * 100 / $total);
          $datos['cargadas'] = round($datos['cargadas'], 2) . " % ($cargadas de $total)";
          $datos['confirmadas'] = ($confirmadas * 100 / $total);
          $datos['confirmadas'] = round($datos['confirmadas'], 2) . " % ($confirmadas de $total)";
          $datos['definitivas'] = ($definitivas * 100 / $total);
          $datos['definitivas'] = round($datos['definitivas'], 2) . " % ($definitivas de $total)";
          }
          else{
          $datos['cargadas'] = $cargadas . " % ($cargadas de $total)";
          $datos['confirmadas'] = $confirmadas . " % ($confirmadas de $total)";
          $datos['definitivas'] = $definitivas .  " % ($definitivas de $total)";
          }

          return $datos; */
    }

    //-----------------------------------------------------------------------------------
    //---- form_mesas_nd ----------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_mesas_nd(gu_kena_ei_formulario $form) {
        /* $cargadas = $this->dep('datos')->tabla('mesa')->get_cant_cargadas(1);
          $confirmadas = $this->dep('datos')->tabla('mesa')->get_cant_confirmadas(1);
          $definitivas = $this->dep('datos')->tabla('mesa')->get_cant_definitivas(1);

          $total = $this->dep('datos')->tabla('mesa')->get_total_mesas(1);

          if ($total != 0) {
          $datos['cargadas'] = ($cargadas * 100 / $total);
          $datos['cargadas'] = round($datos['cargadas'], 2) . " % ($cargadas de $total)";
          $datos['confirmadas'] = ($confirmadas * 100 / $total);
          $datos['confirmadas'] = round($datos['confirmadas'], 2) . " % ($confirmadas de $total)";
          $datos['definitivas'] = ($definitivas * 100 / $total);
          $datos['definitivas'] = round($datos['definitivas'], 2) . " % ($definitivas de $total)";
          }
          else {
          $datos['cargadas'] = $cargadas . " % ($cargadas de $total)";
          $datos['confirmadas'] = $confirmadas . " % ($confirmadas de $total)";
          $datos['definitivas'] = $definitivas . " % ($definitivas de $total)";
          }
          return $datos; */
    }

    //-----------------------------------------------------------------------------------
    //---- form_mesas_d ----------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_mesas_d(gu_kena_ei_formulario $form) {
        /* $cargadas = $this->dep('datos')->tabla('mesa')->get_cant_cargadas(2);
          $confirmadas = $this->dep('datos')->tabla('mesa')->get_cant_confirmadas(2);
          $definitivas = $this->dep('datos')->tabla('mesa')->get_cant_definitivas(2);
          $total = $this->dep('datos')->tabla('mesa')->get_total_mesas(2);

          if ($total != 0) {
          $datos['cargadas'] = ($cargadas * 100 / $total);
          $datos['cargadas'] = round($datos['cargadas'], 2) . " % ($cargadas de $total)";
          $datos['confirmadas'] = ($confirmadas * 100 / $total);
          $datos['confirmadas'] = round($datos['confirmadas'], 2) . " % ($confirmadas de $total)";
          $datos['definitivas'] = ($definitivas * 100 / $total);
          $datos['definitivas'] = round($datos['definitivas'], 2) . " % ($definitivas de $total)";
          }
          else {
          $datos['cargadas'] = $cargadas . " % ($cargadas de $total)";
          $datos['confirmadas'] = $confirmadas . " % ($confirmadas de $total)";
          $datos['definitivas'] = $definitivas . " % ($definitivas de $total)";
          }
          return $datos; */
    }

    //-----------------------------------------------------------------------------------
    //---- EXPORTACION EXCEL ----------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    function vista_excel(toba_vista_excel $salida) {
        $salida->set_nombre_archivo("EscrutinioRector.xls");
        $excel = $salida->get_excel();


        $this->dependencia('cuadro_rector_e')->vista_excel($salida);
        $salida->separacion(3);
        $this->dependencia('cuadro_dhondt_e')->vista_excel($salida);
        $salida->set_hoja_nombre("Estudiantes");

        $salida->crear_hoja();
        $this->dependencia('cuadro_rector_g')->vista_excel($salida);
        $salida->separacion(3);
        $this->dependencia('cuadro_dhondt_g')->vista_excel($salida);
        $salida->set_hoja_nombre("Graduados");

        $salida->crear_hoja();
        $this->dependencia('cuadro_rector_nd')->vista_excel($salida);
        $salida->separacion(3);
        $this->dependencia('cuadro_dhondt_nd')->vista_excel($salida);
        $salida->set_hoja_nombre("No Docente");
//            $excel->getActiveSheet()->setTitle('Parte de Novedades');
//            $excel->getActiveSheet()->getStyle('A5')->getFill()->applyFromArray(array(
//        'type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array( 'rgb' => 'F28A8C' ) ));
    }

}

?>
