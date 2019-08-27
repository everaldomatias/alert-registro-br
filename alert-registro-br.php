<?php

/**
 * Plugin Name:       Alert Registro.br
 * Plugin URI:        https://github.com/everaldomatias/alert-registro-br
 * Description:       Create custom alerts from the expiration date of domains registered in the registry.
 * Version:           0.0.2
 * Author:            Everaldo Matias
 * Author URI:        https://everaldo.dev
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       alert-registro-br
 * Domain Path:       /languages/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'ARB_VERSION', '0.0.1' );
define( 'ARB_FILE', __FILE__ );

if ( ! class_exists( 'Alert_Registro_Br' ) ) {

	include_once dirname( ARB_FILE ) . '/includes/class-alert-registro-br.php';
	add_action( 'init', 'arb_load_plugin_textdomain' );

}

/**
 * Add class to manage wp crons
 */
if ( ! class_exists( 'Alert_Registro_Br_Cron' ) ) {

	include_once dirname( ARB_FILE ) . '/includes/class-cron.php';

}

/**
 * Load the plugin text domain for translation.
 */
function arb_load_plugin_textdomain() {
	load_plugin_textdomain( 'alert-registro-br', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}