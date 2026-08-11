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
	'BBGATEKEEPER_CONFIG_TEMPLATE_NOT_READABLE'   => 'Template non leggibile: %s',
	'BBGATEKEEPER_CONFIG_FAILED_TO_READ_TEMPLATE'   => 'Impossibile leggere il template: %s',
	'BBGATEKEEPER_CONFIG_CANNOT_CREATE_STORE_DIR'   => 'Impossibile creare la directory di memorizzazione: %s',
	'BBGATEKEEPER_CONFIG_FAILED_TO_WRITE'   => 'Impossibile scrivere %s (%s)',
	'BBGATEKEEPER_CONFIG_CANNOT_SOLVE_BASE_PATH'   => 'Impossibile risolvere il percorso di base tramite realpath()',
	'BBGATEKEEPER_CONFIG_DOCUMENT_ROOT_UNAVAILABLE'   => 'DOCUMENT_ROOT è vuoto o non disponibile',
	'BBGATEKEEPER_CONFIG_CANNOT_READ_INI'   => 'Impossibile leggere il file %s esistente',
	'BBGATEKEEPER_CONFIG_WRITE_VERIFICATION_FAIL_INI'   => 'Verifica della scrittura non riuscita per %s',
	'BBGATEKEEPER_CONFIG_ZIP_NOT_AVAILABLE'   => 'La classe ZipArchive non è disponibile (estensione php-zip mancante)',
	'BBGATEKEEPER_CONFIG_FILE_RENDER_FAILED'   => 'Rendering di %s non riuscito:',
	'BBGATEKEEPER_CONFIG_TEMPNAME_FAILED'      => 'tempnam() non riuscita su %s',
	'BBGATEKEEPER_CONFIG_ZIP_OPEN_FAILED'      => 'ZipArchive::open() non riuscito per %s',
]);
