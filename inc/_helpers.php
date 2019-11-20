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

    $arr_bus_for_excel = array();
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

        if (!in_array($origem_nome, $arr_boarding_points_excel))
        {
            $arr_boarding_points_excel[] = $origem_nome;
        }

    }

    if ($return == 'objExcel')
    {            
        return $arr_bus_for_excel;
    }
    else if ($return == 'arrBoardingPoints')
    {
        return $arr_boarding_points_excel;
    }
    else
    {
        return $arr_bus_for_excel;
    }
}



/**
 * Function check_prefix_and_return_ids - Recebe um vetor extrai os prefixos para comparação com registros do wordpress. 
 * Ao encontrar prefixos iguais retorna um array os ids para update.
 * @param Object
 * @return Array
 */
function check_prefix_and_return_ids($obj)
{
    // Extração dos prefixos
    $string_prefix_of_excel = '';
    for($i = 0; $i <= count($obj); $i++)
    {
        if (isset($obj[$i]))
        {
            $string_prefix_of_excel .= $obj[$i] . ',';
        }
    }
    // Conversão em array de prefixos
    $arr_prefix_of_excel = explode(',', $string_prefix_of_excel);
    
    
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
    $prefix_excel_after_excludes = $arr_prefix_of_excel;

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
            // $wbtm_bus_boarding_points = get_post_meta($post_id, 'wbtm_bus_bp_stops', false);

            // Array meta -> String prefixos
            $prefix_bus = '';
            foreach($meta_prefix as $prefix)
            {
                $prefix_bus .= $prefix;
            }
            // Verificar se o prefixo já foi cadastrado
            if(in_array(trim($prefix_bus), $arr_prefix_of_excel))
            {

                // Como domingo é o único dia com horários diferenciados. Com isso supomos que de segunda~sábado os horários sejam os mesmos.
                if($od_sunday == 'yes')
                {
                    $operational_day = "DOM";
                }
                if($od_monday == 'yes')
                {
                    $operational_day = "SEG";
                }

                // Array de Ids para update
                $ids_for_update[] = $post_id;
                
            }
        }
    }
    // echo '<pre>';
    // print_r($ids_for_update);
    // echo '</pre>';
    return $ids_for_update;
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
        $od_sunday                      = get_post_meta($id, 'od_Sun', true);

        // Keys
        $wbtm_bus_stops_meta_key        = 'wbtm_bus_bp_stops';
        $wbtm_bus_start_time_meta_key   = 'wbtm_bus_bp_start_time';
        $od                             = '';

        // definição Operational day
        if ($od_sunday == 'yes')
        {
            $od = "DOM";
        }
        else
        {
            $od = "SEG";
        }


        // Tratamento nos nomes das cidades
        $arr_wrong_words    = array('Sao', 'Joao', 'Jose', 'Guacu', 'Aguai', 'Sp');
        $arr_correct_words  = array('São', 'João', 'José', 'Guaçu', 'Aguaí', 'SP');

        $bp_start_name              = ucwords(strtolower($objExcel[$bus_prefix][$od]['I']['origem_nome']));
        $bp_start_name              = str_replace($arr_wrong_words, $arr_correct_words, $bp_start_name);

        $bp_back_name               = ucwords(strtolower($objExcel[$bus_prefix][$od]['V']['origem_nome']));
        $bp_back_name               = str_replace($arr_wrong_words, $arr_correct_words, $bp_back_name);


        $bp_start_time              = $objExcel[$bus_prefix][$od]['I']['horarios'];
        $bp_back_time               = $objExcel[$bus_prefix][$od]['V']['horarios'];
    
        $bp_time_to_bus             = array_merge($bp_start_time, $bp_back_time);

        $bp_time_to_save = array();
        for ($i = 0; $i < count($bp_time_to_bus); $i++)
        {
            if (($i % 2) == 0)
            {
                $bp_time_to_save[] = ['wbtm_bus_bp_stops_name' => $bp_start_name, 'wbtm_bus_bp_start_time' => $bp_time_to_bus[$i]]; 
            }
            else
            {
                $bp_time_to_save[] = ['wbtm_bus_bp_stops_name' => $bp_back_name, 'wbtm_bus_bp_start_time' => $bp_time_to_bus[$i]];
            }
        }
        update_post_meta($id, 'wbtm_bus_bp_stops', $bp_time_to_save);
    }
}