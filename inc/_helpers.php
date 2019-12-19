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
 * @param Object - Um objeto excel retornado da classe PHPExcelReader
 * @return Array - UM vetor com todos os dados extraídos e armazenados
 */
function extract_read_and_treatment_of_data($objExcel, $return)
{
    // Pegar total de colunas
    $colunas = $objExcel->setActiveSheetIndex(0)->getHighestColumn();
    $total_colunas = PHPExcel_Cell::columnIndexFromString($colunas);

    // Total de linhas
    $total_linhas = $objExcel->setActiveSheetIndex(0)->getHighestRow();

    $arr_bus_from_excel = array();
    $arr_boarding_points_excel = array();

    for ($i = 2; $i <= $total_linhas; $i++)
    {
        $trecho         = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(0, $i));
        $prefixo        = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(1, $i));
        $servico        = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(2, $i));
        $dia_semana     = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(3, $i));
        $hora_saida     = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(4, $i));
        $origem_id      = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(5, $i));
        $origem_nome    = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(6, $i));
        $hora_chegada   = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(7, $i));
        $destino_id     = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(8, $i));
        $destino_nome   = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(9, $i));
        $sentido        = utf8_decode($objExcel->getActiveSheet()->getCellByColumnAndRow(10, $i));


        if (!in_array($prefixo, $arr_bus_from_excel))
        {
            $arr_bus_from_excel[] = $prefixo;
        }
        
        if ($arr_bus_from_excel[$prefixo][0] != $dia_semana)
        {
            $arr_bus_from_excel[$prefixo][0] = $dia_semana;
        }
        
        $arr_bus_from_excel[$prefixo][$dia_semana]['trechos'][] = $trecho;
        
        if ($sentido == "I")
        {
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['origem_id']           = $origem_id;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['origem_nome']         = $origem_nome;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['destino_id']          = $destino_id;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['destino_nome']        = $destino_nome;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['horarios'][]          = $hora_saida;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['horarios_chegada'][]  = $hora_chegada;
        }
        else
        {
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['origem_id']           = $origem_id;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['origem_nome']         = $origem_nome;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['destino_id']          = $destino_id;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['destino_nome']        = $destino_nome;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['horarios'][]          = $hora_saida;
            $arr_bus_from_excel[$prefixo][$dia_semana][$sentido]['horarios_chegada'][]  = $hora_chegada;
        }

        if (!in_array($origem_nome, $arr_boarding_points_excel))
        {
            $arr_boarding_points_excel[] = $origem_nome;
        }

    }

    if ($return == 'objExcel')
    {            
        return $arr_bus_from_excel;
    }
    else if ($return == 'arrBoardingPoints')
    {
        return $arr_boarding_points_excel;
    }
    else
    {
        return $arr_bus_from_excel;
    }
}



/**
 * Function check_prefix - Recebe um vetor extrai os prefixos para comparação com registros do wordpress. 
 * Prefixos já cadastrados no wordpress são adicionados ao vetor de updates.
 * Prefixos não cadastrados são adicionados no vetor de publicação.
 * @param Object = Objeto excel com os dados da planilha
 * @return Array = Array para update ou publicação
 */
function check_prefix($objExcel, $return)
{
    // Extração dos prefixos da planilha
    $arr_prefix_from_excel = array();
    for($i = 0; $i <= count($objExcel); $i++)
    {
        if (isset($objExcel[$i]))
        {
            $arr_prefix_from_excel[$i] = $objExcel[$i];
        }
    }

    // Query para comparação
    $args = array(
        'post_type'         => 'wbtm_bus',
        'post_status'       => 'publish',
        'posts_per_page'    => '-1'
    );
    $get_posts = new WP_Query($args);
    
    // Definição de arrays
    $ids_for_update = array();
    $prefix_for_new_publish = array();
    $prefix_for_exclude = array();

    // Buscar veiculos já cadastrados via prefixo
    if ($get_posts->have_posts())
    {
        while ($get_posts->have_posts())
        {
            $get_posts->the_post();

            $post_id    = get_the_ID();
            $prefix     = trim(get_post_meta($post_id, 'wbtm_bus_no', true));

            // Verificar se o prefixo já foi cadastrado
            if(in_array($prefix, $arr_prefix_from_excel))
            {
                // Array de Ids para update
                $ids_for_update[] = $post_id;
                $prefix_for_exclude[] = $prefix;
            }
        }
    }
    $prefix_for_new_publish = array_diff($arr_prefix_from_excel, $prefix_for_exclude);
    
    // Retorno da função
    if ($return == "idsUpdate")
    {
        return $ids_for_update;
    }
    else if($return == "prefixPublish")
    {
        return $prefix_for_new_publish;
    }
    else
    {
        return $arr_prefix_from_excel;
    }
}

