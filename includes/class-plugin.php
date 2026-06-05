<?php
/**
 * Main plugin bootstrap.
 *
 * @package Brevo_Leads_Capture
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brevo_Leads_Capture_Plugin {
	private static ?Brevo_Leads_Capture_Plugin $instance = null;

	private bool $booted = false;

	private Brevo_Leads_Capture_Settings $settings;

	private Brevo_Leads_Capture_Free_Material_Capture $free_material_capture;

	private function __construct() {
		$this->settings              = new Brevo_Leads_Capture_Settings();
		$this->free_material_capture = new Brevo_Leads_Capture_Free_Material_Capture( $this->settings );
	}

	public static function instance(): Brevo_Leads_Capture_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		add_action( 'init', array( $this, 'load_textdomain' ) );
		$this->settings->register_hooks();
		$this->free_material_capture->register_hooks();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'brevo-leads-capture',
			false,
			dirname( BREVO_LEADS_CAPTURE_BASENAME ) . '/languages'
		);
	}

	public function settings(): Brevo_Leads_Capture_Settings {
		return $this->settings;
	}

	public function free_material_capture(): Brevo_Leads_Capture_Free_Material_Capture {
		return $this->free_material_capture;
	}
}
