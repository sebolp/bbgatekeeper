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
* v1.3.1: relocates the runtime store directory from
* ext/sebo/bbgatekeeper/store/ (INSIDE the extension's own folder) to
* store/sebo-bbgatekeeper/ (phpBB's own store/ directory, at the forum
* root). The old location was silently wiped by the common "delete the
* extension folder, upload the new one" update procedure, since store/
* lived inside ext/sebo/bbgatekeeper/ - taking .htaccess, bbgatekeeper/
* (config.php + logger.php) and logs/ down with it, which produced an
* immediate site-wide 500 (auto_prepend_file pointing at a now-missing
* bbgatekeeper_logger.php), even with the extension left disabled. This
* bug shipped in v1.3.0 and is fixed here in v1.3.1.
*
* Three cases, all handled by move_store_directory():
*  - old store/ still present (in-place/merge update, e.g. from v1.3.0
*    or earlier) -> moved wholesale to the new location via rename().
*  - old store/ already gone (deleted before uploading, e.g. the bug
*    this migration fixes) -> a fresh, empty, .htaccess-protected
*    store/sebo-bbgatekeeper/ is created; the admin will need to hit
*    "Deploy" again in ACP, same as any other missing-files case.
*  - fresh install -> same as above, new directory created up front.
*/
class move_store_v131 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\sebo\bbgatekeeper\migrations\add_v130_features'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'move_store_directory']]],
			['custom', [[$this, 'repoint_ini_line']]],
		];
	}

	/**
	* Deliberately no-op: reverting a filesystem move on
	* uninstall/downgrade is riskier than leaving files in the new
	* location behind (the "Reset deployment" ACP button already covers
	* the explicit, user-triggered cleanup case).
	*/
	public function revert_data()
	{
		return [];
	}

	public function move_store_directory()
	{
		$old_dir = $this->phpbb_root_path . 'ext/sebo/bbgatekeeper/store/';
		$new_dir = $this->phpbb_root_path . 'store/sebo-bbgatekeeper/';

		if (is_dir($old_dir) && !is_dir($new_dir))
		{
			if (!@rename($old_dir, $new_dir))
			{
				// Cross-device or permissions issue: fall back to a
				// recursive copy, then remove the old tree.
				if ($this->copy_directory_recursive($old_dir, $new_dir))
				{
					$this->delete_directory_recursive($old_dir);
				}
			}
		}

		// Fresh install, old store/ already missing (e.g. deleted along
		// with ext/sebo/bbgatekeeper/ before uploading v1.3.0), or the
		// rename/copy above just succeeded: make sure the new directory
		// and its .htaccess are in place either way. bbgatekeeper/ and
		// logs/ are (re)created lazily on demand by
		// config_exporter/deployer/the runtime logger.
		\sebo\bbgatekeeper\core\store_guard::ensure($new_dir);
	}

	/**
	* Updates the auto_prepend_file line written by v1.3.0 (or earlier),
	* which still points at the old logger under ext/sebo/bbgatekeeper/store/
	* that move_store_directory() has just removed. Without this every request
	* would keep failing until the admin deploys again.
	*
	* - new logger present: the line is rewritten to the new path.
	* - new logger missing (old store deleted before uploading): the line is
	*   removed instead, so the site fails open rather than pointing at a
	*   missing file. The admin then has to deploy again from the ACP.
	*
	* A .user.ini without a bbgatekeeper line (fresh install) is left alone.
	*
	* NOTE: \phpbb\db\migration\migration does NOT expose a $container
	* property (only $config, $db, $tools, $phpbb_root_path, $php_ext,
	* $table_prefix), so $this->container->get('request') raises
	* "Undefined property ...::$container". The phpBB request service
	* also disables superglobals, so $_SERVER cannot be used directly.
	* The .user.ini lives in the document root, which always contains
	* $this->phpbb_root_path (or is one of its ancestors): walk up the
	* tree to locate it without needing either the container or $_SERVER.
	*
	* @return void
	*/
	public function repoint_ini_line()
	{
		$base_path = realpath($this->phpbb_root_path);
		if ($base_path === false)
		{
			return;
		}

		// Walk up from phpbb_root_path looking for the .user.ini that
		// lives in the document root. Stop at filesystem root.
		$ini_path = false;
		$dir = $base_path;
		while ($dir !== false && $dir !== '/')
		{
			$candidate = $dir . '/.user.ini';
			if (is_readable($candidate))
			{
				$ini_path = $candidate;
				break;
			}

			$parent = dirname($dir);
			if ($parent === $dir)
			{
				break;
			}
			$dir = $parent;
		}

		if ($ini_path === false)
		{
			return;
		}

		$current = @file_get_contents($ini_path);
		if ($current === false || !preg_match('/^\s*auto_prepend_file\s*=.*bbgatekeeper_logger\.php/mi', $current))
		{
			return;
		}

		$new_logger = str_replace('\\', '/', $base_path) . '/store/sebo-bbgatekeeper/bbgatekeeper/bbgatekeeper_logger.php';
		$logger_present = is_file($new_logger);

		$lines = preg_split('/\r\n|\r|\n/', rtrim($current, "\r\n"));
		$new_lines = [];

		foreach ($lines as $line)
		{
			if (preg_match('/^\s*auto_prepend_file\s*=.*bbgatekeeper_logger\.php/i', $line))
			{
				if ($logger_present)
				{
					$new_lines[] = 'auto_prepend_file = "' . $new_logger . '"';
				}

				continue;
			}

			$new_lines[] = $line;
		}

		@file_put_contents($ini_path, implode("\n", $new_lines) . "\n");
	}

	/**
	* @param string $source trailing slash
	* @param string $dest   trailing slash
	* @return bool
	*/
	protected function copy_directory_recursive(string $source, string $dest): bool
	{
		if (!is_dir($dest) && !@mkdir($dest, 0750, true))
		{
			return false;
		}

		$entries = @scandir($source);
		if ($entries === false)
		{
			return false;
		}

		$success = true;
		foreach ($entries as $entry)
		{
			if ($entry === '.' || $entry === '..')
			{
				continue;
			}

			$from = $source . $entry;
			$to = $dest . $entry;

			if (is_dir($from))
			{
				$success = $this->copy_directory_recursive($from . '/', $to . '/') && $success;
			}
			else
			{
				$success = @copy($from, $to) && $success;
			}
		}

		return $success;
	}

	/**
	* @param string $path trailing slash
	* @return bool
	*/
	protected function delete_directory_recursive(string $path): bool
	{
		$entries = @scandir($path);
		if ($entries === false)
		{
			return false;
		}

		$success = true;
		foreach ($entries as $entry)
		{
			if ($entry === '.' || $entry === '..')
			{
				continue;
			}

			$full_path = $path . $entry;
			$success = is_dir($full_path)
				? ($success && $this->delete_directory_recursive($full_path . '/'))
				: ($success && @unlink($full_path));
		}

		return $success && @rmdir($path);
	}
}