/**
 * Function check_operational_days();
 * Recebe um vetor multidimensional do excel, prefixo do veículo e dia da semana abreviado(SEG,TER,QUA,QUI,SEX,SAB,DOM) para verificar operação no dia;
 * Retorna "yes" para "off-days" e "" para dias de operação.
 * @param Object $objExcel
 * @param String $prefix
 * @param String $day
 */
function check_operational_days($objExcel, $prefix, $day)
{

    if(!is_array($objExcel))
    {
        exit;
    }

    // $week_days = array('DOM','SEG','TER','QUA','QUI','SEX','SAB');
    $curr_day = $objExcel[$prefix][$day];

    if (empty($curr_day) || $curr_day == '')
    {
        // echo 'O Veículo: <b>' . $prefix . '</b> não opera no dia ' . $day . '<br>';
        return 'yes';
    }
    else
    {
        return '';
    }
}

// Function check_duplicated_time_to_bus();
function check_duplicated_time_to_bus($curr_arr, $prev_arr)
{

    $curr_hour          = $curr_arr[0];
    $curr_minutes       = $curr_arr[1];

    $prev_hour          = $prev_arr[0];
    $prev_minutes       = $prev_arr[1];

    if($curr_hour === $prev_hour && ($curr_minutes - $prev_minutes) <= 20)
    {
        return false;
    }
    else
    {
        return true;
    }
}


/**
 * Function check_identical_bp_hours($arr1, $arr2);
 * Faz os tratamentos necessários nos arrays antes da comparação. Depois retorna um bool da comparação 
 */
function check_identical_bp_hours($arr1, $arr2)
{
    array_unique($arr1); # Remover valores duplicados
    array_unique($arr2); # Remover valores duplicados
    sort($arr1); # Ordenação menor > maior
    sort($arr2); # Ordenação menor > maior

    # Comparar os dois arrays e extrair o(s) valor(es) diferente(s)
    $diff1 = array_diff($arr1, $arr2);
    $diff2 = array_diff($arr2, $arr1);

    # Fail fast
    if (count($diff1) > 1 || count($diff2) > 1)
    {
        return false;
    }

    # Se a diferença de um pro outro for 1 assumimos que se trata dos mesmos horários
    if (count($diff1) == 1 || count($diff2) == 1)
    {
        return true;
    }

    return $arr1 == $arr2; # Bool: retorno da comparação
}

/**
 * Function group_of_working($prefix, $objExcel);
 * Encontra dias de operação idênticos, ou seja, com os mesmos horários. E retorna para publicação
 */
