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

// Load archives and helpers
require_once(plugin_dir_path(__FILE__) . '/inc/PHPExcel.php');
require_once(plugin_dir_path(__FILE__) . '/inc/_helpers.php');


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
    private function __construct()
    {
        add_action('admin_menu', array($this, 'create_menu_admin_panel'));
    }

    // Add menu page
    public function create_menu_admin_panel()
    {
        add_menu_page('Excelbus Plugin', 'Excelbus', 'administrator', 'excelbus-plugin', 'Excelbus::excelbus_render_page', 'dashicons-clock', 65);
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
            $PHPExcelReader = new PHPExcel_Reader_Excel2007();
            $PHPExcelReader->setReadDataOnly(true);
            $objExcel = $PHPExcelReader->load($target_file);

            $excel_data_bus             = extract_read_and_treatment_of_data($objExcel, 'objExcel');
            $excel_boarding_points      = extract_read_and_treatment_of_data($objExcel, 'arrBoardingPoints');
            $excel_prefix_for_publish   = check_prefix($excel_data_bus, 'prefixPublish');
            
            publish_bp_and_schedules($excel_prefix_for_publish, $excel_data_bus);
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