<?php
/**
 * This sniff makes sure all class names are lowercased.
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

class LowercaseUnderscoredClassesSniff implements Sniff
{


    /**
     * Only classes
     *
     * @return array(int)
     */
    public function register()
    {
        return [T_CLASS];

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
		$className = $phpcsFile->getDeclarationName($stackPtr);
		if (strtolower($className) !== $className) {
            $error   = 'Use of the uppercase characters in class name is forbidden; use snake_case format for %s.';
            $data    = [$className];
			$phpcsFile->addError($error, $stackPtr, 'NotAllowed', $data);
		}

    }//end process()


}//end class

?>
