jQuery(document).ready(function ($) {
    // Ajax for clear cache
    $('#sch-clear-cache').on('click', function (e) {
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: sch_ajax.ajax_url,
            data: {'action': 'clear_cache', 'nonce': sch_ajax.cache_nonce},
            success: function (response) {
                if (response.success) {
                    $('<p class="success">' + sch_ajax.cache_cleared + '</p>').insertAfter('#sch-clear-cache');
                }
            }
        });

    });
});
