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
        require_once(plugin_dir_path(__FILE__) . '/template-parts/template-home.php');
    }
}

Excelbus::getInstance();