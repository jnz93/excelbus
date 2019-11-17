<?php 
/**
 * Function allowed_file_types(); - Verifica se o arquivo enviado é válido para a leitura e extração. Se for retorna true, caso não seja retorna false
 * @param $fileType; - Tipo do arquivo enviado no upload
 *  */    
function allowed_file_types($fileType)
{
    // [Arr] tipos de arquivos aceitos
    // https://stackoverflow.com/questions/11832930/html-input-file-accept-attribute-file-type-csv
    $allowed_file_types = array('.csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    if (in_array($fileType, $allowed_file_types))
    {
        return true;
    }
    else
    {
        return false;
    }
}



/**
 * Function extract_read_and_treatment_of_data($obj); - Recebe um objeto excel para leitura, extração e tratamento dos dados, ao final retorna um vetor
 * @param $obj - Um objeto excel retornado da classe PHPExcelReader
 * @return vetor - UM vetor com todos os dados extraídos e armazenados
 */
function extract_read_and_treatment_of_data($objExcel, $return)
{
    // Pegar total de colunas
    $colunas = $objExcel->setActiveSheetIndex(0)->getHighestColumn();
    $total_colunas = PHPExcel_Cell::columnIndexFromString($colunas);

    // Total de linhas
    $total_linhas = $objExcel->setActiveSheetIndex(0)->getHighestRow();

    $arr_bus_for_excel = array();
    $arr_cities_boarding_points = array();

    for ($i = 2; $i <= $total_linhas; $i++)
    {
        $trecho         = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(0, $i));
        $prefixo        = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(1, $i));
        $servico        = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(2, $i));
        $dia_semana     = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(3, $i));
        $hora_saida     = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(4, $i));
        $origem_id      = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(5, $i));
        $origem_nome    = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(6, $i));
        $destino_id     = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(7, $i));
        $destino_nome   = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(8, $i));
        $sentido        = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(9, $i));


        if (!in_array($prefixo, $arr_bus_for_excel))
        {
            $arr_bus_for_excel[] = $prefixo;
        }
        
        if ($arr_bus_for_excel[$prefixo][0] != $dia_semana)
        {
            $arr_bus_for_excel[$prefixo][0] = $dia_semana;
        }
        
        $arr_bus_for_excel[$prefixo][$dia_semana]['trechos'][] = $trecho;
        
        if ($sentido == "I")
        {
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['origem_id']    = $origem_id;
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['origem_nome']  = $origem_nome;
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['destino_id']   = $destino_id;
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['destino_nome'] = $destino_nome;
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['horarios'][]   = $hora_saida;
        }
        else
        {
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['origem_id']    = $origem_id;
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['origem_nome']  = $origem_nome;
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['destino_id']   = $destino_id;
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['destino_nome'] = $destino_nome;
            $arr_bus_for_excel[$prefixo][$dia_semana][$sentido]['horarios'][]   = $hora_saida;
        }

        if (!in_array($origem_nome, $arr_cities_boarding_points))
        {
            $arr_cities_boarding_points[] = $origem_nome;
        }

    }

    if ($return == 'objExcel')
    {            
        return $arr_bus_for_excel;
    }
    else if ($return == 'arrCities')
    {
        return $arr_cities_boarding_points;
    }
    else
    {
        return $arr_bus_for_excel;
    }
}



/**
 * Function compare_and_return_result - Recebe um vetor de onde extrai os prefixos vindos do excel para comparação com os prefixos já registrados
 * no wordpress. Ao encontrar prefixos iguais retorna, em forma de um array, os ids para update. Retorna também um array de prefixos para novas
 * publicações.
 * @param vetor - Vetor com os dados do excel
 */
