<?php
/**
 * Free material lead capture handler.
 *
 * @package Brevo_Leads_Capture
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brevo_Leads_Capture_Free_Material_Capture {
	public const ACTION = 'brevo_leads_capture_free_material';
	public const NONCE_ACTION = 'brevo_leads_capture_free_material';
	public const NONCE_FIELD = '_wpnonce';
	public const HONEYPOT_FIELD = 'brevo_leads_capture_website';

	public const META_LIST_ID = '_brevo_leads_capture_list_id';
	public const META_DELIVERY_URL = '_brevo_leads_capture_delivery_url';
	public const META_LEGACY_DELIVERY_URL = '_executive_signal_material_capture_url';

	private Brevo_Leads_Capture_Settings $settings;

	private Brevo_Leads_Capture_Lead_Payload $payload_builder;

	private Brevo_Leads_Capture_Logger $logger;

	/**
	 * @var callable|null
	 */
	private $client_factory;

	/**
	 * @param callable|null $client_factory Optional factory for tests.
	 */
	public function __construct(
		Brevo_Leads_Capture_Settings $settings,
		?Brevo_Leads_Capture_Lead_Payload $payload_builder = null,
		?callable $client_factory = null,
		?Brevo_Leads_Capture_Logger $logger = null
	) {
		$this->settings        = $settings;
		$this->payload_builder = $payload_builder ?: new Brevo_Leads_Capture_Lead_Payload();
		$this->client_factory  = $client_factory;
		$this->logger          = $logger ?: new Brevo_Leads_Capture_Logger();
	}

	public function register_hooks(): void {
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle_request' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_request' ) );
	}

	public function handle_request(): void {
		$request = $this->unslash_array( $_POST );
		$result  = $this->process_submission( $request );
		$data    = $result->data();

		$redirect_url = isset( $data['redirect_url'] ) && is_string( $data['redirect_url'] )
			? $data['redirect_url']
			: $this->fallback_redirect_url( 0, 'error' );

		wp_safe_redirect( $redirect_url, 303 );
		exit;
	}

	/**
	 * @param array<string, mixed> $request
	 */
	public function process_submission( array $request ): Brevo_Leads_Capture_Result {
		$material_id = $this->absint( $request['material_id'] ?? 0 );

		if ( ! $this->is_valid_nonce( $request[ self::NONCE_FIELD ] ?? '' ) ) {
			return $this->failure( 'invalid_nonce', $material_id );
		}

		if ( '' !== $this->clean_string( $request[ self::HONEYPOT_FIELD ] ?? '' ) ) {
			return $this->failure( 'spam', $material_id );
		}

		if ( 0 >= $material_id || ! $this->post_exists( $material_id ) ) {
			return $this->failure( 'invalid_material', 0 );
		}

		$list_id = $this->material_list_id( $material_id );
		if ( 0 >= $list_id ) {
			return $this->failure( 'missing_list', $material_id );
		}

		$delivery_url = $this->material_delivery_url( $material_id );
		if ( '' === $delivery_url ) {
			return $this->failure( 'missing_delivery', $material_id );
		}

		$payload_result = $this->payload_builder->build_contact(
			$this->lead_input_from_request( $request ),
			array(
				'source'   => 'free_material',
				'material' => $this->material_label( $material_id ),
				'list_id'  => $list_id,
			)
		);

		if ( ! $payload_result->is_successful() ) {
			return $this->failure( 'invalid_lead', $material_id );
		}

		$payload = $payload_result->data()['payload'] ?? null;
		if ( ! is_array( $payload ) ) {
			return $this->failure( 'invalid_payload', $material_id );
		}

		$brevo_result = $this->client()->create_or_update_contact( $payload );
		if ( ! $brevo_result->is_successful() ) {
			$this->logger->debug(
				'Free material Brevo request failed.',
				array(
					'material_id' => $material_id,
					'list_id'     => $list_id,
					'status_code' => $brevo_result->status_code(),
					'payload'     => $payload,
					'body'        => $brevo_result->data(),
				)
			);

			return $this->failure( 'brevo_error', $material_id );
		}

		return Brevo_Leads_Capture_Result::success(
			200,
			'Free material lead captured.',
			array(
				'redirect_url' => $delivery_url,
				'material_id'  => $material_id,
				'list_id'      => $list_id,
			)
		);
	}

	public static function nonce_action(): string {
		return self::NONCE_ACTION;
	}

	public static function nonce_field(): string {
		return self::NONCE_FIELD;
	}

	public static function honeypot_field(): string {
		return self::HONEYPOT_FIELD;
	}

	private function client(): Brevo_Leads_Capture_Brevo_Client {
		if ( null !== $this->client_factory ) {
			$client = call_user_func( $this->client_factory );
			if ( $client instanceof Brevo_Leads_Capture_Brevo_Client ) {
				return $client;
			}
		}

		return new Brevo_Leads_Capture_Brevo_Client( $this->settings->api_key() );
	}

	/**
	 * @param array<string, mixed> $request
	 *
	 * @return array<string, mixed>
	 */
	private function lead_input_from_request( array $request ): array {
		$input = array(
			'name'     => $request['name'] ?? '',
			'email'    => $request['email'] ?? '',
			'whatsapp' => $request['whatsapp'] ?? '',
		);

		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' ) as $utm_field ) {
			$input[ $utm_field ] = $request[ $utm_field ] ?? '';
		}

		return $input;
	}

	private function material_list_id( int $material_id ): int {
		$list_id = $this->absint( get_post_meta( $material_id, self::META_LIST_ID, true ) );

		return 0 < $list_id ? $list_id : $this->settings->default_list_id();
	}

	private function material_delivery_url( int $material_id ): string {
		$url = $this->clean_url( get_post_meta( $material_id, self::META_DELIVERY_URL, true ) );

		if ( '' !== $url ) {
			return $url;
		}

		return $this->clean_url( get_post_meta( $material_id, self::META_LEGACY_DELIVERY_URL, true ) );
	}

	private function material_label( int $material_id ): string {
		$title = get_the_title( $material_id );

		return is_string( $title ) ? $this->clean_string( $title ) : '';
	}

	private function post_exists( int $post_id ): bool {
		return null !== get_post( $post_id );
	}

	/**
	 * @param mixed $nonce
	 */
	private function is_valid_nonce( $nonce ): bool {
		$nonce = $this->clean_string( $nonce );

		return '' !== $nonce && false !== wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	private function failure( string $code, int $material_id ): Brevo_Leads_Capture_Result {
		return Brevo_Leads_Capture_Result::failure(
			0,
			'Free material lead capture failed.',
			array(
				'code'         => $code,
				'redirect_url' => $this->fallback_redirect_url( $material_id, $code ),
				'material_id'  => $material_id,
			)
		);
	}

	private function fallback_redirect_url( int $material_id, string $code ): string {
		$url = 0 < $material_id ? get_permalink( $material_id ) : '';

		if ( ! is_string( $url ) || '' === $url ) {
			$url = wp_get_referer();
		}

		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url( '/' );
		}

		return add_query_arg(
			array(
				'brevo_leads_capture' => 'error',
				'brevo_error'         => $code,
			),
			$url
		);
	}

	/**
	 * @param mixed $value
	 */
	private function clean_string( $value ): string {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * @param mixed $value
	 */
	private function clean_url( $value ): string {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return esc_url_raw( (string) $value );
	}

	/**
	 * @param mixed $value
	 */
	private function absint( $value ): int {
		return max( 0, (int) $value );
	}

	/**
	 * @param array<string, mixed> $value
	 *
	 * @return array<string, mixed>
	 */
	private function unslash_array( array $value ): array {
		return array_map(
			function ( $item ) {
				if ( is_array( $item ) ) {
					return $this->unslash_array( $item );
				}

				return is_string( $item ) ? wp_unslash( $item ) : $item;
			},
			$value
		);
	}
}
