<?php 
/**
 * Display upload item content
 *
 * @package Excelbus
 * @since 0.4.0
 */
?>
<div class="row justify-content-center col-lg-10 col-xl-10" style="display: none;">
    <header class="headerContent">
        <h3 class="headerContent__title">Upload, tratamento dos dados e publicação</h3>
        <p class="headerContent__text">Fique tranquilo! Estamos cuidado de tudo. Nós vamos salvar o arquivo, fazer o tratamento dos dados e publicar tudo!.</p>
    </header>

    <div class="bodyContent">
        <ul class="listUploads">
            <li class="listUploads__item">
                <div class="listUploads__thumb">
                    <i class="icon"></i>
                </div>
                <div class="listUploads__content">
                    <span class="listUploads__text">nome_do_arquivo.xls</span>
                    <span class="listUploads__text">1.73/1.98 MB</span>
                    <div class="listUploads__loadBar"></div>
                    <button class="btn">Cancelar</button>
                    <span class="listUploads__loadText">75%</span>
                </div>
            </li>
        </ul>

        <div class="bodyContent__footer">
            <p class="bodyContent__text">Quer enviar outra planilha?</p>
            <form style="margin-top: 30px" action="/wp-admin/admin.php?page=excelbus-plugin" enctype="multipart/form-data" method="post">
                <i class="icon"></i>
                <input type="file" name="<?php echo PREFIX; ?>_file_upload" id="'<?php echo PREFIX; ?>_file_upload_id" class=""><br>
                <input type="submit" name="<?php echo PREFIX; ?>_submit_btn" id="<?php echo PREFIX; ?>_submit_btn_id" class="btn" value="Enviar arquivo">
            </form>
        </div>
    </div>
</div>
