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
