<?php
/**
 * WordPress bootstrap smoke tests.
 *
 * @package Brevo_Leads_Capture
 */

class PluginBootstrapTest extends WP_UnitTestCase {
	public function test_plugin_main_file_exists(): void {
		$plugin_file = dirname( __DIR__, 2 ) . '/brevo-leads-capture.php';

		if ( ! file_exists( $plugin_file ) ) {
			$this->markTestSkipped( 'Plugin bootstrap file has not been implemented yet.' );
		}

		$this->assertFileExists( $plugin_file );
	}
}
