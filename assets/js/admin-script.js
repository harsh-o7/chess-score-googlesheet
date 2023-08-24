jQuery(document).ready(function($) {
    $('#import-from-google').click(function() {
        $('body').css('cursor', 'wait');
        var data = {
            'action': 'import_google_sheets',
            'nonce': chess_score_ajax.nonce
        };

        $.post(chess_score_ajax.ajax_url, data, function(response) {
            alert(response.data.message); // Display the success message
            $('body').css('cursor', 'pointer');
        });
    });
});
