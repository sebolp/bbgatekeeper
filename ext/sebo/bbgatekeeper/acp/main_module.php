<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sebo\bbgatekeeper\acp;

class main_module
{
	/** @var string */
	public $u_action;

	public $tpl_name;
	public $page_title;

	/**
	* @param int    $id
	* @param string $mode
	* @return void
	*/
	public function main($id, $mode)
	{
		global $user, $request, $template, $phpbb_root_path, $phpEx;

		$user->add_lang_ext('sebo/bbgatekeeper', 'common');

		/*
		*	Detect whois
		*/
		$whois_ip = $request->variable('whois', '');
			if ($whois_ip !== '')
			{
				if (!filter_var($whois_ip, FILTER_VALIDATE_IP))
				{
					trigger_error('FORM_INVALID');
				}
				if (!function_exists('user_ipwhois'))
				{
					include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
				}
				$template->assign_var('WHOIS', user_ipwhois($whois_ip));
				$this->tpl_name   = 'bbgatekeeper_whois';
				$this->page_title = 'BBGATEKEEPER_WHOIS';
				return; // exits before the switch, no form_key needed here
			}

		add_form_key('sebo_bbgatekeeper');

		switch ($mode)
		{
			case 'settings':
				$this->tpl_name = 'bbgatekeeper_settings';
				$this->page_title = 'ACP_BBGATEKEEPER_SETTINGS';
				$this->settings();
				break;

			case 'logs':
				$this->tpl_name = 'bbgatekeeper_logs';
				$this->page_title = 'ACP_BBGATEKEEPER_LOGS';
				$this->logs();
				break;

			case 'hits_and_bans':
				$this->tpl_name = 'bbgatekeeper_hits_and_bans';
				$this->page_title = 'ACP_BBGATEKEEPER_HITS_AND_BANS';
				$this->hits_and_bans();
				break;
		}
	}

