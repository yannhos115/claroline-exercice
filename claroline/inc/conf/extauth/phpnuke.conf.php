<?php // $Id$

/**
 * PHPNuke authentication driver
 *
 * @version     1.9 $Revision$
 * @copyright   2001-2008 Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLAUTH
 */

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

// do not change the following section
$driverConfig['driver'] = array(
    'enabled' => true,
    'class' => 'PearAuthDriver',
    'authSourceType' => 'DB',
    'authSourceName' => 'phpnuke',
    'userRegistrationAllowed' => true,
    'userUpdateAllowed' => true
);

// you can change the driver from this point

$driverConfig['extAuthOptionList'] = array(
    // PUT HERE THE CORRECT DSN FOR YOUR DB SYSTEM
    'dsn'         => 'mysql://dbuser:dbpassword@domain/phpnuke',
    'table'       => 'nuke_users', // warning ! table prefix can change from one system to another 
    'usernamecol' => 'username',
    'passwordcol' => 'user_password',
    'db_fields'   => array('name', 'user_email'),
    'cryptType'   => 'md5'
);

$driverConfig['extAuthAttribNameList'] = array(
    'lastname'     => 'name',
    'email'        => 'user_email'
);

$driverConfig['extAuthAttribTreatmentList'] = array (
    'status' => 5
);

$driverConfig['extAuthAttribToIgnore'] = array(
    'isCourseCreator'
);
?>