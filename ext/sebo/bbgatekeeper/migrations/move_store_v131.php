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
