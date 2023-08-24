<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_PARSE);
set_time_limit(0);

// Function to fetch data from Google Sheet using cURL
function fetch_data_from_google_sheet()
{
    $rowMap = 'Registrations!A:M';
    // $sheetID = '1KqeNqZ2UeIkaFaCCzDulDZoumhzK3fnD7VyUuzCtlQA';
    // $apiKey = 'AIzaSyCBf1uzXV1D-Y4vYbXFEJUFnyo15ufmmmA'; // Replace with your Google API key
    
    $sheetID = esc_html(get_option('sheetID'));
    $apiKey = esc_html(get_option('apiKey'));

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://sheets.googleapis.com/v4/spreadsheets/' . $sheetID . '/values/' . $rowMap . '?key=' . $apiKey . '',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $decodedData = json_decode($response);

    // Check if $decodedData is null or empty
    if ($decodedData === null || empty($decodedData->values)) {
        return []; // Return an empty array if there's no valid data
    }

    $values = $decodedData->values;

    $keys = $values[0];
    array_shift($values);

    $resultArray = [];
    foreach ($values as $row) {
        $row = array_pad($row, count($keys), 0);
        $resultArray[] = array_combine($keys, $row);
    }

    return $resultArray;
}


// Function to insert data into the database
function insert_data_into_database()
{
    // Fetch data from Google Sheet
    $data = fetch_data_from_google_sheet();

    
    if (empty($data)) {
        return; // No data to insert, return
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'chess_score';
    
    // Truncate the table to remove all existing data
    $wpdb->query("TRUNCATE TABLE $table_name;");
    
    foreach ($data as $row) {

            // Combine "Name" and "Surname" keys into "player_name" field
            $player_name = sanitize_text_field($row['Name'] . ' ' . $row['Surname']);

            // Check if the player name already exists in the database, if yes, skip insertion
            $existing_player = $wpdb->get_var($wpdb->prepare("SELECT email FROM $table_name WHERE email = %s", $row['Email']));
            if (!empty($existing_player)) {
                continue;
            }

            $points = floatval($row['Score']);
            $rating = intval($row['Rating']);
            $games = intval($row['Rounds']);
            $email = $row['Email'];

            $wpdb->insert($table_name, array(
                'player_name' => $player_name,
                'points' => $points,
                'rating' => $rating,
                'games' => $games,
                'email' => $email,
            ));
    }
}