function compare_and_return_result($obj)
{
    // Tratamento dos prefixos do excel e transformação em array
    $string_prefix_of_excel = '';
    $arr_prefix_origin_and_hours = array();
    for($i = 0; $i <= count($obj); $i++)
    {
        if (isset($obj[$i]))
        {
            $string_prefix_of_excel .= $obj[$i] . ',';
        }
    }
    $arr_prefix_of_excel = explode(',', $string_prefix_of_excel);
    
    
    // Fase 2
    // Pegar todos as publicações
    $args = array(
        'post_type'         => 'wbtm_bus',
        'post_status'       => 'publish',
        'posts_per_page'    => '-1'
    );
    $get_posts = new WP_Query($args);
    
    // Loop para encontrar Prefixos(onibus) já cadastrados
    if ($get_posts->have_posts())
    {
        while ($get_posts->have_posts())
        {
            $get_posts->the_post();

            $post_id            = get_the_ID();
            $title              = get_the_title();
            $meta_prefix        = get_post_meta($post_id, 'wbtm_bus_no', false);
            
            // Dias de funcionamento
            $od_sunday          = get_post_meta($post_id, 'od_Sun', true);
            $od_monday          = get_post_meta($post_id, 'od_Mon', true);
            $od_tuesday         = get_post_meta($post_id, 'od_Tue', true);
            $od_wednesday       = get_post_meta($post_id, 'od_Wed', true);
            $od_thursday        = get_post_meta($post_id, 'od_Thu', true);
            $od_friday          = get_post_meta($post_id, 'od_Fri', true);
            $od_saturday        = get_post_meta($post_id, 'od_Sat', true);
            
            // Cidades/Trechos de partida
            $wbtm_bus_boarding_points = get_post_meta($post_id, 'wbtm_bus_bp_stops', false);

            // Array meta -> String prefixos
            $string_prefix = '';
            foreach($meta_prefix as $prefix)
            {
                $string_prefix .= $prefix;
            }
            
            // $arr_prefix_registered = explode(',', $string_prefix);

            // Verifica, através do prefixo, se o veículo já está cadastrado
            if(in_array(trim($string_prefix), $arr_prefix_of_excel))
            {

                // descobrir dias de operação
                if($od_sunday == 'yes')
                {
                    $operational_day = "DOM";
                }
                if($od_monday == 'yes')
                {
                    $operational_day = "SEG";
                }
                
                // Verifica se o "od"(operational day) é domingo. Útil já que aos domingos temos horários diferenciados
                if ($od_sunday == 'yes')
                {
                    echo "Convencional - " . $string_prefix . " (Domingo) / Post id: " . $post_id . "<br>";
                }
                else
                {
                    echo "Convencional - " . $string_prefix . " (Seg-Sab) / Post id: " . $post_id . "<br>"; 
                }

                // Pontos de embarque e horários de partida já cadastrados
                $arr_od_cities = array();
                $arr_od_time = array();
                foreach ($wbtm_bus_boarding_points as $index => $arr_point)
                {
                    echo $index . "<br>";
                    foreach($arr_point as $value)
                    {
                        // echo $value['wbtm_bus_bp_stops_name'] . " Saída: " .  $value['wbtm_bus_bp_start_time'] . "<br>";

                        if (!in_array($value['wbtm_bus_bp_stops_name'], $arr_od))
                        {
                            $arr_od_cities[] = $value['wbtm_bus_bp_stops_name'];
                        }
                        $arr_od_time[] = $value['wbtm_bus_bp_start_time'];
                    }
                }

                if (in_array($obj[$string_prefix][$operational_day]['I']['origem_nome'], $arr_od_cities))
                {
                    // echo "Sim";
                    echo $obj[$string_prefix][$operational_day]['I']['origem_nome'];
                }
                else
                {
                    // echo "Não";
                    echo $obj[$string_prefix][$operational_day]['I']['origem_nome'];
                }


                echo "<pre>";
                // echo $wbtm_bus_boarding_points[0][0]['wbtm_bus_bp_stops_name'];
                // echo $wbtm_bus_boarding_points[0][1]['wbtm_bus_bp_stops_name'];
                // echo $wbtm_bus_boarding_points[0][2]['wbtm_bus_bp_stops_name'];
                // var_dump($arr_od);
                // var_dump($arr_od_time);
                // var_dump($arr_prefix_origin_and_hours);
                // var_dump($obj[$string_prefix][$operational_day]['I']['origem_nome']);
                // var_dump($obj[$string_prefix][$operational_day]['I']['horarios']);
                // var_dump($obj[$string_prefix][$operational_day]['V']['origem_nome']);
                // var_dump($obj[$string_prefix][$operational_day]['V']['horarios']);
                echo "</pre>";
            }
            else
            {
                $prefix_for_publish .= $string_prefix . ',';
            }
        }
    }
}