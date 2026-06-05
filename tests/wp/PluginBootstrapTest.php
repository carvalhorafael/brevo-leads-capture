<?php
/**
 * WordPress bootstrap smoke tests.
 *
 * @package Brevo_Leads_Capture
 */

class PluginBootstrapTest extends WP_UnitTestCase {
	public function test_plugin_main_file_exists(): void {
		$plugin_file = dirname( __DIR__, 2 ) . '/brevo-leads-capture.php';

		$this->assertFileExists( $plugin_file );
	}

	public function test_plugin_bootstrap_defines_expected_constants(): void {
		$this->assertTrue( defined( 'BREVO_LEADS_CAPTURE_VERSION' ) );
		$this->assertTrue( defined( 'BREVO_LEADS_CAPTURE_FILE' ) );
		$this->assertTrue( defined( 'BREVO_LEADS_CAPTURE_DIR' ) );
		$this->assertTrue( function_exists( 'brevo_leads_capture' ) );
	}

	public function test_plugin_registers_textdomain_loader(): void {
		$this->assertSame( 10, has_action( 'init', array( brevo_leads_capture(), 'load_textdomain' ) ) );
	}
}
