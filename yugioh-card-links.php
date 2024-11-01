<?php

/*
Plugin Name: YuGiOh Card Links
Plugin URI: http://www.tcgplayer.com/
Description: The goal of this Plug-in is to provide an instantaneous way for you to turn all Yu-Gi-Oh! card names within your blog posts into card information links with Hi-Mid-Low pricing! You never need to highlight a card name and hit a button over and over again. Just type up your entire post and then click the "Card Parse Article" button. All Yugioh card names are instantly turned into links that have a hover effect showing the card image and its current Hi-Mid-Low price from the internets cheapest vendors!
Version: 1.0.0
Author: TCGPlayer.com
Author URI: http://www.tcgplayer.com
License: GPL2
*/

/*  Copyright 2010 TCGPlayer.com (email : webmaster@tcgplayer.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Useful links:
http://codex.wordpress.org/Function_Reference
http://codex.wordpress.org/Writing_a_Plugin
http://codex.wordpress.org/Creating_Tables_with_Plugins
http://codex.wordpress.org/Function_Reference/wpdb_Class#Run_Any_Query_on_the_Database
http://codex.wordpress.org/Data_Validation
http://codex.wordpress.org/Plugin_API
http://codex.wordpress.org/Plugin_API/Action_Reference
http://codex.wordpress.org/Adding_Administration_Menus
*/
	
	require_once( 'constants.inc.php' );
	add_action( 'init', 'yugiohcardref_load_translation_file' );
	add_action( 'init', 'yugiohcardref_load_javascript' );
	add_action( 'admin_init', 'yugiohcardref_add_button' );
	add_action( 'admin_init', 'yugiohcardref_add_before_jquery_and_options' );
	add_action( 'admin_menu', 'yugiohcardref_settings_menu' );
	register_activation_hook( __FILE__, 'yugiohcardref_plugin_install' );
	register_deactivation_hook( __FILE__,  'yugiohcardref_plugin_uninstall' );
 
	function yugiohcardref_load_translation_file() {
    // relative path to WP_PLUGIN_DIR where the translation files will sit:
		$plugin_path = plugin_basename( dirname( __FILE__ ) .'/languages' );
	    load_plugin_textdomain( YUGIOHCARDREF_PLUGIN_NAME, '', $plugin_path );
	}
	
	function yugiohcardref_plugin_install() {
		global $wpdb;
		$sql_file_name = YUGIOHCARDREF_DIRECTORY . YUGIOHCARDREF_PLUGIN_NAME . '_' . str_replace('.', '-', YUGIOHCARDREF_VERSION) . '.sql';

		$installation_version = get_option( YUGIOHCARDREF_OPTION_NAME );
	// This is the first installation
		if( !isset($installation_version) || !$installation_version ) {
		// Create the option so that we can check for upgrades later and the default partner code
			add_option( YUGIOHCARDREF_OPTION_NAME, YUGIOHCARDREF_VERSION, null, 'no' );
			add_option( YUGIOHCARDREF_PLUGIN_NAME . '_partner_code', 'WORDPRESS', null, 'no' );

			$creation_query = "CREATE TABLE $wpdb->prefix" . YUGIOHCARDREF_TABLE_NAME . " (card_name varchar(255) NOT NULL, PRIMARY KEY (card_name));";

			if( !isset( $wpdb ) ) {
				include_once( ABSPATH . '/wp-load.php' );
				include_once( ABSPATH . '/wp-includes/wp-db.php' );
			}

		// Create the table
			if( $wpdb->query( $creation_query ) === false )
				die( "Could not create the plugin table in the database. The error message was " . $wpdb->print_error() . ".\n");

		// Insert the data
			if( !is_file( $sql_file_name ) )
				die( "Could not insert the card list in the plugin table. The error message was " . $wpdb->print_error() . '.' );
			elseif( $wpdb->query( "INSERT IGNORE INTO $wpdb->prefix" . YUGIOHCARDREF_TABLE_NAME . " (card_name) VALUES" . file_get_contents( $sql_file_name ) ) === false )
				die( "Could not insert the card list in the plugin table. The error message was " . $wpdb->print_error() . '.' );

		// Copy the TinyMCE plugin file
			if( !yugiohcardref_recursive_copy( YUGIOHCARDREF_DIRECTORY . 'yugioh_tinymce/', ABSPATH . YUGIOHCARDREF_TINYMCE_PLUGIN_DIRECTORY ) )
				error_log( "Could not copy the editor plugin to the appropriate directory. Please see installation notes in order to copy the directory manually. This plugin will not work without a successful copy.\n" . YUGIOHCARDREF_DIRECTORY . 'yugioh_tinymce/', 0 );

	// This is an upgrade
		} elseif ( $installation_version != YUGIOHCARDREF_VERSION ) {
			update_option( YUGIOHCARDREF_OPTION_NAME, YUGIOHCARDREF_VERSION );
		// Update the TinyMCE plugin
			yugiohcardref_recursive_copy( YUGIOHCARDREF_DIRECTORY . 'yugioh_tinymce/', ABSPATH . YUGIOHCARDREF_TINYMCE_PLUGIN_DIRECTORY );

			if( !isset( $wpdb ) ) {
				include_once( ABSPATH . '/wp-load.php' );
				include_once( ABSPATH . '/wp-includes/wp-db.php' );
			}

		// Update the data in the database
			if( !is_file( $sql_file_name ) )
				die( "Could not insert the card list in the plugin table. The error message was " . $wpdb->print_error() . '.' );
			elseif( !$wpdb->query( "INSERT IGNORE INTO $wpdb->prefix" . YUGIOHCARDREF_TABLE_NAME . " VALUES" . file_get_contents( $sql_file_name ) ) )
				die( "Could not insert the card list in the plugin table. The error message was " . $wpdb->print_error() . '.' );
		}
	}

