(function($) {
    class SearchController {
        constructor() {
            // Elementi
            this.$triggerBtn = $('.cs-header-search-trigger'); // KLASA KOJU MORAŠ DATI LUPICI U HEADERU
            this.$popup = $('#cs-search-popup');
            this.$input = $('#cs-search-input');
            this.$results = $('#cs-search-results');
            this.$closeBtn = $('.cs-search-close');
            this.$overlay = $('.cs-search-overlay');
            this.$loader = $('.cs-search-loader');

            // Podešavanja
            this.typingTimer = null;
            this.doneTypingInterval = 400; // 400ms delay (debounce)
            
            // Podaci iz lokalizacije
            this.ajaxurl = (typeof voting_list_vars !== 'undefined') ? voting_list_vars.ajaxurl : '/wp-admin/admin-ajax.php';
            this.nonce = (typeof voting_list_vars !== 'undefined') ? voting_list_vars.nonce : '';

            this.initEvents();
        }

        initEvents() {
            // Otvaranje
            // Koristimo 'body' delegaciju jer Elementor ponekad kasnije učita header
            $('body').on('click', '.cs-header-search-trigger', (e) => {
                e.preventDefault();
                this.openSearch();
            });

            // Zatvaranje
            this.$closeBtn.on('click', () => this.closeSearch());
            this.$overlay.on('click', () => this.closeSearch());
            
            // Zatvaranje na ESC
            $(document).on('keyup', (e) => {
                if (e.key === "Escape" && this.$popup.is(':visible')) {
                    this.closeSearch();
                }
            });

            // Kucanje (Search Logic)
            this.$input.on('keyup', () => {
                clearTimeout(this.typingTimer);
                this.$loader.show(); // Pokaži loader dok čeka kucanje
                if (this.$input.val()) {
                    this.typingTimer = setTimeout(() => this.performSearch(), this.doneTypingInterval);
                } else {
                    this.$loader.hide();
                    this.$results.empty(); // Očisti ako je prazno
                }
            });
        }

        openSearch() {
            this.$popup.fadeIn(200);
            $('body').css('overflow', 'hidden'); // Spreči skrol pozadine
            setTimeout(() => {
                this.$input.focus(); // Fokusiraj polje odmah
            }, 100);
        }

        closeSearch() {
            this.$popup.fadeOut(200);
            $('body').css('overflow', '');
            this.$input.val(''); // Opciono: Očisti polje
            this.$results.empty(); // Opciono: Očisti rezultate
        }

        performSearch() {
            const term = this.$input.val();
            
            if (term.length < 2) { // Ne traži za 1 slovo
                this.$results.html('<p class="cs-search-msg">Unesite bar 2 karaktera...</p>');
                this.$loader.hide();
                return;
            }

            $.ajax({
                url: this.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cs_search_voting_lists',
                    term: term,
                    nonce: this.nonce
                },
                success: (response) => {
                    this.$loader.hide();
                    if (response.success) {
                        this.renderResults(response.data);
                    } else {
                        this.$results.html('<p class="cs-search-msg">Došlo je do greške.</p>');
                    }
                },
                error: () => {
                    this.$loader.hide();
                    this.$results.html('<p class="cs-search-msg">Greška u komunikaciji.</p>');
                }
            });
        }

        renderResults(items) {
            this.$results.empty();

            if (items.length === 0) {
                this.$results.html('<p class="cs-search-msg">Nema rezultata za uneti pojam.</p>');
                return;
            }

            let html = '<ul class="cs-search-list">';
            items.forEach(item => {
                // Provera slike
                let imgHtml = item.image ? `<img src="${item.image}" alt="${item.title}">` : '';
                
                html += `
                    <li>
                        <a href="${item.url}" class="cs-search-item">
                            <div class="cs-search-img">
                                ${imgHtml}
                            </div>
                            <div class="cs-search-info">
                                <span class="cs-search-cat">${item.category}</span>
                                <h4>${item.title}</h4>
                            </div>
                        </a>
                    </li>
                `;
            });
            html += '</ul>';

            this.$results.html(html);
        }
    }

    // Inicijalizacija
    $(document).ready(function() {
        new SearchController();
    });

})(jQuery);