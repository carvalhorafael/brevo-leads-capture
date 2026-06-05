<?php
/**
 * Bootstrap file for unit tests without WordPress.
 *
 * @package Brevo_Leads_Capture
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/fixtures/wordpress/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/includes/class-result.php';
require_once dirname( __DIR__ ) . '/includes/class-logger.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-lead-payload.php';
require_once dirname( __DIR__ ) . '/includes/class-brevo-client.php';
require_once dirname( __DIR__ ) . '/includes/integrations/class-elementor-form-mapper.php';
