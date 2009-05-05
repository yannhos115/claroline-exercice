<?php // $Id$

/**
 * aTutor authentication driver
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
    'authSourceName' => 'atutor',
    'userRegistrationAllowed' => true,
    'userUpdateAllowed' => true
);

// you can change the driver from this point

$driverConfig['extAuthOptionList'] = array(
    // PUT HERE THE CORRECT DSN FOR YOUR DB SYSTEM
    'dsn'         => 'mysql://dbuser:dbpassword@domain/atutor',
    'table'       => 'AT_members', // warning ! table prefix can change from one system to another 
    'usernamecol' => 'login',
    'passwordcol' => 'password',
    'db_fields'   => array(' first_name', ' last_name', 'email'),
    'cryptType'   => 'none'
);

$driverConfig['extAuthAttribNameList'] = array(
    'lastname'  => 'last_name',
    'firstname' => 'first_name',
    'email'     => 'email'
);

$driverConfig['extAuthAttribTreatmentList'] = array (
    'status' => 5
);

$driverConfig['extAuthAttribToIgnore'] = array(
    'isCourseCreator'
);
?>