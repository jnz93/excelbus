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

        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'register_and_enqueue_scripts'));
    }

    public function register_and_enqueue_scripts()
    {
        // Registro de folhas de estilos e frameworks css
        wp_register_style('ibm-plex-san-font', 'https://fonts.googleapis.com/css?family=IBM+Plex+Sans:400,600&display=swap', array(), 'all');
        wp_register_style('bootstrap-cdn', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css', array(), 'all');
        wp_register_style('style-excelbus', plugins_url() . '/excelbus/css/style-excelbus.css', array(), false);

        // Enfileiramento css
        wp_enqueue_style('ibm-plex-san-font');
        wp_enqueue_style('bootstrap-cdn');
        wp_enqueue_style('style-excelbus');


        // Registro de scripts
        wp_register_script('jquery', plugins_url() . '/excelbus/js/jquery-3.4.1.min.js', array(), false);
        wp_register_script('jquery-ui', plugins_url() . '/excelbus/js/jquery-ui.min.js', array('jquery'), false);
        wp_register_script('fontawesome', 'https://kit.fontawesome.com/f18f521cf8.js', array(), false);

        // Enfileiramento js
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui');
        wp_enqueue_script('fontawesome');
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