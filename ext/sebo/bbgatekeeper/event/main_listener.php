<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sebo\bbgatekeeper\event;

use phpbb\config\config;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\template\template;
use phpbb\language\language;
use sebo\bbgatekeeper\acp\deploy_status_checker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Bad Bot Gatekeeper main event listener.
*
* Runs AFTER phpBB has already bootstrapped (DB, container, session).
* It is NOT part of the request filtering chain - that is handled
* entirely by the standalone script in store/bbgatekeeper/runtime/,
* deployed via core/deployer.php. This listener only surfaces status
* information inside the ACP and, for admins only, on the forum index.
*/
class main_listener implements EventSubscriberInterface
{
	/** @var config */
	protected $config;

	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var template */
	protected $template;

	/** @var language */
	protected $language;

	/** @var deploy_status_checker */
	protected $deploy_status_checker;

	/**
	* @param config                $config
	* @param user                  $user
	* @param auth                  $auth
	* @param template              $template
	* @param language              $language
	* @param deploy_status_checker $deploy_status_checker
	*/
	public function __construct(config $config, user $user, auth $auth, template $template, language $language, deploy_status_checker $deploy_status_checker)
	{
		$this->config = $config;
		$this->user = $user;
		$this->auth = $auth;
		$this->template = $template;
		$this->language = $language;
		$this->deploy_status_checker = $deploy_status_checker;
	}

	/**
	* {@inheritdoc}
	*
	* TODO: 'core.acp_main_notices' for add_acp_overview_notice() still
	* needs verification against the live phpBB 3.3.17 event list.
	*/
	public static function getSubscribedEvents()
	{
		return [
			'core.acp_main_notices'	=> 'add_acp_overview_notice',
			'core.page_header'			=> 'add_index_admin_warning',
		];
	}

	/**
	* ACP overview notice when the deployment looks unhealthy.
	*
	* @param \phpbb\event\data $event
	* @return void
	*/
	public function add_acp_overview_notice($event)
	{
		if (!$this->deploy_status_checker->all_ok())
		{
			$this->language->add_lang('common', 'sebo/bbgatekeeper');

			$notices = $event['notices'];
			$notices[] = $this->language->lang('BBGATEKEEPER_DEPLOY_WARNING');
			$event['notices'] = $notices;
		}
	}

	/**
	* Assigns the admin-only forum index warning vars. The actual visual
	* placement lives entirely in
	* styles/all/template/event/index_body_forumlist_body_after.html,
	* which is only parsed on the forum index - this runs on every page
	* but has no visible effect anywhere else.
	*
	* @return void
	*/
	public function add_index_admin_warning()
	{
		if (!$this->auth->acl_get('a_board'))
		{
			return;
		}

		if ($this->deploy_status_checker->all_ok())
		{
			return;
		}

		$this->language->add_lang('common', 'sebo/bbgatekeeper');

		$this->template->assign_vars([
			'S_BBGATEKEEPER_SHOW_INDEX_WARNING'	=> true,
			'BBGATEKEEPER_INDEX_WARNING_TEXT'		=> $this->language->lang('BBGATEKEEPER_DEPLOY_WARNING'),
			'U_BBGATEKEEPER_ACP'					=> append_sid("{$this->user->page['root_script_path']}adm/index.php", 'i=-sebo-bbgatekeeper-acp-main_module&mode=settings', true, $this->user->session_id),
		]);
	}
}