<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sebo\bbgatekeeper\migrations;

/**
* v1.3.2: the extension no longer touches the website root .user.ini by
* default. Writing it ("automatic mode") is opt-in and requires an explicit
* consent that is recorded server side (time and administrator).
*/
class add_ini_auto_v132 extends \phpbb\db\migration\migration
{
	/**
	* {@inheritdoc}
	*/
	public static function depends_on()
	{
		return ['\sebo\bbgatekeeper\migrations\move_store_v131'];
	}

	/**
	* {@inheritdoc}
	*/
	public function update_data()
	{
		return [
			// 0 = manual mode (default), 1 = automatic mode (consent given)
			['config.add', ['bbgatekeeper_ini_auto_enable', '0']],

			// Unix time and username of the administrator who gave the consent
			['config.add', ['bbgatekeeper_ini_auto_consent_time', '0']],
			['config.add', ['bbgatekeeper_ini_auto_consent_user', '']],
		];
	}

	/**
	* {@inheritdoc}
	*/
	public function revert_data()
	{
		return [
			['config.remove', ['bbgatekeeper_ini_auto_enable']],
			['config.remove', ['bbgatekeeper_ini_auto_consent_time']],
			['config.remove', ['bbgatekeeper_ini_auto_consent_user']],
		];
	}
}
