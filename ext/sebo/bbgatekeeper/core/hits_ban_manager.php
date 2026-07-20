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
 * Reads and manages the .ban / .hit marker files written by
 * bbgatekeeper_logger.php (record_block_and_check_ban()).
 *
 * File formats on disk (see bbgatekeeper_logger.php.template):
 *   *.ban  ->  "{ip}|{ban_unix_time}"   (older files may be empty)
 *   *.hit  ->  "{start_time}|{hits}|{ip}"  (older files may miss the ip part)
 *
 * "Active" means: still enforced by the gatekeeper logic right now.
 *   - a .ban is active for BAN_TTL seconds from its ban_time (matches
 *     is_ip_banned()'s 1800s window in the logger).
 *   - a .hit is active for HIT_TTL seconds from its window start_time
 *     (matches the 60s rolling window in record_block_and_check_ban()).
 * Past that, the file is a stale leftover: the logger itself would
 * ignore/reset it on the next request, so it's safe to delete.
 */
class hits_ban_manager
{
	public const BAN_TTL = 1800; // 30 minuti, deve combaciare con is_ip_banned()
	public const HIT_TTL = 60;   // 1 minuto, deve combaciare con la finestra hit

	/** @var string */
	protected $bans_dir;

	public function __construct(string $bans_dir)
	{
		$this->bans_dir = rtrim($bans_dir, '/');
	}

	/**
	* @return array List of bans: hash, ip, mtime, ban_time, active
	*/
	public function get_bans(): array
	{
		return $this->read_entries('.ban');
	}

	/**
	* @return array List of hits: hash, ip, mtime, start_time, hits, active
	*/
	public function get_hits(): array
	{
		return $this->read_entries('.hit');
	}

	/**
	* @param string $suffix '.ban' or '.hit'
	* @return array
	*/
	protected function read_entries(string $suffix): array
	{
		$entries = [];

		if (!is_dir($this->bans_dir))
		{
			return $entries;
		}

		$files = glob($this->bans_dir . '/*' . $suffix);

		if ($files === false)
		{
			return $entries;
		}

		$now = time();

		foreach ($files as $file)
		{
			$hash = basename($file, $suffix);
			$content = @file_get_contents($file);
			$mtime = @filemtime($file) ?: 0;

			$ip = '';
			$hits = null;
			$start_time = null;
			$ban_time = null;

			if ($content !== false && $content !== '')
			{
				$parts = explode('|', $content);

				if ($suffix === '.ban')
				{
					// "{ip}|{ban_time}"
					$ip = $parts[0] ?? '';
					$ban_time = isset($parts[1]) ? (int) $parts[1] : $mtime;
				}
				else
				{
					// "{start_time}|{hits}|{ip}"
					$start_time = isset($parts[0]) ? (int) $parts[0] : null;
					$hits = isset($parts[1]) ? (int) $parts[1] : null;
					$ip = $parts[2] ?? '';
				}
			}

			if ($suffix === '.ban')
			{
				$ban_time = $ban_time !== null ? $ban_time : $mtime;
				$active = ($now - $ban_time) < self::BAN_TTL;
			}
			else
			{
				$reference = $start_time !== null ? $start_time : $mtime;
				$active = ($now - $reference) < self::HIT_TTL;
			}

			$entries[] = [
				'hash'       => $hash,
				'ip'         => $ip !== '' ? $ip : null,
				'mtime'      => $mtime,
				'ban_time'   => $ban_time !== null ? $ban_time : $mtime,
				'start_time' => $start_time,
				'hits'       => $hits,
				'active'     => $active,
			];
		}

		// Most recent first
		usort($entries, function ($a, $b)
		{
			return $b['mtime'] <=> $a['mtime'];
		});

		return $entries;
	}

	/**
	* @param string $hash md5(ip), already validated by the caller
	* @return bool
	*/
	public function delete_ban(string $hash): bool
	{
		return $this->delete_file($hash . '.ban');
	}

	/**
	* @param string $hash md5(ip), already validated by the caller
	* @return bool
	*/
	public function delete_hit(string $hash): bool
	{
		return $this->delete_file($hash . '.hit');
	}

	/**
	* @return int number of files removed
	*/
	public function delete_all_bans(): int
	{
		return $this->delete_all('.ban');
	}

	/**
	* @return int number of files removed
	*/
	public function delete_all_hits(): int
	{
		return $this->delete_all('.hit');
	}

	/**
	* Removes only .ban files older than BAN_TTL (no longer enforced).
	* Active bans are left untouched.
	*
	* @return int number of files removed
	*/
	public function delete_expired_bans(): int
	{
		return $this->delete_where('.ban', function (array $entry)
		{
			return !$entry['active'];
		});
	}

	/**
	* Removes only .hit files older than HIT_TTL (window already lapsed,
	* the logger would reset the counter anyway on next attempt).
	*
	* @return int number of files removed
	*/
	public function delete_expired_hits(): int
	{
		return $this->delete_where('.hit', function (array $entry)
		{
			return !$entry['active'];
		});
	}

	/**
	* @param string   $suffix    '.ban' or '.hit'
	* @param callable $predicate function(array $entry): bool — true = delete
	* @return int number of files removed
	*/
	protected function delete_where(string $suffix, callable $predicate): int
	{
		$count = 0;

		foreach ($this->read_entries($suffix) as $entry)
		{
			if ($predicate($entry) && $this->delete_file($entry['hash'] . $suffix))
			{
				$count++;
			}
		}

		return $count;
	}

	/**
	* @param string $filename basename only, e.g. "<hash>.ban"
	* @return bool
	*/
	protected function delete_file(string $filename): bool
	{
		// basename() strips any path component, protecting against
		// path traversal even if a caller forgot to validate $hash.
		$path = $this->bans_dir . '/' . basename($filename);

		if (!is_file($path))
		{
			return false;
		}

		return @unlink($path);
	}

	/**
	* @param string $suffix '.ban' or '.hit'
	* @return int number of files removed
	*/
	protected function delete_all(string $suffix): int
	{
		$count = 0;

		if (!is_dir($this->bans_dir))
		{
			return $count;
		}

		$files = glob($this->bans_dir . '/*' . $suffix);

		if ($files === false)
		{
			return $count;
		}

		foreach ($files as $file)
		{
			if (@unlink($file))
			{
				$count++;
			}
		}

		return $count;
	}
}