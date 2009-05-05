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
 * @package CLFRM
 *
 */

// TOOL
$conf_def['config_code']='CLFRM';
$conf_def['config_file']='CLFRM.conf.php';
$conf_def['config_name'] = 'Forums';
$conf_def['config_class']='tool';

$conf_def['section']['forum']['label']='General settings';
$conf_def['section']['forum']['description']='Settings of the tool';
$conf_def['section']['forum']['properties'] =
array ( 'allow_html'
      , 'posts_per_page'
      , 'topics_per_page'
      , 'clfrm_notification_enabled'
      );

//PROPERTIES
// Setup forum Options.
$conf_def_property_list['allow_html']
= array ('label'     => 'HTML in posts'
        ,'description' => 'Allow user to use html tag in messages'
        ,'display'       => false
        ,'default'   => '1'
        ,'type'      => 'enum'
        ,'container' => 'VAR'
        ,'readonly'      => FALSE
        ,'acceptedValue' => array ( '1'=>'Yes'
                                  , '0'=>'No'
                                  )
        );

$conf_def_property_list['posts_per_page']
= array ('label'     => 'Number of posts per page'
        ,'default'   => '5'
        ,'unit'      => 'posts'
        ,'type'      => 'integer'
        ,'container' => 'VAR'
        ,'acceptedValue' => array ( 'min'=>2
                                  , 'max'=>25
                                  )
        );

$conf_def_property_list['topics_per_page']
= array ('label'     => 'Number of topics per page'
        ,'default'   => '5'
        ,'unit'      => 'topics'
        ,'type'      => 'integer'
        ,'container' => 'VAR'
        );

$conf_def_property_list['clfrm_notification_enabled']
= array ('label'     => 'Enable notification of new items'
        ,'description' => ''
        ,'display'       => false
        ,'default'   => TRUE
        ,'type'        => 'boolean'
        ,'display'     => TRUE
        ,'readonly'    => FALSE
        ,'acceptedValue' => array ('TRUE'=>'On', 'FALSE' => 'Off')
        );
