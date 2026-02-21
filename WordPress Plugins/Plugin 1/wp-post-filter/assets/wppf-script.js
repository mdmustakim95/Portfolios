jQuery(document).ready(function($){
    var $form = $('#wppf-filter-form');
    var $results = $('#wppf-results');
    var $pagination = $('#wppf-pagination');
    var DEBOUNCE_MS = 600;

    function debounce(fn, delay){
        var timer = null;
        return function(){
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function(){ fn.apply(ctx, args); }, delay);
        };
    }

    function fetch(paged){
        var data = {
            action: 'wppf_filter_posts',
            nonce: wppf_ajax.nonce,
        
            // visible filters
            focus_area: $('#wppf-focus-area').val(),
            publication_type: $('#wppf-publication-type').val(),
            post_author: $('#wppf-post-author').val(),
            s: $('#wppf-search').val(),
        
            // pagination
            posts_per_page: $form.data('posts-per-page') || 6,
            paged: paged || 1,
        
            // hidden EXCLUSION filters from shortcode
            hidden_author_id: $form.data('hidden-author-id') || '',
            hidden_focus_area: $form.data('hidden-focus-area') || '',
            hidden_publication_type: $form.data('hidden-publication-type') || ''
        };

        $results.html('<p class="wppf-loading">Loading…</p>');
        $.post(wppf_ajax.ajax_url, data, function(resp){
            if (resp && resp.success) {
                $results.html(resp.data.html);

                // build pagination based on server emitted .wppf-pages
                var $pages = $results.find('.wppf-pages');
                if ($pages.length) {
                    var total = parseInt($pages.data('total')) || 1;
                    var current = parseInt($pages.data('current')) || 1;
                    var html = '';
                    for (var i=1;i<=total;i++){
                        html += '<button class="wppf-page-btn' + (i===current? ' active':'') + '" data-page="'+i+'">'+i+'</button>';
                    }
                    $pagination.html(html);
                } else {
                    $pagination.empty();
                }
            } else {
                $results.html('<p class="wppf-error">An error occurred.</p>');
                $pagination.empty();
            }
        }, 'json');
    }

    // selects: instant fetch on change
    $form.on('change', 'select', function(){ fetch(1); });

    // form submit (enter) immediate
    $form.on('submit', function(e){ e.preventDefault(); fetch(1); });

    // debounced search while typing
    var debouncedFetch = debounce(function(){ fetch(1); }, DEBOUNCE_MS);
    $('#wppf-search').on('input', debouncedFetch);

    // Enter key triggers immediate search
    $('#wppf-search').on('keypress', function(e){
        if (e.which === 13) { e.preventDefault(); fetch(1); }
    });

    // pagination click
    $pagination.on('click', '.wppf-page-btn', function(){
        var page = parseInt($(this).data('page')) || 1;
        fetch(page);
        $('html,body').animate({scrollTop: $('.wppf-wrapper').offset().top - 20}, 300);
    });

    // initial load
    fetch(1);
});
