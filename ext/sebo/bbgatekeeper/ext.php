<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sebo\bbgatekeeper;

/**
* Extension base class.
*
* Note: this extension does NOT perform request filtering itself. The
* actual edge filter (hCaptcha challenge, UA blocklist, FCrDNS bot check)
* runs as a standalone auto_prepend_file script under store/runtime/, so
* it executes before phpBB bootstraps on every request - including
* requests that never even reach phpBB. This extension only provides the
* ACP control panel used to configure and (re)generate that script.
*/
class ext extends \phpbb\extension\base
{
	/**
	* {@inheritdoc}
	*
	* Accepts phpBB 3.3.x and 4.0.x; PHP 7.4 is the floor for 3.3, PHP 8
	* will be the floor once running under 4.0, both covered here since
	* PHP 8 satisfies the >= 7.4 check too.
	*/
	public function is_enableable()
	{
		return phpbb_version_compare(PHPBB_VERSION, '3.3.0', '>=')
			&& phpbb_version_compare(PHPBB_VERSION, '5.0.0-dev', '<')
			&& version_compare(PHP_VERSION, '7.4.0', '>=');
	}

	/**
	* {@inheritdoc}
	*
	* Best-effort cleanup: if the admin disables the extension without
	* first using the ACP "Reset" button, the .user.ini auto_prepend_file
	* line would otherwise keep pointing at a logger that may no longer
	* be readable, producing a PHP warning on every request.
	*/
	public function disable_step($old_state)
	{
		if ($old_state === false)
		{
			try
			{
				$this->container->get('sebo.bbgatekeeper.deployer')->remove_ini_line();
			}
			catch (\Exception $e)
			{
				// Best effort only - never block disabling the extension
			}
		}

		return parent::disable_step($old_state);
	}

	/**
	* {@inheritdoc}
	*
	* Same cleanup as disable_step(), in case the admin purges directly
	* without disabling first.
	*/
	public function purge_step($old_state)
	{
		if ($old_state === false)
		{
			try
			{
				$this->container->get('sebo.bbgatekeeper.deployer')->remove_ini_line();
			}
			catch (\Exception $e)
			{
				// Best effort only
			}
		}

		return parent::purge_step($old_state);
	}
}
