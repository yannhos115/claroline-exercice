<?php // $Id$
/**
 * CLAROLINE
 *
 * @version 0.1 $Revision$
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLDOC
 *
 * @author Sebastien Piraux
 *
 */

$tlabelReq = 'CLDOC';

require_once dirname( __FILE__ ) . '/../../../claroline/inc/claro_init_global.inc.php';

if ( !claro_is_tool_allowed() )
{
    if ( claro_is_in_a_course() )
    {
        claro_die( get_lang( "Not allowed" ) );
    }
    else
    {
        claro_disp_auth_form( true );
    }
}


$inLP = (claro_called_from() == 'CLLP')? true : false;

if( !$inLP )
{
    claro_redirect('../document.php'); 
}

$url = Url::Contextualize(get_path('url') . '/claroline/backends/download.php?url=' . $_REQUEST['url']);

$claroline->setDisplayType( CL_FRAMESET );

$docFrame = new ClaroFrame('document', $url);
$docFrame->allowScrolling(true);
$docFrame->noFrameBorder();

$progressFrame = new ClaroFrame('progress', Url::Contextualize('./cllp.progress.cnr.php'));
$progressFrame->disableResize(true);
$progressFrame->noFrameBorder();


$claroline->display->addRow($docFrame, '*');
$claroline->display->addRow($progressFrame, '50');

// output outer frameset with inner frameset within in embedded mode
echo $claroline->display->render();
?>