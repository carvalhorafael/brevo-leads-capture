<?php
/**
 * GitHub updater integration tests.
 *
 * @package Brevo_Leads_Capture
 */

class GitHubUpdaterTest extends WP_UnitTestCase {
	public function tear_down(): void {
		delete_site_transient( 'brevo_leads_capture_github_release' );

		parent::tear_down();
	}

	public function test_plugin_registers_github_updater_hooks(): void {
		$this->assertSame(
			10,
			has_filter( 'pre_set_site_transient_update_plugins', array( brevo_leads_capture()->github_updater(), 'filter_update_plugins' ) )
		);
		$this->assertSame(
			10,
			has_filter( 'plugins_api', array( brevo_leads_capture()->github_updater(), 'filter_plugin_information' ) )
		);
	}

	public function test_adds_update_response_when_github_release_is_newer(): void {
		$updater = new Brevo_Leads_Capture_GitHub_Updater(
			BREVO_LEADS_CAPTURE_FILE,
			'0.1.0',
			$this->http_client_for_release( 'v0.2.0', 'brevo-leads-capture-0.2.0.zip' )
		);

		$transient          = new stdClass();
		$transient->checked = array(
			BREVO_LEADS_CAPTURE_BASENAME => '0.1.0',
		);

		$filtered = $updater->filter_update_plugins( $transient );

		$this->assertArrayHasKey( BREVO_LEADS_CAPTURE_BASENAME, $filtered->response );
		$this->assertSame( '0.2.0', $filtered->response[ BREVO_LEADS_CAPTURE_BASENAME ]->new_version );
		$this->assertSame(
			'https://github.com/carvalhorafael/brevo-leads-capture/releases/download/v0.2.0/brevo-leads-capture-0.2.0.zip',
			$filtered->response[ BREVO_LEADS_CAPTURE_BASENAME ]->package
		);
	}

	public function test_does_not_offer_update_without_installable_release_asset(): void {
		$updater = new Brevo_Leads_Capture_GitHub_Updater(
			BREVO_LEADS_CAPTURE_FILE,
			'0.1.0',
			$this->http_client_for_release( 'v0.2.0', 'source-code.zip' )
		);

		$transient          = new stdClass();
		$transient->checked = array(
			BREVO_LEADS_CAPTURE_BASENAME => '0.1.0',
		);

		$filtered = $updater->filter_update_plugins( $transient );

		$this->assertFalse( property_exists( $filtered, 'response' ) );
	}

	public function test_plugin_information_uses_latest_release_metadata(): void {
		$updater = new Brevo_Leads_Capture_GitHub_Updater(
			BREVO_LEADS_CAPTURE_FILE,
			'0.1.0',
			$this->http_client_for_release( 'v0.2.0', 'brevo-leads-capture-0.2.0.zip' )
		);

		$args       = (object) array( 'slug' => 'brevo-leads-capture' );
		$plugin_api = $updater->filter_plugin_information( false, 'plugin_information', $args );

		$this->assertSame( 'Brevo Leads Capture', $plugin_api->name );
		$this->assertSame( '0.2.0', $plugin_api->version );
		$this->assertSame(
			'https://github.com/carvalhorafael/brevo-leads-capture/releases/download/v0.2.0/brevo-leads-capture-0.2.0.zip',
			$plugin_api->download_link
		);
	}

	private function http_client_for_release( string $tag, string $asset_name ): callable {
		return static function () use ( $tag, $asset_name ): array {
			return array(
				'response' => array(
					'code' => 200,
				),
				'body'     => wp_json_encode(
					array(
						'tag_name'     => $tag,
						'name'         => $tag,
						'draft'        => false,
						'prerelease'   => false,
						'html_url'     => 'https://github.com/carvalhorafael/brevo-leads-capture/releases/tag/' . $tag,
						'published_at' => '2026-06-05T12:00:00Z',
						'body'         => 'Release notes.',
						'assets'       => array(
							array(
								'name'                 => $asset_name,
								'browser_download_url' => 'https://github.com/carvalhorafael/brevo-leads-capture/releases/download/' . $tag . '/' . $asset_name,
							),
						),
					)
				),
			);
		};
	}
}