function check_group_of_working($prefix, $obj)
{
    # ARRAY: Checagem dias de operação
    # "yes" = dia de folga
    # "" = dia de operação 
    $week_checked_od['DOM'] = check_operational_days($obj, $prefix, 'DOM');
    $week_checked_od['SEG'] = check_operational_days($obj, $prefix, 'SEG');
    $week_checked_od['TER'] = check_operational_days($obj, $prefix, 'TER');
    $week_checked_od['QUA'] = check_operational_days($obj, $prefix, 'QUA');
    $week_checked_od['QUI'] = check_operational_days($obj, $prefix, 'QUI');
    $week_checked_od['SEX'] = check_operational_days($obj, $prefix, 'SEX');
    $week_checked_od['SAB'] = check_operational_days($obj, $prefix, 'SAB');

    # ARRAY: Tratamento dos horários de embarque por dia
    $boarding_hours_per_day         = array();
    foreach ($week_checked_od as $day => $od)
    {
        if (empty($od))
        {
            $hours_per_day_going[$day] = $obj[$prefix][$day]['I']['horarios'];
            $hours_per_day_return[$day] = $obj[$prefix][$day]['V']['horarios'];

            $boarding_hours_per_day[$day] = array_merge($hours_per_day_going[$day], $hours_per_day_return[$day]);
        }
    }

    # Checagem e armazenamento de veiculos que operam nos mesmos horarios em dias diferentes
    /**
     * Comparar horarios de embarque dia-a-dia com todos os outros dias restantes na semana, isso é, a partir do dia atual menos ele mesmo.
     * Dias com horários iguais serão salvos em um array
     * Dias com horários diferentes serão salvos em outro array
     */
    $days_with_same_hours       = array();
    $days_with_diferent_hours   = array();
    $week_days                  = array('DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB');
    $week_for_compare           = $week_days;
    foreach($week_days as $day)
    {
        # Se $od for vazio prossegue a operação
        $od = $week_checked_od[$day];
        if (empty($od)){

            $curr_day_bp_start_hours    = $boarding_hours_per_day[$day]; # Arr de horarios do dia atual
            // $curr_day_bp_start_hours    = array_unique($curr_day_bp_start_hours); # Elimina valores duplicados
            // sort($curr_day_bp_start_hours); # Ordenação do menor para o maior
            
            # Baseado no $day compara os horarios de operação com todos os outros dias
            foreach ($week_for_compare as $day_for_compare)
            {
                if ($day_for_compare != $day)
                {
                    $hours = $boarding_hours_per_day[$day_for_compare]; # Coleta os horários do dia para comparação
                    // $hours = array_unique($hours); # Elimina valores duplicados
                    // sort($hours); # Ordenação do menor para o maior

                    if (check_identical_bp_hours($curr_day_bp_start_hours, $hours)) 
                    {
                        if (!in_array($day, $days_with_same_hours))
                        {
                            $days_with_same_hours[] = $day;
                        }
                    }
                }
            }

            # Dias com horários distintos dos outros
            if (!in_array($day, $days_with_same_hours))
            {
                $days_with_diferent_hours[] = $day;
            }
        }

    }

     # -> LOGS
     echo 'Veículo ' . $prefix . ' Opera nos mesmos horários nos dias: <br><pre>';
     print_r($days_with_same_hours);
     echo '</pre><br>';
     echo 'E opera me horários distintos nos dias: <br><pre>';
     print_r($days_with_diferent_hours);
     echo '</pre><br>';
}


/**
 * Function register_boarding_points_from_excel($arr);
 * Desc: Recebe um array de pontos de embarques, faz o tratamento dos nomes, com os nomes tratados verifica se os pontos já estão registrados. 
 * Pontos que não estiverem registrados do wordpress serão inseridos
 * @param Array
 */
function register_boarding_points_from_excel($arr)
{
    if (!is_array($arr))
    {
        echo "Os Pontos de embarque são inválidos. Aplicação finalizada.";
        exit;
    }
    
    // Tratamento dos nomes, checagem de existencia e registro
    foreach($arr as $bp)
    {
        // Tratamento das strings
        $bp = ucwords(strtolower($bp));
        $bp = str_replace(['Sao', 'Joao', 'Jose', 'Guacu', 'Aguai', 'Sp'], ['São', 'João', 'José', 'Guaçu', 'Aguaí', 'SP'], $bp);
        
        // Checagem e registro
        $taxonomy = 'wbtm_bus_stops';
        $bp_exists = term_exists($bp, $taxonomy);
        if ($bp_exists == 0 || $bp_exists == false)
        {
            wp_insert_term($bp, $taxonomy);
        }
    }

}


/**
 * Function update_bp_and_schedules($arr);
 * Recebe um array com ID's das publicações que devem ser atualizadas com pontos de embarques ou horários.
 * @param Array
 */
