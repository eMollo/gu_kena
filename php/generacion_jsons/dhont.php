<?php

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
        $columns[] = array('field' => 'escanos', 'title' =>'Cargos');
        for ($index3 = 1; $index3 <= $escano_max; $index3++) {
            $columns[] = array('field' => $index3, 'title' => $index3);
        }
        return array($columns, $datos);
    }


