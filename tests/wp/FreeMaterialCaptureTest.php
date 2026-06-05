<?php
/**
 * Free material capture integration tests.
 *
 * @package Brevo_Leads_Capture
 */

class Brevo_Leads_Capture_Test_Client extends Brevo_Leads_Capture_Brevo_Client {
	/**
	 * @var array<string, mixed>|null
	 */
	public ?array $last_payload = null;

	private Brevo_Leads_Capture_Result $result;

	public function __construct( ?Brevo_Leads_Capture_Result $result = null ) {
		parent::__construct( 'test-api-key' );

		$this->result = $result ?: Brevo_Leads_Capture_Result::success( 201, 'Created.' );
	}

	/**
	 * @param array<string, mixed> $lead
	 */
	public function create_or_update_contact( array $lead ): Brevo_Leads_Capture_Result {
		$this->last_payload = $lead;

		return $this->result;
	}
}

class FreeMaterialCaptureTest extends WP_UnitTestCase {
	private Brevo_Leads_Capture_Test_Client $client;

	private Brevo_Leads_Capture_Free_Material_Capture $capture;

	public function set_up(): void {
		parent::set_up();

		$this->client  = new Brevo_Leads_Capture_Test_Client();
		$this->capture = new Brevo_Leads_Capture_Free_Material_Capture(
			brevo_leads_capture()->settings(),
			null,
			fn(): Brevo_Leads_Capture_Test_Client => $this->client
		);
	}

	public function test_registers_admin_post_hooks(): void {
		$this->assertSame(
			10,
			has_action(
				'admin_post_nopriv_' . Brevo_Leads_Capture_Free_Material_Capture::ACTION,
				array( brevo_leads_capture()->free_material_capture(), 'handle_request' )
			)
		);

		$this->assertSame(
			10,
			has_action(
				'admin_post_' . Brevo_Leads_Capture_Free_Material_Capture::ACTION,
				array( brevo_leads_capture()->free_material_capture(), 'handle_request' )
			)
		);
	}

	public function test_processes_valid_free_material_submission(): void {
		$material_id = $this->create_material(
			array(
				Brevo_Leads_Capture_Free_Material_Capture::META_LIST_ID      => '123',
				Brevo_Leads_Capture_Free_Material_Capture::META_DELIVERY_URL => 'https://example.com/download',
			)
		);

		$result = $this->capture->process_submission(
			$this->valid_request(
				$material_id,
				array(
					'name'         => 'Rafael Carvalho',
					'email'        => 'RAFAEL@example.com',
					'whatsapp'     => '+55 (11) 99999-9999',
					'utm_source'   => 'linkedin',
					'utm_campaign' => 'material',
				)
			)
		);

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( 'https://example.com/download', $result->data()['redirect_url'] );
		$this->assertSame( 'rafael@example.com', $this->client->last_payload['email'] );
		$this->assertSame( array( 123 ), $this->client->last_payload['listIds'] );
		$this->assertSame( 'free_material', $this->client->last_payload['attributes']['SOURCE'] );
		$this->assertSame( 'Material Teste', $this->client->last_payload['attributes']['MATERIAL'] );
		$this->assertSame( 'linkedin', $this->client->last_payload['attributes']['UTM_SOURCE'] );
		$this->assertSame( 'material', $this->client->last_payload['attributes']['UTM_CAMPAIGN'] );
	}

	public function test_rejects_invalid_nonce_without_calling_brevo(): void {
		$material_id = $this->create_material();

		$result = $this->capture->process_submission(
			$this->valid_request(
				$material_id,
				array( Brevo_Leads_Capture_Free_Material_Capture::NONCE_FIELD => 'invalid' )
			)
		);

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( 'invalid_nonce', $result->data()['code'] );
		$this->assertNull( $this->client->last_payload );
	}

	public function test_rejects_honeypot_without_calling_brevo(): void {
		$material_id = $this->create_material();

		$result = $this->capture->process_submission(
			$this->valid_request(
				$material_id,
				array( Brevo_Leads_Capture_Free_Material_Capture::HONEYPOT_FIELD => 'filled' )
			)
		);

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( 'spam', $result->data()['code'] );
		$this->assertNull( $this->client->last_payload );
	}

