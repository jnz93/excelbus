<?php 
/**
 * Plugin name: Excelbus
 * Plugin URI: https://github.com/joanezandrades/excelbus
 * Description: Plugin que faz upload de arquivos excel e transforma os dados da planilha em publicações/atualizações
 * da tabela dos horários de ônibus.
 * Version: 0.1.0
 * Author: JA93
 * Author URI: http://unitycode.tech
 * Text domain: excelbus
 * License: GPL2
*/
if (!defined('ABSPATH'))
{
    exit;
}

// Load PHP Excel Class
require_once(plugin_dir_path(__FILE__) . '/inc/PHPExcel.php');


// Constants
define('TEXT_DOMAIN', 'excelbus_plugin');
define('PREFIX', 'exb');

class Excelbus {

    private static $instance;

    public static function getInstance()
    {
        if (self::$instance == NULL)
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Adicionar hooks aqui
    // Ex: add_action('admin_menu', array($this, 'start_upload_achive'));
    private function __construct()
    {
        // Adicionar menu
        add_action('admin_menu', array($this, 'create_menu_admin_panel'));
    }

    // Add menu page
    public function create_menu_admin_panel()
    {
        add_menu_page('Excelbus Plugin', 'Excelbus', 'administrator', 'excelbus-plugin', 'Excelbus::excelbus_render_page', 'dashicons-clock', 65);
    }

    /**
     * Function allowed_file_types(); - Verifica se o arquivo enviado é válido para a leitura e extração. Se for retorna true, caso não seja retorna false
     * @param $fileType; - Tipo do arquivo enviado no upload
     *  */    
    public function allowed_file_types($fileType)
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
    public function extract_read_and_treatment_of_data($objExcel)
    {
        // Pegar total de colunas
        $colunas = $objExcel->setActiveSheetIndex(0)->getHighestColumn();
        $total_colunas = PHPExcel_Cell::columnIndexFromString($colunas);

        // Total de linhas
        $total_linhas = $objExcel->setActiveSheetIndex(0)->getHighestRow();

        $arr_bus_for_excel = array();

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
        }
        // echo '<pre>';
        // print_r($arr_bus_for_excel);
        // echo '</pre>';
        return $arr_bus_for_excel;
    }


    /**
     * Function compare_and_return_result - Recebe um vetor de onde extrai os prefixos vindos do excel para comparação com os prefixos já registrados
     * no wordpress. Ao encontrar prefixos iguais retorna, em forma de um array, os ids para update. Retorna também um array de prefixos para novas
     * publicações.
     * @param vetor - Vetor com os dados do excel
     */
    public function compare_and_return_result($obj)
    {
        // Tratamento dos prefixos do excel e transformação em array
        $string_prefix_of_excel = '';
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
            'post_type'         => 'post',
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

                // Array meta -> String prefixos
                $string_prefix = '';
                foreach($meta_prefix as $prefix)
                {
                    $string_prefix .= $prefix;
                }
                // echo $string_prefix . '<br>';
                $arr_prefix_registered = explode(',', $string_prefix);

                if(in_array(trim($string_prefix), $arr_prefix_of_excel))
                {
                    // echo $string_prefix . ' ID: ' . $post_id . '<br>';
                    $ids_for_update .= $post_id . ',';
                    $prefix_for_update .= $string_prefix . ',';
                }
            }
            echo $ids_for_update;
            echo '<br>';
            echo $prefix_for_update;
        }
    }
    // Render excelbus html page
    public function excelbus_render_page()
    {
        echo '<h3>'. __('Excelbus Plugin - Extraia dados de uma planilha e transforme em publicações', 'TEXT_DOMAIN') .'</h3>';


        if (isset($_FILES[PREFIX . '_file_upload']))
        {
            // Diretório de upload atual
            $dir = '../wp-content/uploads' . wp_upload_dir()['subdir'];

            // Enviar o arquivo para o diretório de upload
            $target_file = $dir . '/' . basename($_FILES[PREFIX . '_file_upload']['name']);
            move_uploaded_file($_FILES[PREFIX . '_file_upload']['tmp_name'], $target_file);
            $file_name = basename($_FILES[PREFIX . '_file_upload']['name']);
            
            // Iniciar Objeto
            $PHPExcelReader = new PHPExcel_Reader_Excel5();
            $PHPExcelReader->setReadDataOnly(true);
            $objExcel = $PHPExcelReader->load($target_file);

            $excel_data_bus = Excelbus::extract_read_and_treatment_of_data($objExcel);

            Excelbus::compare_and_return_result($excel_data_bus);

        }

        echo '
            <form style="margin-top: 30px" action="/wp-admin/admin.php?page=excelbus-plugin" enctype="multipart/form-data" method="post">
                <label for="" class="">Selecione a planilha excel:</label><br>
                <input type="file" name="'. PREFIX . '_file_upload" id="'. PREFIX .'_file_upload_id" class=""><br>
                <input type="submit" name="'. PREFIX .'_submit_btn" id="'. PREFIX .'_submit_btn_id" class="" value="Fazer Upload do arquivo">
            </form>
        ';
    }
}

Excelbus::getInstance();