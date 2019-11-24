<?php 
/**
 * Display result content
 *
 * @package Excelbus
 * @since 0.4.0
 */
?>
<header class="headerContent">
    <h3 class="headerContent__title">Sucesso! Aqui estão os resultados</h3>
    <p class="headerContent__text">Tarefa realizada com sucesso! Clique nos botões para visualizar as listas de publicações e atualizações.</p>
</header>
<div class="bodyContent">
    <div class="bodyContent__cards">

        <div class="card">
            <h4 class="card__title">Veículos publicados</h4>
            <div class="card__resultContainer">
                <h2 class="card__resultNumber">18</h2>
            </div>
            <button class="btn">Ver publicados</button>
        </div>
        <div class="card">
            <h4 class="card__title">Veículos Atualizados</h4>
            <div class="card__resultContainer">
                <h2 class="card__resultNumber">38</h2>
            </div>
            <button class="btn">Ver atualizados</button>
        </div>

    </div>

    <div class="bodyContent__footer">
        <button class="btn">Voltar ao inicio</button>
    </div>
</div>

<footer id="footer-content" class="footerContent">
    <!-- Mensagens aqui -->
</footer>