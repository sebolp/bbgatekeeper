<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sebo\bbgatekeeper\core;

use phpbb\config\config;

/**
* Renders store/runtime/config.php from the current phpBB config values
* and the static template in templates/config.php.template.
*/
class config_exporter
{
	/** @var config */
	protected $config;

	/** @var string absolute path to this extension's store/ directory, trailing slash */
	protected $store_path;

	/** @var string absolute path to the rendering template */
	protected $template_path;

	/**
	* @param config $config
	* @param string $store_path
	*/
	public function __construct(config $config, string $store_path)
	{
		$this->config = $config;
		$this->store_path = $store_path;
		$this->template_path = __DIR__ . '/templates/config.php.template';
	}

	/**
	* Renders and writes store/bbgatekeeper_config.php
	*
	* @return bool true on success
	*/
	public function export(): bool
	{
		if (!is_readable($this->template_path))
		{
			return false;
		}

		$template = file_get_contents($this->template_path);
		if ($template === false)
		{
			return false;
		}

		$replacements = $this->build_replacements();
		$rendered = str_replace(array_keys($replacements), array_values($replacements), $template);

		$target = $this->store_path . 'bbgatekeeper_config.php';
		if (@file_put_contents($target, $rendered) === false)
		{
			return false;
		}

		// Owner read/write, group read only, no access for others. Also
		// what acp/deploy_status_checker.php expects when reporting "ok".
		@chmod($target, 0640);

		return true;
	}

	/**
	* Builds the {{PLACEHOLDER}} => safe PHP literal map. Every value goes
	* through var_export(), so quoting/escaping is handled correctly
	* regardless of content.
	*
	* @return array<string, string>
	*/
	protected function build_replacements(): array
	{
		global $db, $table_prefix;

		/** Retrieve variables */
		$ua_patterns = [];
		$bot_domains = [];

		// Build the SQL query using the array method per phpBB standards
		$sql_array = [
			'SELECT'    => 'setting_name, setting_value',
			'FROM'      => [
				$table_prefix . 'sebo_bbgatekeeper_settings' => 's'
			],
			'WHERE'     => $db->sql_in_set('setting_name', ['ua_patterns', 'bot_domains'])
		];

		// Execute the query
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);

		// Fetch results and assign variables
		while ($row = $db->sql_fetchrow($result))
		{
			if ($row['setting_name'] === 'ua_patterns')
			{
				$ua_patterns = json_decode((string) $row['setting_value'], true);
			}
			else if ($row['setting_name'] === 'bot_domains')
			{
				$bot_domains = json_decode((string) $row['setting_value'], true);
			}
		}

		// Always free the result
		$db->sql_freeresult($result);

		// Ensure they are arrays in case json_decode fails
		$ua_patterns = is_array($ua_patterns) ? $ua_patterns : [];
		$bot_domains = is_array($bot_domains) ? $bot_domains : [];

		$values = [
			'HCAP_SITE_SECRET'      => (string) ($this->config['bbgatekeeper_hcap_site_secret'] ?? ''),
			'HCAP_SITE_KEY'         => (string) ($this->config['bbgatekeeper_hcap_site_key'] ?? ''),
			'HCAP_SIGN_SECRET'      => (string) ($this->config['bbgatekeeper_hcap_sign_secret'] ?? ''),
			'HCAP_COOKIE_NAME'      => (string) ($this->config['bbgatekeeper_cookie_name'] ?? 'fpc_verified_hcap'),
			'HCAP_COOKIE_TTL'       => (int) ($this->config['bbgatekeeper_cookie_ttl'] ?? 86400),
			'HCAP_COOKIE_SAMESITE'  => (string) ($this->config['bbgatekeeper_cookie_samesite'] ?? 'Lax'),
			'HCAP_COOKIE_DOMAIN'    => (string) ($this->config['cookie_domain'] ?? ''),
			'IP_BINDING_LEVEL'      => (int) ($this->config['bbgatekeeper_ip_binding_level'] ?? 2),
			'DRY_RUN'               => (bool) ($this->config['bbgatekeeper_dry_run'] ?? true),
			'BLOCKED_UA_PATTERNS'   => is_array($ua_patterns) ? $ua_patterns : [],
			'ALLOWED_BOT_DOMAINS'   => is_array($bot_domains) ? $bot_domains : [],
		];

		$replacements = [];
		foreach ($values as $placeholder => $value)
		{
			$replacements['{{' . $placeholder . '}}'] = var_export($value, true);
		}

		return $replacements;
	}
}