function update_bp_and_schedules($ids, $objExcel)
{
    if (!is_array($ids))
    {
        echo "O parametro deve ser um array de ID's. Aplicação finalizada.";
        exit;
    }

    // $objExcel - deve conter os dados da planilha que serão atualizados. Portanto precisamos dar get no prefixo da publicação, e buscar os dados no objeto excel referente ao prefixo, ai então poderemos partir para tratamento e atualização dos dados no wordpress.
    foreach($ids as $id)
    {
        // Get meta values
        $wbtm_bus_boarding_points       = get_post_meta($id, 'wbtm_bus_bp_stops');
        $bus_prefix                     = get_post_meta($id, 'wbtm_bus_no', true);

        // Dias de funcionamento
        $od_sunday          = get_post_meta($id, 'od_Sun', true);
        $od_monday          = get_post_meta($id, 'od_Mon', true);
        $od_tuesday         = get_post_meta($id, 'od_Tue', true);
        $od_wednesday       = get_post_meta($id, 'od_Wed', true);
        $od_thursday        = get_post_meta($id, 'od_Thu', true);
        $od_friday          = get_post_meta($id, 'od_Fri', true);
        $od_saturday        = get_post_meta($id, 'od_Sat', true);
        
        // Keys
        $wbtm_bus_stops_meta_key        = 'wbtm_bus_bp_stops';
        $wbtm_bus_start_time_meta_key   = 'wbtm_bus_bp_start_time';
        $od                             = '';

        // definição Operational day
        if ($od_sunday == "yes")
        {
            $od = "DOM";
        }
        else if($od_monday == "yes" && $od_tuesday == "yes" && $od_wednesday == "yes" && $od_thursday == "yes" && $od_friday == "yes")
        {
            $od = "SEG";
        }
        else if ($od_saturday == "yes")
        {
            $od = "SAB";
        }
        else if ($od_friday == "yes")
        {
            $od = "SEX";
        }
        else
        {
            $od = "SEG";
        }
        
        // Tratamento nos nomes das cidades
        $arr_wrong_words            = array('Sao', 'Joao', 'Jose', 'Guacu', 'Aguai', 'Sp');
        $arr_correct_words          = array('São', 'João', 'José', 'Guaçu', 'Aguaí', 'SP');

        $bp_origin_name             = ucwords(strtolower($objExcel[$bus_prefix][$od]['I']['origem_nome']));
        $bp_origin_name             = str_replace($arr_wrong_words, $arr_correct_words, $bp_origin_name);

        $bp_destiny_name            = ucwords(strtolower($objExcel[$bus_prefix][$od]['V']['origem_nome']));
        $bp_destiny_name            = str_replace($arr_wrong_words, $arr_correct_words, $bp_destiny_name);


        // Tratamento e armazenamento dos horários de partida
        $bp_start_time              = $objExcel[$bus_prefix][$od]['I']['horarios'];
        $bp_back_time               = $objExcel[$bus_prefix][$od]['V']['horarios'];

        $bp_time_to_bus             = array_merge($bp_start_time, $bp_back_time);
        $bp_time_to_save            = array();
        asort($bp_time_to_bus);

        // Tratamento e armazenamento dos horários de chegada
        $start_arrival_time         = $objExcel[$bus_prefix][$od]['I']['horarios_chegada'];
        $back_arrival_time          = $objExcel[$bus_prefix][$od]['V']['horarios_chegada'];

        $arrival_time               = array_merge($start_arrival_time, $back_arrival_time);
        $arrival_time_to_save       = array();
        asort($arrival_time);


        for ($i = 0; $i < count($bp_time_to_bus); $i++)
        {
            $prev_index     = ($i - 1);
            $time           = $bp_time_to_bus[$i];
            $time_stop      = $arrival_time[$i];

            $curr_time_toArr    = explode(':', $time);

            $prev_time          = $bp_time_to_bus[$prev_index];
            $prev_time_toArr    = explode(':', $prev_time);

            $check_duplicated   = check_duplicated_time_to_bus($curr_time_toArr, $prev_time_toArr);
            if ($check_duplicated == true)
            {
                if (($i % 2) == 0)
                {
                    $bp_time_to_save[]      = ['wbtm_bus_bp_stops_name' => $bp_origin_name, 'wbtm_bus_bp_start_time' => $time];
                    $arrival_time_to_save[] = ['wbtm_bus_next_stops_name' => $bp_destiny_name, 'wbtm_bus_next_end_time' => $time_stop];
                }
                else
                {
                    $bp_time_to_save[] = ['wbtm_bus_bp_stops_name' => $bp_destiny_name, 'wbtm_bus_bp_start_time' => $time];
                    $arrival_time_to_save[] = ['wbtm_bus_next_stops_name' => $bp_origin_name, 'wbtm_bus_next_end_time' => $time_stop];
                }
            }

        }

        update_post_meta($id, 'wbtm_bus_bp_stops', $bp_time_to_save);
        update_post_meta($id, 'wbtm_bus_next_stops', $arrival_time_to_save);
    }

}