	/**
	* @return void
	*/
	public function settings()
	{
		global $config, $request, $template, $user, $phpbb_container, $db, $table_prefix;

		$do_save = $request->is_set_post('submit_save');
		$do_deploy = $request->is_set_post('submit_deploy');
		$do_reset = $request->is_set_post('submit_reset');
		$do_generate_secret = $request->is_set_post('submit_generate_secret');

		if (($do_save || $do_deploy || $do_reset || $do_generate_secret) && !check_form_key('sebo_bbgatekeeper'))
		{
			trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
		}

		if ($do_reset)
		{
			/** @var \sebo\bbgatekeeper\core\reset $reset */
			$reset = $phpbb_container->get('sebo.bbgatekeeper.reset');
			$success = $reset->reset();
			$results = $reset->get_results();
			$config->set('bbgatekeeper_deploy_ok', '0');

			$labels = [
				'remove_ini_line'       => $user->lang('BBGATEKEEPER_CONFIRM_REMOVE_INI_LINE'),
				'delete_store_contents' => $user->lang('BBGATEKEEPER_CONFIRM_REMOVE_STORE_FILES'),
			];

			$lines = [];
			foreach ($results as $key => $ok)
			{
				$label = $labels[$key] ?? $key;
				$lines[] = $label . ': ' . ($ok ? $user->lang('BBGATEKEEPER_FUNCTION_DONE') : $user->lang('BBGATEKEEPER_FUNCTION_ERROR'));
			}

			trigger_error(
				implode('<br>', $lines) . adm_back_link($this->u_action),
				$success ? E_USER_NOTICE : E_USER_WARNING
			);
		}

		// Generating a fresh secret saves the rest of the form too (same
		// <form>, same submit), then overwrites the signing key with a
		// freshly generated random value.
		if ($do_save || $do_deploy || $do_generate_secret)
		{
			$this->save_settings($request, $config);
		}

		if ($do_generate_secret)
		{
			$new_secret = bin2hex(random_bytes(32));
			$config->set('bbgatekeeper_hcap_sign_secret', $new_secret);

			trigger_error($user->lang('BBGATEKEEPER_SIGN_SECRET_GENERATED') . adm_back_link($this->u_action), E_USER_NOTICE);
		}

		if ($do_deploy)
		{
			/** @var \sebo\bbgatekeeper\core\config_exporter $exporter */
			$exporter = $phpbb_container->get('sebo.bbgatekeeper.config_exporter');
			/** @var \sebo\bbgatekeeper\core\deployer $deployer */
			$deployer = $phpbb_container->get('sebo.bbgatekeeper.deployer');

			$results = [];

			$results['export_config'] = $exporter->export();

			// if export fails do not deploy bbgatekeeper_config.php
			$results['deploy_logger'] = $results['export_config'] && $deployer->deploy();

			$success = $results['export_config'] && $results['deploy_logger'];

			$config->set('bbgatekeeper_deploy_ok', $success ? '1' : '0');
			$config->set('bbgatekeeper_last_deploy_time', time());

			$labels = [
				'export_config' => $user->lang('BBGATEKEEPER_CONFIG_FILE_GENERATING'),
				'deploy_logger' => $user->lang('BBGATEKEEPER_LOGGER_AND_INI_DEPLOY'),
			];

			$lines = [];
			foreach ($results as $key => $ok)
			{
				$label = $labels[$key] ?? $key;
				$lines[] = $label . ': ' . ($ok ? $user->lang('BBGATEKEEPER_FUNCTION_DONE') : $user->lang('BBGATEKEEPER_FUNCTION_ERROR'));
			}

			trigger_error(
				implode('<br>', $lines) . adm_back_link($this->u_action),
				$success ? E_USER_NOTICE : E_USER_WARNING
			);
		}
		else if ($do_save)
		{
			trigger_error($user->lang('BBGATEKEEPER_SETTINGS_SAVED') . adm_back_link($this->u_action));
		}

		/** @var \sebo\bbgatekeeper\acp\deploy_status_checker $checker */
		$checker = $phpbb_container->get('sebo.bbgatekeeper.acp.deploy_status_checker');

		foreach ($checker->fundamental_checks() as $check)
		{
			$template->assign_block_vars('fundamental_checks', [
				'LABEL'         => $user->lang($check['label']),
				'STATUS'        => $check['status'],

				// Convenience booleans for the Twig template
				'S_OK'          => ($check['status'] === 'ok'),
				'S_BAD'         => ($check['status'] === 'bad'),
				'S_NO_PERMS'    => ($check['status'] === 'no_right_permissions'),
			]);
		}

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

		// English comment: Fetch results and assign variables
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

		$samesite = (string) ($config['bbgatekeeper_cookie_samesite'] ?? 'Lax');
		$ip_level = (int) ($config['bbgatekeeper_ip_binding_level'] ?? 1);

		$template->assign_vars([
			'U_ACTION'          => $this->u_action,

			'HCAP_EXTERNAL_LINK'    => '<a href="https://www.hcaptcha.com/" target="_blank" rel="noopener noreferrer"> https://www.hcaptcha.com/ <i class="fa fa-external-link" aria-hidden="true"></i></a>',

			'S_INI_PREPEND_OK'      => $checker->ini_has_prepend_line(),
			'S_INI_STATUS'          => $checker->get_ini_status(),

			'HCAP_SITE_KEY'         => (string) ($config['bbgatekeeper_hcap_site_key'] ?? ''),
			'HCAP_SITE_SECRET'      => (string) ($config['bbgatekeeper_hcap_site_secret'] ?? ''),
			'HCAP_SIGN_SECRET'      => (string) ($config['bbgatekeeper_hcap_sign_secret'] ?? ''),
			'COOKIE_DOMAIN'         => (string) ($config['cookie_domain'] ?? ''),
			'COOKIE_NAME'           => (string) ($config['bbgatekeeper_cookie_name'] ?? 'sebo_bbgatekeeper_verified_hcap'),
			'COOKIE_TTL'            => (int) ($config['bbgatekeeper_cookie_ttl'] ?? 86400),
			'COOKIE_SAMESITE_LAX'       => ($samesite === 'Lax'),
			'COOKIE_SAMESITE_STRICT'    => ($samesite === 'Strict'),

			'UA_PATTERNS'           => implode("\n", (array) $ua_patterns),
			'BOT_DOMAINS'           => implode("\n", (array) $bot_domains),

			'IP_LEVEL_1'            => ($ip_level === 1),
			'IP_LEVEL_2'            => ($ip_level === 2),
			'IP_LEVEL_3'            => ($ip_level === 3),
			'IP_LEVEL_4'			=> ($ip_level === 4),

			'DRY_RUN'           => (bool) ($config['bbgatekeeper_dry_run'] ?? true),

			// Toggle to enable/disable the access log write
			// (store/logs/access.log) from the auto_prepend logger, to
			// save one write per request when it's not needed.
			'ENABLE_ACCESS_LOG' => (bool) ($config['bbgatekeeper_enable_access_log'] ?? true),

			'TRUSTED_PROXY_ENABLE'      => (bool) ($config['bbgatekeeper_trusted_proxy_enable'] ?? false),
			'TRUSTED_PROXY_REMOTE_ADDR' => (string) ($config['bbgatekeeper_trusted_proxy_remote_addr'] ?? ''),

			// Warn when REMOTE_ADDR still looks like a local proxy hop
			// and Trusted Proxy isn't configured: means every visitor is
			// currently sharing the same address for bans/logs.
			'SHOW_TRUSTED_PROXY_WARNING' => (bool) preg_match('/^127\.0\.0\.\d+$/', (string) $request->server('REMOTE_ADDR'))
				&& !(bool) ($config['bbgatekeeper_trusted_proxy_enable'] ?? false),

			// Static, always-reachable diagnostic file: shows $_SERVER
			// exactly as the web server passes it, before phpBB (and
			// startup.php) touches anything - opened directly by the
			// admin in a new tab, no deploy/config needed.
			'U_IP_PROBE' => generate_board_url() . '/ext/sebo/bbgatekeeper/sebo-bbgatekeeper-ip-probe.php',

			// Stage 0 (Apache, before PHP-FPM): static page + .htaccess block
			'PRECHECK_ENABLE'    => (bool) ($config['bbgatekeeper_precheck_enable'] ?? false),
			'PRECHECK_DEPLOYED'  => $phpbb_container->get('sebo.bbgatekeeper.precheck_deployer')->is_deployed(),

			'DEPLOY_OK'         => $checker->all_ok(),
			'LAST_DEPLOY_TIME'      => !empty($config['bbgatekeeper_last_deploy_time']) ? $user->format_date((int) $config['bbgatekeeper_last_deploy_time']) : '-',

			'SHOW_PERMISSIONS_WARNING'  => $checker->config_permissions_warning(),
		]);
	}

