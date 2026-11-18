# Bad Bot Gatekeeper

## What is for
🛡️ Bad Bot Gatekeeper v1.0 — anti-bot protection

If you don't want to, or cannot use CloudFlare to prevent BOTS - a phpBB extension built in-house to filter unwanted automated traffic (scraper bots, malicious crawlers, DDoS attacks) that slow down your forum surfing!
Adds a hCaptcha challenge while opening every webpage on your website!

hCaptcha REQUIRED. (free registration is ok)
You will need "site key" and "secret key"

HOW IT WORKS
This ext generate 3 files:
store/sebo-bbgatekeeper/bbgatekeeper folder
- bbgatekeeper_config.php (configuration file needed for bbgatekeeper_logger.php file)
- bbgatekeeper_logger.php file (the worker of this ext, that blocks, make the challenge, leave passing throug...)

website root folder
- .user.ini file (or if exist, change it) that needs to call before every page the bbgatekeeper_logger.php file

The extension doesn't do the filtering itself. Instead, it generates a lightweight "sentinel" script (the two PHP files above)that loads automatically *before* anything else (via `auto_prepend_file` in the .user.ini file), so suspicious requests get blocked before phpBB or the entire website even boots up.
It makes a hCaptcha challenge while accessing, and store a verified cookie (with variable duration) to allow the website navigation (setting like accuracy and duration of cookie can be setted in ACP).
The extension itself only acts as the control panel: an ACP page to configure, generate, and monitor that script.

## Updates

v1.1
- added capability to use WHOIS function in logs.
- added a new level of IP check. Now level 2 has the MULTI function available (preferred): MULTI is different from single cause of capability to register 5 different IPs from the user in the same cookie. This is almost for the mobilephones that switch from mobile cells to different WiFi overwriting existing cookie and invalidating the level 2 check. (eg now you can use work location + home + preferred location etc in the same cookie!)
- added a clarifying message while clicking on "save settings" button in acp module

v1.2.1
- HIT&BAN module/system: when an ip give a wrong answer to hcapthca = 1 hit. IF the same IP give two wrong answer in one minute = 2 HIT = 1 BAN. 1 ban is equal to 30 minutes of error 429 from server (so that ip will not drain PHP resources from our server)
- You can whois, delete, view active, view remaining/elapsed time and purge all HIT&BANS or schedule a purge of those
- Added a safe check for X_FOWARDED_FOR (now check if REMOTE_ADDR is the same of your server to prevent spoofing of X_FOWARDED_FOR)
- added a static precheck (disabled by default). maybe unuseful...maybe i will remove in next revs (it's a static file that blocks only stupid bots with a js check, but it's extremely bad for SEO)
- You can decide if register logs or not

v1.2.2
- removed prestatic-check

v1.3.0
- fixed a stored XSS in the ACP Logs page (URI/User-Agent were not escaped)
- fixed a bypass of the static-asset exemption via query string (e.g. "?x=.css")
- added Autologin: runs the challenge automatically on page load and submits it without a manual click (hCaptcha requires an "invisible" site key on the hCaptcha dashboard; Turnstile works automatically)
- added Excluded paths: an admin-configurable list of internal paths that always skip the challenge
- the FCrDNS search-engine whitelist check now only runs when a challenge would otherwise be shown, instead of on every request
- the challenge page now shows the real forum name and description, resolved from phpBB's own board settings
- fixed the challenge page's cookie domain being written to phpBB's core "cookie_domain" instead of a namespaced key
- fixed a missing translation key that left a raw placeholder in the "challenge not completed" alert
- fixed the admin-only forum index warning (dead event name, missing template)
- the .user.ini auto_prepend_file line is now removed automatically when the extension is disabled or purged
- composer.json phpBB version constraint corrected
- misc: remaining Italian code comments translated to English; a migration now registers all config keys used by the ACP

v1.3.1
- fixed a critical bug where the common "delete ext/ folder, upload the new one" update procedure wiped the runtime store directory (bbgatekeeper_config.php, bbgatekeeper_logger.php, access.log, hits/bans), causing an immediate site-wide 500 even with the extension disabled
- the runtime store directory now lives at store/sebo-bbgatekeeper/ (phpBB's own store/ folder) instead of inside ext/sebo/bbgatekeeper/, so it's never touched by future extension updates
- a new migration moves any existing deployment to the new location automatically; a self-healing check also re-verifies the directory and its .htaccess on every "Deploy" in ACP

v1.3.2 (validation fixes)
- fixed a fatal error in the ACP dashboard (core.acp_main_notice carries no data): the deploy warning is now a template variable rendered by the acp_main_notice_after template event
- removed the static asset exemption (genuine static files never reach PHP) and the Lighthouse User-Agent shortcut: both let a client skip the challenge
- the excluded paths list is now matched against the executed script path (SCRIPT_NAME), so appending extra path info cannot skip the challenge
- the ACP now proves that the prepended logger really runs on the current request (it never does under mod_php) instead of only checking that the files exist
- REMOTE_ADDR is always used unless Trusted Proxy is enabled; X-Forwarded-For is no longer trusted by default
- only a captcha token that was submitted and rejected counts as a hit; a plain POST without cookie no longer does, and valid cookies are renewed once past half of their lifetime
- updating from 1.3.0 now rewrites the .user.ini auto_prepend_file line to the new store path (or removes it if the new logger is missing, so the board stays up)
- all functions, constants and variables of the generated runtime files are prefixed/scoped to avoid collisions with other applications under the same document root
- the access log switch is now really honoured by the runtime files
- an empty cookie signing secret is never stored or deployed
- fixed an empty confirmation dialog on the Reset button, missing/stale language strings, English titles in the Italian ACP module names
- Twig: legacy uppercase INCLUDE tags replaced, template loops accessed through loops.*
- composer.json phpBB constraint aligned with ext.php (>=3.3.0,<3.9.0)
- the extension no longer edits the website root .user.ini by default: the ACP shows the exact auto_prepend_file line to add by hand (manual mode). Editing it ("automatic mode") is optional and needs an explicit consent, recorded server side with date, time and administrator (also in the admin log). The "Delete generated files" button is available only in automatic mode.

## Requirements

- phpBB 3.3.x
- PHP running as CGI, FastCGI or PHP-FPM. PHP running as an Apache module (mod_php) ignores .user.ini, so the filter would never run: the ACP warns about it.

## Uninstalling

In automatic mode, disabling or purging the extension removes the auto_prepend_file line from .user.ini. In manual mode the line is yours and is never touched: the filter keeps running (the generated files stay in place) until you remove it by hand. In both modes the extension deliberately leaves the store/sebo-bbgatekeeper/ folder in place. It contains the generated config (captcha secret and cookie signing secret), the access log and the ban files: delete it manually (or use the ACP "Delete generated files" button before uninstalling) if you do not plan to reinstall.

## Installation

Copy the extension to phpBB/ext/sebo/bbgatekeeper

Go to "ACP" > "Customise" > "Extensions" and enable the "Bad Bot Gatekeeper" extension.

## License

[GNU General Public License v2](license.txt)
