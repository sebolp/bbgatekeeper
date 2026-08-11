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
* Builds a downloadable ZIP fallback package (bbgatekeeper_config.php,
* bbgatekeeper_logger.php, .user.ini) for admins whose server doesn't
* let deployer / config_exporter write directly (read-only store/ or
* read-only document root). Never writes anything under store/ - output
* always goes to the system temp directory, since that's usually still
* writable when the extension's own paths are not.
*/
class fallback_packager
{
	/**
	* Relative path (inside the ZIP) matching the extension's own fixed
	* location under a phpBB installation: ext/sebo/bbgatekeeper/. This
	* is not configurable - composer/installers always places phpBB
	* extensions at ext/<vendor>/<name>/.
	*/
	protected const EXT_RELATIVE_PATH = 'ext/sebo/bbgatekeeper/store/bbgatekeeper/';

	/** @var config_exporter */
	protected $config_exporter;

	/** @var deployer */
	protected $deployer;

	/** @var string last technical error, for logs */
	protected $last_error = '';

	/**
	* @param config_exporter $config_exporter
	* @param deployer        $deployer
	*/
	public function __construct(config_exporter $config_exporter, deployer $deployer)
	{
		$this->config_exporter = $config_exporter;
		$this->deployer = $deployer;
	}

	/**
	* @return string
	*/
	public function get_last_error(): string
	{
		return $this->last_error;
	}

	/**
	* @return bool false if the php-zip extension is not available
	*/
	public function is_available(): bool
	{
		return class_exists('ZipArchive');
	}

	/**
	* Builds the ZIP in the system temp directory and returns its
	* absolute path, or null on failure (see get_last_error()). The
	* caller is responsible for deleting the file after streaming it.
	*
	* @return string|null
	*/
	public function build_zip(): ?string
	{
		global $user;
		$user->add_lang_ext('sebo/bbgatekeeper', 'config_lang');

		$this->last_error = '';

		if (!$this->is_available())
		{
			$this->last_error = $user->lang('BBGATEKEEPER_CONFIG_ZIP_NOT_AVAILABLE');
			return null;
		}

		$config_content = $this->config_exporter->render();
		if ($config_content === null)
		{
			$this->last_error = $user->lang('BBGATEKEEPER_CONFIG_FILE_RENDER_FAILED', 'bbgatekeeper_config') . ' ' . $this->config_exporter->get_last_error();
			return null;
		}

		$logger_content = $this->deployer->render_logger();
		if ($logger_content === null)
		{
			$this->last_error = $user->lang('BBGATEKEEPER_CONFIG_FILE_RENDER_FAILED', 'bbgatekeeper_logger') . ' ' . $this->deployer->get_last_error();
			return null;
		}

		$ini_content = $this->deployer->render_ini_full();
		if ($ini_content === null)
		{
			$this->last_error = $user->lang('BBGATEKEEPER_CONFIG_FILE_RENDER_FAILED', '.user.ini') . ' ' . $this->deployer->get_last_error();
			return null;
		}

		$tmp = @tempnam(sys_get_temp_dir(), 'bbgk_');
		if ($tmp === false)
		{
			$this->last_error = $user->lang('BBGATEKEEPER_CONFIG_TEMPNAME_FAILED', sys_get_temp_dir());
			return null;
		}

		// tempnam() already created an empty file; ZipArchive behaves
		// more predictably with some clients when the path ends in
		// .zip, so move to a sibling path and drop the placeholder.
		$zip_path = $tmp . '.zip';
		@rename($tmp, $zip_path);

		$zip = new \ZipArchive();
		if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
		{
			$this->last_error = $user->lang('BBGATEKEEPER_CONFIG_ZIP_OPEN_FAILED', $zip_path);
			@unlink($zip_path);
			return null;
		}

		$zip->addFromString(self::EXT_RELATIVE_PATH . 'bbgatekeeper_config.php', $config_content);
		$zip->addFromString(self::EXT_RELATIVE_PATH . 'bbgatekeeper_logger.php', $logger_content);
		// Placed under a clearly separate top-level folder, since its
		// real destination (the website/domain root) is a different
		// base directory than the phpBB/ext path above and can't be
		// merged into the same tree.
		$zip->addFromString('website_root/.user.ini', $ini_content);
		$zip->addFromString('README.txt', $this->build_readme());
		$zip->close();

		return $zip_path;
	}

	/**
	* Bilingual (EN/IT) install instructions bundled inside the ZIP.
	* Deliberately plain text rather than routed through the phpBB
	* language system, since the admin opens this file outside the ACP -
	* possibly on a machine with no phpBB installed at all.
	*
	* @return string
	*/
	protected function build_readme(): string
	{
		return <<<TXT
/**
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 * https://github.com/sebolp/bbgatekeeper/
 */
======================================================================
Bad Bot Gatekeeper — manual deploy package
======================================================================

EN
--
This ZIP was generated because the extension could not write the
runtime files directly on the server (store/ directory or document
root not writable by PHP).

======================================================================
1) CORE FILES
======================================================================

This ZIP already contains the correct folder structure:
   ext/sebo/bbgatekeeper/store/bbgatekeeper/bbgatekeeper_config.php
   ext/sebo/bbgatekeeper/store/bbgatekeeper/bbgatekeeper_logger.php

Upload the whole "ext" folder into your forum root via FTP/SFTP,
merging it with the existing ext/ folder already there (do NOT
replace the entire ext/ directory - only these two files go in).
Recommended permissions: 0640 (owner read/write, group read only).
Don't worry about your password and keys: the store path is secured
by an .htaccess file too.

======================================================================
2) VERY IMPORTANT - .user.ini CONFIGURATION
======================================================================

This ZIP includes it under the "website_root/" folder as a reminder
of where it goes - but that folder name is just a label, it does NOT
match your real folder structure.
The real .user.ini must be placed in the root website path:
- Not in the forum path.
- Not in the ext path.
- It has to be placed in the domain root path, which is the first folder 
  you open when you connect via FTP.

WARNING: Do NOT simply overwrite an existing .user.ini on your server 
with the one provided in this ZIP file.

1. IF A .user.ini FILE ALREADY EXISTS ON YOUR SERVER:
   - Download and back it up.
   - Open it and manually add (or update) ONLY the "auto_prepend_file" 
     line, copying the value from the file included in this ZIP.
   - Every other line in your existing file must stay untouched, 
     since .user.ini often holds important server settings (upload 
     limits, memory_limit, timezone, etc.).

2. FTP UPLOAD RESTRICTIONS:
   - Your server configuration might prevent you from directly uploading 
     a .user.ini file via FTP.
   - In that case, log in to your server control panel, make a backup, 
     and edit the file directly online using the built-in file manager 
     or editor provided by your hosting.

3. IF NO .user.ini EXISTS YET:
   - Only if no .user.ini file exists on the server, you may upload 
     the one included here as-is.

======================================================================
INSTALLATION DONE
======================================================================
Once files are in place and .user.ini is updated, go back to
ACP > Customise > Bad Bot Gatekeeper > Settings 
the "Minimal required deployment steps" box in the page now shows all green.

TXT;
	}
}
