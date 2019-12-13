<?php 
/**
 * Display homepage excelbus
 *
 * @package Excelbus
 * @since 0.4.0
 */

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

    publish_vehicle_by_prefix($excel_prefix_for_publish, $excel_data_bus);
    // update_bp_and_schedules($bus_ids_for_update, $excel_data_bus);
}
?>
<main class="container-fluid row justify-content-center" role="main">
    <section id="content-home" class="sectionMain row justify-content-center col-lg-10 col-xl-10">
        <div class="row justify-content-center col-lg-10 col-xl-10">
            <header id="header-main" class="headerMain col-lg-10 col-xl-10">
                <h5 class="headerMain__title">Excelbus Plugin</h5>
                <p class="headerMain__subtitle"><?php _e('Extraia dados de uma planilha e transforme em publicações', 'TEXT_DOMAIN'); ?></p>
            </header>

            <nav class="navSteps col-lg-10 col-xl-10">
                <ul class="listSteps">
                    <li class="listSteps__item">
                        <i class="listSteps__icon"></i>
                        <span class="listSteps__text"></span>
                    </li>
                    <li class="listSteps__item">
                        <i class="listSteps__icon"></i>
                        <span class="listSteps__text"></span>
                    </li>
                    <li class="listSteps__item">
                        <i class="listSteps__icon"></i>
                        <span class="listSteps__text"></span>
                    </li>
                </ul>
            </nav>

            <div class="bodyWrapper row justify-content-center col-lg-10 col-xl-10">
                <?php include_once('content/content-select.php'); ?>
                <?php include_once('content/content-upload.php'); ?>
                <?php #include_once('content/content-result.php'); ?>
            </div>

            <footer id="footer-content" class="footerContent col-lg-10 col-xl-10">
                <!-- Mensagens aqui -->
                <span id="success-message" class="success__message" style="display: none"></span>
                <span id="danger-message" class="danger__message" style="display: none"></span>
            </footer>
        </div>
    </section>
</main>
