<?php
/**
 * Elementor Pro form action adapter.
 *
 * @package Brevo_Leads_Capture
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brevo_Leads_Capture_Elementor_Form_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {
	private Brevo_Leads_Capture_Settings $settings;

	private Brevo_Leads_Capture_Elementor_Form_Mapper $mapper;

	private Brevo_Leads_Capture_Lead_Payload $payload_builder;

	private Brevo_Leads_Capture_Logger $logger;

	public function __construct(
		Brevo_Leads_Capture_Settings $settings,
		Brevo_Leads_Capture_Elementor_Form_Mapper $mapper,
		Brevo_Leads_Capture_Lead_Payload $payload_builder,
		?Brevo_Leads_Capture_Logger $logger = null
	) {
		$this->settings        = $settings;
		$this->mapper          = $mapper;
		$this->payload_builder = $payload_builder;
		$this->logger          = $logger ?: new Brevo_Leads_Capture_Logger();
	}

	public function get_name(): string {
		return 'brevo';
	}

	public function get_label(): string {
		return esc_html__( 'Brevo CRM', 'brevo-leads-capture' );
	}

	/**
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ): void {
		$widget->start_controls_section(
			'section_brevo',
			array(
				'label'     => esc_html__( 'Brevo CRM', 'brevo-leads-capture' ),
				'condition' => array(
					'submit_actions' => $this->get_name(),
				),
			)
		);

		foreach ( $this->mapper->controls() as $control_id => $control ) {
			$widget->add_control(
				$control_id,
				array(
					'label'       => esc_html( (string) $control['label'] ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'placeholder' => $control['placeholder'] ?? '',
					'default'     => $control['default'] ?? '',
					'description' => $control['description'] ?? '',
				)
			);
		}

		$widget->end_controls_section();
	}

	/**
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ): void {
		$settings = $record->get( 'form_settings' );
		$settings = is_array( $settings ) ? $settings : array();

		$api_key = $this->api_key_for_settings( $settings );
		$list_id = $this->list_id_for_settings( $settings );

		if ( '' === $api_key || 0 >= $list_id ) {
			$ajax_handler->add_error_message( esc_html__( 'Configuração Brevo incompleta.', 'brevo-leads-capture' ) );
			return;
		}

		$raw_fields = $record->get( 'fields' );
		$raw_fields = is_array( $raw_fields ) ? $raw_fields : array();
		$fields     = $this->mapper->normalize_fields( $raw_fields );
		$fields     = $this->mapper->inject_posted_utm_fields( $fields, $this->unslash_array( $_POST ) );

		$mapped = $this->mapper->map_to_payload_input(
			array_merge( $settings, array( 'brevo_list_id' => $list_id ) ),
			$fields
		);

		$payload_result = $this->payload_builder->build_contact( $mapped['input'], $mapped['context'] );
		if ( ! $payload_result->is_successful() ) {
			$ajax_handler->add_error_message( esc_html__( 'Email inválido.', 'brevo-leads-capture' ) );
			return;
		}

		$payload = $payload_result->data()['payload'] ?? null;
		if ( ! is_array( $payload ) ) {
			$ajax_handler->add_error_message( esc_html__( 'Payload Brevo inválido.', 'brevo-leads-capture' ) );
			return;
		}

		$result = ( new Brevo_Leads_Capture_Brevo_Client( $api_key ) )->create_or_update_contact( $payload );
		if ( $result->is_successful() ) {
			$ajax_handler->add_success_message( esc_html__( 'Contato adicionado ao Brevo.', 'brevo-leads-capture' ) );
			return;
		}

		$this->logger->debug(
			'Elementor Brevo request failed.',
			array(
				'status_code' => $result->status_code(),
				'payload'     => $payload,
				'body'        => $result->data(),
			)
		);

		$ajax_handler->add_error_message( esc_html__( 'Erro ao adicionar contato ao Brevo. Tente novamente.', 'brevo-leads-capture' ) );
	}

	/**
	 * @param array<string, mixed> $element
	 *
	 * @return array<string, mixed>
	 */
	public function on_export( $element ): array {
		foreach ( array_keys( $this->mapper->controls() ) as $field ) {
			unset( $element[ $field ] );
		}

		return $element;
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function api_key_for_settings( array $settings ): string {
		$global_api_key = $this->settings->api_key();
		if ( '' !== $global_api_key ) {
			return $global_api_key;
		}

		return isset( $settings['brevo_api_key'] ) && is_string( $settings['brevo_api_key'] )
			? trim( $settings['brevo_api_key'] )
			: '';
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function list_id_for_settings( array $settings ): int {
		$list_id = isset( $settings['brevo_list_id'] ) ? max( 0, (int) $settings['brevo_list_id'] ) : 0;

		return 0 < $list_id ? $list_id : $this->settings->default_list_id();
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