	/**
	* @param \phpbb\request\request_interface $request
	* @param \phpbb\config\config              $config
	* @return void
	*/
	private function save_settings($request, $config)
	{
		global $db, $table_prefix, $phpbb_container, $user;

		$config->set('bbgatekeeper_hcap_site_key', $request->variable('hcap_site_key', ''));
		$config->set('bbgatekeeper_hcap_site_secret', $request->variable('hcap_site_secret', ''));

		// Field is fully visible/editable now; default to the current
		// stored value so a missing POST field never blanks it out.
		$current_sign_secret = (string) ($config['bbgatekeeper_hcap_sign_secret'] ?? '');
		$config->set('bbgatekeeper_hcap_sign_secret', $request->variable('hcap_sign_secret', $current_sign_secret));

		$config->set('bbgatekeeper_cookie_name', $request->variable('cookie_name', 'fpc_verified_hcap'));
		$config->set('bbgatekeeper_cookie_ttl', (string) max(60, $request->variable('cookie_ttl', 86400)));
		$config->set('cookie_domain', $request->variable('cookie_domain', ''));

		$samesite = $request->variable('cookie_samesite', 'Lax');
		$config->set('bbgatekeeper_cookie_samesite', in_array($samesite, ['Lax', 'Strict'], true) ? $samesite : 'Lax');

		// English comment: Save textareas and ensure UTF-8 support
		$ua_patterns_json = $this->textarea_to_json($request->variable('ua_patterns', '', true));
		$bot_domains_json = $this->textarea_to_json($request->variable('bot_domains', '', true));

	// English comment: Define the array with the column to update
		$sql_ary_ua = [
		'setting_value' => $ua_patterns_json,
		];

	// English comment: Construct the UPDATE query using sql_build_array for the SET clause
		$sql = 'UPDATE ' . $table_prefix . 'sebo_bbgatekeeper_settings
		SET ' . $db->sql_build_array('UPDATE', $sql_ary_ua) . "
		WHERE setting_name = 'ua_patterns'";

	// English comment: Execute the update query for UA patterns
		$db->sql_query($sql);

	// English comment: Define the array for the Bot domains update
		$sql_ary_bot = [
		'setting_value' => $bot_domains_json,
		];

	// English comment: Construct and execute the update query for Bot domains
		$sql = 'UPDATE ' . $table_prefix . 'sebo_bbgatekeeper_settings
		SET ' . $db->sql_build_array('UPDATE', $sql_ary_bot) . "
		WHERE setting_name = 'bot_domains'";

		$db->sql_query($sql);

		$ip_level = $request->variable('ip_binding_level', 2);
		$config->set('bbgatekeeper_ip_binding_level', (string) (in_array($ip_level, [1, 2, 3, 4], true) ? $ip_level : 2));

		$config->set('bbgatekeeper_dry_run', $request->variable('dry_run', false) ? '1' : '0');

		// If not yet present in the form (older bbgatekeeper_settings.html
		// not updated yet), the default stays "enabled" so we don't
		// silently stop writing the log on sites already in production:
		// we use is_set_post to distinguish "checkbox absent from the
		// form" from "checkbox present but unchecked".
		if ($request->is_set_post('enable_access_log') || $request->is_set_post('submit_save') || $request->is_set_post('submit_deploy'))
		{
			$config->set('bbgatekeeper_enable_access_log', $request->variable('enable_access_log', false) ? '1' : '0');
		}

		// Enable check for trusted PROXY to access to X_FORWARDED_FOR
		$trusted_proxy_remote_addr = trim($request->variable('trusted_proxy_remote_addr', ''));
		$trusted_proxy_enable = $request->variable('trusted_proxy_enable', false);

		if ($trusted_proxy_enable && !filter_var($trusted_proxy_remote_addr, FILTER_VALIDATE_IP))
		{
			trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$config->set('bbgatekeeper_trusted_proxy_enable', $trusted_proxy_enable ? '1' : '0');
		$config->set('bbgatekeeper_trusted_proxy_remote_addr', $trusted_proxy_remote_addr);

		// ============ Static precheck (sebo-bbgatekeeper.html + .htaccess) ============
		// Acts only on a state CHANGE, not on every save: writing to
		// .htaccess is a delicate operation (can take the site down if it
		// goes wrong), so it should only be touched when the admin
		// actually flips the checkbox, not on every settings save.
		$old_precheck_enable = (bool) ($config['bbgatekeeper_precheck_enable'] ?? false);
		$new_precheck_enable = (bool) $request->variable('precheck_enable', false);

		if ($new_precheck_enable !== $old_precheck_enable)
		{
			/** @var \sebo\bbgatekeeper\core\precheck_deployer $precheck */
			$precheck = $phpbb_container->get('sebo.bbgatekeeper.precheck_deployer');

			$precheck_ok = $new_precheck_enable ? $precheck->deploy() : $precheck->remove();

			if ($precheck_ok)
			{
				$config->set('bbgatekeeper_precheck_enable', $new_precheck_enable ? '1' : '0');
			}
			else
			{
				// Don't update the config if the disk write failed: the
				// state shown in ACP must stay consistent with the real
				// on-disk state (checkbox not saved = nothing changed on
				// the filesystem).
				trigger_error($user->lang('BBGATEKEEPER_PRECHECK_ERROR') . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}
	}

	/**
	* @param string $raw
	* @return string JSON-encoded array
	*/
	private function textarea_to_json($raw)
	{
		$lines = preg_split('/\r\n|\r|\n/', (string) $raw);
		$lines = array_values(array_filter(array_map('trim', $lines), function ($line)
		{
			return $line !== '';
		}));

		return json_encode($lines);
	}

	/**
	* @return void
	*/
	private function logs()
	{
		global $request, $template, $phpbb_container;

		/** @var \phpbb\controller\helper $helper */
		$helper = $phpbb_container->get('controller.helper');

		/** @var log_reader $log_reader */
		$log_reader = $phpbb_container->get('sebo.bbgatekeeper.acp.log_reader');

		if ($request->is_set_post('submit_clear') && check_form_key('sebo_bbgatekeeper'))
		{
			$log_reader->clear();
		}

		// Pagination
		$start = $request->variable('start', 0);
		$limit = 100;

		// Extract all lines
		$all_lines = $log_reader->tail(5000);
		$total_lines = count($all_lines);

		// Only that page
		$lines = array_slice($all_lines, $start, $limit);

		foreach ($lines as $line)
		{
			// Add a check to ensure $line is an array before accessing offsets
			if (!is_array($line))
			{
				continue;
			}

			$template->assign_block_vars('log_lines', [
				'DATETIME'      => isset($line['datetime']) ? $line['datetime'] : '',
				'IP'            => isset($line['ip']) ? $line['ip'] : '',
				'STATUS'        => isset($line['status']) ? $line['status'] : '',
				'URI'           => isset($line['uri']) ? $line['uri'] : '',
				'USER_AGENT'    => isset($line['user_agent']) ? $line['user_agent'] : '',
				'U_WHOIS' => $helper->route('sebo_bbgatekeeper_whois', ['ip' => isset($line['ip']) ? $line['ip'] : '']),
			]);
		}

		// Create Pagination
		$pagination = $phpbb_container->get('pagination');
		$pagination->generate_template_pagination($this->u_action, 'pagination', 'start', $total_lines, $limit, $start);

		$template->assign_vars([
			'U_ACTION'  => $this->u_action,
			'LOG_EMPTY' => empty($lines),
		]);
	}

	/**
	* Shows two tables: currently banned IPs, and IPs with a pending hCaptcha
	* failure hit that hasn't escalated to a ban yet. Both come from the flat
	* .ban / .hit marker files written by bbgatekeeper_logger.php in
	* store/logs/bans/. Supports:
	*  - deleting a single entry or wiping a whole category
	*  - cleaning up only the entries that are no longer active (expired)
	*  - configuring an automatic cleanup interval (executed by the real
	*    cron task in core/cron/cleanup_task.php, not by this page itself)
	* plus a whois link reusing the same mechanism as logs().
	*
	* @return void
	*/
	private function hits_and_bans()
	{
		global $request, $template, $user, $config, $phpbb_root_path, $phpbb_container;

		/** @var \phpbb\controller\helper $helper */
		$helper = $phpbb_container->get('controller.helper');

		/** @var \sebo\bbgatekeeper\core\hits_ban_manager $manager */
		$manager = new \sebo\bbgatekeeper\core\hits_ban_manager($phpbb_root_path . 'ext/sebo/bbgatekeeper/store/logs/bans');

		// ============ Auto-cleanup settings (form at the top of the page) ============
		if ($request->is_set_post('submit_autoclean_save'))
		{
			if (!check_form_key('sebo_bbgatekeeper'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$config->set('bbgatekeeper_autoclean_enable', $request->variable('autoclean_enable', false) ? '1' : '0');

			$interval = $request->variable('autoclean_interval', 60);
			$config->set('bbgatekeeper_autoclean_interval', (string) max(5, $interval));

			trigger_error($user->lang('BBGATEKEEPER_AUTOCLEAN_SAVED') . adm_back_link($this->u_action), E_USER_NOTICE);
		}

		// ============ Actions on single entries / whole categories ============
		if ($request->is_set_post('action'))
		{
			if (!check_form_key('sebo_bbgatekeeper'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$action = $request->variable('action', '');
			$hash   = $request->variable('hash', '');

			switch ($action)
			{
				case 'delete_ban':
					if (preg_match('/^[a-f0-9]{32}$/', $hash))
					{
						$manager->delete_ban($hash);
					}
					break;

				case 'delete_hit':
					if (preg_match('/^[a-f0-9]{32}$/', $hash))
					{
						$manager->delete_hit($hash);
					}
					break;

				case 'delete_all_bans':
					$manager->delete_all_bans();
					break;

				case 'delete_all_hits':
					$manager->delete_all_hits();
					break;

				case 'cleanup_expired_bans':
					$manager->delete_expired_bans();
					break;

				case 'cleanup_expired_hits':
					$manager->delete_expired_hits();
					break;
			}

			trigger_error($user->lang('BBGATEKEEPER_HITSBANS_UPDATED') . adm_back_link($this->u_action), E_USER_NOTICE);
		}

		$bans = $manager->get_bans();
		$hits = $manager->get_hits();

		foreach ($bans as $ban)
		{
			$remaining = max(0, \sebo\bbgatekeeper\core\hits_ban_manager::BAN_TTL - (time() - $ban['ban_time']));

			$template->assign_block_vars('bans', [
				'IP'        => $ban['ip'] ?: $user->lang('BBGATEKEEPER_IP_UNKNOWN'),
				'HASH'      => $ban['hash'],
				'BAN_TIME'  => $user->format_date($ban['ban_time']),
				'REMAINING' => gmdate('i:s', $remaining),
				'ACTIVE'    => $ban['active'],
				'U_WHOIS'   => $helper->route('sebo_bbgatekeeper_whois', ['ip' => $ban['ip'] ?: '']),
			]);
		}

		foreach ($hits as $hit)
		{
			$reference = $hit['start_time'] !== null ? $hit['start_time'] : $hit['mtime'];
			$elapsed = max(0, time() - $reference);

			$days    = (int) floor($elapsed / 86400);
			$hours   = (int) floor(($elapsed % 86400) / 3600);
			$minutes = (int) floor(($elapsed % 3600) / 60);

			$template->assign_block_vars('hits', [
				'IP'        => $hit['ip'] ?: $user->lang('BBGATEKEEPER_IP_UNKNOWN'),
				'HASH'      => $hit['hash'],
				'HITS'      => $hit['hits'] !== null ? $hit['hits'] : '-',
				'FIRST_HIT' => $hit['start_time'] ? $user->format_date($hit['start_time']) : '-',
				'S_DAYS'    => $days,
				'S_HOURS'   => $hours,
				'S_MINUTES' => $minutes,
				'ACTIVE'    => $hit['active'],
				'U_WHOIS'   => $helper->route('sebo_bbgatekeeper_whois', ['ip' => $hit['ip'] ?: '']),
			]);
		}

		$template->assign_vars([
			'U_ACTION'           => $this->u_action,
			'BANS_EMPTY'         => empty($bans),
			'HITS_EMPTY'         => empty($hits),

			'AUTOCLEAN_ENABLE'   => (bool) ($config['bbgatekeeper_autoclean_enable'] ?? false),
			'AUTOCLEAN_INTERVAL' => (int) ($config['bbgatekeeper_autoclean_interval'] ?? 60),
		]);
	}
}