<?php
/**
 * This sniff makes sure all function/method names are lowercased.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Jakub Senko <jakubsenko@gmail.com>
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

namespace PhpbbCodingStandard\Sniffs\NamingConventions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class LowercaseUnderscoredFunctionsSniff implements Sniff
{

	public $exceptions = [
		'getSubscribedEvents',
	];

    /**
     * Only functions
     *
     * @return array(int)
     */
    public function register()
    {
        return [T_FUNCTION];

    }//end register()


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being checked.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
		$functionName = $phpcsFile->getDeclarationName($stackPtr);
		if (!in_array($functionName, $this->exceptions) && strtolower($functionName) !== $functionName) {
            $error   = 'Use of the uppercase characters in function name is forbidden; use snake_case format for %s.';
            $data    = [$functionName];
			$phpcsFile->addError($error, $stackPtr, 'NotAllowed', $data);
		}

    }//end process()


}//end class

?>
