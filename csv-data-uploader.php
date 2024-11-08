<?php
/*
 * Plugin Name:       CSV Data Uploader
 * Plugin URI:        https://github.com/developerbayazid/csv-data-uploader
 * Description:       This plugin will upload CSV data to DB Table
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Bayazid Hasan
 * Author URI:        https://github.com/developerbayazid
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cdu
*/


define('CDU_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

add_shortcode('csv-data', 'cdu_display_csv_form_data');
function cdu_display_csv_form_data(){

    // Start PHP buffer
    ob_start();

    include_once CDU_PLUGIN_DIR_PATH."/template/cdu_form.php"; // Put all contents into buffer

    // Read buffer
    $template = ob_get_contents();

    // Clean buffer
    ob_end_clean();

    return $template;
}

// DB Table create on plugin activation
register_activation_hook(__FILE__, 'cdu_table_create');
function cdu_table_create(){

    global $wpdb;

    $table_prefix = $wpdb->prefix;
    $table_name = $table_prefix."students_data";
    $table_collate = $wpdb->get_charset_collate();


    $sql_command = "
        CREATE TABLE `".$table_name."` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) DEFAULT NULL,
        `email` varchar(50) DEFAULT NULL,
        `age` int(5) DEFAULT NULL,
        `phone` varchar(30) DEFAULT NULL,
        `photo` varchar(120) DEFAULT NULL,
        PRIMARY KEY (`id`)
        ) ".$table_collate."
    ";

    require_once(ABSPATH."/wp-admin/includes/upgrade.php");

    dbDelta($sql_command);
}

add_action('wp_enqueue_scripts', 'cdu_add_js_file');
function cdu_add_js_file(){
    wp_enqueue_script('cdu-script-js', plugin_dir_url(__FILE__).'assets/js/cdu-script.js', array('jquery'));
    wp_localize_script('cdu-script-js', 'cdu_obj', array(
        'ajax_url' => admin_url('admin-ajax.php') 
    ));
}

// Handle Ajax Request
add_action('wp_ajax_cdu_submit_form_data', 'cdu_handle_ajax_req'); // When user is logged in
add_action('wp_ajax_nopriv_cdu_submit_form_data', 'cdu_handle_ajax_req'); // When user is logout

function cdu_handle_ajax_req(){

    if($_FILES['csv_data_file']){

        $csvFile = $_FILES['csv_data_file']['tmp_name'];
        $handle = fopen($csvFile, 'r');

        global $wpdb;
        $table_name = $wpdb->prefix."students_data";

        $email = $wpdb->get_results(
            "SELECT email from {$table_name}"
        );

        $data_exist = false;

        if($handle){
            $row = 0;
            while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){

                if($row == 0){
                    $row++;
                    continue;
                }

                //Data Duplicate Check
                foreach($email as $mail){
                    if($mail->email == $data[2]){
                        $data_exist = true;
                    }
                }

                //Insert data to table
                if(!$data_exist){
                    $wpdb->insert($table_name, array(
                        'name'  => $data[1],
                        'email' => $data[2],
                        'age'   => $data[3],
                        'phone' => $data[4],
                        'photo' => $data[5],
                    ));
                    echo json_encode(array(
                        'status' => 1,
                        'message' => 'Data Uploaded Successfully!'
                    ));
                }else{
                    echo json_encode(array(
                        'status' => 0,
                        'message' => 'Duplicate Data Found!'
                    ));
                }

            }

            fclose($handle);
            
        }


    } else {
        echo json_encode(array(
            'status' => 0,
            'message'=> 'No File Found!'
        ));

    }
    exit;
}


// Read Data from database
add_shortcode('students-data', 'cdu_data_reader_f_data');
function cdu_data_reader_f_data(){
    global $wpdb;
    $table_name = $wpdb->prefix."students_data";

    $email = $wpdb->get_results(
        "SELECT email from {$table_name}"
    );

    if(count($email) > 0){
        $output_html = "<ul>";
        foreach ($email as $mail) {
            $output_html.= "<li>".$mail->email."</li>";
        }
        $output_html .= "</ul>";
        return $output_html;
    }
    return "No Email Found!";


}




?>