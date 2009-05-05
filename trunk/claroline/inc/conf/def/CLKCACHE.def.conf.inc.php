<?php //$Id$
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * This file describe the parameter for forum tool
 *
 * @version 1.8 $Revision$
 *
 * @copyright 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/index.php/Config
 *
 * @author Claro Team <cvs@claroline.net>
 *
 * @package CLCACHE
 *
 */

// TOOL
$conf_def['config_code']='CLKCACHE';
$conf_def['config_file']='CLKCACHE.conf.php';
$conf_def['config_name'] = 'Cache system';
$conf_def['config_class']='kernel';


$conf_def['section']['main']['properties'] =
array ( 'cache_lifeTime'
      , 'cache_automaticCleaningFactor'

);
//PROPERTIES
// Setup forum Options.

$conf_def_property_list['cache_lifeTime']
= array ('label'     => 'Time to keep a cache as valid'
        ,'default'   => 10
        ,'unit'      => 'second'
        ,'type'      => 'integer'
        ,'container' => 'VAR'
        ,'acceptedValue' => array ( 'min'=>2
                                  , 'max'=>3600*24
                                  )
        );

$conf_def_property_list['cache_automaticCleaningFactor']
= array ('label'     => 'Automatic cleaning factor'
        ,'description' => 'write n-1 times without check if (others) cached files are or not deprecated'
        ,'default'   => '40'
        ,'unit'      => 'times'
        ,'type'      => 'integer'
        ,'container' => 'VAR'
        ,'acceptedValue' => array ( 'min'=>0
                                  , 'max'=>1000)
        );
?>
