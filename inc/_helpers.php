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
 * Ao encontrar prefixos já publicados adiciona em um array para update.
 * Se o prefixo não for registrado no wordpress cria uma coleção de prefixos para publicação.
 * A função precisa de um retorno especificado para ocasião de uso.
 * @param Object
 * @return Array
 */
function check_prefix($objExcel, $return)
{
    // Extração dos prefixos
    $string_prefix_of_excel = '';
    for($i = 0; $i <= count($objExcel); $i++)
    {
        if (isset($objExcel[$i]))
        {
            $string_prefix_of_excel .= $objExcel[$i] . ',';
        }
    }
    // Conversão em array de prefixos
    $arr_prefix_from_excel = explode(',', $string_prefix_of_excel);
    
    
    // WP_Query('wbtm_bus') para comparações
    $args = array(
        'post_type'         => 'wbtm_bus',
        'post_status'       => 'publish',
        'posts_per_page'    => '-1'
    );
    $get_posts = new WP_Query($args);
    
    // Arrays
    $ids_for_update = array();
    $prefix_for_new_publish = array();
    $prefix_for_exclude = array();
    $prefix_excel_after_excludes = $arr_prefix_from_excel;

    // Loop para encontrar Prefixos(onibus) já cadastrados
    if ($get_posts->have_posts())
    {
        while ($get_posts->have_posts())
        {
            $get_posts->the_post();

            $post_id            = get_the_ID();
            $title              = get_the_title();
            $meta_prefix        = get_post_meta($post_id, 'wbtm_bus_no', false);
            $od_sunday          = get_post_meta($post_id, 'od_Sun', true);
            $od_monday          = get_post_meta($post_id, 'od_Mon', true);
            $od_tuesday         = get_post_meta($post_id, 'od_Tue', true);
            $od_wednesday       = get_post_meta($post_id, 'od_Web', true);
            $od_thursday        = get_post_meta($post_id, 'od_Thu', true);
            $od_friday          = get_post_meta($post_id, 'od_Fri', true);
            $od_saturday        = get_post_meta($post_id, 'od_Sat', true);
            
            // Array meta -> String prefixos
            $prefix_bus = '';
            foreach($meta_prefix as $prefix)
            {
                $prefix_bus .= $prefix;
            }
            // Verificar se o prefixo já foi cadastrado
            if(in_array(trim($prefix_bus), $arr_prefix_from_excel))
            {
                // Array de Ids para update
                $ids_for_update[] = $post_id;
                $prefix_for_exclude[] = $prefix_bus;
            }
        }
    }
    $prefix_for_new_publish = array_diff($arr_prefix_from_excel, $prefix_for_exclude);
    
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
 * Recebe um vetor do excel, prefixo do veículo e dia da semana abreviado(SEG,TER,QUA,QUI,SEX,SAB,DOM) para verificar operação no dia;
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

    // Extrair prefixos
    $arr_of_prefix = array();
    for ($i = 0; $i <= count($objExcel); $i++)
    {
        if ($objExcel[$i] != '')
        {
            $arr_of_prefix[] = $objExcel[$i];
        }
    }

    $offday = $objExcel[$prefix][$day];

    // print $offday;

    
    if (empty($offday) || $offday == '')
    {
        return 'yes';
    }
    else
    {
        echo '<pre>';
        print_r($offday);
        echo '</pre>';
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
 * Function publish_bp_and_schedules($arr);
 * Recebe um array com os prefixos ainda não registrados, cria a publicações, pega o ID e faz update nos pontos de embarque e horários
 * @param Array
 */
function publish_bp_and_schedules($list_prefix, $objExcel)
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

    // Publicação de veículos que operam aos domingos
    $ids_sundays_to_update = array();
    foreach ($sunday_prefix as $prefix)
    {
        $od_for_title = '[DOM]';
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
                'od_Sun'        => 'yes',
            ),
        );
        $sunday_post_id = wp_insert_post($post_arr);
        
        if(!is_wp_error($sunday_post_id))
        {
            // echo 'Domingo: <br>';
            // echo '<li>'. $title .' <a href="'. get_edit_post_link($sunday_post_id) .'" target="_blank">Ver</a> </li>';
            $ids_sundays_to_update[] = $sunday_post_id;
        }
        else{
            echo $sunday_post_id->get_error_message();
        }
    }

    // Merge arrays ids
    $all_ids = array_merge($ids_week_to_update, $ids_sundays_to_update);

    
    $all_ids_update = check_prefix($objExcel, 'idsUpdate');
    
    // Update nas publicações criadas [SEG~SAB]
    if (!empty($all_ids_update))
    {
        update_bp_and_schedules($all_ids_update, $objExcel);
    }
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