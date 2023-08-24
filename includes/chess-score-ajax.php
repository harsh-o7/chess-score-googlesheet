<?php
global $wpdb;

$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10; // Number of records per page

// Calculate the offset based on the current page
$offset = ($page - 1) * $per_page;

// Modify the query to sort by points (maximum score) in descending order and limit to specified records
$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}chess_score ORDER BY points DESC LIMIT $per_page OFFSET $offset");

if (!empty($results)) {
    echo '<div id="chess-score-inner"><table class="score-table">';
    echo '<tr><th>Standings</th><th>Points</th><th>Player Name</th><th>Rating</th><th>Games</th></tr>';
    $rank = $offset + 1;

    foreach ($results as $index => $row) {
        $trClass = $tdClass = $badge = '';
        if ($rank == 1) {
            $trClass = "winner-tr";
            $tdClass = "winner-td";
            $badge = '<img src="' . esc_url(plugins_url('chess-score/assets/images/badge.svg')) . '" alt="winner badge">';
        }
        if ($index === 0) {
            $trClass .= ' first-row';
        }
        echo '<tr class="' . $trClass . '">';
        echo '<td class="' . $tdClass . '">' . esc_html($rank) . ' ' . $badge . '</td>';
        echo '<td>' . esc_html($row->points) . '</td>';
        echo '<td>' . esc_html($row->player_name) . '</td>';
        echo '<td>' . esc_html($row->rating) . '</td>';
        echo '<td>' . esc_html($row->games) . '</td>';
        echo '</tr>';
        $rank++;
    }
    echo '</table></div>';

    // Generate pagination controls with < 1 > >>
    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chess_score");
    $total_pages = ceil($total_records / $per_page);

    if ($total_records > $per_page) {
        echo '<div class="pagination-wrapper">';

        $pagination .= '<div class="per-page-wrapper"><label for="per-page-filter">Rows Per Page </label><select id="per-page-filter">
            <option value="10" ' . selected($per_page, 10, false) . '>10</option>
            <option value="20" ' . selected($per_page, 20, false) . '>20</option>
            <option value="50" ' . selected($per_page, 50, false) . '>50</option>
            <!-- Add more options if needed -->
        </select></div>';
        
        $pagination .= '<div class="pagination">';
        
        $pagination .= '<span class="page-number">' . $page . ' of ' . $total_pages . '</span>';
        
        $pagination .= '<div class="nav-wrapper">';

        if ($page > 1) {
            $pagination .= '<a class="navs arrow" href="#" data-page="' . ($page - 1) . '">&lt;</a>';
        } else {
            $pagination .= '<a href="javascript:void(0)" class="navs disabled">&lt;</a>';
        }
    
    
        if ($page < $total_pages) {
            $pagination .= '<a class="navs arrow" href="#" data-page="' . ($page + 1) . '">&gt;</a>';
        } else {
            $pagination .= '<a href="javascript:void(0)" class="navs disabled">&gt;</a>';
        }
        
        $pagination .= '</div></div></div>';
        echo $pagination;
    }
} else {
    echo 'No data found.';
}

wp_die(); // Always use wp_die() to terminate AJAX calls
?>