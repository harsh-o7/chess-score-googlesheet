jQuery(document).ready(function($) {
    var itemsPerPage = 10; // Number of items per page
    var $chessScoreContainer = $('#chess-score-container');
    var currentPage = 1;

    // Function to fetch and display data
    function fetchAndDisplayData(page) {
        $('body').css('cursor', 'wait');
        $.ajax({
            url: chess_score_ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'chess_score_get_data',
                page: page,
                per_page: itemsPerPage,
                nonce: chess_score_ajax_params.nonce,
            },
            success: function(response) {
                $chessScoreContainer.html(response);
                $('body').css('cursor', 'pointer');
            },
        });
    }

    // Initial fetch and display
    fetchAndDisplayData(currentPage);

    // Handle pagination link clicks
    $chessScoreContainer.on('click', '.pagination a', function(e) {
        e.preventDefault();
        currentPage = $(this).data('page');
        fetchAndDisplayData(currentPage);
    });

    // Handle per page filter change
    $(document).on('change', '#per-page-filter', function() {
        itemsPerPage = $(this).val();
        currentPage = 1; // Reset to first page
        fetchAndDisplayData(currentPage);
    });
});