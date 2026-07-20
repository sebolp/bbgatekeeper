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
 * Migration to add the new 'hits_and_bans' ACP module.
 */
class add_hits_and_bans_module_v120 extends \phpbb\db\migration\migration
{
	/**
	 * {@inheritdoc}
	 */
	public static function depends_on()
	{
		// Make sure this runs after the initial install migration
		return ['\sebo\bbgatekeeper\migrations\install_gatekeeper_v10x'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function update_data()
	{
		return [
			['module.add', [
				'acp',
				'ACP_BBGATEKEEPER_TITLE',
				[
					'module_basename'	=> '\sebo\bbgatekeeper\acp\main_module',
					'modes'				=> ['hits_and_bans'],
				],
			]],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function revert_data()
	{
		return [
			['module.remove', [
				'acp',
				'ACP_BBGATEKEEPER_TITLE',
				[
					'module_basename'	=> '\sebo\bbgatekeeper\acp\main_module',
					'modes'				=> ['hits_and_bans'],
				],
			]],
		];
	}
}
