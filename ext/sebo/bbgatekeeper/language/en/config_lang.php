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
	'BBGATEKEEPER_CONFIG_TEMPLATE_NOT_READABLE'   => 'Template not readable: %s',
	'BBGATEKEEPER_CONFIG_FAILED_TO_READ_TEMPLATE'   => 'Failed to read template: %s',
	'BBGATEKEEPER_CONFIG_CANNOT_CREATE_STORE_DIR'   => 'Could not create store directory: %s',
	'BBGATEKEEPER_CONFIG_FAILED_TO_WRITE'   => 'Failed to write %s (%s)',
	'BBGATEKEEPER_CONFIG_CANNOT_SOLVE_BASE_PATH'   => 'Could not resolve base path via realpath()',
	'BBGATEKEEPER_CONFIG_DOCUMENT_ROOT_UNAVAILABLE'   =>'DOCUMENT_ROOT is empty or unavailable',
	'BBGATEKEEPER_CONFIG_CANNOT_READ_INI'   => 'Could not read existing %s',
	'BBGATEKEEPER_CONFIG_WRITE_VERIFICATION_FAIL_INI'   => 'Write verification failed for %s',
	'BBGATEKEEPER_CONFIG_ZIP_NOT_AVAILABLE'   => 'ZipArchive class is not available (php-zip extension missing)',
	'BBGATEKEEPER_CONFIG_FILE_RENDER_FAILED'   => '%s render failed:',
	'BBGATEKEEPER_CONFIG_TEMPNAME_FAILED' => 'tempnam() failed on %s',
	'BBGATEKEEPER_CONFIG_ZIP_OPEN_FAILED'        => 'ZipArchive::open() failed for %s',
]);
