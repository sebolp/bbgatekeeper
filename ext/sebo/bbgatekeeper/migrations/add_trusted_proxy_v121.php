<?php

namespace sebo\bbgatekeeper\migrations;

class add_trusted_proxy_v121 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\sebo\bbgatekeeper\migrations\add_hits_and_bans_module_v120'];
	}

	public function update_data()
	{
		return [
			['config.add', ['bbgatekeeper_trusted_proxy_enable', '0']],
			['config.add', ['bbgatekeeper_trusted_proxy_remote_addr', '']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['bbgatekeeper_trusted_proxy_enable']],
			['config.remove', ['bbgatekeeper_trusted_proxy_remote_addr']],
		];
	}
}
