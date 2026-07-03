# Bad Bot Gatekeeper

## Installation

Copy the extension to phpBB/ext/sebo/bbgatekeeper

Go to "ACP" > "Customise" > "Extensions" and enable the "Bad Bot Gatekeeper" extension.

## How it works
This ext generate 3 files:
in ext/sebo/bbgatekeeper/store/bbgatekeeper folder
- bbgatekeeper_config.php (configuration file needed for bbgatekeeper_logger.php file)
- bbgatekeeper_logger.php file (the worker of this ext, that blocks, make the challenge, leave passing throug...)

in website root folder
- .user.ini file (or if exist, change it) that needs to call before every page the bbgatekeeper_logger.php file

The extension doesn't do the filtering itself. Instead, it generates a lightweight "sentinel" script (the two PHP files above)that loads automatically *before* anything else (via `auto_prepend_file` in the .user.ini file), so suspicious requests get blocked before phpBB or the entire website even boots up.
It makes a hCaptcha challenge while accessing, and store a verified cookie (with variable duration) to allow the website navigation (setting like accuracy and duration of cookie can be setted in ACP).
The extension itself only acts as the control panel: an ACP page to configure, generate, and monitor that script.

## License

[GNU General Public License v2](license.txt)
