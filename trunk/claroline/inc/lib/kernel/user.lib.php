<?php // $Id$

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Description
 *
 * @version     1.9 $Revision$
 * @copyright   2001-2008 Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     PACKAGE_NAME
 */

FromKernel::uses ( 'kernel/object.lib', 'core/claroline.lib', 'database/database.lib' );

class Claro_User extends KernelObject
{
    protected $_userId;
    
    public function __construct( $userId )
    {
        $this->_userId = $userId;
    }
    
    public function loadFromDatabase()
    {
        $tbl = claro_sql_get_main_tbl();
        
        $sqlUserId = (int) $this->_userId;
        
        $sql = "SELECT "
            . "`user`.`user_id` AS userId,\n"
            // . "`user`.`username`,\n"
            . "`user`.`prenom` AS firstName,\n"
            . "`user`.`nom` AS lastName,\n"
            . "`user`.`email`AS `mail`,\n"
            . "`user`.`officialEmail` AS `officialEmail`,\n"
            . "`user`.`language`,\n"
            . "`user`.`isCourseCreator`,\n"
            . "`user`.`isPlatformAdmin`,\n"
            . "`user`.`creatorId` AS creatorId,\n"
            . "`user`.`officialCode`,\n"
            . "`user`.`language`,\n"
            . "`user`.`authSource`,\n"
            . "`user`.`phoneNumber` AS `phone`,\n"
            . "`user`.`pictureUri` AS `picture`,\n"
            
            . ( get_conf('is_trackingEnabled')
                ? "UNIX_TIMESTAMP(`tracking`.`date`) "
                : "DATE_SUB(CURDATE(), INTERVAL 1 DAY) " )
                
            . "AS lastLogin\n"
            . "FROM `{$tbl['user']}` AS `user`\n"
            
            . ( get_conf('is_trackingEnabled')
                ? "LEFT JOIN `{$tbl['tracking_event']}` AS `tracking`\n"
                . "ON `user`.`user_id`  = `tracking`.`user_id`\n"
                . "AND `tracking`.`type` = 'user_login'\n"
                : '')
                
            . "WHERE `user`.`user_id` = ".$sqlUserId."\n"
            
            . ( get_conf('is_trackingEnabled')
                ? "ORDER BY `tracking`.`date` DESC LIMIT 1"
                : '')
            ;

        $userData = Claroline::getDatabase()->query( $sql )->fetch();
        
        if ( ! $userData )
        {
            throw new Exception("Cannot load user data for {$this->_userId}");
        }
        else
        {
            $userData['isPlatformAdmin'] = (bool) $userData['isPlatformAdmin'];
            $userData['isCourseCreator'] = (bool) $userData['isCourseCreator'];
            
            $this->_rawData = $userData;
            pushClaroMessage( "User {$this->_userId} loaded from database", 'debug' );
            
            $this->loadUserProperties();
        }
    }
    
    public function loadUserProperties()
    {
        $tbl = claro_sql_get_main_tbl();
        
        $sqlUserId = (int) $this->_userId;
        
        $sql = "SELECT propertyId AS name, propertyValue AS value, scope\n"
            . "FROM `{$tbl['user_property']}`\n"
            . "WHERE userId = " . $sqlUserId
            ;
            
            
        $userProperties = Claroline::getDatabase()->query( $sql );
        $userProperties->setFetchMode(Database_ResultSet::FETCH_OBJECT);
        
        $this->_rawData['userProperties'] = array();
        
        foreach ( $userProperties as $property )
        {
            if ( ! array_key_exists( $property->name, $this->_rawData['userProperties'] )
                || ! is_array( $this->_rawData['userProperties'][$property->name] ) )
            {
                $this->_rawData['userProperties'][$property->name] = array();
            }
            
            $this->_rawData['userProperties'][$property->name][$property->scope] = $property->value;
        }
    }
    
    public function getUserProperty( $name, $scope )
    {
        if ( array_key_exists( $name, $this->_rawData['userProperties'] )
            && array_key_exists( $scope, $this->_rawData['userProperties'][$property->name] )
        )
        {
            return $this->_rawData['userProperties'][$property->name][$property->scope];
        }
        else
        {
            return null;
        }
    }
}

class Claro_CurrentUser extends Claro_User
{
    public function __construct( $userId = null )
    {
        $userId = empty( $userId )
            ? claro_get_current_user_id()
            : $userId
            ;
            
        parent::__construct( $userId );
    }
    
    public function loadFromSession()
    {
        if ( !empty($_SESSION['_user']) )
        {
            $this->_rawData = $_SESSION['_user'];
            pushClaroMessage( "User {$this->_userId} loaded from session", 'debug' );
        }
        else
        {
            throw new Exception("Cannot load user data from session for {$this->_userId}");
        }
    }
    
    public function saveToSession()
    {
        $_SESSION['_user'] = $this->_rawData;
    }
    
    public function firstLogin()
    {
        return ($this->_userId != $this->creatorId);
    }
    
    public function updateCreatorId()
    {
        $tbl = claro_sql_get_main_tbl();
        
        $sql = "UPDATE `{$tbl['user']}`\n"
            . "SET   creatorId = user_id\n"
            . "WHERE user_id = " . (int)$this->_userId
            ;
            
        pushClaroMessage( "Creator id updated for user {$this->_userId}", 'debug' );
    
        return Claroline::getDatabase()->exec($sql);
    }
    
    protected static $instance = false;
    
    public static function getInstance( $uid = null, $forceReload = false )
    {
        if ( $forceReload || ! self::$instance )
        {
            self::$instance = new self( $uid );
            
            if ( !$forceReload && claro_is_user_authenticated() )
            {
                self::$instance->loadFromSession();
            }
            else
            {
                self::$instance->loadFromDatabase();
            }
        }
        
        return self::$instance;
    }
}
