<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_BBGATEKEEPER_TITLE'        => 'Bad Bot Gatekeeper',
	'ACP_BBGATEKEEPER_SETTINGS'     => 'Settings',
	'ACP_BBGATEKEEPER_LOGS'         => 'Logs',
	'ACP_BBGATEKEEPER_HITS_AND_BANS'	=> 'Hits &amp; Ban',
]);
