<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sebo\bbgatekeeper\cron;

/**
 * Cron task: automatic cleanup of no-longer-active .ban/.hit files in
 * store/logs/bans/. Enabled/configured (interval in minutes) from the
 * "Hits & Ban" ACP page (bbgatekeeper_autoclean_enable / _interval).
 *
 * Register as a service tagged cron.task (see services_cron_snippet.yml).
 */
class cleanup_task extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var string */
	protected $phpbb_root_path;

	/**
	* @param \phpbb\config\config     $config
	* @param \phpbb\log\log_interface $log
	* @param string                   $phpbb_root_path
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\log\log_interface $log, $phpbb_root_path)
	{
		$this->config = $config;
		$this->log = $log;
		$this->phpbb_root_path = $phpbb_root_path;
	}

	/**
	 * Returns the name of the cron task to be used in the URL
	 *
	 * @return string
	 */
	public function get_name()
	{
		return 'sebo.bbgatekeeper.cron.cleanup_task';
	}

	/**
	* The task only exists/runs if the admin has enabled automatic cleanup
	* from the Hits & Ban page.
	*
	* @return bool
	*/
	public function is_runnable()
	{
		return (bool) ($this->config['bbgatekeeper_autoclean_enable'] ?? false);
	}

	/**
	* @return bool
	*/
	public function should_run()
	{
		$interval_minutes = max(5, (int) ($this->config['bbgatekeeper_autoclean_interval'] ?? 60));
		$last_run = (int) ($this->config['bbgatekeeper_autoclean_last_run'] ?? 0);

		return (time() - $last_run) >= ($interval_minutes * 60);
	}

	/**
	* @return void
	*/
	public function run()
	{
		$manager = new \sebo\bbgatekeeper\core\hits_ban_manager(
			$this->phpbb_root_path . 'ext/sebo/bbgatekeeper/store/logs/bans'
		);

		$deleted_bans = $manager->delete_expired_bans();
		$deleted_hits = $manager->delete_expired_hits();

		$this->config->set('bbgatekeeper_autoclean_last_run', (string) time());

		$this->log->add(
			'admin',
			ANONYMOUS,
			'',
			'LOG_BBGATEKEEPER_AUTOCLEAN',
			time(),
			array((int) $deleted_bans, (int) $deleted_hits)
		);
	}
}
