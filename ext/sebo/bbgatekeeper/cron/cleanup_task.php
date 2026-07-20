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
 * Cron task: pulizia automatica dei file .ban/.hit non più attivi in
 * store/logs/bans/. Attivabile/configurabile (intervallo in minuti) dalla
 * pagina ACP "Hits & Ban" (bbgatekeeper_autoclean_enable / _interval).
 *
 * Registrare come servizio con tag cron.task (vedi services_cron_snippet.yml).
 */
class cleanup_task extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var string */
	protected $phpbb_root_path;

	/**
	* @param \phpbb\config\config $config
	* @param string               $phpbb_root_path
	*/
	public function __construct(\phpbb\config\config $config, $phpbb_root_path)
	{
		$this->config = $config;
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
	* Il task esiste/gira solo se l'admin ha abilitato la pulizia automatica
	* dalla pagina Hits & Ban.
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

		$manager->delete_expired_bans();
		$manager->delete_expired_hits();

		$this->config->set('bbgatekeeper_autoclean_last_run', (string) time());
	}
}
