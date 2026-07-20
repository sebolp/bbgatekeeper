<?php
/**
 * sebo-bbgatekeeper-ip-probe.php — static, standalone diagnostic file.
 *
 * Deliberately does NOT include phpBB's common.php / startup.php: the
 * whole point is to show $_SERVER exactly as the web server passes it,
 * before phpBB (or any community patch to includes/startup.php that
 * rewrites REMOTE_ADDR from X-Forwarded-For) can touch it.
 *
 * Publicly reachable by design, same as any "what's my IP" page: it only
 * ever reveals the requester's own connection info, nothing site-specific
 * or sensitive. Bot-filtering (UA blocklist, hCaptcha challenge) still
 * applies normally via the auto_prepend logger, since that runs for every
 * PHP file on the server regardless of location.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

header('Content-Type: text/plain; charset=UTF-8');

echo '> REMOTE_ADDR: ' . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";
echo '> HTTP_X_FORWARDED_FOR: ' . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '-') . "\n";
echo '> HTTP_X_REAL_IP: ' . ($_SERVER['HTTP_X_REAL_IP'] ?? '-') . "\n";
echo '> SERVER_ADDR: ' . ($_SERVER['SERVER_ADDR'] ?? '-') . "\n";
