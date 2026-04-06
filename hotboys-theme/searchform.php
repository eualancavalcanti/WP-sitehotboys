<?php
/**
 * Formulario de busca
 *
 * @package HotBoys
 */
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label class="screen-reader-text" for="search-field">Buscar:</label>
    <input type="search" id="search-field" class="search-field" placeholder="Buscar cenas, atores..." value="<?php echo get_search_query(); ?>" name="s" autocomplete="off">
    <button type="submit" class="search-submit" aria-label="Buscar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    </button>
</form>