// Remove the option for the plugin version and the table containing all data
	function yugiohcardref_plugin_uninstall() {
		global $wpdb;

		delete_option( YUGIOHCARDREF_OPTION_NAME );
		
	// Only send the query if the database access is enabled
		if( isset( $wpdb ) )
			@$wpdb->query( 'DROP TABLE ' . $wpdb->prefix . YUGIOHCARDREF_TABLE_NAME );
	}

	function yugiohcardref_add_button() {
	// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
			return;

	// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') ) {
			add_filter( 'mce_external_plugins', 'yugiohcardref_add_tinymce_plugin' );
			add_filter( 'mce_buttons', 'yugiohcardref_register_button' );
		}
	}
	
// The button to the TinyMCE UI
	function yugiohcardref_register_button( $buttons_array ) {
		array_push( $buttons_array, 'separator', YUGIOHCARDREF_PLUGIN_NAME );
		return $buttons_array;
	}
	
// Add the TinyMCE plugin to the list of enabled plugins
	function yugiohcardref_add_tinymce_plugin( $plugins_array ) {
		$plugins_array[YUGIOHCARDREF_PLUGIN_NAME] = 'plugins/yugioh_tinymce/editor_plugin.js';
		return $plugins_array;
	}
	
	function yugiohcardref_add_before_jquery_and_options() {
		wp_localize_script( 'jquery', 'yugiohValues', array( 'nonce' => wp_create_nonce( YUGIOHCARDREF_NONCE_NAME ), 'parser' => plugins_url( 'parser.php', __FILE__ ), 'path' => realpath(ABSPATH) ) );
		wp_enqueue_script( 'jquery' );
		register_setting( 'yugiohcardref_options', YUGIOHCARDREF_PLUGIN_NAME . '_partner_code', 'yugiohcardref_verify_options' );
	}

	function yugiohcardref_load_javascript() {
		wp_enqueue_script( YUGIOHCARDREF_PLUGIN_NAME . '_cluetip', WP_PLUGIN_URL . '/yugioh-card-links/jquery.cluetip.min.js', array('jquery') );
		wp_enqueue_script( 'yugioh-card-links', WP_PLUGIN_URL . '/yugioh-card-links/yugioh.js', array('jquery') );
		wp_enqueue_style( 'yugioh-card-links', WP_PLUGIN_URL . '/yugioh-card-links/jquery.cluetip.css' );
	}

	function yugiohcardref_settings_menu() {
		add_options_page('Options for the YuGiOh card links plugin', 'YuGiOh Card Links', 'install_plugins', 'yugiohcardref_settings', 'yugiohcardref_display_options');
	}
	
	function yugiohcardref_display_options() {
		$partner_code = get_option( YUGIOHCARDREF_PLUGIN_NAME . '_partner_code' );
		$partner_code_name = YUGIOHCARDREF_PLUGIN_NAME . '_partner_code';
		$save_changes_text = __( 'Save Changes' );

		echo <<<OPTION_END
<div class="wrap">
	<h2>YuGiOh card links options</h2>
	<p>Enter a custom name for your Blog other than "WORDPRESS". The name must contain between 6 and 10 capital letters. This setting is optional.</p>
	<form method="post" action="options.php">
OPTION_END;
		
		settings_fields( 'yugiohcardref_options' );

		echo <<<OPTION_END
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Blog name</th>
				<td><input type="text" name="$partner_code_name" value="$partner_code" /></td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" value="$save_changes_text" /></p>
	</form>
</div>
OPTION_END;
	}
	
	function yugiohcardref_verify_options($user_input) {
		$verified_input = $user_input;
		
		if( !preg_match( '/^[A-Z]{6,10}$/', $verified_input ) )
			$verified_input = '';
		
		return $verified_input;
	}

/**
 * Functions not directly related to Wordpress
 */
	function yugiohcardref_recursive_copy( $source, $destination, $directory_permissions = 0755, $file_permissions = 0644 ) {
		if( !is_dir($source) )
			return copy( $source, $destination );
			
		if( $source[strlen($source) - 1] != '/' )
			$source .= '/';
		
	// Create directory because copy does not create the destination and assign permissions
		@mkdir( $destination );
		chmod( $destination, $directory_permissions );
		
		if( $destination[strlen($destination) - 1] != '/' )
			$destination .= '/';
		
		$source_directory = opendir( $source );
		
		if( $source_directory ) {
			while( $file_read = readdir($source_directory) ) {
				if( $file_read != '.' && $file_read != '..' ) {
					$current_file = $source . $file_read;
					$destination_file = $destination . $file_read;

					if(is_dir($current_file)) {
						yugiohcardref_recursive_copy( $current_file, $destination_file );
					} else {
						copy( $current_file, $destination_file );
						chmod( $destination_file, $file_permissions );
					}
				}
			}
			
			closedir( $source_directory );
		} else {
			return false;
		}

		return true;
	}

?>