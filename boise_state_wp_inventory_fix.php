<?php
/*
Plugin Name: Boise State WP Inventory Fix
Description: Plugin for programmatically fixing minor accessibility issues introduced by the WP Inventory plugin.
Plugin URI: https://github.com/OITWPsupport/boise-state-wp-inventory-fix/releases/latest
Makes the following changes:
 - Adds title="inventory_search" to the inventory_search text input.
 - Adds title="inventory_sort_by" to the inventory_sort_by select input.
 - Adds title="inventory_category_id" to the inventory_category_id select input.
 - Strips the HREF tag from the fob/ISBN value in the results table. ISBN is often blank and an empty HREF is an a11y error.
 - Adds id="inventory_sort_by" to the inventory_sort_by select input.
 - Changes the label whose for attribute = 'inventory_sort' to for='inventory_sort_by'
Version: 0.0.4
Author: David Lentz
Author URI: https://webguide.boisestate.edu/
*/

defined( 'ABSPATH' ) or die( 'No hackers' );

if( ! class_exists( 'Boise_State_Plugin_Updater' ) ){
	include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}

$updater = new Boise_State_Plugin_Updater( __FILE__ );
$updater->set_username( 'OITWPsupport' );
$updater->set_repository( 'boise-state-wp-inventory-fix' );
$updater->initialize();

function boise_state_wp_inventory_fix($content){

	// As advised on this page: http://stackoverflow.com/questions/7997936/how-do-you-format-dom-structures-in-php
	libxml_use_internal_errors(true);

	$dom = new DOMDocument();
	$dom->encoding = 'utf-8';
//	$dom->loadHTML(utf8_decode($content));
	$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
	$xpath = new DOMXPath($dom);

	$inputs = $dom->getElementsByTagName('input');
	foreach($inputs as $input){
		$type = $input->getAttribute('type');
		$name = $input->getAttribute('name');
		if(($type == 'text') && ($name == 'inventory_search')) {
			$input->setAttribute('title', 'inventory_search');
		}
	}
	
	$selects = $dom->getElementsByTagName('select');
	foreach($selects as $select){
		$name = $select->getAttribute('name');
		if($name == 'inventory_sort_by') {
			$select->setAttribute('title', 'inventory_sort_by');
			$select->setAttribute('id', 'inventory_sort_by');
		} else if($name == 'inventory_category_id') {
			$select->setAttribute('title', 'inventory_category_id');
		}
	}

	$labels = $dom->getElementsByTagName('label');
	foreach($labels as $label){
		$for = $label->getAttribute('for');
		if($for == 'inventory_sort') {
			$label->setAttribute('for', 'inventory_sort_by');
		}
	}

	$tds = $dom->getElementsByTagName('td');
	foreach($tds as $td){
		$class = $td->getAttribute('class');
		if($class == 'inventory_fob') {
          $td->nodeValue = strip_tags($td->nodeValue);
		}
	}

	// This is from here: http://stackoverflow.com/questions/27442075/issues-with-dom-parsing-a-partial-html
	// ...and aims to prevent the additional DOCTYPE, HTML, and BODY tags that the previous saveHTML call adds:
	$html = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $dom->saveHTML()));

	return $html;

	// As advised on this page: http://stackoverflow.com/questions/7997936/how-do-you-format-dom-structures-in-php
	if (libxml_use_internal_errors(true) === true) {
		libxml_clear_errors();
	} 	

}

// The 3rd parameter here sets the priority. It's optional and defaults to 10.
// By setting this higher, these string replacements happen *after* other plugins (like Tablepress) have done their thing.
add_filter('the_content', 'boise_state_wp_inventory_fix', 999);
add_filter('the_excerpt', 'boise_state_wp_inventory_fix', 999);
