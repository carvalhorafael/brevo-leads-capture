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
	public const REST_NONCE_FIELD = 'brevo_leads_capture_nonce';
	public const HONEYPOT_FIELD = 'brevo_leads_capture_website';
	public const REST_NAMESPACE = 'brevo-leads-capture/v1';
	public const REST_ROUTE = '/free-material';
	public const REST_NONCE_ROUTE = '/free-material/nonce';
	public const SHORTCODE_ERROR_MESSAGE = 'brevo_leads_capture_error';

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
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_shortcode( self::SHORTCODE_ERROR_MESSAGE, array( $this, 'render_error_message_shortcode' ) );
	}

	public function handle_request(): void {
		$request = $this->unslash_array( $_POST );
		$result  = $this->process_submission( $request );
		$data    = $result->data();

		$redirect_url = isset( $data['redirect_url'] ) && is_string( $data['redirect_url'] )
			? $data['redirect_url']
			: $this->fallback_redirect_url( 0, 'error' );

		$this->safe_redirect( $redirect_url, true === ( $data['allow_external_redirect'] ?? false ) );
		exit;
	}

	public function register_rest_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_rest_request' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_NONCE_ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_rest_nonce_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle_rest_nonce_request(): WP_REST_Response {
		$response = rest_ensure_response(
			array(
				'nonce' => wp_create_nonce( self::NONCE_ACTION ),
			)
		);

		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );

		return $response;
	}

	/**
	 * @param WP_REST_Request $request REST request.
	 */
	public function handle_rest_request( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_params();
		if ( isset( $params[ self::REST_NONCE_FIELD ] ) ) {
			$params[ self::NONCE_FIELD ] = $params[ self::REST_NONCE_FIELD ];
		}

		$result = $this->process_submission( $params );
		$data   = $result->data();

		if ( $result->is_successful() ) {
			return rest_ensure_response(
				array(
					'success'      => true,
					'redirect_url' => $data['redirect_url'] ?? '',
					'message'      => $this->settings->success_message(),
				)
			);
		}

		$code = isset( $data['code'] ) && is_string( $data['code'] ) ? $data['code'] : 'brevo_error';

		return new WP_REST_Response(
			array(
				'success' => false,
				'code'    => $code,
				'message' => $this->public_error_message( $code ),
			),
			$this->rest_error_status( $code )
		);
	}

	public function enqueue_frontend_assets(): void {
		$style_path  = BREVO_LEADS_CAPTURE_DIR . 'assets/css/free-material-capture.css';
		$script_path = BREVO_LEADS_CAPTURE_DIR . 'assets/js/free-material-capture.js';

		wp_enqueue_style(
			'brevo-leads-capture-free-material',
			plugins_url( 'assets/css/free-material-capture.css', BREVO_LEADS_CAPTURE_FILE ),
			array(),
			$this->asset_version( $style_path )
		);

		wp_enqueue_script(
			'brevo-leads-capture-free-material',
			plugins_url( 'assets/js/free-material-capture.js', BREVO_LEADS_CAPTURE_FILE ),
			array(),
			$this->asset_version( $script_path ),
			true
		);

		wp_localize_script(
			'brevo-leads-capture-free-material',
			'BrevoLeadsCaptureFreeMaterial',
			array(
				'restUrl'             => esc_url_raw( rest_url( self::REST_NAMESPACE . self::REST_ROUTE ) ),
				'nonceUrl'            => esc_url_raw( rest_url( self::REST_NAMESPACE . self::REST_NONCE_ROUTE ) ),
				'genericMessage'      => $this->settings->error_message( 'brevo_error' ),
				'invalidNonceMessage' => $this->settings->error_message( 'invalid_nonce' ),
				'successMessage'      => $this->settings->success_message(),
				'successLabel'        => __( 'Sucesso', 'brevo-leads-capture' ),
				'errorLabel'          => __( 'Erro', 'brevo-leads-capture' ),
				'redirectLinkLabel'   => __( 'Acessar o material agora', 'brevo-leads-capture' ),
				'redirectDelayMs'     => 5000,
			)
		);
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
					'payload'     => $this->payload_summary( $payload ),
					'brevo_error' => $this->brevo_error_summary( $brevo_result ),
				)
			);

			return $this->failure( $this->brevo_failure_code( $brevo_result ), $material_id );
		}

		return Brevo_Leads_Capture_Result::success(
			200,
			'Free material lead captured.',
			array(
				'redirect_url'             => $delivery_url,
				'material_id'              => $material_id,
				'list_id'                  => $list_id,
				'allow_external_redirect' => true,
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

	public function current_error_message(): string {
		$request = $this->unslash_array( $_GET );

		if ( 'error' !== $this->clean_string( $request['brevo_leads_capture'] ?? '' ) ) {
			return '';
		}

		$code = $this->clean_string( $request['brevo_error'] ?? '' );
		if ( ! in_array( $code, Brevo_Leads_Capture_Settings::ERROR_MESSAGE_CODES, true ) ) {
			$code = 'brevo_error';
		}

		return $this->public_error_message( $code );
	}

	/**
	 * @param mixed $atts Shortcode attributes.
	 */
	public function render_error_message_shortcode( $atts = array() ): string {
		$message = $this->current_error_message();

		return $this->error_message_markup( $message );
	}

	public function render_error_message(): void {
		echo $this->error_message_markup( $this->current_error_message() );
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
				'message'      => $this->public_error_message( $code ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $payload
	 *
	 * @return array<string, mixed>
	 */
	private function payload_summary( array $payload ): array {
		$attributes = isset( $payload['attributes'] ) && is_array( $payload['attributes'] )
			? array_keys( $payload['attributes'] )
			: array();

		return array(
			'has_email'      => isset( $payload['email'] ) && '' !== $payload['email'],
			'attribute_keys' => array_values( array_map( 'strval', $attributes ) ),
			'list_ids'       => isset( $payload['listIds'] ) && is_array( $payload['listIds'] )
				? array_values( array_map( 'intval', $payload['listIds'] ) )
				: array(),
			'update_enabled' => isset( $payload['updateEnabled'] ) ? (bool) $payload['updateEnabled'] : null,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function brevo_error_summary( Brevo_Leads_Capture_Result $result ): array {
		$data = $result->data();

		if ( isset( $data['error_summary'] ) && is_array( $data['error_summary'] ) ) {
			return $data['error_summary'];
		}

		return array( 'status_code' => $result->status_code() );
	}

	private function brevo_failure_code( Brevo_Leads_Capture_Result $result ): string {
		$summary = $this->brevo_error_summary( $result );
		$code    = isset( $summary['code'] ) && is_string( $summary['code'] ) ? $summary['code'] : '';

		switch ( $code ) {
			case 'invalid_parameter':
				return 'brevo_invalid_parameter';
			case 'missing_parameter':
				return 'brevo_missing_parameter';
			case 'duplicate_parameter':
				return 'brevo_duplicate_parameter';
			case 'document_not_found':
				return 'brevo_document_not_found';
			case 'unauthorized':
			case 'permission_denied':
				return 'brevo_permission_error';
		}

		if ( 400 === $result->status_code() ) {
			return 'brevo_bad_request';
		}

		return 'brevo_error';
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

	private function safe_redirect( string $redirect_url, bool $allow_external_redirect ): void {
		if ( $allow_external_redirect ) {
			$host = $this->redirect_host( $redirect_url );

			if ( '' !== $host ) {
				add_filter(
					'allowed_redirect_hosts',
					static function ( array $hosts, string $requested_host ) use ( $host ): array {
						if ( strtolower( $requested_host ) === strtolower( $host ) && ! in_array( $host, $hosts, true ) ) {
							$hosts[] = $host;
						}

						return $hosts;
					},
					10,
					2
				);
			}
		}

		wp_safe_redirect( $redirect_url, 303 );
	}

	private function public_error_message( string $code ): string {
		return $this->settings->error_message( $code );
	}

	private function asset_version( string $path ): string {
		$modified = file_exists( $path ) ? filemtime( $path ) : false;

		return BREVO_LEADS_CAPTURE_VERSION . ( false !== $modified ? '-' . (string) $modified : '' );
	}

	private function rest_error_status( string $code ): int {
		if ( 'invalid_nonce' === $code ) {
			return 403;
		}

		if ( in_array( $code, array( 'missing_list', 'missing_delivery' ), true ) ) {
			return 500;
		}

		return 400;
	}

	private function error_message_markup( string $message ): string {
		$attributes = array(
			'class'                             => 'brevo-leads-capture-message es-panel es-operational-feedback',
			'data-brevo-leads-capture-message' => '',
			'data-tone'                         => 'muted',
			'data-padding'                      => 'md',
			'data-feedback-tone'                => 'danger',
			'role'                              => 'alert',
			'aria-live'                         => 'polite',
		);

		if ( '' === $message ) {
			$attributes['hidden'] = 'hidden';
		}

		$attribute_html = '';
		foreach ( $attributes as $name => $value ) {
			$attribute_html .= '' === $value
				? ' ' . esc_attr( $name )
				: ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return '<div' . $attribute_html . '><span class="es-badge" data-tone="danger">' . esc_html__( 'Erro', 'brevo-leads-capture' ) . '</span><p class="es-operational-feedback__message">' . esc_html( $message ) . '</p></div>';
	}

	private function redirect_host( string $redirect_url ): string {
		$host = wp_parse_url( $redirect_url, PHP_URL_HOST );

		return is_string( $host ) ? $host : '';
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
