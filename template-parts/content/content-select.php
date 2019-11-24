<?php 
/**
 * Display select archive content
 *
 * @package Excelbus
 * @since 0.4.0
 */
?>
<header class="headerContent">
    <h3 class="headerContent__title">Vamos começar!</h3>
    <p class="headerContent__text">Para fazer upload arraste o arquivo da planilha, que está no seu computador, e solte em cima da área selecionada. Ou então clique no botão "selecionar arquivo" para navegar até o diretório.</p>
</header>
<div class="mainContent">
    <form style="margin-top: 30px" action="/wp-admin/admin.php?page=excelbus-plugin" enctype="multipart/form-data" method="post">
        <!-- <label for="" class="">Selecione a planilha excel:</label><br> -->
        <i class="icon"></i>
        <input type="file" name="<?php echo PREFIX; ?>_file_upload" id="'<?php echo PREFIX; ?>_file_upload_id" class=""><br>
        <input type="submit" name="<?php echo PREFIX; ?>_submit_btn" id="<?php echo PREFIX; ?>_submit_btn_id" class="btn" value="Enviar arquivo">
    </form>
</div>

<footer id="footer-content" class="footerContent">
    <!-- Mensagens aqui -->
</footer>