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

	/**
	 * @param mixed $input
	 *
	 * @return array{api_key: string, default_list_id: int}
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
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
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

	public function render_brevo_section(): void {
		echo '<p>' . esc_html__( 'Configure os dados globais usados pelas capturas de leads.', 'brevo-leads-capture' ) . '</p>';
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
	private function absint( $value ): int {
		return max( 0, (int) $value );
	}
}
