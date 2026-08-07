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
	* @param string $status_filter '', 'passed', or 'blocked'
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

		if ($content === false)
		{
			trigger_error('file_get_contents failed');
			return [];
		}

		// Normalize line endings and extract lines
		$content = str_replace(["\r\n", "\r"], "\n", $content);
		$raw_lines = array_filter(explode("\n", rtrim($content, "\n")));

		$entries = [];
		foreach (array_reverse($raw_lines) as $raw_line)
		{
			$parts = explode('|', $raw_line, 5);
			if (count($parts) !== 5)
			{
				continue;
			}

			// Matches the write order in bbgatekeeper_logger.php:
			// "{datetime}|{ip}|{status}|{uri}|{user_agent}"
			[$datetime, $ip, $status, $uri, $user_agent] = $parts;

			// Apply the filter before counting toward max_lines, otherwise
			// matching entries further back in the file would be missed
			if ($status_filter !== '' && $this->classify_status($status) !== $status_filter)
			{
				continue;
			}

			$entries[] = [
				'datetime'   => $datetime,
				'ip'         => $ip,
				'status'     => $status,
				'uri'        => $uri,
				'user_agent' => $user_agent,
			];

			if (count($entries) >= $max_lines)
			{
				break;
			}
		}

		return $entries;
	}

	/**
	* Maps a raw status value from the log line to a coarse category used
	* by the ACP filter dropdown. bbgatekeeper_logger.php only ever writes
	* '✅' (passed) or '🚫:<reason>' (blocked, e.g. '🚫: hCaptcha', '🚫:empty-ua').
	*
	* @param string $status raw status value from the log line
	* @return string 'passed', 'blocked', or 'other'
	*/
	protected function classify_status(string $status): string
	{
		if (strpos($status, '🚫') === 0)
		{
			return 'blocked';
		}

		if (strpos($status, '✅') === 0)
		{
			return 'passed';
		}

		return 'other';
	}

	/**
	* Groups entries by IP prefix to help spot clusters of related
	* addresses behind repeated attacks.
	*
	* @param array<int, array{datetime: string, ip: string, uri: string, status: string, user_agent: string}> $entries
	* @param int $depth number of leading address segments to group by (2 or 3) — octets for IPv4, hextets for IPv6
	* @return array<string, array<int, array>> entries grouped by IP prefix, largest group first
	*/
	public function group_by_ip_prefix(array $entries, int $depth = 2): array
	{
		$groups = [];
		foreach ($entries as $entry)
		{
			$groups[$this->ip_group_key($entry['ip'], $depth)][] = $entry;
		}

		// Largest cluster first, so likely attack sources surface at the top
		uasort($groups, function ($a, $b) {
			return count($b) - count($a);
		});

		return $groups;
	}

	/**
	* @param string $ip
	* @param int    $depth number of leading address segments to group by (2 or 3) — octets for IPv4, hextets for IPv6
	* @return string
	*/
	protected function ip_group_key(string $ip, int $depth = 2): string
	{
		$depth = max(2, min(3, $depth));

		if (strpos($ip, ':') !== false)
		{
			// IPv6: depth 2 = /32 (ISP-level), depth 3 = /48 (typical
			// per-customer allocation) — same depth toggle as IPv4, but the
			// bit boundaries differ since each hextet is 16 bits, not 8
			$hextets = explode(':', $ip);
			return (count($hextets) >= $depth) ? implode(':', array_slice($hextets, 0, $depth)) : $ip;
		}

		// IPv4: depth 2 = /16, depth 3 = /24 (8 bits per octet)
		$octets = explode('.', $ip);
		return (count($octets) >= $depth) ? implode('.', array_slice($octets, 0, $depth)) : $ip;
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
