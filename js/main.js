'use-strict'
/**
 * Upload do arquivo
 */
function handleFileSelect()
{
    if(!window.File || !window.FileReader || !window.FileList || !window.Blob)
    {
        alert('O seu navegador não suporte a API FILE.');
        return;
    }
    
    input           = jQuery('#exb_file_upload_id');

    fileName        = jQuery('#archive-name');    
    submitBtn       = jQuery('#exb_submit_btn_id');
    boxMessage      = jQuery('#success-message');
    
    successMessage = 'Seu arquivo esta pronto para ser enviado, clique em "enviar arquivo" para prosseguir.';

    input.change(function(){
        fileName.text(jQuery(this).val());
        submitBtn.fadeIn();
        boxMessage
            .fadeIn()
            .text(successMessage);
    });
}


