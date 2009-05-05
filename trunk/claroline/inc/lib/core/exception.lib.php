<?php // $Id$

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Exception library
 *
 * @version     1.9 $Revision$
 * @copyright   2001-2008 Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

// PHP Error to Exception converter

/**
 * Class to convert a PHP error to an Exception
 * 
 * taken from php.net online PHP manual
 */
class PHP_Error_Exception extends Exception
{
   public function __construct ( $code, $message, $file, $line )
   {
       parent::__construct($message, $code);
       $this->file = $file;
       $this->line = $line;
   }
}

/**
 * Error handler to convert PHP errors to Exceptions and so have
 * only one error handling system to handle
 * 
 * taken from php.net online PHP manual
 */
function exception_error_handler( $code, $message, $file, $line )
{
    throw new PHP_Error_Exception( $code, $message, $file, $line );
}

// Standard Exceptions

//    class FileNotFoundException extends Exception
//    {
//        public function __construct( $filePath )
//        {
//            parent::__construct( "File {$filePath} not found" );
//        }
//    }
