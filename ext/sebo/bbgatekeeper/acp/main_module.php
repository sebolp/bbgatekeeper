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
				return; // esce prima dello switch, non serve form_key
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

				// Booleani comodi per il template Twig
				'S_OK'          => ($check['status'] === 'ok'),
				'S_BAD'         => ($check['status'] === 'bad'),
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
		global $db, $table_prefix;

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
}
