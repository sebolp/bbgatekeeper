<?php
/**
 *
 * Bad Bot Gatekeeper extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 sebo, fiatpandaclub.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sebo\bbgatekeeper\core;

/**
* Deploys the "static waiting room" precheck stage: a plain HTML file
* (sebo-bbgatekeeper.html) served directly by Apache, plus a .htaccess
* block that redirects any request without the clearance cookie to it -
* intercepted before PHP-FPM, so it costs nothing on Tophost.
*
* Safety model for .htaccess (higher blast radius than .user.ini: a bad
* write here can 500 the whole site, not just fail-open):
*   - The FIRST time this deploys, the .htaccess found on disk is RENAMED
*     (not copied) to a fixed backup file (.htaccess.bbgatekeeper-original).
*     That backup is treated as sacred: if it already exists, it is never
*     touched or overwritten again by this class.
*   - Every subsequent deploy rebuilds .htaccess from THAT pristine backup
*     plus a freshly generated marker block - never from the live file,
*     which may already contain our block from a previous deploy.
*   - The backup is never deleted automatically, not even by remove() or a
*     full extension reset. If the live .htaccess ever makes the site
*     unreachable, an admin with FTP/SSH access can simply delete
*     .htaccess and rename .htaccess.bbgatekeeper-original back to
*     .htaccess to restore the exact pre-gatekeeper state, no phpBB or
*     database access required.
*/
class precheck_deployer
{
	public const BLOCK_START = '# BEGIN sebo-bbgatekeeper precheck';
	public const BLOCK_END   = '# END sebo-bbgatekeeper precheck';

	public const HTML_MARKER = '<!-- sebo-bbgatekeeper: generated file, safe to regenerate -->';

	public const HTML_FILENAME = 'sebo-bbgatekeeper.html';
	public const BACKUP_SUFFIX = '.bbgatekeeper-original';

	/** @var \phpbb\request\request_interface */
	protected $request;

	/**
	* @param \phpbb\request\request $request
	*/
	public function __construct(\phpbb\request\request $request)
	{
		$this->request = $request;
	}

	/**
	* Full deploy: static html + .htaccess block. Both must succeed for
	* the precheck to be considered active; if the html write fails we
	* don't even attempt the riskier .htaccess write.
	*
	* @return bool
	*/
	public function deploy(): bool
	{
		$ok_html = $this->deploy_html();

		return $ok_html && $this->deploy_htaccess();
	}

	/**
	* Removes the .htaccess block (leaving the rest of the live file
	* untouched) and deletes the static html file. Never touches
	* .htaccess.bbgatekeeper-original - that stays as a permanent,
	* manually-restorable safety net.
	*
	* @return bool
	*/
	public function remove(): bool
	{
		$ok_htaccess = $this->remove_htaccess_block();
		$ok_html = $this->remove_html();

		return $ok_htaccess && $ok_html;
	}

	/**
	* @return bool true if the marker block is currently present in the live .htaccess
	*/
	public function is_deployed(): bool
	{
		$path = $this->get_htaccess_path();
		if ($path === null || !is_readable($path))
		{
			return false;
		}

		$content = @file_get_contents($path);

		return $content !== false && strpos($content, self::BLOCK_START) !== false;
	}

	// ====================== HTML ======================

	/**
	* @return bool
	*/
	protected function deploy_html(): bool
	{
		$doc_root = $this->request->server('DOCUMENT_ROOT');
		if (empty($doc_root))
		{
			return false;
		}

		$template_path = __DIR__ . '/templates/precheck.html.template';
		if (!is_readable($template_path))
		{
			return false;
		}

		$content = file_get_contents($template_path);
		if ($content === false)
		{
			return false;
		}

		$target = rtrim($doc_root, '/\\') . '/' . self::HTML_FILENAME;

		// Caso limite: se esiste già un file con questo identico nome e NON
		// è uno generato da noi (niente marker in testa), lo mettiamo da
		// parte invece di sovrascriverlo alla cieca - non dovrebbe mai
		// succedere, ma il nome potrebbe in teoria collidere con un file
		// reale caricato dall'utente.
		if (file_exists($target))
		{
			$existing = @file_get_contents($target);
			if ($existing !== false && strpos($existing, self::HTML_MARKER) === false)
			{
				$backup = $target . self::BACKUP_SUFFIX;
				if (!file_exists($backup))
				{
					@rename($target, $backup);
				}
			}
		}

		$lang_map = [
			'BB_LOGGER_LANG_SECURITY_CHECK'             => 'BBGATEKEEPER_TEMPLATE_LOGGER_SECURITY_CHECK',
			'BB_LOGGER_LANG_SECURITY_CHECK_EXPLAIN'     => 'BBGATEKEEPER_TEMPLATE_LOGGER_SECURITY_CHECK_EXPLAIN',
			'BB_LOGGER_LANG_SECURITY_CHECK_COOKIE'      => 'BBGATEKEEPER_TEMPLATE_LOGGER_COOKIE',
			];

		$rendered = self::HTML_MARKER . "\n" . $content;

		if (@file_put_contents($target, $rendered) === false)
		{
			return false;
		}

		@chmod($target, 0644);

		return true;
	}

