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
* Initial install migration: default config keys, the store/bbgatekeeper/
* runtime directory tree (with a deny-all .htaccess), and the ACP module
* registration (Settings + Logs modes).
*
* Note: the store/bbgatekeeper/ directory created here is NOT removed
* automatically if the extension is later disabled/purged - that is a
* deliberate choice, see the "Reset deployment" button in ACP for the
* explicit, user-triggered equivalent.
*/
class install_gatekeeper_v10x extends \phpbb\db\migration\migration
{
	/**
	* {@inheritdoc}
	*/
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	/**
	* {@inheritdoc}
	*/
	public function update_schema()
	{
		return [
			'add_tables'        => [
				$this->table_prefix . 'sebo_bbgatekeeper_settings'  => [
					'COLUMNS'       => [
						'bbgatekeeper_setting_id'           => ['UINT', null, 'auto_increment'],
						'setting_name'              => ['VCHAR:200', ''],
						'setting_value'             => ['TEXT_UNI', ''],
					],
					'PRIMARY_KEY'   => 'bbgatekeeper_setting_id',
				],
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['bbgatekeeper_hcap_site_key', '']],
			['config.add', ['bbgatekeeper_hcap_site_secret', '']],
			['config.add', ['bbgatekeeper_hcap_sign_secret', bin2hex(random_bytes(32))]],

			['config.add', ['bbgatekeeper_cookie_name', 'sebo_bbgatekeeper_verified_hcap']],
			['config.add', ['bbgatekeeper_cookie_ttl', '86400']],
			['config.add', ['bbgatekeeper_cookie_samesite', 'Lax']],

			['config.add', ['bbgatekeeper_ip_binding_level', '2']],
			['config.add', ['bbgatekeeper_dry_run', '1']],

			['config.add', ['bbgatekeeper_deploy_ok', '0']],
			['config.add', ['bbgatekeeper_last_deploy_time', '0']],

			// ['custom', [[$this, 'create_store_directory']]],

			['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_BBGATEKEEPER_TITLE']],
			['module.add', ['acp', 'ACP_BBGATEKEEPER_TITLE', [
				'module_basename'   => '\sebo\bbgatekeeper\acp\main_module',
				'modes'             => ['settings', 'logs'],
			]]],

			['custom', [[$this, 'table_sebo_bbgatekeeper_install']]],
		];
	}

	/**
	* Default reverse-DNS domains trusted for the FCrDNS search engine
	* whitelist. Editable afterwards in ACP.
	*
	* @return array<int, string>
	*/
	protected function default_bot_domains()
	{
		return [
			'.googlebot.com',
			'.google.com',
			'.search.msn.com',
			'.crawl.baidu.com',
			'.duckduckgo.com',
			'.applebot.apple.com',
		];
	}

	/**
	* Default User-Agent blocklist: command-line HTTP libraries/tools
	* plus a few aggressive SEO/AI crawlers. Editable afterwards in ACP.
	*
	* @return array<int, string>
	*/
	protected function default_ua_patterns()
	{
		return [
			'curl', 'wget', 'python-requests', 'python-urllib', 'scrapy',
			'go-http-client', 'libwww-perl', 'httpclient', 'okhttp',
			'axios', 'node-fetch', 'phantomjs', 'masscan', 'nikto',
			'semrushbot', 'ahrefsbot', 'mj12bot', 'dotbot',
			'gptbot', 'claudebot', 'ccbot', 'bytespider', 'petalbot', 'amazonbot',
		];
	}

	public function table_sebo_bbgatekeeper_install()
	{
		// Prepare a multi-dimensional array for multiple row insertion
		$data = [
			[
				'setting_name'  => 'ua_patterns',
				'setting_value' => json_encode($this->default_ua_patterns()),
			],
			[
				'setting_name'  => 'bot_domains',
				'setting_value' => json_encode($this->default_bot_domains()),
			],
		];

		$sql_ary = [
			'SELECT' => 'COUNT(s.bbgatekeeper_setting_id) AS total_rows',
			'FROM'   => [$this->table_prefix . 'sebo_bbgatekeeper_settings' => 's'],
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);

		// Fetch the total rows directly to avoid pointer conflicts
		$total_rows = (int) $this->db->sql_fetchfield('total_rows');
		$this->db->sql_freeresult($result);

		if ($total_rows === 0)
		{
			// Standard phpBB DBAL syntax for multiple row insertion
			$this->db->sql_multi_insert($this->table_prefix . 'sebo_bbgatekeeper_settings', $data);
		}
	}


	public function revert_data()
	{
		return [
			// Remove modules
			['module.remove', ['acp', 'ACP_BBGATEKEEPER_TITLE', [
				'module_basename'   => '\sebo\bbgatekeeper\acp\main_module',
				'modes'             => ['settings', 'logs'],
			]]],
			['module.remove', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_BBGATEKEEPER_TITLE']],

			// Remove configs
			['config.remove', ['bbgatekeeper_hcap_site_key']],
			['config.remove', ['bbgatekeeper_hcap_site_secret']],
			['config.remove', ['bbgatekeeper_hcap_sign_secret']],

			['config.remove', ['bbgatekeeper_cookie_name']],
			['config.remove', ['bbgatekeeper_cookie_ttl']],
			['config.remove', ['bbgatekeeper_cookie_samesite']],

			['config.remove', ['bbgatekeeper_ip_binding_level']],
			['config.remove', ['bbgatekeeper_dry_run']],

			['config.remove', ['bbgatekeeper_deploy_ok']],
			['config.remove', ['bbgatekeeper_last_deploy_time']],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
					$this->table_prefix . 'sebo_bbgatekeeper_settings',
				],
		];
	}
}
