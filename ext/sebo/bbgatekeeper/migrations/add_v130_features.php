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
* v1.3.0: autologin, path exclusion, namespaced cookie domain, plus the
* config keys that were already read/written by ACP/cron code but never
* registered by a migration (flagged during phpBB extension validation).
*/
class add_v130_features extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\sebo\bbgatekeeper\migrations\add_captcha_provider_v124'];
	}

	public function update_data()
	{
		return [
			// New feature: autologin (invisible challenge)
			['config.add', ['bbgatekeeper_autologin_enable', '0']],

			// New feature: excluded paths list
			['custom', [[$this, 'add_excluded_paths_setting']]],

			// Namespaced cookie domain (previously written to core cookie_domain)
			['config.add', ['bbgatekeeper_cookie_domain', '']],
			['custom', [[$this, 'copy_legacy_cookie_domain']]],

			// Keys already used by acp/main_module.php and
			// cron/cleanup_task.php but never registered by a migration
			['config.add', ['bbgatekeeper_enable_access_log', '1']],
			['config.add', ['bbgatekeeper_autoclean_enable', '0']],
			['config.add', ['bbgatekeeper_autoclean_interval', '60']],
			['config.add', ['bbgatekeeper_autoclean_last_run', '0']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['bbgatekeeper_autologin_enable']],
			['custom', [[$this, 'remove_excluded_paths_setting']]],
			['config.remove', ['bbgatekeeper_cookie_domain']],
			['config.remove', ['bbgatekeeper_enable_access_log']],
			['config.remove', ['bbgatekeeper_autoclean_enable']],
			['config.remove', ['bbgatekeeper_autoclean_interval']],
			['config.remove', ['bbgatekeeper_autoclean_last_run']],
		];
	}

	/**
	* Seeds the 'excluded_paths' row in sebo_bbgatekeeper_settings if
	* missing, mirroring how ua_patterns/bot_domains were seeded in
	* install_gatekeeper_v10x.
	*/
	public function add_excluded_paths_setting()
	{
		$sql_ary = [
			'SELECT' => 'COUNT(s.bbgatekeeper_setting_id) AS total_rows',
			'FROM'   => [$this->table_prefix . 'sebo_bbgatekeeper_settings' => 's'],
			'WHERE'  => "s.setting_name = 'excluded_paths'",
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$total_rows = (int) $this->db->sql_fetchfield('total_rows');
		$this->db->sql_freeresult($result);

		if ($total_rows === 0)
		{
			$this->db->sql_query(
				'INSERT INTO ' . $this->table_prefix . 'sebo_bbgatekeeper_settings ' .
				$this->db->sql_build_array('INSERT', [
					'setting_name'  => 'excluded_paths',
					'setting_value' => '[]',
				])
			);
		}
	}

	public function remove_excluded_paths_setting()
	{
		$sql = 'DELETE FROM ' . $this->table_prefix . "sebo_bbgatekeeper_settings WHERE setting_name = 'excluded_paths'";
		$this->db->sql_query($sql);
	}

	/**
	* Preserves the board's existing cookie_domain value (which this
	* extension was writing to/reading from by mistake) by copying it
	* once into the new namespaced key, so already deployed sites do not
	* silently lose their configured cookie domain on upgrade.
	*/
	public function copy_legacy_cookie_domain()
	{
		$legacy_value = (string) $this->config['cookie_domain'];
		if ($legacy_value !== '')
		{
			$this->config->set('bbgatekeeper_cookie_domain', $legacy_value);
		}
	}
}
