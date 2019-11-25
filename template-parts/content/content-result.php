<?php 
/**
 * Display result content
 *
 * @package Excelbus
 * @since 0.4.0
 */
?>
<div class="row justify-content-center col-lg-10 col-xl-10" style="display: none;">
    <header class="bodyWrapper__header">
        <h6 class="bodyWrapper__title">Sucesso! Aqui estão os resultados</h6>
        <p class="bodyWrapper__text">Tarefa realizada com sucesso! Clique nos botões para visualizar as listas de publicações e atualizações.</p>
    </header>
    <div class="bodyWrapper__content row justify-content-center col-lg-10 col-xl-10">

            <div class="cardResult col-lg-5 col-xl-5">
                <p class="cardResult__title">Veículos publicados</p>
                <div class="cardResult__resultContainer">
                    <h4 class="cardResult__resultNumber">18</h2>
                </div>
                <button class="btn btn__secondary btn__secondary--small">Ver publicados</button>
            </div>
            <div class="cardResult col-lg-5 col-xl-5">
                <p class="cardResult__title">Veículos Atualizados</p>
                <div class="cardResult__resultContainer">
                    <h4 class="cardResult__resultNumber">38</h2>
                </div>
                <button class="btn btn__secondary btn__secondary--small">Ver atualizados</button>
            </div>

        </div>

        <div class="bodyContent__footer">
            <button class="btn btn__primary">Voltar ao inicio</button>
        </div>
    </div>
</div>