/**
 * Function publish_vehicle_by_prefix($arr);
 * Recebe um array com os prefixos ainda não registrados, cria a publicações, pega o ID e faz update nos pontos de embarque e horários
 * @param Array
 */
function publish_vehicle_by_prefix($list_prefix, $objExcel)
{
    if (!is_array($list_prefix))
    {
        echo "O parametro deve ser um array de Prefixos. Aplicação finalizada.";
        exit;
    }

    $ids_week_to_update = array();
    $sunday_prefix      = array(); // Recebe prefixos que operam aos domingo
    foreach ($list_prefix as $prefix)
    {
        if ($prefix != '')
        {

            $od_sunday      = check_operational_days($objExcel, $prefix, 'DOM');
            $od_monday      = check_operational_days($objExcel, $prefix, 'SEG');
            $od_tuesday     = check_operational_days($objExcel, $prefix, 'TER');
            $od_wednesday   = check_operational_days($objExcel, $prefix, 'QUA');
            $od_thursday    = check_operational_days($objExcel, $prefix, 'QUI');
            $od_friday      = check_operational_days($objExcel, $prefix, 'SEX');
            $od_saturday    = check_operational_days($objExcel, $prefix, 'SAB');
    
            // Extração dos prefixos que operam aos domingos
            if ($od_sunday == 'yes')
            {
                $sunday_prefix[] = $prefix;
            }
            
            // Definição do título
            if ($od_monday == 'yes' && $od_saturday == 'yes')
            {
                $od_for_title = '[SEG~SAB]';
            }
            else if ($od_monday == '' && $od_friday == 'yes')
            {
                $od_for_title = '[SEX]';
            }
            else
            {
                $od_for_title = '[SEG~SEX]';
            }
    
            // Construção do novo post
            $title = 'Convencional - ' . $prefix . ' ' . $od_for_title;
            $post_arr = array(
                'post_title'    => $title,
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'wbtm_bus',
                'post_author'   => get_current_user_id(),
                'tax_input'     => array(
                    'wbtm_bus_cat'  => 22
                ),
                'meta_input'    => array(
                    'wbtm_bus_no'   => $prefix,
                    'od_Sun'        => $od_sunday,
                    'od_Mon'        => $od_monday,
                    'od_Tue'        => $od_tuesday,
                    'od_Wed'        => $od_wednesday,
                    'od_Thu'        => $od_thursday,
                    'od_Fri'        => $od_friday,
                    'od_Sat'        => $od_saturday,
                ),
            );
            $new_post_id = wp_insert_post($post_arr);
            
            if(!is_wp_error($new_post_id))
            {
                // echo 'Semana: <br>';
                // echo '<li>'. $title .' <a href="'. get_edit_post_link($new_post_id) .'" target="_blank">Ver</a> </li>';
                $ids_week_to_update[] = $new_post_id;
            }
            else{
                echo $new_post_id->get_error_message();
            }
        }
    }

    // Merge arrays ids
    $all_ids = array_merge($ids_week_to_update, $ids_sundays_to_update);

    
    $all_ids_update = check_prefix($objExcel, 'idsUpdate');
    
    // Update nas publicações criadas [SEG~SAB]
    // if (!empty($all_ids_update))
    // {
    //     update_bp_and_schedules($all_ids_update, $objExcel);
    // }
    // print_r($all_ids_update);
    // Render
    render_response_page($all_ids_update);
}

function render_response_page($arr)
{
    if (!is_array($arr))
    {
        echo 'A função render_response_page precisa que o parâmetro seja uma array.';
        exit;
    }

    echo '<div class="resultWrapper">';
    echo '<h6 class="resultWrapper__title">Resultados da aplicação:</h6>';
    echo '<ul class="resultList col-lg-11 row">';
    foreach($arr as $id)
    {
        $title      = get_the_title($id);
        $linkEdit   = get_edit_post_link($id);

        echo '<li class="resultList__item col-lg-5"><a href="'. $linkEdit .'" target="_blank"> <i class="resultList__icon fas fa-bus-alt"></i>'. $title .' </a></li>';
    }
    echo '</ul>';
    echo '<a href="" class="btn btn__secondary">Voltar ao inicio</a>';
    echo '</div>';

    // includes_once(plugins_url() . '/templates-parts/content/content-result.php');
}