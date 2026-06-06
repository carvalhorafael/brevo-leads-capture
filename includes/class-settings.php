<?php
/**
 * Global plugin settings.
 *
 * @package Brevo_Leads_Capture
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brevo_Leads_Capture_Settings {
	public const OPTION_DEFAULT_LIST_ID = 'brevo_leads_capture_default_list_id';
	public const OPTION_SETTINGS = 'brevo_leads_capture_settings';
	public const SETTINGS_GROUP = 'brevo_leads_capture_settings';
	public const SETTINGS_PAGE = 'brevo-leads-capture';

	public const ERROR_MESSAGE_CODES = array(
		'invalid_nonce',
		'spam',
		'invalid_material',
		'missing_list',
		'missing_delivery',
		'invalid_lead',
		'invalid_payload',
		'brevo_invalid_parameter',
		'brevo_missing_parameter',
		'brevo_duplicate_parameter',
		'brevo_document_not_found',
		'brevo_permission_error',
		'brevo_bad_request',
		'brevo_error',
	);

	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_page(): void {
		add_options_page(
			__( 'Brevo Leads Capture', 'brevo-leads-capture' ),
			__( 'Brevo Leads Capture', 'brevo-leads-capture' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'brevo_leads_capture_brevo_section',
			__( 'Configuração Brevo', 'brevo-leads-capture' ),
			array( $this, 'render_brevo_section' ),
			self::SETTINGS_PAGE
		);

		add_settings_field(
			'brevo_leads_capture_api_key',
			__( 'API key Brevo', 'brevo-leads-capture' ),
			array( $this, 'render_api_key_field' ),
			self::SETTINGS_PAGE,
			'brevo_leads_capture_brevo_section',
			array( 'label_for' => 'brevo_leads_capture_api_key' )
		);

		add_settings_field(
			'brevo_leads_capture_default_list_id',
			__( 'Lista padrão Brevo', 'brevo-leads-capture' ),
			array( $this, 'render_default_list_id_field' ),
			self::SETTINGS_PAGE,
			'brevo_leads_capture_brevo_section',
			array( 'label_for' => 'brevo_leads_capture_default_list_id' )
		);

		add_settings_section(
			'brevo_leads_capture_messages_section',
			__( 'Mensagens para usuários', 'brevo-leads-capture' ),
			array( $this, 'render_messages_section' ),
			self::SETTINGS_PAGE
		);

		add_settings_field(
			'brevo_leads_capture_error_messages',
			__( 'Mensagens de erro', 'brevo-leads-capture' ),
			array( $this, 'render_error_messages_field' ),
			self::SETTINGS_PAGE,
			'brevo_leads_capture_messages_section'
		);

		add_settings_field(
			'brevo_leads_capture_success_message',
			__( 'Mensagem de sucesso', 'brevo-leads-capture' ),
			array( $this, 'render_success_message_field' ),
			self::SETTINGS_PAGE,
			'brevo_leads_capture_messages_section',
			array( 'label_for' => 'brevo_leads_capture_success_message' )
		);
	}

	public function api_key(): string {
		if ( defined( 'BREVO_LEADS_CAPTURE_API_KEY' ) && is_string( BREVO_LEADS_CAPTURE_API_KEY ) ) {
			return trim( BREVO_LEADS_CAPTURE_API_KEY );
		}

		return $this->option_string( 'api_key' );
	}

	public function default_list_id(): int {
		if ( defined( 'BREVO_LEADS_CAPTURE_DEFAULT_LIST_ID' ) ) {
			return $this->absint( BREVO_LEADS_CAPTURE_DEFAULT_LIST_ID );
		}

		$list_id = $this->absint( $this->option_string( 'default_list_id' ) );
		if ( 0 < $list_id ) {
			return $list_id;
		}

		if ( function_exists( 'get_option' ) ) {
			return $this->absint( get_option( self::OPTION_DEFAULT_LIST_ID, 0 ) );
		}

		return 0;
	}

	public function has_api_key(): bool {
		return '' !== $this->api_key();
	}

	public function error_message( string $code ): string {
		$messages = $this->error_messages();

		return $messages[ $code ] ?? $messages['brevo_error'];
	}

	public function success_message(): string {
		$message = $this->option_string( 'success_message' );

		return '' !== $message
			? $message
			: __( 'Cadastro recebido. Você será redirecionado para a página do material em 5 segundos.', 'brevo-leads-capture' );
	}

	/**
	 * @return array<string, string>
	 */
	public function error_messages(): array {
		$messages = $this->default_error_messages();
		$options  = $this->options();
		$stored   = $options['error_messages'] ?? array();

		if ( is_array( $stored ) ) {
			foreach ( self::ERROR_MESSAGE_CODES as $code ) {
				$value = $stored[ $code ] ?? '';
				if ( is_string( $value ) && '' !== trim( $value ) ) {
					$messages[ $code ] = trim( $value );
				}
			}
		}

		return $messages;
	}

	/**
	 * @param mixed $input
	 *
	 * @return array{api_key: string, default_list_id: int, error_messages: array<string, string>, success_message: string}
	 */
	public function sanitize_options( $input ): array {
		$current = $this->options();
		$input   = is_array( $input ) ? $input : array();

		$api_key = $current['api_key'] ?? '';
		if ( ! defined( 'BREVO_LEADS_CAPTURE_API_KEY' ) && array_key_exists( 'api_key', $input ) ) {
			$new_api_key = $this->clean_string( $input['api_key'] );
			if ( '' !== $new_api_key ) {
				$api_key = $new_api_key;
			}
		}

		return array(
			'api_key'         => $api_key,
			'default_list_id' => $this->absint( $input['default_list_id'] ?? 0 ),
			'error_messages'  => $this->sanitize_error_messages( $input['error_messages'] ?? ( $current['error_messages'] ?? array() ) ),
			'success_message' => $this->clean_textarea( $input['success_message'] ?? ( $current['success_message'] ?? '' ) ),
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php $this->render_status_panel(); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_PAGE );
				submit_button( __( 'Salvar configurações', 'brevo-leads-capture' ) );
				?>
			</form>
		</div>
		<?php
	}

	public function render_status_panel(): void {
		$api_key_status = $this->has_api_key()
			? __( 'Configurada', 'brevo-leads-capture' )
			: __( 'Não configurada', 'brevo-leads-capture' );
		$list_status = 0 < $this->default_list_id()
			? (string) $this->default_list_id()
			: __( 'Não configurada', 'brevo-leads-capture' );
		?>
		<div class="notice notice-info inline">
			<p><strong><?php echo esc_html__( 'Status da configuração', 'brevo-leads-capture' ); ?></strong></p>
			<ul>
				<li><?php echo esc_html__( 'API key:', 'brevo-leads-capture' ) . ' ' . esc_html( $api_key_status ); ?></li>
				<li><?php echo esc_html__( 'Lista padrão:', 'brevo-leads-capture' ) . ' ' . esc_html( $list_status ); ?></li>
				<li><?php echo esc_html__( 'Logs técnicos:', 'brevo-leads-capture' ) . ' ' . esc_html( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? __( 'Ativos via WP_DEBUG', 'brevo-leads-capture' ) : __( 'Inativos', 'brevo-leads-capture' ) ); ?></li>
			</ul>
		</div>
		<?php
	}

	public function render_brevo_section(): void {
		echo '<p>' . esc_html__( 'Configure os dados globais usados pelas capturas de leads.', 'brevo-leads-capture' ) . '</p>';
	}

	public function render_messages_section(): void {
		echo '<p>' . esc_html__( 'Configure os textos públicos exibidos após a captura de material gratuito. Respostas técnicas da Brevo continuam restritas aos logs.', 'brevo-leads-capture' ) . '</p>';
	}

	public function render_api_key_field(): void {
		$constant_configured = defined( 'BREVO_LEADS_CAPTURE_API_KEY' ) && is_string( BREVO_LEADS_CAPTURE_API_KEY ) && '' !== trim( BREVO_LEADS_CAPTURE_API_KEY );
		$stored_configured   = '' !== $this->option_string( 'api_key' );
		?>
		<input
			type="password"
			id="brevo_leads_capture_api_key"
			name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[api_key]"
			value=""
			autocomplete="new-password"
			class="regular-text"
			<?php disabled( $constant_configured ); ?>
		/>
		<?php if ( $constant_configured ) : ?>
			<p class="description"><?php echo esc_html__( 'A API key está configurada pela constante BREVO_LEADS_CAPTURE_API_KEY.', 'brevo-leads-capture' ); ?></p>
		<?php elseif ( $stored_configured ) : ?>
			<p class="description"><?php echo esc_html__( 'Uma API key já está salva. Deixe em branco para mantê-la.', 'brevo-leads-capture' ); ?></p>
		<?php else : ?>
			<p class="description"><?php echo esc_html__( 'Informe a API key da Brevo. Ela será salva no banco de dados do WordPress.', 'brevo-leads-capture' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_default_list_id_field(): void {
		$constant_configured = defined( 'BREVO_LEADS_CAPTURE_DEFAULT_LIST_ID' );
		?>
		<input
			type="number"
			min="0"
			step="1"
			id="brevo_leads_capture_default_list_id"
			name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[default_list_id]"
			value="<?php echo esc_attr( (string) $this->default_list_id() ); ?>"
			class="regular-text"
			<?php disabled( $constant_configured ); ?>
		/>
		<?php if ( $constant_configured ) : ?>
			<p class="description"><?php echo esc_html__( 'A lista padrão está configurada pela constante BREVO_LEADS_CAPTURE_DEFAULT_LIST_ID.', 'brevo-leads-capture' ); ?></p>
		<?php else : ?>
			<p class="description"><?php echo esc_html__( 'Usada quando o material gratuito não define uma lista Brevo própria.', 'brevo-leads-capture' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_error_messages_field(): void {
		$messages = $this->error_messages();
		$labels   = $this->error_message_labels();
		?>
		<div class="brevo-leads-capture-error-messages">
			<?php foreach ( self::ERROR_MESSAGE_CODES as $code ) : ?>
				<p>
					<label for="<?php echo esc_attr( 'brevo_leads_capture_error_message_' . $code ); ?>">
						<strong><?php echo esc_html( $labels[ $code ] ?? $code ); ?></strong>
						<code><?php echo esc_html( $code ); ?></code>
					</label><br>
					<textarea
						id="<?php echo esc_attr( 'brevo_leads_capture_error_message_' . $code ); ?>"
						name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[error_messages][<?php echo esc_attr( $code ); ?>]"
						rows="2"
						class="large-text"
					><?php echo esc_textarea( $messages[ $code ] ?? '' ); ?></textarea>
				</p>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php echo esc_html__( 'Deixe um campo em branco para voltar ao texto padrão.', 'brevo-leads-capture' ); ?></p>
		<?php
	}

	public function render_success_message_field(): void {
		?>
		<textarea
			id="brevo_leads_capture_success_message"
			name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[success_message]"
			rows="2"
			class="large-text"
		><?php echo esc_textarea( $this->success_message() ); ?></textarea>
		<p class="description"><?php echo esc_html__( 'Texto exibido quando a captura for concluída antes do redirecionamento automático.', 'brevo-leads-capture' ); ?></p>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	public function options(): array {
		if ( ! function_exists( 'get_option' ) ) {
			return array();
		}

		$options = get_option( self::OPTION_SETTINGS, array() );

		return is_array( $options ) ? $options : array();
	}

	private function option_string( string $key ): string {
		$options = $this->options();
		$value   = $options[ $key ] ?? '';

		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return trim( (string) $value );
	}

	/**
	 * @param mixed $value
	 */
	private function clean_string( $value ): string {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		if ( function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $value );
		}

		return trim( strip_tags( $value ) );
	}

	/**
	 * @param mixed $value
	 */
	private function clean_textarea( $value ): string {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		return function_exists( 'sanitize_textarea_field' )
			? sanitize_textarea_field( $value )
			: $this->clean_string( $value );
	}

	/**
	 * @param mixed $value
	 */
	private function absint( $value ): int {
		return max( 0, (int) $value );
	}

	/**
	 * @param mixed $messages
	 *
	 * @return array<string, string>
	 */
	private function sanitize_error_messages( $messages ): array {
		$messages  = is_array( $messages ) ? $messages : array();
		$sanitized = array();

		foreach ( self::ERROR_MESSAGE_CODES as $code ) {
			$value = $messages[ $code ] ?? '';
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = '';
			}

			$value = trim( (string) $value );
			if ( '' === $value ) {
				continue;
			}

			$sanitized[ $code ] = $this->clean_textarea( $value );
		}

		return $sanitized;
	}

	/**
	 * @return array<string, string>
	 */
	private function default_error_messages(): array {
		$generic_retry = __( 'Não conseguimos concluir seu cadastro agora. Tente novamente em instantes.', 'brevo-leads-capture' );
		$config_error  = __( 'Não conseguimos concluir seu cadastro porque este material ainda não está configurado corretamente.', 'brevo-leads-capture' );

		return array(
			'invalid_nonce'            => __( 'A sessão do formulário expirou. Recarregue a página e tente novamente.', 'brevo-leads-capture' ),
			'spam'                     => $generic_retry,
			'invalid_material'         => $config_error,
			'missing_list'             => $config_error,
			'missing_delivery'         => $config_error,
			'invalid_lead'             => __( 'Revise os dados informados e tente novamente.', 'brevo-leads-capture' ),
			'invalid_payload'          => $generic_retry,
			'brevo_invalid_parameter'  => $generic_retry,
			'brevo_missing_parameter'  => $generic_retry,
			'brevo_duplicate_parameter' => $generic_retry,
			'brevo_document_not_found' => $generic_retry,
			'brevo_permission_error'   => $generic_retry,
			'brevo_bad_request'        => $generic_retry,
			'brevo_error'              => $generic_retry,
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function error_message_labels(): array {
		return array(
			'invalid_nonce'            => __( 'Nonce inválido ou expirado', 'brevo-leads-capture' ),
			'spam'                     => __( 'Honeypot preenchido', 'brevo-leads-capture' ),
			'invalid_material'         => __( 'Material inválido', 'brevo-leads-capture' ),
			'missing_list'             => __( 'Lista Brevo ausente', 'brevo-leads-capture' ),
			'missing_delivery'         => __( 'URL de entrega ausente', 'brevo-leads-capture' ),
			'invalid_lead'             => __( 'Dados do lead inválidos', 'brevo-leads-capture' ),
			'invalid_payload'          => __( 'Payload inválido', 'brevo-leads-capture' ),
			'brevo_invalid_parameter'  => __( 'Parâmetro inválido na Brevo', 'brevo-leads-capture' ),
			'brevo_missing_parameter'  => __( 'Parâmetro ausente na Brevo', 'brevo-leads-capture' ),
			'brevo_duplicate_parameter' => __( 'Parâmetro duplicado na Brevo', 'brevo-leads-capture' ),
			'brevo_document_not_found' => __( 'Registro não encontrado na Brevo', 'brevo-leads-capture' ),
			'brevo_permission_error'   => __( 'Erro de permissão na Brevo', 'brevo-leads-capture' ),
			'brevo_bad_request'        => __( 'Requisição recusada pela Brevo', 'brevo-leads-capture' ),
			'brevo_error'              => __( 'Erro genérico da Brevo', 'brevo-leads-capture' ),
		);
	}
}