	/**
	* @return bool
	*/
	protected function remove_html(): bool
	{
		$doc_root = $this->request->server('DOCUMENT_ROOT');
		if (empty($doc_root))
		{
			return false;
		}

		$target = rtrim($doc_root, '/\\') . '/' . self::HTML_FILENAME;

		if (!file_exists($target))
		{
			return true;
		}

		// Cancella solo se porta il nostro marker: mai un file "vero" che
		// dovesse trovarsi (per coincidenza) con lo stesso nome.
		$content = @file_get_contents($target);
		if ($content !== false && strpos($content, self::HTML_MARKER) === false)
		{
			return true;
		}

		return @unlink($target);
	}

	// ====================== HTACCESS ======================

	/**
	* @return string|null
	*/
	protected function get_htaccess_path(): ?string
	{
		$doc_root = $this->request->server('DOCUMENT_ROOT');
		if (empty($doc_root))
		{
			return null;
		}

		return rtrim($doc_root, '/\\') . '/.htaccess';
	}

	/**
	* @return bool
	*/
	protected function deploy_htaccess(): bool
	{
		$path = $this->get_htaccess_path();
		if ($path === null)
		{
			return false;
		}

		$backup_path = $path . self::BACKUP_SUFFIX;

		// Prima volta: quello che c'e' ora e' l'originale, lo congeliamo
		// rinominandolo. Se il backup esiste gia', non lo tocchiamo MAI
		// piu' - potrebbe gia' contenere il vero originale pre-gatekeeper.
		if (!file_exists($backup_path))
		{
			if (file_exists($path))
			{
				if (!@rename($path, $backup_path))
				{
					return false;
				}
			}
			else
			{
				// Nessun .htaccess preesistente: registriamo comunque un
				// backup vuoto, cosi' "nessun file" resta lo stato
				// originale coerente da ripristinare a mano in emergenza.
				if (@file_put_contents($backup_path, '') === false)
				{
					return false;
				}
			}
		}

		$pristine = @file_get_contents($backup_path);
		if ($pristine === false)
		{
			return false;
		}

		// Difesa extra: se il backup contenesse gia' il nostro blocco (es.
		// backup manuale fatto per errore dopo un deploy), lo rimuoviamo
		// prima di reinserirlo, per non duplicarlo.
		$pristine = $this->strip_block($pristine);

		$new_content = $this->inject_block($pristine);

		if (@file_put_contents($path, $new_content) === false)
		{
			return false;
		}

		clearstatcache(true, $path);
		$verify = @file_get_contents($path);

		if ($verify !== $new_content)
		{
			return false;
		}

		@chmod($path, 0644);

		return true;
	}

	/**
	* @return bool
	*/
	protected function remove_htaccess_block(): bool
	{
		$path = $this->get_htaccess_path();
		if ($path === null)
		{
			return true;
		}

		if (!file_exists($path))
		{
			return true;
		}

		$current = @file_get_contents($path);
		if ($current === false)
		{
			return false;
		}

		$stripped = $this->strip_block($current);

		if ($stripped === $current)
		{
			// No block here, nothing to do.
			return true;
		}

		if (@file_put_contents($path, $stripped) === false)
		{
			return false;
		}

		clearstatcache(true, $path);
		$verify = @file_get_contents($path);

		return $verify === $stripped;
	}

	/**
	* @param string $content
	* @return string content with our marker block (if any) removed
	*/
	protected function strip_block(string $content): string
	{
		$pattern = '/\R?' . preg_quote(self::BLOCK_START, '/') . '.*?' . preg_quote(self::BLOCK_END, '/') . '\R?/s';

		return preg_replace($pattern, '', $content);
	}

	/**
	* Inserts the gatekeeper block right after the first "RewriteEngine On"
	* line (case-insensitive), or - if the file has none - prepends both.
	*
	* @param string $content pristine .htaccess content (block-free)
	* @return string
	*/
	protected function inject_block(string $content): string
	{
		$block = $this->build_block();

		if (preg_match('/^[ \t]*RewriteEngine[ \t]+On[ \t]*$/im', $content, $matches, PREG_OFFSET_CAPTURE))
		{
			$line = $matches[0][0];
			$offset = $matches[0][1];
			$insert_at = $offset + strlen($line);

			return substr($content, 0, $insert_at) . "\n\n" . $block . "\n" . substr($content, $insert_at);
		}

		// No "RewriteEngine On" found in original file:
		// we add it before all.
		return "RewriteEngine On\n\n" . $block . "\n\n" . $content;
	}

	/**
	* @return string the marker-delimited RewriteRule block
	*/
	protected function build_block(): string
	{
		return self::BLOCK_START . "\n"
			. "# Do not block if it is for the requested file\n"
			. "RewriteCond %{REQUEST_URI} ^/sebo-bbgatekeeper\\.html$ [NC]\n"
			. "RewriteRule ^ - [L]\n"
			. "\n"
			. "# Do not affect static asset (css/js/immages)\n"
			. "RewriteCond %{REQUEST_URI} \\.(jpg|jpeg|png|gif|css|js|webp|woff|woff2)$ [NC]\n"
			. "RewriteRule ^ - [L]\n"
			. "\n"
			. "# Allow critical bot files (ads.txt and robots.txt) to bypass the gatekeeper\n"
			. "RewriteCond %{REQUEST_URI} ^/(ads|robots)\.txt$ [NC]\n"
			. "RewriteRule ^ - [L]\n"
			. "\n"
			. "# If clearence cookie is not there, redirect to html page\n"
			. "RewriteCond %{HTTP_COOKIE} !sebo-bbgatekeeper_clearance=granted [NC]\n"
			. "RewriteRule ^ /sebo-bbgatekeeper.html [L]\n"
			. self::BLOCK_END;
	}
}
