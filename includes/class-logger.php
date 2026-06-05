<?php
/**
 * Controlled debug logger.
 *
 * @package Brevo_Leads_Capture
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brevo_Leads_Capture_Logger {
	private const REDACTED = '[redacted]';

	/**
	 * @param array<string, mixed> $context
	 */
	public function debug( string $message, array $context = array() ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		error_log( '[brevo-leads-capture] ' . $message . ' ' . wp_json_encode( $this->redact_context( $context ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	public function is_enabled(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * @param array<string, mixed> $context
	 *
	 * @return array<string, mixed>
	 */
	public function redact_context( array $context ): array {
		foreach ( $context as $key => $value ) {
			if ( $this->is_sensitive_key( (string) $key ) ) {
				$context[ $key ] = self::REDACTED;
				continue;
			}

			if ( is_array( $value ) ) {
				$context[ $key ] = $this->redact_context( $value );
			}
		}

		return $context;
	}

	private function is_sensitive_key( string $key ): bool {
		$key = strtolower( $key );

		foreach ( array( 'api', 'key', 'token', 'secret', 'password', 'email', 'whatsapp', 'phone', 'payload', 'body' ) as $needle ) {
			if ( str_contains( $key, $needle ) ) {
				return true;
			}
		}

		return false;
	}
}
