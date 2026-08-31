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

/**
* Single source of truth for the store/sebo-bbgatekeeper/ base
* directory and its deny-all .htaccess. Since that directory lives
* outside ext/sebo/bbgatekeeper/, it can no longer be shipped as a
* static file inside the extension package - it must be (re)created in
* code. Called both from migrations/move_store_v131.php (one-time, on
* install/update) and from deployer::deploy_logger() /
* config_exporter::export() (every single "Deploy" in ACP), so the
* directory self-heals even if it's ever deleted or restored from an
* old backup without the .htaccess.
*/
class store_guard
{
	/**
	* @return string
	*/
	public static function htaccess_content(): string
	{
		return "# Deny all direct web access to this directory.\n"
			. "# Written in both legacy (Apache < 2.4) and modern syntax for compatibility.\n"
			. "<IfModule mod_authz_core.c>\n"
			. "\tRequire all denied\n"
			. "</IfModule>\n"
			. "<IfModule !mod_authz_core.c>\n"
			. "\tOrder allow,deny\n"
			. "\tDeny from all\n"
			. "</IfModule>\n";
	}

	/**
	* Ensures $base_dir exists and contains a deny-all .htaccess.
	* Never throws - best effort, since callers already have their own
	* error handling/reporting for the rest of the deploy.
	*
	* @param string $base_dir absolute path, trailing slash
	* @return bool true if the directory exists (already did, or was
	*              just created) by the time this returns
	*/
	public static function ensure(string $base_dir): bool
	{
		if (!is_dir($base_dir) && !@mkdir($base_dir, 0750, true))
		{
			return false;
		}

		$htaccess = $base_dir . '.htaccess';
		if (!file_exists($htaccess))
		{
			@file_put_contents($htaccess, self::htaccess_content());
		}

		return true;
	}
}