	public function test_rejects_negative_material_id_without_calling_brevo(): void {
		$result = $this->capture->process_submission( $this->valid_request( -123 ) );

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( 'invalid_material', $result->data()['code'] );
		$this->assertSame( 0, $result->data()['material_id'] );
		$this->assertNull( $this->client->last_payload );
	}

	public function test_rejects_invalid_email_without_calling_brevo(): void {
		$material_id = $this->create_material();

		$result = $this->capture->process_submission(
			$this->valid_request(
				$material_id,
				array( 'email' => 'invalid-email' )
			)
		);

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( 'invalid_lead', $result->data()['code'] );
		$this->assertNull( $this->client->last_payload );
	}

	public function test_uses_legacy_delivery_url_fallback(): void {
		$material_id = $this->create_material(
			array(
				Brevo_Leads_Capture_Free_Material_Capture::META_DELIVERY_URL        => '',
				Brevo_Leads_Capture_Free_Material_Capture::META_LIST_ID             => '456',
				Brevo_Leads_Capture_Free_Material_Capture::META_LEGACY_DELIVERY_URL => 'https://example.com/legacy-download',
			)
		);

		$result = $this->capture->process_submission( $this->valid_request( $material_id ) );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( 'https://example.com/legacy-download', $result->data()['redirect_url'] );
		$this->assertSame( array( 456 ), $this->client->last_payload['listIds'] );
	}

	public function test_returns_controlled_error_when_brevo_fails(): void {
		$this->client = new Brevo_Leads_Capture_Test_Client(
			Brevo_Leads_Capture_Result::failure( 400, 'Brevo request returned an error.', array( 'body' => array( 'api-key' => 'secret' ) ) )
		);
		$this->capture = new Brevo_Leads_Capture_Free_Material_Capture(
			brevo_leads_capture()->settings(),
			null,
			fn(): Brevo_Leads_Capture_Test_Client => $this->client
		);
		$material_id = $this->create_material();

		$result = $this->capture->process_submission( $this->valid_request( $material_id ) );

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( 'brevo_error', $result->data()['code'] );
		$this->assertStringContainsString( 'brevo_leads_capture=error', $result->data()['redirect_url'] );
		$this->assertStringNotContainsString( 'secret', $result->message() );
		$this->assertStringNotContainsString( 'secret', $result->data()['redirect_url'] );
	}

	/**
	 * @param array<string, string> $meta
	 */
	private function create_material( array $meta = array() ): int {
		$post_id = self::factory()->post->create(
			array(
				'post_title'  => 'Material Teste',
				'post_status' => 'publish',
			)
		);

		$defaults = array(
			Brevo_Leads_Capture_Free_Material_Capture::META_LIST_ID      => '123',
			Brevo_Leads_Capture_Free_Material_Capture::META_DELIVERY_URL => 'https://example.com/download',
		);

		foreach ( array_merge( $defaults, $meta ) as $key => $value ) {
			if ( '' !== $value ) {
				update_post_meta( $post_id, $key, $value );
			} else {
				delete_post_meta( $post_id, $key );
			}
		}

		return $post_id;
	}

	/**
	 * @param array<string, mixed> $overrides
	 *
	 * @return array<string, mixed>
	 */
	private function valid_request( int $material_id, array $overrides = array() ): array {
		return array_merge(
			array(
				Brevo_Leads_Capture_Free_Material_Capture::NONCE_FIELD    => wp_create_nonce( Brevo_Leads_Capture_Free_Material_Capture::NONCE_ACTION ),
				Brevo_Leads_Capture_Free_Material_Capture::HONEYPOT_FIELD => '',
				'material_id' => (string) $material_id,
				'name'        => 'Lead Teste',
				'email'       => 'lead@example.com',
				'whatsapp'    => '+55 11 99999-9999',
			),
			$overrides
		);
	}
}
