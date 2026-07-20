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
	'ACP_BBGATEKEEPER_SETTINGS_LANGUAGE_FALLBACK' => 'Attenzione:',
	'ACP_BBGATEKEEPER_SETTINGS_LANGUAGE_FALLBACK_EXPLAIN'   => 'I file generati saranno nella lingua dell\'utente SOLO SE esiste una traduzione completa dell\'estensione. In caso contrario, i file saranno generati in inglese.',

	'BBGATEKEEPER_FUNDAMENTAL_STEPS_EXPLAIN' => 'Per funzionare questa estensione ha bisogno di generare tre file fondamentali:<br />➀ config.php ➁ logger.php ➂ .user.ini<br />Qui puoi verificare se i file sono stati generati / posizionati correttamente / hanno i permessi chmod corretti.',

	'BBGATEKEEPER_HCAPTCHA'			=> 'hCaptcha',
	'BBGATEKEEPER_HCAP_SITE_KEY'		=> 'Chiave pubblica del sito',
	'BBGATEKEEPER_HCAP_SITE_KEY_EXPLAIN'	=> 'Puoi trovarla sul sito di hCaptcha, creandone una nuova o usandone una esistente.<br />La trovi navigando nella scheda "Site".',
	'BBGATEKEEPER_HCAP_SITE_SECRET'		=> 'Chiave segreta del sito',
	'BBGATEKEEPER_HCAP_SITE_SECRET_EXPLAIN'	=> 'Puoi trovarla sul sito di hCaptcha.<br />Solitamente si trova in Impostazioni generali dell\'account -> Secret',
	'BBGATEKEEPER_HCAP_SIGN_SECRET'		=> 'Segreto di firma del cookie',

	'BBGATEKEEPER_COOKIE'			=> 'Cookie di verifica',
	'BBGATEKEEPER_COOKIE_NAME'		=> 'Nome del cookie',
	'BBGATEKEEPER_COOKIE_NAME_EXPLAIN'	=> 'Puoi cambiarlo come preferisci. Ricorda che ogni volta che lo cambi, vengono invalidati tutti i cookie già rilasciati ai visitatori.',
	'BBGATEKEEPER_COOKIE_TTL'		=> 'Durata del cookie',
	'BBGATEKEEPER_COOKIE_TTL_EXPLAIN'	=> 'Per quanto tempo il cookie consente l\'accesso al sito prima di resettarsi.<br />Espresso in secondi. Predefinito 86400 (24 ore). Puoi usare il tempo che preferisci.<br />Esempi: 3600 (1 ora), 86400 (1 giorno), 604800 (1 settimana), 2629800 (1 mese)',
	'BBGATEKEEPER_COOKIE_DOMAIN'		=> 'Dominio del cookie',
	'BBGATEKEEPER_COOKIE_DOMAIN_EXPLAIN'	=> 'È lo stesso della configurazione della board.<br />Usa <em>.example.com</em> per includere tutti i sottodomini.<br />Usa <em>.subdomain.example.com</em> per usarlo solo in un sottodominio.',
	'BBGATEKEEPER_COOKIE_SAMESITE'		=> 'Policy SameSite',
	'BBGATEKEEPER_COOKIE_SAMESITE_EXPLAIN'	=> 'Differenti policy per i cookie.<br /><em>LAX</em>: (predefinito) invia i cookie solo durante la navigazione di primo livello, come cliccare su un link esterno.<br /><em>STRICT</em>: (più sicuro) invia i cookie esclusivamente quando navighi direttamente all\'interno del sito di origine.',

	'BBGATEKEEPER_UA_BLOCKLIST'		=> 'Lista di blocco User-Agent',
	'BBGATEKEEPER_UA_PATTERNS'		=> 'Pattern bloccati (uno per riga)',
	'BBGATEKEEPER_UA_PATTERNS_EXPLAIN'	=> 'Non distingue tra maiuscole e minuscole, corrispondenza parziale rispetto all\'header User-Agent della richiesta.',

	'BBGATEKEEPER_BOT_WHITELIST'		=> 'Whitelist dei motori di ricerca',
	'BBGATEKEEPER_BOT_DOMAINS'		=> 'Domini rDNS consentiti (uno per riga)',
	'BBGATEKEEPER_BOT_DOMAINS_EXPLAIN'	=> 'Lascialo così se non sai come verificare.<br />Il DNS inverso (rDNS) associa gli indirizzi IP ai domini. Verifica i crawler ufficiali dei motori di ricerca, migliora la sicurezza delle email e previene lo spam. È un sistema di autenticazione e reputazione.<br />Puoi verificarlo con validatori online.',

	'BBGATEKEEPER_MODE'			=> 'Modalità operativa',
	'BBGATEKEEPER_DRY_RUN'			=> 'Dry run (Simulazione)',
	'BBGATEKEEPER_DRY_RUN_EXPLAIN'		=> 'Registra cosa verrebbe bloccato senza bloccare effettivamente nulla.<br />Per bloccare, deve essere disabilitato.',

	'BBGATEKEEPER_DEPLOY_STATUS'		=> 'Stato del deployment',
	'BBGATEKEEPER_DEPLOY_OK'		=> 'Distribuito e attivo',
	'BBGATEKEEPER_DEPLOY_NOT_OK'		=> 'Non distribuito / non verificato',
	'BBGATEKEEPER_LAST_DEPLOY'		=> 'Ultima generazione',
	'BBGATEKEEPER_GENERATE_DEPLOY'		=> 'Salva impostazioni &amp; genera file',

	'BBGATEKEEPER_SETTINGS_SAVED'		=> 'Impostazioni salvate.',
	'BBGATEKEEPER_DEPLOY_SUCCESS'		=> 'Impostazioni salvate, script di runtime generato con successo. Controlla la cartella store/bbagatekeeper nel percorso ext/sebo/bbgatekeeper e il file .user.ini nella cartella root del sito per confermare.',
	'BBGATEKEEPER_DEPLOY_FAILED'		=> 'Impostazioni salvate, ma la generazione o la distribuzione dello script di runtime è fallita. Controlla i permessi di scrittura sulla cartella store/.',
	'BBGATEKEEPER_DEPLOY_WARNING'		=> 'Bad Bot Gatekeeper non è ancora stato attivato (o l\'ultimo deployment è fallito). Visita la pagina delle impostazioni ACP.',

	'BBGATEKEEPER_FILTER'			=> 'Filtro',
	'BBGATEKEEPER_FILTER_ALL'		=> 'Tutte le voci',
	'BBGATEKEEPER_FILTER_PASSED'		=> 'Solo passati',
	'BBGATEKEEPER_FILTER_BLOCKED'		=> 'Solo bloccati',
	'BBGATEKEEPER_FILTER_CHALLENGE'		=> 'Solo challenge hCaptcha',

	'BBGATEKEEPER_DATETIME'			=> 'Data/ora',
	'BBGATEKEEPER_IP'			=> 'IP',
	'BBGATEKEEPER_URI'			=> 'URI',
	'BBGATEKEEPER_STATUS'			=> 'Stato',
	'BBGATEKEEPER_USER_AGENT'		=> 'User-Agent',
	'BBGATEKEEPER_LOG_EMPTY'		=> 'Nessuna voce di log da mostrare.',
	'BBGATEKEEPER_CLEAR_LOG'		=> 'Pulisci log',
	'BBGATEKEEPER_LOGS_EXPLAIN'		=> 'Qui puoi leggere le prime <strong>500</strong> righe dei log. Per leggere il file completo devi controllare il file <code class="inline">access.log</code> nella cartella <code class="inline">ext/sebo/bbgatekeeper/store/logs</code>.',

	'BBGATEKEEPER_FUNDAMENTAL_STEPS'	=> 'Passaggi minimi necessari per il deployment',
	'BBGATEKEEPER_CHECK_CONFIG'		=> 'config.php',
	'BBGATEKEEPER_CHECK_LOGGER'		=> 'logger.php',
	'BBGATEKEEPER_CHECK_INI'		=> '.user.ini',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_ALL_OK'	=> 'OK',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_BAD'	=> 'File mancante',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_NO_RIGHT_PERMISSIONS'	=> 'Il file ha permessi chmod errati',
	'BBGATEKEEPER_INI_PREPEND_INCORRECT'	=> 'Il file .user.ini esiste ma non contiene il percorso previsto. Dai un\'occhiata.',

	'BBGATEKEEPER_PERMISSIONS_WARNING'	=> 'Il tuo file di configurazione (config.php) è attualmente scrivibile da chiunque (world-writable). Ti consigliamo vivamente di cambiare i permessi a 640 o almeno a 644 (per esempio: chmod 640 config.php).',

	'BBGATEKEEPER_IP_BINDING'		=> 'Binding IP (tolleranza cambio rete/mobile)',
	'BBGATEKEEPER_IP_BINDING_EXPLAIN'	=> 'Spostarsi all\'interno della stessa città o cambiare fornitore di rete può modificare il tuo indirizzo IP.<br />Questa opzione previene possibili cambi di IP per evitare di dover affrontare la challenge di hCaptcha troppe volte.<br /><em>Alta</em> - IP esattamente identico: devi avere un fornitore con IP statico e non spostarti all\'interno della tua città.<br /><em>Media (predefinito)</em> - Puoi spostarti all\'interno della città e avere un fornitore con IP dinamico a casa.<br /><em>Media MULTI (preferito)</em> - Come al livello Medio ma memorizza fino a 5 indirizzi IP preferiti. Utile per casa + lavoro + luogo preferito ad esempio.<br /><em>Bassa</em> - Nessun controllo IP (riduce la protezione contro il furto/riutilizzo dei cookie)',
	'BBGATEKEEPER_IP_LEVEL_1'		=> 'Alta - Tolleranza di strada',
	'BBGATEKEEPER_IP_LEVEL_2'		=> 'Media (predefinito) - Tolleranza di città',
	'BBGATEKEEPER_IP_LEVEL_3'		=> 'Media MULTI (preferito) - Tolleranza di città e gestione fino a 5 luoghi multipli',
	'BBGATEKEEPER_IP_LEVEL_4'		=> 'Bassa — Nessun binding IP',

	'BBGATEKEEPER_SAVE_CHANGES'		=> 'Salva impostazioni',
	'BBGATEKEEPER_RESET'			=> 'Elimina i file generati e pulisci il file .user.ini',
	'BBGATEKEEPER_RESET_EXPLAIN'		=> 'Se vuoi eliminare i file e il file .user.ini, usa il relativo pulsante.<br />Rimuove auto_prepend_file da .user.ini ed elimina i file PHP generati sotto store/gatekeeper.<br />Le impostazioni salvate vengono mantenute.<br />',
	'BBGATEKEEPER_RESET_EXPLAIN_WARNING'	=> 'Questo comando potrebbe fallire a causa dei permessi di scrittura/accesso. Se fallisce, devi modificare manualmente o eliminare/ricaricare i file.',
	'BBGATEKEEPER_RESET_SUCCESS'		=> 'Reset del deployment eseguito con successo.',
	'BBGATEKEEPER_RESET_FAILED'		=> 'Reset fallito - controlla i permessi dei file su .user.ini e store/.',

	'BBGATEKEEPER_GENERATE'			=> 'Genera',
	'BBGATEKEEPER_HCAP_SIGN_SECRET_EXPLAIN'	=> 'Questa è una chiave di verifica dell\'estensione autogenerata per il cookie.<br />Visibile qui per scopi di backup/ripristino. Mantieni questa chiave riservata agli amministratori fidati.<br />Cambiarla (manualmente o tramite Genera) invalida ogni cookie hCaptcha già rilasciato ai visitatori.<br />Rigenerala se sospetti che non sia più segreta.',
	'BBGATEKEEPER_SIGN_SECRET_GENERATED'	=> 'È stato generato e salvato un nuovo segreto di firma.',

	'BBGATEKEEPER_GOTO_SETTINGS'		=> 'Vai alle impostazioni di Bad Bot Gatekeeper',
	'BBGATEKEEPER_TOTAL_LINES'		=> 'Totale voci di log: %s',

	'BBGATEKEEPER_FUNCTION_DONE'		=> '🗹 Fatto',
	'BBGATEKEEPER_FUNCTION_ERROR'		=> '✗ Errore. Impossibile eseguire questa operazione.',
	'BBGATEKEEPER_CONFIRM_REMOVE_INI_LINE'	=> 'Eliminazione della riga "auto_prepend_file" dal file .user.ini nella cartella root del sito',
	'BBGATEKEEPER_CONFIRM_REMOVE_STORE_FILES'	=> 'Rimozione dei file config.php e logger.php dalla cartella store/bbgatekeeper',
	'BBGATEKEEPER_CONFIG_FILE_GENERATING'	=> 'Creazione del file <strong>bbgatekeeper_config.php</strong> nella cartella store/bbgatekeeper',
	'BBGATEKEEPER_LOGGER_AND_INI_DEPLOY'	=> 'Creazione del file <strong>bbgatekeeper_logger.php</strong> nella cartella store/bbgatekeeper e del file <strong>.user.ini</strong> nella cartella root del sito',

	// >= 1.1
	'BBGATEKEEPER_WHOIS' => 'IP WHOIS',
	'BBGATEKEEPER_SAVE_MODAL_TITLE'   => 'Stai salvando le impostazioni',
	'BBGATEKEEPER_SAVE_MODAL_TEXT'    => 'Questa azione salva le impostazioni ma non genera il file di configurazione. Per applicare le modifiche definitivamente devi cliccare sull\'altro pulsante:',
	'BBGATEKEEPER_SAVE_MODAL_CONFIRM' => 'Salva comunque',
	// >= 1.2
	'ACP_BBGATEKEEPER_HITS_AND_BANS'	=> 'Hits &amp; Ban',

	'BBGATEKEEPER_HITSBANS_EXPLAIN'	=> 'Elenco degli indirizzi IP con hCaptcha fallito (HIT, non ancora bannati) e di quelli attualmente bannati (BAN). Puoi rimuovere singolarmente una voce o svuotare l\'intera categoria.',

	'BBGATEKEEPER_BANS_TITLE'		=> 'IP bannati',
	'BBGATEKEEPER_HITS_TITLE'		=> 'IP con tentativi falliti (in attesa di ban)',

	'BBGATEKEEPER_NO_BANS'			=> 'Nessun IP attualmente bannato.',
	'BBGATEKEEPER_NO_HITS'			=> 'Nessun tentativo fallito registrato.',

	'BBGATEKEEPER_COL_IP'			=> 'Indirizzo IP',
	'BBGATEKEEPER_COL_BAN_TIME'		=> 'Data ban',
	'BBGATEKEEPER_COL_REMAINING'		=> 'Tempo rimanente',
	'BBGATEKEEPER_COL_HITS'		=> 'Tentativi',
	'BBGATEKEEPER_COL_FIRST_HIT'		=> 'Primo tentativo',
	'BBGATEKEEPER_COL_ELAPSED_FIRST_HIT'		=> 'Tempo trascorso',
	'BBGATEKEEPER_COL_ACTIONS'		=> 'Azioni',

	'BBGATEKEEPER_IP_UNKNOWN'		=> 'IP sconosciuto (file precedente al tracciamento IP)',

	'BBGATEKEEPER_DELETE_ALL_BANS'		=> 'Elimina tutti i ban',
	'BBGATEKEEPER_DELETE_ALL_HITS'		=> 'Elimina tutti gli hit',

	'BBGATEKEEPER_CONFIRM_DELETE_ONE'	=> 'Confermi la rimozione di questa voce?',
	'BBGATEKEEPER_CONFIRM_DELETE_ALL_BANS'	=> 'Confermi di voler eliminare TUTTI i ban attivi? Gli IP bannati torneranno immediatamente a poter accedere al sito.',
	'BBGATEKEEPER_CONFIRM_DELETE_ALL_HITS'	=> 'Confermi di voler eliminare TUTTI i record di hit? I relativi contatori ripartiranno da zero.',

	'BBGATEKEEPER_HITSBANS_UPDATED'	=> 'Operazione completata.',

	'BBGATEKEEPER_HITSBANS_DAYS' =>	'giorni',
	'BBGATEKEEPER_HITSBANS_HOURS' => 'ore',
	'BBGATEKEEPER_HITSBANS_MINUTES' => 'minuti',

	'BBGATEKEEPER_AUTOCLEAN_TITLE'		=> 'Pulizia automatica',
	'BBGATEKEEPER_AUTOCLEAN_EXPLAIN'	=> 'Se abilitata, un cron di phpBB rimuove periodicamente i file .ban/.hit scaduti (non più attivi), senza bisogno di intervento manuale.',
	'BBGATEKEEPER_AUTOCLEAN_ENABLE'	=> 'Abilita pulizia automatica',
	'BBGATEKEEPER_AUTOCLEAN_INTERVAL'	=> 'Intervallo di esecuzione',
	'BBGATEKEEPER_AUTOCLEAN_SAVED'		=> 'Impostazioni di pulizia automatica salvate.',

	'BBGATEKEEPER_LOGGING'			=> 'Log di accesso',
	'BBGATEKEEPER_ENABLE_ACCESS_LOG'	=> 'Registra access log',
	'BBGATEKEEPER_ENABLE_ACCESS_LOG_EXPLAIN' => 'Se disattivato, il logger auto_prepend non scrive più su store/logs/access.log ad ogni richiesta (risparmia una operazione di I/O per request). Non influisce sui file .ban/.hit, che restano sempre attivi.',

	'BBGATEKEEPER_ACTIVE'			=> 'Attivo',
	'BBGATEKEEPER_EXPIRED'			=> 'Scaduto',

	'BBGATEKEEPER_CLEANUP_EXPIRED_BANS'	=> 'Pulisci ban scaduti',
	'BBGATEKEEPER_CLEANUP_EXPIRED_HITS'	=> 'Pulisci hit scaduti',

	'BBGATEKEEPER_CONFIRM_CLEANUP_BANS'	=> 'Confermi la pulizia dei soli ban scaduti (i ban ancora attivi non verranno toccati)?',
	'BBGATEKEEPER_CONFIRM_CLEANUP_HITS'	=> 'Confermi la pulizia dei soli hit scaduti (gli hit ancora attivi non verranno toccati)?',

	'BBGATEKEEPER_PRECHECK_TITLE'		=> 'Precheck statico (stadio 0)',

	'BBGATEKEEPER_PRECHECK_EXPLAIN'	=> 'Aggiunge un controllo preliminare gestito direttamente da Apache, prima ancora che la richiesta arrivi a PHP-FPM: chi non ha un cookie di clearance valido viene reindirizzato a una pagina statica che imposta il cookie via JavaScript e poi ricarica la pagina originale. Filtra i bot senza JavaScript a costo zero, senza consumare processi PHP-FPM. Si affianca al controllo hCaptcha esistente, non lo sostituisce.<br />⚠️ questo pre-check impedisce ai motori di ricerca di passare (non rispecchia le regole salvate in questa pagina). Utile negli attacchi massivi come ulteriore difesa dei BOT più semplici, ma causa problemi SEO. Disattivalo se non necessario.',

	'BBGATEKEEPER_PRECHECK_WARNING_TITLE'	=> 'Attenzione: modifica il file .htaccess',

	'BBGATEKEEPER_PRECHECK_WARNING_EXPLAIN' => 'Prima di attivarlo per la prima volta, il tuo .htaccess attuale viene rinominato in .htaccess.bbgatekeeper-original nella document root e non viene più toccato. Se dopo l\'attivazione il sito diventasse irraggiungibile, puoi ripristinare tutto via FTP: cancella .htaccess e rinomina .htaccess.bbgatekeeper-original in .htaccess.',

	'BBGATEKEEPER_PRECHECK_ENABLE'		=> 'Abilita precheck statico.',

	'BBGATEKEEPER_PRECHECK_STATUS'		=> 'Stato attuale',
	'BBGATEKEEPER_PRECHECK_STATUS_ACTIVE'	=> 'Attivo (blocco presente in .htaccess)',
	'BBGATEKEEPER_PRECHECK_STATUS_NOT_ACTIVE' => 'Non attivo',

	'BBGATEKEEPER_PRECHECK_ERROR'		=> 'Impossibile scrivere sul filesystem (file html o .htaccess). Nessuna modifica è stata salvata; verifica i permessi di scrittura sulla document root.',
	// >= 1.2.1
	'BBGATEKEEPER_TRUSTED_PROXY' => 'Proxy fidato',
	'BBGATEKEEPER_TRUSTED_PROXY_ENABLE' => 'Abilita: usa X_FORWARDED_FOR solo da proxy fidato',
	'BBGATEKEEPER_TRUSTED_PROXY_ENABLE_EXPLAIN' => 'Abilitalo solo se il forum è dietro Nginx, Cloudflare o un reverse proxy. Se disattivato viene usato sempre <samp class="error">X_FORWARDED_FOR</samp>.',
	'BBGATEKEEPER_TRUSTED_PROXY_REMOTE_ADDR' => 'REMOTE_ADDR del proxy fidato',
	'BBGATEKEEPER_TRUSTED_PROXY_REMOTE_ADDR_EXPLAIN' => 'Inserisci il valore <samp class="error">REMOTE_ADDR</samp> visto dal pulsante utilizzato sopra nel riquadro di spiegazione.',
	'BBGATEKEEPER_CURRENT_REQUEST_PRE_EXPLAIN' => '<samp class="error">REMOTE_ADDR</samp> e <samp class="error"><samp class="error">X_FORWARDED_FOR</samp></samp> vengono sempre forniti entrambi e letti insieme, ma hanno un\'affidabilità molto diversa.<br />• <samp class="error">REMOTE_ADDR</samp> è l\'indirizzo IP che il server vede realmente collegarsi: nessun visitatore può falsificarlo.<br />• <samp class="error">X_FORWARDED_FOR</samp> invece è un semplice header HTTP che chiunque può scrivere a piacere, quindi di per sé non è affidabile.',
	'BBGATEKEEPER_CURRENT_REQUEST_EXPLAIN' => 'Questo controllo funziona così:<br />- Se <samp class="error">REMOTE_ADDR</samp> corrisponde esattamente al proxy fidato configurato qui sotto (es. il proxy interno del vostro hosting), la richiesta è passata davvero da lì, quindi <samp class="error">X_FORWARDED_FOR</samp> <strong style="color:green;">è sicuro</strong>.<br />- Se <samp class="error">REMOTE_ADDR</samp> non corrisponde al proxy fidato, la richiesta non è passata da lì: <samp class="error">X_FORWARDED_FOR</samp> potrebbe essere stato scritto liberamente dal client, quindi <strong style="color:red;">non è sicuro</strong>.',
	'BBGATEKEEPER_IP_PROBE_LINK' => 'Visualizza il tuo REMOTE_ADDR ed i tuoi indirizzi',
]);
