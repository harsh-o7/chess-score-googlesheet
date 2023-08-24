<?php
/*
Plugin Name: Chess Score
Plugin URI: https://your-website.com/chess-score-plugin
Description: A WordPress plugin to read data from Google Sheets and store it in the database.
Version: 1.0
Author: Your Name
Author URI: https://your-website.com
License: GPLv2 or later
*/

// Function to display the menu in the WordPress dashboard
add_action('admin_menu', 'chess_score_plugin_menu');
function chess_score_plugin_menu()
{
    // Add a top-level menu page
    $page = add_menu_page(
        'Chess Score Plugin',
        'Chess Score',
        'manage_options',
        'chess_score_plugin',
        'chess_score_plugin_settings_page',
        'dashicons-groups',
        10
    );

    // Enqueue JavaScript for the AJAX functionality
    add_action('admin_enqueue_scripts', function($hook) use ($page) {
        if ($hook == $page) {
            wp_enqueue_script('chess-score-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin-script.js', array('jquery'), null, true);
            wp_localize_script('chess-score-admin-script', 'chess_score_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('chess-score-nonce'),
            ));
        }
    });
}


// Hook the activation function to the plugin activation hook
register_activation_hook(__FILE__, 'chess_score_plugin_activate');
function chess_score_plugin_activate()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'chess_score';

  $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
    id INT(11) NOT NULL AUTO_INCREMENT,
    player_name VARCHAR(100) NOT NULL,
    points FLOAT(10, 2) NOT NULL,
    rating INT(11) NOT NULL,
    games INT(11) NOT NULL,
    email VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}



// Callback function to display the plugin settings page
function chess_score_plugin_settings_page()
{
  settings_errors(); ?>

  <div class="wrap">
  <h1>Chess Score</h1>
    <form method="post" action="options.php">
      <?php settings_fields('chess_option_group'); ?>
      <div style="margin-top: 30px;">
        <label>Sheet ID : <input style="margin-left: 15px;" type="text" name="sheetID" value="<?php echo esc_html(get_option('sheetID')); ?>"></label>
      </div>
      <div style="margin-top: 20px;">
        <label>API Key : <input style="margin-left: 20px;" type="text" name="apiKey" value="<?php echo esc_html(get_option('apiKey')); ?>"></label>
      </div>
      <?php submit_button('Save Changes'); ?>
    </form>
    <button id="import-from-google" class="button button-primary">Import Data from Google Sheets</button>
  </div>

<?php
}


add_action('admin_menu', 'maq_register_form_settings_function');
function maq_register_form_settings_function()
{
  register_setting('chess_option_group', 'maq_option_name');
  if (isset($_POST['action']) && current_user_can('manage_options')) {
    update_option('sheetID', sanitize_text_field($_POST['sheetID']));
    update_option('apiKey', sanitize_text_field($_POST['apiKey']));
  }
}



// Include the Google API Client library
require_once plugin_dir_path(__FILE__) . 'includes/import-from-google-sheet.php';



// Shortcode to display stored data with AJAX pagination
add_shortcode('chess_score_table', 'chess_score_table_shortcode');
function chess_score_table_shortcode($atts)
{
    ob_start();
    include plugin_dir_path(__FILE__) . 'includes/chess-score-table.php';
    return ob_get_clean();
}



// Add AJAX handler for fetching data
add_action('wp_ajax_chess_score_get_data', 'chess_score_get_data');
add_action('wp_ajax_nopriv_chess_score_get_data', 'chess_score_get_data');

function chess_score_get_data() {
    check_ajax_referer('chess_score_nonce', 'nonce');

    ob_start();
    include plugin_dir_path(__FILE__) . 'includes/chess-score-ajax.php';
    $response = ob_get_clean();

    echo $response;

    wp_die(); // Always use wp_die() to terminate AJAX calls
}



// Enqueue the AJAX script
function enqueue_chess_score_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('chess-score-ajax', plugin_dir_url(__FILE__) . 'assets/js/chess-score-ajax.js', array('jquery'), time(), true);

    // Localize data for AJAX
    wp_localize_script('chess-score-ajax', 'chess_score_ajax_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('chess_score_nonce'),
    ));

    wp_enqueue_style('chess-score-style', plugin_dir_url(__FILE__) . 'assets/css/chess-score-style.css',array(),time());
}
add_action('wp_enqueue_scripts', 'enqueue_chess_score_scripts');



// AJAX handler for importing data from Google Sheets
add_action('wp_ajax_import_google_sheets', 'chess_score_import_google_sheets');
function chess_score_import_google_sheets()
{
    insert_data_into_database();
    wp_send_json_success(array('message' => 'Import successful.'));
}



// Enqueue JavaScript file for the AJAX functionality
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook == 'toplevel_page_chess_score_plugin') {
        wp_enqueue_script('chess-score-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
    }
});