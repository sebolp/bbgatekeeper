# Bad Bot Gatekeeper

## What is for
🛡️ Bad Bot Gatekeeper v1.0 — anti-bot protection

If you don't want to, or cannot use CloudFlare to prevent BOTS - a phpBB extension built in-house to filter unwanted automated traffic (scraper bots, malicious crawlers, DDoS attacks) that slow down your forum surfing!
Adds a hCaptcha challenge while opening every webpage on your website!

hCaptcha REQUIRED. (free registration is ok)
You will need "site key" and "secret key"

HOW IT WORKS
This ext generate 3 files:
ext/sebo/bbgatekeeper/store/bbgatekeeper folder
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

## Installation

Copy the extension to phpBB/ext/sebo/bbgatekeeper

Go to "ACP" > "Customise" > "Extensions" and enable the "Bad Bot Gatekeeper" extension.

## License

[GNU General Public License v2](license.txt)
