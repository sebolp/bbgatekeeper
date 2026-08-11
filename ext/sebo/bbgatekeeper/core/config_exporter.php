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
* Renders store/bbgatekeeper_config.php from the current phpBB config
* values and the static template in templates/config.php.template.
*
* render() only builds the string in memory and never touches disk - it
* is reused both by export() (normal deploy) and by fallback_packager
* (manual ZIP download when store/ isn't writable).
*/
class config_exporter
{
	/** @var config */
	protected $config;

	/** @var string absolute path to this extension's store/ directory, trailing slash */
	protected $store_path;

	/** @var string absolute path to the rendering template */
	protected $template_path;

	/** @var string last technical error, for logs - not translated, not shown to end users directly */
	protected $last_error = '';

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
	* @return string technical reason for the last failure of render()
	*                or export(), empty string if the last call succeeded
	*/
	public function get_last_error(): string
	{
		return $this->last_error;
	}

	/**
	* Renders the full bbgatekeeper_config.php content in memory, without
	* writing anything to disk.
	*
	* @return string|null null on failure - check get_last_error()
	*/
	public function render(): ?string
	{
		global $user;

		$user->add_lang_ext('sebo/bbgatekeeper', 'config_lang');

		if (!is_readable($this->template_path))
		{
			$this->last_error = sprintf($user->lang('BBGATEKEEPER_CONFIG_TEMPLATE_NOT_READABLE', $this->template_path));
			return null;
		}

		$template = file_get_contents($this->template_path);
		if ($template === false)
		{
			$this->last_error = sprintf($user->lang('BBGATEKEEPER_CONFIG_FAILED_TO_READ_TEMPLATE', $this->template_path));
			return null;
		}

		$replacements = $this->build_replacements();

		return str_replace(array_keys($replacements), array_values($replacements), $template);
	}

	/**
	* Renders and writes store/bbgatekeeper_config.php.
	*
	* @return bool true on success - check get_last_error() on failure
	*/
	public function export(): bool
	{
		global $user;

		$user->add_lang_ext('sebo/bbgatekeeper', 'config_lang');

		$this->last_error = '';

		$rendered = $this->render();
		if ($rendered === null)
		{
			// last_error already set by render()
			return false;
		}

		if (!is_dir($this->store_path) && !@mkdir($this->store_path, 0750, true))
		{
			$this->last_error = sprintf($user->lang('BBGATEKEEPER_CONFIG_CANNOT_CREATE_STORE_DIR', $this->store_path));
			return false;
		}

		$target = $this->store_path . 'bbgatekeeper_config.php';
		if (@file_put_contents($target, $rendered) === false)
		{
			$error = error_get_last();
			$this->last_error = sprintf($user->lang('BBGATEKEEPER_CONFIG_FAILED_TO_WRITE', $target, $error['message'] ?? 'unknown error'));
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

		// Only the active provider's site key/secret are baked into the
		// deployed config.php - the other provider's keys stay in the
		// database (so switching tabs in ACP never loses them) but are
		// never written to the runtime file.
		$provider = (string) ($this->config['bbgatekeeper_captcha_provider'] ?? 'hcaptcha');
		$provider = in_array($provider, ['hcaptcha', 'turnstile'], true) ? $provider : 'hcaptcha';

		if ($provider === 'turnstile')
		{
			$captcha_site_key = (string) ($this->config['bbgatekeeper_turnstile_site_key'] ?? '');
			$captcha_site_secret = (string) ($this->config['bbgatekeeper_turnstile_site_secret'] ?? '');
			$captcha_verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
			$captcha_response_field = 'cf-turnstile-response';
			$captcha_widget_class = 'cf-turnstile';
			$captcha_widget_script_src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
		}
		else
		{
			$captcha_site_key = (string) ($this->config['bbgatekeeper_hcap_site_key'] ?? '');
			$captcha_site_secret = (string) ($this->config['bbgatekeeper_hcap_site_secret'] ?? '');
			$captcha_verify_url = 'https://hcaptcha.com/siteverify';
			$captcha_response_field = 'h-captcha-response';
			$captcha_widget_class = 'h-captcha';
			$captcha_widget_script_src = 'https://js.hcaptcha.com/1/api.js';
		}

		$values = [
			'CAPTCHA_PROVIDER'          => $provider,
			'CAPTCHA_SITE_KEY'          => $captcha_site_key,
			'CAPTCHA_SITE_SECRET'       => $captcha_site_secret,
			'CAPTCHA_VERIFY_URL'        => $captcha_verify_url,
			'CAPTCHA_RESPONSE_FIELD'    => $captcha_response_field,
			'CAPTCHA_WIDGET_CLASS'      => $captcha_widget_class,
			'CAPTCHA_WIDGET_SCRIPT_SRC' => $captcha_widget_script_src,

			// Provider-agnostic: signs OUR OWN verification cookie, not
			// tied to whichever captcha vendor issued the challenge.
			'HCAP_SIGN_SECRET'      => (string) ($this->config['bbgatekeeper_hcap_sign_secret'] ?? ''),
			'HCAP_COOKIE_NAME'      => (string) ($this->config['bbgatekeeper_cookie_name'] ?? 'fpc_verified_hcap'),
			'HCAP_COOKIE_TTL'       => (int) ($this->config['bbgatekeeper_cookie_ttl'] ?? 86400),
			'HCAP_COOKIE_SAMESITE'  => (string) ($this->config['bbgatekeeper_cookie_samesite'] ?? 'Lax'),
			'HCAP_COOKIE_DOMAIN'    => (string) ($this->config['cookie_domain'] ?? ''),
			'HCAP_COOKIE_SECURE'    => (bool) ($this->config['cookie_secure'] ?? 'true'),
			'IP_BINDING_LEVEL'      => (int) ($this->config['bbgatekeeper_ip_binding_level'] ?? 2),
			'DRY_RUN'               => (bool) ($this->config['bbgatekeeper_dry_run'] ?? true),
			'ENABLE_ACCESS_LOG'     => (bool) ($this->config['bbgatekeeper_enable_access_log'] ?? true),
			'BLOCKED_UA_PATTERNS'   => is_array($ua_patterns) ? $ua_patterns : [],
			'ALLOWED_BOT_DOMAINS'   => is_array($bot_domains) ? $bot_domains : [],
			'TRUSTED_PROXY_ENABLE'      => (bool) ($this->config['bbgatekeeper_trusted_proxy_enable'] ?? false),
			'TRUSTED_PROXY_REMOTE_ADDR' => (string) ($this->config['bbgatekeeper_trusted_proxy_remote_addr'] ?? ''),
		];

		$replacements = [];
		foreach ($values as $placeholder => $value)
		{
			$replacements['{{' . $placeholder . '}}'] = var_export($value, true);
		}

		return $replacements;
	}
}