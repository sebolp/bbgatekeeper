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
	'BBGATEKEEPER_TEMPLATE_LOGGER_SECURITY_CHECK'           => 'Security check in progress...',
	'BBGATEKEEPER_TEMPLATE_LOGGER_SECURITY_CHECK_EXPLAIN'   => 'Please wait a moment, we are verifying your connection.',
	'BBGATEKEEPER_TEMPLATE_LOGGER_BUTTON'                   => 'Verify and Continue',
	'BBGATEKEEPER_TEMPLATE_LOGGER_COOKIE'                   => 'Cookies are required for this check 🍪.',
	'BBGATEKEEPER_TEMPLATE_LOGGER_CLAUSE'                   => 'By clicking the "Verify and Continue" button, you agree to the storage of a technical (non-tracking) cookie in your browser:<br />a pass for %s (required for site navigation).',
	'BBGATEKEEPER_TEMPLATE_LOGGER_JS'                       => 'JavaScript must also be enabled.',
	'BBGATEKEEPER_TEMPLATE_LOGGER_DO_NOT_AGREE'             => 'If you do not agree to this processing / purpose, please do not continue.',
	'BBGATEKEEPER_TEMPLATE_LOGGER_ASSISTANCE'               => 'For any communication or assistance:',
	'BBGATEKEEPER_TEMPLATE_LOGGER_ERROR_TOO_MANY_REQUESTS'          => 'Too many requests or access denied. Please try later.',
	'BBGATEKEEPER_TEMPLATE_LOGGER_WORD_MINUTE'          => 'minute',
	'BBGATEKEEPER_TEMPLATE_LOGGER_WORD_MINUTES'         => 'minutes',
	'BBGATEKEEPER_TEMPLATE_LOGGER_WORD_HOUR'                => 'hour',
	'BBGATEKEEPER_TEMPLATE_LOGGER_WORD_HOURS'               => 'hours',
	'BBGATEKEEPER_TEMPLATE_LOGGER_WORD_DAY'                 => 'day',
	'BBGATEKEEPER_TEMPLATE_LOGGER_WORD_DAYS'                => 'days',
	'BBGATEKEEPER_TEMPLATE_LOGGER_WORD_WEEK'                => 'week',
	'BBGATEKEEPER_TEMPLATE_LOGGER_WORD_WEEKS'               => 'weeks',

]);
