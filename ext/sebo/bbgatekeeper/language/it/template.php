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
    'BBGATEKEEPER_TEMPLATE_LOGGER_SECURITY_CHECK' => 'Controllo di sicurezza in corso...',
    'BBGATEKEEPER_TEMPLATE_LOGGER_SECURITY_CHECK_EXPLAIN' => 'Attendi un istante, stiamo verificando la tua connessione.',
    'BBGATEKEEPER_TEMPLATE_LOGGER_BUTTON'   => 'Verifica e Prosegui',
    'BBGATEKEEPER_TEMPLATE_LOGGER_COOKIE' => 'Per questo controllo sono necessari i 🍪.',
    'BBGATEKEEPER_TEMPLATE_LOGGER_CLAUSE' => 'Cliccando sul pulsante "Verifica e Prosegui" accetti che venga salvato<br />un cookie tecnico (non tracciante) nel tuo browser:<br />un lasciapassare per %s (necessario per la navigazione nel sito).',
    'BBGATEKEEPER_TEMPLATE_LOGGER_JS' => 'E\' necessario anche l\'uso dei JavaScript.',
    'BBGATEKEEPER_TEMPLATE_LOGGER_DO_NOT_AGREE' => 'Se non acconsenti a questo trattamento / scopo non proseguire.',
    'BBGATEKEEPER_TEMPLATE_LOGGER_ASSISTANCE' => 'Per qualunque comunicazione o assistenza:',
    
]);