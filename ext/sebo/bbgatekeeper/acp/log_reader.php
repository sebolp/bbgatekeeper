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
* Reads and parses store/logs/access.log for the ACP "Logs" tab.
*/
class log_reader
{
	/** @var string absolute path to store/, trailing slash */
	protected $store_path;

	/**
	* @param string $store_path
	*/
	public function __construct(string $store_path)
	{                
		$this->store_path = $store_path;
	}

	/**
	* @param int    $max_lines
	* @param string $status_filter substring match against the status field
	* @return array<int, array{datetime: string, ip: string, uri: string, status: string, user_agent: string}>
	*/
	public function tail(int $max_lines, string $status_filter = ''): array
	{
	global $request;
		
	$phpbb_root = dirname($request->server('SCRIPT_FILENAME', ''), 2);
    $path = $phpbb_root . '/ext/sebo/bbgatekeeper/store/logs/access.log';

    if (!is_readable($path))
    {
        return [];
    }

	$content = file_get_contents($path);

	// Check if content is retrieved
	if ($content === false)
	{
		//return [];
		trigger_error('file_get_contents failed');
	}

		// Normalize line endings and extract lines
		$content = str_replace(["\r\n", "\r"], "\n", $content);
		$raw_lines = array_filter(explode("\n", rtrim($content, "\n")));
		
		// Take the last $max_lines
		$raw_lines = array_slice($raw_lines, -$max_lines);

		$entries = [];
		foreach (array_reverse($raw_lines) as $raw_line)
		{
			$parts = explode('|', $raw_line, 5);
			if (count($parts) !== 5)
			{
				continue;
			}

			[$datetime, $ip, $uri, $status, $user_agent] = $parts;

			$entries[] = [
				'datetime'		=> $datetime,
				'ip'			=> $ip,
				'status'		=> $status,
				'uri'			=> $uri,
				'user_agent'	=> $user_agent,
			];
		}

		return $entries;
	}

	/**
	* @return bool
	*/
	public function clear(): bool
	{
		global $request;
		$phpbb_root = dirname($request->server('SCRIPT_FILENAME', ''), 2);
    	$path = $phpbb_root . '/ext/sebo/bbgatekeeper/store/logs/access.log';
		if (!file_exists($path))
		{
			return true;
		}

		return @file_put_contents($path, '') !== false;
	}
}