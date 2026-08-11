<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sebo\bbgatekeeper\controller;

use sebo\bbgatekeeper\core\fallback_packager;

class download_package_controller
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\user */
	protected $user;

	/** @var fallback_packager */
	protected $packager;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/**
	* @param \phpbb\auth\auth         $auth
	* @param \phpbb\user              $user
	* @param fallback_packager        $packager
	* @param \phpbb\log\log_interface $log
	*/
	public function __construct($auth, $user, fallback_packager $packager, $log)
	{
		$this->auth = $auth;
		$this->user = $user;
		$this->packager = $packager;
		$this->log = $log;
	}

	public function handle()
	{
		if (!$this->auth->acl_get('a_board'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$zip_path = $this->packager->build_zip();

		if ($zip_path === null)
		{
			$this->log->add(
				'admin',
				$this->user->data['user_id'],
				$this->user->ip,
				'LOG_BBGATEKEEPER_PACKAGE_FAILED',
				time(),
				[$this->packager->get_last_error()]
			);

			trigger_error('BBGATEKEEPER_PACKAGE_FAILED', E_USER_WARNING);
		}

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="bbgatekeeper_deploy_package.zip"');
		header('Content-Length: ' . filesize($zip_path));

		// The ZIP contains plaintext secrets (captcha keys, cookie
		// signing secret): make sure no intermediate proxy/cache stores
		// a copy of the response.
		header('Cache-Control: private, no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('X-Content-Type-Options: nosniff');

		// Guarantee cleanup of the temp file even if readfile() fails
		// partway through (interrupted connection, permission error).
		try
		{
			readfile($zip_path);
		}
		finally
		{
			@unlink($zip_path);
		}

		exit;
	}
}
