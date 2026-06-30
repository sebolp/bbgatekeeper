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
* Resets the deployment without touching the settings saved in
* phpbb_config: removes auto_prepend_file from .user.ini first, and only
* if that succeeds, deletes store/runtime/ and store/logs/. This order is
* mandatory - removing store/ first while .user.ini still points at it
* would take the whole site down on every request.
*/
class reset
{
	/** @var deployer */
	protected $deployer;

	/** @var string absolute path to store/, trailing slash */
	protected $store_path;

	/** @var array<string,bool> for every step of last reset */
    protected $results = [];

	/**
	* @param deployer $deployer
	* @param string   $store_path
	*/
	public function __construct(deployer $deployer, string $store_path)
	{
		$this->deployer = $deployer;
		$this->store_path = $store_path;
	}

	/**
	* @return bool
	*/
	public function reset(): bool
	{
		$this->results = [];

        $ok_ini = $this->deployer->remove_ini_line();
        $this->results['remove_ini_line'] = $ok_ini;

        if (!$ok_ini)
        {
            // non procediamo con la cancellazione dello store per sicurezza
            $this->results['delete_store_contents'] = false;
            return false;
        }

        $ok_store = $this->delete_store_contents($this->store_path);
        $this->results['delete_store_contents'] = $ok_store;

        return $ok_ini && $ok_store;
    }

    public function get_results(): array
    {
        return $this->results;
    }

	/**
	* Deletes everything under $path except .htaccess, but never $path
	* itself - store/ and its protective .htaccess always survive.
	*
	* @param string $path
	* @return bool
	*/
	protected function delete_store_contents(string $path): bool
	{
		if (!is_dir($path))
		{
			return true;
		}

		$entries = @scandir($path);
		if ($entries === false)
		{
			return false;
		}

		$success = true;
		foreach ($entries as $entry)
		{
			if ($entry === '.' || $entry === '..' || $entry === '.htaccess')
			{
				continue;
			}

			$full_path = $path . $entry;
			$success = is_dir($full_path)
				? ($success && $this->delete_directory_recursive($full_path . '/'))
				: ($success && @unlink($full_path));
		}

		return $success;
	}

	/**
	* Fully recursive delete, including the directory itself.
	*
	* @param string $path
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