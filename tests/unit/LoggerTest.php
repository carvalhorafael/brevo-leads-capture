<?php
/**
 * Logger unit tests.
 *
 * @package Brevo_Leads_Capture
 */

namespace BrevoLeadsCapture\Tests\Unit;

use BrevoLeadsCapture\Tests\TestCase;
use Brevo_Leads_Capture_Logger;

class LoggerTest extends TestCase {
	public function test_redacts_sensitive_context_values(): void {
		$logger = new Brevo_Leads_Capture_Logger();

		$context = $logger->redact_context(
			array(
				'api_key' => 'secret',
				'email'   => 'lead@example.com',
				'nested'  => array(
					'payload' => array(
						'email' => 'lead@example.com',
					),
				),
				'status_code' => 400,
			)
		);

		$this->assertSame( '[redacted]', $context['api_key'] );
		$this->assertSame( '[redacted]', $context['email'] );
		$this->assertSame( '[redacted]', $context['nested']['payload'] );
		$this->assertSame( 400, $context['status_code'] );
	}
}
