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
	'ACP_BBGATEKEEPER_SETTINGS'     => 'Impostazioni',
	'ACP_BBGATEKEEPER_LOGS'         => 'Log',
	'ACP_BBGATEKEEPER_SETTINGS_PRE_EXPLAIN' => 'Benvenuto in BadBotGatekeeper!<br />Questa estensione necessita di una registrazione al sistema hCaptcha. Se non lo hai fatto, registrati ed ottieni le chiavi necessarie al link: %s',
	'ACP_BBGATEKEEPER_SETTINGS_EXPLAIN' => 'Da questa pagina puoi: configurare la sfida hCaptcha, la blacklist User-Agent e la whitelist dei motori di ricerca che potranno visitare il tuo forum.<br />Dopo aver modificato queste impostazioni, utilizza "Salva e genera/distribuisci" per scrivere lo script di runtime.',

	'BBGATEKEEPER_FUNDAMENTAL_STEPS_EXPLAIN' => 'Per funzionare questa estensione ha bisogno di generare tre file fondamentali:<br />➀ config.php ➁ logger.php ➂ .user.ini<br />Qui puoi verificare se i file sono stati generati / posizionati correttamente / hanno i permessi chmod corretti.',

	'BBGATEKEEPER_HCAPTCHA'         => 'hCaptcha',
	'BBGATEKEEPER_HCAP_SITE_KEY'        => 'Site key (pubblica)',
	'BBGATEKEEPER_HCAP_SITE_SECRET' => 'Site secret',
	'BBGATEKEEPER_HCAP_SIGN_SECRET' => 'Chiave di firma del cookie',
	'BBGATEKEEPER_HCAP_SIGN_SECRET_EXPLAIN' => 'Lascia vuoto per mantenere la chiave attuale. Cambiandola, tutti i cookie hCaptcha già rilasciati ai visitatori diventano invalidi.',

	'BBGATEKEEPER_COOKIE'           => 'Cookie di verifica',
	'BBGATEKEEPER_COOKIE_NAME'      => 'Nome cookie',
	'BBGATEKEEPER_COOKIE_TTL'       => 'Durata cookie',
	'BBGATEKEEPER_COOKIE_DOMAIN'        => 'Domain cookie',
	'BBGATEKEEPER_COOKIE_SAMESITE'      => 'Politica SameSite',

	'BBGATEKEEPER_UA_BLOCKLIST'     => 'Blocklist User-Agent',
	'BBGATEKEEPER_UA_PATTERNS'      => 'Pattern bloccati (uno per riga)',
	'BBGATEKEEPER_UA_PATTERNS_EXPLAIN'  => 'Confronto case-insensitive, anche parziale, sull\'header User-Agent della richiesta.',

	'BBGATEKEEPER_BOT_WHITELIST'        => 'Whitelist motori di ricerca',
	'BBGATEKEEPER_BOT_DOMAINS'      => 'Domini reverse DNS ammessi (uno per riga)',
	'BBGATEKEEPER_BOT_DOMAINS_EXPLAIN'  => 'Verificati tramite Forward-Confirmed reverse DNS, es. ".googlebot.com".',

	'BBGATEKEEPER_MODE'         => 'Modalità operativa',
	'BBGATEKEEPER_DRY_RUN'          => 'Dry run',
	'BBGATEKEEPER_DRY_RUN_EXPLAIN'      => 'Registra cosa verrebbe bloccato senza bloccare effettivamente nulla. Disattiva dopo aver controllato i log.',

	'BBGATEKEEPER_DEPLOY_STATUS'        => 'Stato distribuzione',
	'BBGATEKEEPER_DEPLOY_OK'        => 'Distribuito e attivo',
	'BBGATEKEEPER_DEPLOY_NOT_OK'        => 'Non distribuito / non verificato',
	'BBGATEKEEPER_LAST_DEPLOY'      => 'Ultima generazione',
	'BBGATEKEEPER_GENERATE_DEPLOY'      => 'Salva e genera / distribuisci',

	'BBGATEKEEPER_SETTINGS_SAVED'       => 'Impostazioni salvate.',
	'BBGATEKEEPER_DEPLOY_SUCCESS'       => 'Impostazioni salvate, script di runtime generato e distribuito correttamente.',
	'BBGATEKEEPER_DEPLOY_FAILED'        => 'Impostazioni salvate, ma la generazione o la distribuzione dello script di runtime è fallita. Controlla i permessi di scrittura sulla cartella store/.',
	'BBGATEKEEPER_DEPLOY_WARNING'       => 'Bad Bot Gatekeeper non è ancora stato distribuito (o l\'ultima distribuzione è fallita). Vai alla pagina impostazioni in ACP.',

	'BBGATEKEEPER_FILTER'           => 'Filtro',
	'BBGATEKEEPER_FILTER_ALL'       => 'Tutte le voci',
	'BBGATEKEEPER_FILTER_PASSED'        => 'Solo passate',
	'BBGATEKEEPER_FILTER_BLOCKED'       => 'Solo bloccate',
	'BBGATEKEEPER_FILTER_CHALLENGE' => 'Solo sfida hCaptcha',

	'BBGATEKEEPER_DATETIME'     => 'Data/ora',
	'BBGATEKEEPER_IP'           => 'IP',
	'BBGATEKEEPER_URI'          => 'URI',
	'BBGATEKEEPER_STATUS'           => 'Stato',
	'BBGATEKEEPER_USER_AGENT'       => 'User-Agent',
	'BBGATEKEEPER_LOG_EMPTY'        => 'Nessuna voce di log da mostrare.',
	'BBGATEKEEPER_CLEAR_LOG'        => 'Svuota log',

	'BBGATEKEEPER_FUNDAMENTAL_STEPS'    => 'Passaggi fondamentali',
	'BBGATEKEEPER_CHECK_CONFIG'     => 'config.php generato',
	'BBGATEKEEPER_CHECK_LOGGER'     => 'logger.php generato',
	'BBGATEKEEPER_CHECK_INI'            => '.user.ini collegato (auto_prepend_file)',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_ALL_OK' => 'OK',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_BAD' => 'File mancante',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_NO_RIGHT_PERMISSIONS' => 'Il file ha permessi chmod non corretti',
	'BBGATEKEEPER_INI_PREPEND_INCORRECT' => 'Il file .user.ini esiste ma non contiene il percorso previsto. Controllalo.',

	'BBGATEKEEPER_PERMISSIONS_WARNING'  => 'Il tuo file di configurazione (config.php) è attualmente scrivibile da chiunque. Ti consigliamo vivamente di cambiare i permessi a 640 o almeno a 644 (per esempio: chmod 640 config.php).',

	'BBGATEKEEPER_IP_BINDING'       => 'Associazione IP (tolleranza al cambio rete / mobile)',
	'BBGATEKEEPER_IP_LEVEL_1'       => 'Alta (predefinita) — associazione all\'indirizzo IP esatto',
	'BBGATEKEEPER_IP_LEVEL_2'       => 'Media — associazione solo al prefisso di rete (tollerante alla rotazione IP/operatore)',
	'BBGATEKEEPER_IP_LEVEL_3'       => 'Bassa — nessuna associazione IP (riduce la protezione contro il furto/riutilizzo dei cookie)',

	'BBGATEKEEPER_RESET'            => 'Ripristina distribuzione',
	'BBGATEKEEPER_RESET_EXPLAIN'        => 'Rimuove auto_prepend_file da .user.ini ed elimina i file generati in store/. Le impostazioni salvate vengono mantenute; premi "Salva & genera / distribuisci" in seguito per ridistribuire con le stesse impostazioni.',
	'BBGATEKEEPER_RESET_SUCCESS'        => 'Distribuzione ripristinata con successo.',
	'BBGATEKEEPER_RESET_FAILED'     => 'Ripristino fallito - controlla i permessi dei file su .user.ini e store/.',

	'BBGATEKEEPER_GENERATE'             => 'Generate',
	'BBGATEKEEPER_HCAP_SIGN_SECRET_EXPLAIN' => 'Visible here for backup/recovery purposes - keep this page restricted to trusted admins. Changing it (manually or via Generate) invalidates every hCaptcha cookie already issued to visitors.',
	'BBGATEKEEPER_SIGN_SECRET_GENERATED'    => 'A new signing secret has been generated and saved.',

	'BBGATEKEEPER_GOTO_SETTINGS'    => 'Vai alle impostazioni di Bad Bot Gatekeeper',

]);
