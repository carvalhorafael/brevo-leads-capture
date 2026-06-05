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

	public function api_key(): string {
		if ( defined( 'BREVO_LEADS_CAPTURE_API_KEY' ) && is_string( BREVO_LEADS_CAPTURE_API_KEY ) ) {
			return trim( BREVO_LEADS_CAPTURE_API_KEY );
		}

		return '';
	}

	public function default_list_id(): int {
		if ( defined( 'BREVO_LEADS_CAPTURE_DEFAULT_LIST_ID' ) ) {
			return $this->absint( BREVO_LEADS_CAPTURE_DEFAULT_LIST_ID );
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
	 * @param mixed $value
	 */
	private function absint( $value ): int {
		if ( function_exists( 'absint' ) ) {
			return absint( $value );
		}

		return max( 0, (int) $value );
	}
}
