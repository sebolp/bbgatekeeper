<?php

namespace sebo\bbgatekeeper\migrations;

class add_captcha_provider_v124 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\sebo\bbgatekeeper\migrations\add_trusted_proxy_v121'];
	}

	public function update_data()
	{
		return [
			// Existing installs keep working exactly as before: default
			// provider is 'hcaptcha', matching the only behaviour that
			// existed prior to this migration.
			['config.add', ['bbgatekeeper_captcha_provider', 'hcaptcha']],
			['config.add', ['bbgatekeeper_turnstile_site_key', '']],
			['config.add', ['bbgatekeeper_turnstile_site_secret', '']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['bbgatekeeper_captcha_provider']],
			['config.remove', ['bbgatekeeper_turnstile_site_key']],
			['config.remove', ['bbgatekeeper_turnstile_site_secret']],
		];
	}
}
