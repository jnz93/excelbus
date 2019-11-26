<?php 
/**
 * Display select archive content
 *
 * @package Excelbus
 * @since 0.4.0
 */
// wp_enqueue_script( 'rest-uploader' );
?>
<div class="row justify-content-center col-lg-10 col-xl-10">
    <header class="bodyWrapper__header">
        <h6 class="bodyWrapper__title">Vamos começar!</h6>
        <span class="bodyWrapper__text">Para fazer upload arraste o arquivo da planilha, que está no seu computador, e solte em cima da área selecionada. Ou então clique no botão "selecionar arquivo" para navegar até o diretório.</span>
    </header>
    <div class="bodyWrapper__content row justify-content-center col-lg-10 col-xl-10">
        <form action="" enctype="multipart/form-data" method="post" class="selectForm row col-lg-12 col-xl-12">
            <div class="selectForm__dragAndDrop">
                <label for="<?php echo PREFIX; ?>_file_upload_id" class="selectForm__dragAndDrop selectForm__dragAndDrop--inner">
                    <i class="selectForm__icon fas fa-file-upload"></i>                    
                    <span class="btn btn__primary btn__primary--small" onClick="handleFileSelect()">Selecione o arquivo</span>
                    <span id="archive-name" class="selectForm__archiveName"></span>

                    <input type="file" name="<?php echo PREFIX; ?>_file_upload" id="<?php echo PREFIX; ?>_file_upload_id" class="btn__primary"><br>
                </label>
            </div>
            <button name="<?php echo PREFIX; ?>_submit_btn" id="<?php echo PREFIX; ?>_submit_btn_id" class="btn btn__secondary" style="display: none;">Enviar arquivo</button>
        </form>
    </div>
</div>