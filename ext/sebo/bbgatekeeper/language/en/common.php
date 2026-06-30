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
	'ACP_BBGATEKEEPER_SETTINGS_PRE_EXPLAIN' => 'Welcome to BadBotGatekeeper!<br />This extension requires registration with the hCaptcha system. If you haven\'t already done so, register and obtain the necessary keys at this link: %s',
	'ACP_BBGATEKEEPER_SETTINGS_EXPLAIN' => 'From this page, you can configure the hCaptcha challenge, the User-Agent blacklist, and the search engine whitelist that can visit your forum.<br />After modifying these settings, use "Save and Generate/Deploy" to write the runtime script.',
	'ACP_BBGATEKEEPER_SETTINGS_LANGUAGE_FALLBACK' => 'WARNING: Language files output',
	'ACP_BBGATEKEEPER_SETTINGS_LANGUAGE_FALLBACK_EXPLAIN'   => 'Generated files will be in user language only IF there is a complete translation of the extension. If there isn\'t, files will be generated in English.',

	'BBGATEKEEPER_FUNDAMENTAL_STEPS_EXPLAIN' => 'To work, this extension needs to generate three essential files:<br />➀ config.php ➁ logger.php ➂ .user.ini<br />Here you can check if those files are correctly generated / located / has right permission in chmod.',

	'BBGATEKEEPER_HCAPTCHA'         => 'hCaptcha',
	'BBGATEKEEPER_HCAP_SITE_KEY'        => 'Site public key',
	'BBGATEKEEPER_HCAP_SITE_KEY_EXPLAIN'        => 'You can find it in hCapctha website, creating a new one or using an existing one.<br />You find it surfing to "Site" tab.',
	'BBGATEKEEPER_HCAP_SITE_SECRET' => 'Site secret key',
	'BBGATEKEEPER_HCAP_SITE_SECRET_EXPLAIN' => 'You can find it in hCapctha website.<br />You can find it usually in General Account settings -> Secret',
	'BBGATEKEEPER_HCAP_SIGN_SECRET' => 'Cookie signing secret',

	'BBGATEKEEPER_COOKIE'           => 'Verification cookie',
	'BBGATEKEEPER_COOKIE_NAME'      => 'Cookie name',
	'BBGATEKEEPER_COOKIE_NAME_EXPLAIN'      => 'You can change it as you prefer. Remember that every time you change it, invalidates every cookie already issued to visitors.',
	'BBGATEKEEPER_COOKIE_TTL'       => 'Cookie lifetime',
	'BBGATEKEEPER_COOKIE_TTL_EXPLAIN'       => 'How long your cookie allows access to the website before resetting.<br />Expressed in seconds. Default 86400 (24 hours). You can use the time you prefer.<br />Examples: 3600 (1 hour), 86400 (1 day), 604800 (1 week), 2629800 (1 month)',
	'BBGATEKEEPER_COOKIE_DOMAIN'        => 'Cookie domain',
	'BBGATEKEEPER_COOKIE_DOMAIN_EXPLAIN'        => 'It is the same of the board configuration.<br />Use <em>.example.com</em> to include all subdomains.<br />Use <em>.subdomain.example.com</em> to use only in one subdomain.',
	'BBGATEKEEPER_COOKIE_SAMESITE'      => 'SameSite policy',
	'BBGATEKEEPER_COOKIE_SAMESITE_EXPLAIN'      => 'Different cookie policy.<br /><em>LAX</em>: (default) sends cookies only during top-level navigation like clicking an external link.<br /><em>STRICT</em>: (more secure) sends cookies exclusively when you are browsing directly within the origin site.',

	'BBGATEKEEPER_UA_BLOCKLIST'     => 'User-Agent blocklist',
	'BBGATEKEEPER_UA_PATTERNS'      => 'Blocked patterns (one per line)',
	'BBGATEKEEPER_UA_PATTERNS_EXPLAIN'  => 'Case-insensitive, partial match against the request User-Agent header.',

	'BBGATEKEEPER_BOT_WHITELIST'        => 'Search engine whitelist',
	'BBGATEKEEPER_BOT_DOMAINS'      => 'Allowed reverse DNS domains (one per line)',
	'BBGATEKEEPER_BOT_DOMAINS_EXPLAIN'      => 'Leave it as is if you don\'t know how to verify.<br />Reverse DNS (rDNS) associates IP addresses with domains. It verifies official search engine crawlers, improves email security, and prevents spam. It\'s an authentication and reputation system.<br />You can verify it with online validators.',

	'BBGATEKEEPER_MODE'         => 'Operating mode',
	'BBGATEKEEPER_DRY_RUN'          => 'Dry run',
	'BBGATEKEEPER_DRY_RUN_EXPLAIN'      => 'Log what would be blocked without actually blocking anything.<br />To block needs to be disabled.',

	'BBGATEKEEPER_DEPLOY_STATUS'        => 'Deployment status',
	'BBGATEKEEPER_DEPLOY_OK'        => 'Deployed and active',
	'BBGATEKEEPER_DEPLOY_NOT_OK'        => 'Not deployed / not verified',
	'BBGATEKEEPER_LAST_DEPLOY'      => 'Last generated',
	'BBGATEKEEPER_GENERATE_DEPLOY'      => 'Save settings &amp; generate files',

	'BBGATEKEEPER_SETTINGS_SAVED'       => 'Settings saved.',
	'BBGATEKEEPER_DEPLOY_SUCCESS'       => 'Settings saved, runtime script generated successfully. Check store/bbagatekeeper folder in ext/sebo/bbgatekeeper path to confirm and .user.ini file in root website folder.',
	'BBGATEKEEPER_DEPLOY_FAILED'        => 'Settings saved, but generating or deploying the runtime script failed. Check write permissions on the store/ directory.',
	'BBGATEKEEPER_DEPLOY_WARNING'       => 'Bad Bot Gatekeeper has not been activated yet (or the last deployment failed). Visit its ACP settings page.',

	'BBGATEKEEPER_FILTER'           => 'Filter',
	'BBGATEKEEPER_FILTER_ALL'       => 'All entries',
	'BBGATEKEEPER_FILTER_PASSED'        => 'Passed only',
	'BBGATEKEEPER_FILTER_BLOCKED'       => 'Blocked only',
	'BBGATEKEEPER_FILTER_CHALLENGE' => 'hCaptcha challenge only',

	'BBGATEKEEPER_DATETIME'     => 'Date/time',
	'BBGATEKEEPER_IP'           => 'IP',
	'BBGATEKEEPER_URI'          => 'URI',
	'BBGATEKEEPER_STATUS'           => 'Status',
	'BBGATEKEEPER_USER_AGENT'       => 'User-Agent',
	'BBGATEKEEPER_LOG_EMPTY'        => 'No log entries to show.',
	'BBGATEKEEPER_CLEAR_LOG'        => 'Clear log',
	'BBGATEKEEPER_LOGS_EXPLAIN'     => 'Here you can read the first <strong>500</strong> lines of logs. To read the full file you have to check the <code class="inline">access.log</code> file in <code class="inline">ext/sebo/bbgatekeeper/store/logs</code> folder.',

	'BBGATEKEEPER_FUNDAMENTAL_STEPS'    => 'Minimal required deployment steps',
	'BBGATEKEEPER_CHECK_CONFIG'     => 'config.php',
	'BBGATEKEEPER_CHECK_LOGGER'     => 'logger.php',
	'BBGATEKEEPER_CHECK_INI'            => '.user.ini',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_ALL_OK' => 'OK',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_BAD' => 'File is missing',
	'BBGATEKEEPER_FUNDAMENTAL_STEPS_NO_RIGHT_PERMISSIONS' => 'File has incorrect chmod permissions',
	'BBGATEKEEPER_INI_PREPEND_INCORRECT' => '.user.ini file exists but it does not contain the expected path. Take a look.',

	'BBGATEKEEPER_PERMISSIONS_WARNING'  => 'Your config file (config.php) is currently world-writable. We strongly encourage you to change the permissions to 640 or at least to 644 (for example: chmod 640 config.php).',

	'BBGATEKEEPER_IP_BINDING'       => 'IP binding (mobile / network change tolerance)',
	'BBGATEKEEPER_IP_BINDING_EXPLAIN'       => 'Moving within the same city or network provider may change your IP address.<br />This option looks forward possible IP changes to avoid facing the hCaptcha challenge too many times.<br /><em>High</em> - Exactly the same IP: You must have a provider with a static IP and not move within your city.<br /><em>Medium (default)</em> - You can move within the city and have a provider with a dynamic IP at home.<br /><em>Low</em> - No IP checks (reduces protection against cookie theft/reuse)',
	'BBGATEKEEPER_IP_LEVEL_1'       => 'High - Street tolerance',
	'BBGATEKEEPER_IP_LEVEL_2'       => 'Medium (default) - City tolerance',
	'BBGATEKEEPER_IP_LEVEL_3'       => 'Low — no IP binding at all',

	'BBGATEKEEPER_SAVE_CHANGES'     => 'Save settings',
	'BBGATEKEEPER_RESET'            => 'Delete generated files and purge .user.ini file',
	'BBGATEKEEPER_RESET_EXPLAIN'        => 'If you want to purge files and .user.ini file use the related button.<br />Removes auto_prepend_file from .user.ini and deletes the generated PHP files under store/gatekeeper.<br />Saved settings are kept.<br />',
	'BBGATEKEEPER_RESET_EXPLAIN_WARNING'    => 'This command may fail due to write/access permissions. If it fails you have to manually edit or delete/re-upload the files.',
	'BBGATEKEEPER_RESET_SUCCESS'        => 'Deployment reset successfully.',
	'BBGATEKEEPER_RESET_FAILED'     => 'Reset failed - check file permissions on .user.ini and store/.',

	'BBGATEKEEPER_GENERATE'             => 'Generate',
	'BBGATEKEEPER_HCAP_SIGN_SECRET_EXPLAIN' => 'This is an auto-generated extension verification key for the cookie.<br />Visible here for backup/recovery purposes. Keep this key restricted to trusted admins.<br />Changing it (manually or via Generate) invalidates every hCaptcha cookie already issued to visitors.<br />Re-generate if you suspect that is not secret anymore.',
	'BBGATEKEEPER_SIGN_SECRET_GENERATED'    => 'A new signing secret has been generated and saved.',

	'BBGATEKEEPER_GOTO_SETTINGS'    => 'Go to Bad Bot Gatekeeper settings',
	'BBGATEKEEPER_TOTAL_LINES' => 'Total log entries: %s',

	'BBGATEKEEPER_FUNCTION_DONE'    => '🗹 Done',
	'BBGATEKEEPER_FUNCTION_ERROR'   => '✗ Error. Cannot perform this operation.',
	'BBGATEKEEPER_CONFIRM_REMOVE_INI_LINE' => 'Deleting "auto_prepend_file" line from .user.ini file in website root folder',
	'BBGATEKEEPER_CONFIRM_REMOVE_STORE_FILES' => 'Removing config.php and logger.php files from store/bbgatekeeper folder',
	'BBGATEKEEPER_CONFIG_FILE_GENERATING' => 'Creating <strong>bbgatekeeper_config.php</strong> file into store/bbgatekeeper folder',
	'BBGATEKEEPER_LOGGER_AND_INI_DEPLOY' => 'Creating <strong>bbgatekeeper_logger.php</strong> file into store/bbgatekeeper folder and <strong>.user.ini</strong> file into root website folder',
]);
