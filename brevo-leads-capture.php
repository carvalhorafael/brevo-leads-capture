<?php
/**
 * Plugin Name: Brevo Leads Capture
 * Description: Centraliza capturas de leads WordPress e envio de contatos para o Brevo CRM.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Rafael Carvalho
 * Plugin URI: https://github.com/carvalhorafael/brevo-leads-capture
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/carvalhorafael/brevo-leads-capture
 * Text Domain: brevo-leads-capture
 * Domain Path: /languages
 *
 * @package Brevo_Leads_Capture
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BREVO_LEADS_CAPTURE_VERSION', '0.1.0' );
define( 'BREVO_LEADS_CAPTURE_FILE', __FILE__ );
define( 'BREVO_LEADS_CAPTURE_DIR', plugin_dir_path( __FILE__ ) );
define( 'BREVO_LEADS_CAPTURE_BASENAME', plugin_basename( __FILE__ ) );

require_once BREVO_LEADS_CAPTURE_DIR . 'includes/class-result.php';
require_once BREVO_LEADS_CAPTURE_DIR . 'includes/class-logger.php';
require_once BREVO_LEADS_CAPTURE_DIR . 'includes/class-settings.php';
require_once BREVO_LEADS_CAPTURE_DIR . 'includes/class-lead-payload.php';
require_once BREVO_LEADS_CAPTURE_DIR . 'includes/class-brevo-client.php';
require_once BREVO_LEADS_CAPTURE_DIR . 'includes/class-github-updater.php';
require_once BREVO_LEADS_CAPTURE_DIR . 'includes/class-free-material-capture.php';
require_once BREVO_LEADS_CAPTURE_DIR . 'includes/integrations/class-elementor-form-mapper.php';
require_once BREVO_LEADS_CAPTURE_DIR . 'includes/class-plugin.php';

/**
 * Returns the plugin singleton.
 */
function brevo_leads_capture(): Brevo_Leads_Capture_Plugin {
	return Brevo_Leads_Capture_Plugin::instance();
}

/**
 * Returns the current public error message for the free material form.
 */
function brevo_leads_capture_get_free_material_error_message(): string {
	return brevo_leads_capture()->free_material_capture()->current_error_message();
}

/**
 * Renders the current public error message for the free material form.
 */
function brevo_leads_capture_render_free_material_error_message(): void {
	brevo_leads_capture()->free_material_capture()->render_error_message();
}

brevo_leads_capture()->boot();
