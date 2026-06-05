<?php
/**
 * Settings integration tests.
 *
 * @package Brevo_Leads_Capture
 */

class SettingsTest extends WP_UnitTestCase {
	private Brevo_Leads_Capture_Settings $settings;

	public function set_up(): void {
		parent::set_up();

		$this->settings = new Brevo_Leads_Capture_Settings();
		delete_option( Brevo_Leads_Capture_Settings::OPTION_SETTINGS );
		delete_option( Brevo_Leads_Capture_Settings::OPTION_DEFAULT_LIST_ID );
	}

	public function test_plugin_registers_settings_admin_hooks(): void {
		$this->assertSame( 10, has_action( 'admin_menu', array( brevo_leads_capture()->settings(), 'register_page' ) ) );
		$this->assertSame( 10, has_action( 'admin_init', array( brevo_leads_capture()->settings(), 'register_settings' ) ) );
	}

	public function test_reads_api_key_and_default_list_id_from_grouped_option(): void {
		update_option(
			Brevo_Leads_Capture_Settings::OPTION_SETTINGS,
			array(
				'api_key'         => 'stored-api-key',
				'default_list_id' => '789',
			)
		);

		$this->assertSame( 'stored-api-key', $this->settings->api_key() );
		$this->assertSame( 789, $this->settings->default_list_id() );
		$this->assertTrue( $this->settings->has_api_key() );
	}

	public function test_default_list_id_falls_back_to_legacy_option(): void {
		update_option( Brevo_Leads_Capture_Settings::OPTION_DEFAULT_LIST_ID, '456' );

		$this->assertSame( 456, $this->settings->default_list_id() );
	}

	public function test_sanitize_options_preserves_existing_api_key_when_empty(): void {
		update_option(
			Brevo_Leads_Capture_Settings::OPTION_SETTINGS,
			array(
				'api_key'         => 'existing-api-key',
				'default_list_id' => 123,
			)
		);

		$sanitized = $this->settings->sanitize_options(
			array(
				'api_key'         => '',
				'default_list_id' => '-999',
			)
		);

		$this->assertSame( 'existing-api-key', $sanitized['api_key'] );
		$this->assertSame( 0, $sanitized['default_list_id'] );
	}

	public function test_sanitize_options_accepts_new_api_key_and_absints_list_id(): void {
		$sanitized = $this->settings->sanitize_options(
			array(
				'api_key'         => ' new-api-key ',
				'default_list_id' => '321abc',
			)
		);

		$this->assertSame( 'new-api-key', $sanitized['api_key'] );
		$this->assertSame( 321, $sanitized['default_list_id'] );
	}
}
