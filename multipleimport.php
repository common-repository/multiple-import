<?php
/*
Plugin Name: Multiple Import 1.0
Plugin URI: http://olt.ubc.ca/
Description: Imports multiple blogs
Author: OLT UBC / Michael Ha
Author URI: http://olt.ubc.ca/
Version: 1.0
*/

add_action('admin_menu', 'add_import_form');

function add_import_form() {
    add_management_page('Multiple Import', 'Multiple Import', 9, dirname(__FILE__).'/multipleimportform.php');
}
?>
