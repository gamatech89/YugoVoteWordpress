<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>

<div id="cs-search-popup" class="cs-search-popup" style="display:none;" aria-hidden="true">
    <div class="cs-search-overlay"></div>
    
    <div class="cs-search-content">
        <button class="cs-search-close" aria-label="Zatvori pretragu">&times;</button>
        
        <div class="cs-search-input-wrapper">
            <input type="text" id="cs-search-input" placeholder="Pretraži liste (npr. Najbolji filmovi...)" autocomplete="off">
            <div class="cs-search-loader" style="display:none;"></div> </div>

        <div id="cs-search-results" class="cs-search-results">
            </div>
    </div>
</div>