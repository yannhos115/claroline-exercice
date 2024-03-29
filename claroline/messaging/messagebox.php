<?php // $Id$

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * front controler for message box
 *
 * @version     1.9 $Revision$
 * @copyright   2001-2008 Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


    $cidReset = TRUE; 
    require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';
    require_once get_path('incRepositorySys') . '/lib/user.lib.php';

    // move to kernel
    $claroline = Claroline::getInstance();
    
    // ------------- Business Logic ---------------------------
    if ( ! claro_is_user_authenticated() )
    {
        claro_disp_auth_form(false);
    }
    
    include claro_get_conf_repository() . 'CLMSG.conf.php';
    require_once dirname(__FILE__) . '/lib/permission.lib.php';

    $userId = isset($_REQUEST['userId']) ? (int)$_REQUEST['userId'] : NULL;
    
    $link_arg = array();
    
    if (!is_null($userId) && !empty($userId))
    {
        $currentUserId = (int)$_REQUEST['userId'];
        $link_arg['userId'] = $currentUserId;
    }
    else
    {
        $currentUserId = claro_get_current_user_id();
    }
    
    if ($currentUserId != claro_get_current_user_id() && !claro_is_platform_admin())
    {
        claro_die(get_lang("Not allowed"));
    }
    
    // user exist ?
    if ($currentUserId != claro_get_current_user_id())
    {
        $userData = user_get_properties($currentUserId);
        if ($userData === false)
        {
            claro_die(get_lang("User not found"));        
        }
        else
        {
            $title = get_lang('Messages of %firstName %lastName', 
                array ('%firstName' =>htmlspecialchars($userData['firstname']), '%lastName' => htmlspecialchars($userData['lastname'])));
        }
    }
    else
    {
        $title = get_lang('My messages');
    }
    
    $cssLoader = CssLoader::getInstance();
    $linkPage = $_SERVER['PHP_SELF'];
    
    $acceptedValues = array('inbox','outbox','trashbox');
    
    if (!isset($_REQUEST['box']) && !in_array($_REQUEST['box'],$acceptedValues))
    {
        $_REQUEST['box'] = "inbox";
    }
    
    $link_arg['box'] = $_REQUEST['box'];
    
    
    require_once dirname(__FILE__) . '/lib/tools.lib.php';
    
    $content = "";
    if ($link_arg['box'] == "inbox")
    {
        include dirname(__FILE__) . '/inboxcontroler.inc.php';
    }
    elseif ($link_arg['box'] == "outbox")
    {
        include dirname(__FILE__) . '/outboxcontroler.inc.php';
    }
    else
    {
        include dirname(__FILE__) . '/trashboxcontroler.inc.php';
    }
    
    $claroline->display->banner->breadcrumbs->append($title,$_SERVER['PHP_SELF'].'?box='.$link_arg['box']);
    $claroline->display->body->appendContent(claro_html_tool_title($title));
    $claroline->display->body->appendContent($content);
    // ------------ display ----------------------
    
    echo $claroline->display->render();
?>