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
* ACP module info.
*/
class main_info
{
	public function module()
	{
		return [
			'filename'  => '\sebo\bbgatekeeper\acp\main_module',
			'title'     => 'ACP_BBGATEKEEPER_TITLE',
			'modes'     => [
				'settings'  => [
					'title' => 'ACP_BBGATEKEEPER_SETTINGS',
					'auth'  => 'acl_a_board',
					'cat'   => ['ACP_BBGATEKEEPER_TITLE'],
				],
				'logs'  => [
					'title' => 'ACP_BBGATEKEEPER_LOGS',
					'auth'  => 'acl_a_board',
					'cat'   => ['ACP_BBGATEKEEPER_TITLE'],
				],
				 'hits_and_bans' => [
					'title' => 'ACP_BBGATEKEEPER_HITS_AND_BANS',
					'auth'  => 'acl_a_board',
					'cat'   => ['ACP_BBGATEKEEPER_TITLE'],
					],
			],
		];
	}
}
