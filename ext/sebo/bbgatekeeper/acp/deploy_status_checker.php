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

/**
* The 3 fundamental, ordered checks shown in the ACP settings page
* ("passaggi fondamentali"): config.php exists, bbgatekeeper_logger.php exists, and
* the document root's .user.ini actually wires up the prepend. Also
* reports a separate, non-blocking permissions hardening warning.
*/
class deploy_status_checker
{
	/** @var string absolute path to store/, trailing slash */
	protected $store_path;

	/** @var string absolute path to the phpBB/document root, trailing slash */
	protected $root_path;

	/** @var \phpbb\request\request_interface */
	public $request;

	/**
	* @param string $store_path
	* @param string $root_path
	*/
	public function __construct(string $store_path, string $root_path, \phpbb\request\request $request)
	{
		$this->store_path = $store_path;
		$this->root_path = $root_path;
		$this->request = $request;
	}

    /**
    * @return array<int, array{key: string, label: string, status: string}>
    */
    public function fundamental_checks(): array
    {
        return [
            ['key' => 'config', 'label' => 'BBGATEKEEPER_CHECK_CONFIG', 'status' => $this->get_file_status($this->store_path . 'bbgatekeeper_config.php')],
            ['key' => 'logger', 'label' => 'BBGATEKEEPER_CHECK_LOGGER', 'status' => $this->get_file_status($this->store_path . 'bbgatekeeper_logger.php')],
            ['key' => 'ini',    'label' => 'BBGATEKEEPER_CHECK_INI',    'status' => $this->get_ini_status()],
        ];
    }

	/**
    * Check if file exists and it is readeable.
    * 
    * @return string 'ok', 'bad', o 'no_right_permissions'
    */
    private function get_file_status(string $path): string
    {
        if (!file_exists($path))
        {
            return 'bad';
        }

        return 'ok';
    }

	/**
    * Check .user.ini file
    * 
    * @return string 'ok', 'bad', o 'no_rigth_permissions'
    */
    public function get_ini_status(): string
    {
        $doc_root = $this->request->server('DOCUMENT_ROOT');
		
		if (empty($doc_root))
        {
            return 'bad';
        }
        
		$ini_path = rtrim($doc_root, '/\\') . '/.user.ini';

        if (!file_exists($ini_path))
        {
			return 'bad';
        }

        // if exists
        return 'ok';
    }

    /**
    * @return bool true only if every fundamental check status is 'ok'
    */
    public function all_ok(): bool
    {
        foreach ($this->fundamental_checks() as $check)
        {
            // Controlla che lo status sia esattamente 'ok'
            if ($check['status'] !== 'ok')
            {
                return false;
            }
        }

        return true;
    }

	/**
	* Checks, via regex, whether .user.ini points auto_prepend_file at
	* our generated bbgatekeeper_logger.php.
	*
	* Note: PHP-FPM caches .user.ini for user_ini.cache_ttl seconds
	* (300s by default), so this can report "ok" a few minutes before the
	* live site actually picks up a fresh deploy.
	*
	* @return bool
	*/
	public function ini_has_prepend_line(): bool
	{
		$doc_root = $this->request->server('DOCUMENT_ROOT');
        
        if (empty($doc_root))
        {
            return false;
        }
        
		$ini_path = rtrim($doc_root, '/\\') . '/.user.ini';

		if (!is_readable($ini_path))
		{
			return false;
		}

		$content = file_get_contents($ini_path);
		if ($content === false)
		{
			return false;
		}

        // make the dir to check
        $script_dir = dirname($this->request->server('SCRIPT_FILENAME'));
        
        $base_path = realpath($script_dir . '/' . $this->root_path);
		
		if ($base_path === false)
        {
            return false;
        }

        // Remove points: transform "./../ext/..." to "ext/..."
        $clean_store_path = preg_replace('#^(\.{1,2}/)+#', '', $this->store_path);

        $absolute_logger_path = $base_path . '/' . $clean_store_path . 'bbgatekeeper_logger.php';
        
        // Normalize slashs
        $absolute_logger_path = str_replace('\\', '/', $absolute_logger_path);

        $expected_path = preg_quote($absolute_logger_path, '/');

        return (bool) preg_match('/^\s*auto_prepend_file\s*=\s*"?' . $expected_path . '"?/m', $content);
    }

	/**
	* Mirrors phpBB's own installer check on bbgatekeeper_config.php: warns if group
	* or other has the write bit set (0022), regardless of owner perms.
	*
	* @return bool true if a permissions warning should be shown
	*/
	public function config_permissions_warning(): bool
	{
		$path = $this->store_path . 'bbgatekeeper_config.php';
		if (!file_exists($path))
		{
			return false;
		}

		$perms = @fileperms($path);
		if ($perms === false)
		{
			return false;
		}

		return ($perms & 0022) !== 0;
	}
}