<?php // $Id$
/**
 * CLAROLINE
 *
 * @version 1.9 $Revision$
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package ADMIN
 *
 * @author claro team <cvs@claroline.net>
 * @since 1.8
 */

require '../../inc/claro_init_global.inc.php';

//SECURITY CHECK

if ( ! claro_is_user_authenticated() )
{
    claro_disp_auth_form();
}

if ( ! claro_is_platform_admin() )
{
    claro_die(get_lang('Not allowed'));
}

//CONFIG and DEVMOD vars :

//SQL table name

$tbl_name        = claro_sql_get_main_tbl();
$tbl_module      = $tbl_name['module'];
$tbl_module_info = $tbl_name['module_info'];
$tbl_dock        = $tbl_name['dock'];

//NEEDED LIBRAIRIES

require_once get_path('incRepositorySys') . '/lib/module/manage.lib.php';
require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';

$undeactivable_tool_array = array('CLDOC',
                                  'CLGRP'
                                 );

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmMakeVisible ()
{
    if (confirm(\" ".clean_str_for_javascript(get_lang("Are you sure you want to make this module visible in all courses ?"))."\"))
        {return true;}
    else
        {return false;}
}
function confirmMakeInVisible ()
{
    if (confirm(\" ".clean_str_for_javascript(get_lang("Are you sure you want to make this module invisible in all courses ?"))."\"))
        {return true;}
    else
        {return false;}
}
</script>";

//----------------------------------
// GET REQUEST VARIABLES
//----------------------------------

$cmd = (isset($_REQUEST['cmd'])? $_REQUEST['cmd'] : null);
$item = (isset($_REQUEST['item'])? $_REQUEST['item'] : 'GLOBAL');
$section_selected = (isset($_REQUEST['section'])? $_REQUEST['section'] : null);
$moduleId = (isset($_REQUEST['module_id'])? $_REQUEST['module_id'] : null);
$module = get_module_info($moduleId);
$dockList = get_dock_list($module['type']);

// FIXME : BAD use of get_lang !!!!!
ClaroBreadCrumbs::getInstance()->prepend( get_lang($module['module_name']) );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Module list'), get_path('rootAdminWeb').'module/module_list.php?typeReq=' . $module['type'] );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$nameTools = get_lang('Module settings');
$noPHP_SELF=true;

//----------------------------------
// EXECUTE COMMAND
//----------------------------------

switch ( $cmd )
{
    case 'courseactiv' :
    {
        $tbl_mdb_names        = claro_sql_get_main_tbl();
        $tbl_tool_list        = $tbl_mdb_names['tool'];
        
        $sql = "UPDATE `{$tbl_tool_list}` "
            . "SET add_in_course = 'AUTOMATIC' "
            . "WHERE claro_label = '" . claro_sql_escape( $module['label']) . "'"
            ;
        
        if (claro_sql_query($sql))
        {
            $dialogBox = get_lang('Module activation at course creation set to AUTOMATIC');
            $module['activateInCourses']  = 'AUTOMATIC';
        }
        else
        {
            $dialogBox = get_lang('Cannot change module activation on course creation');
        }
        break;
    }
    case 'coursedeactiv' :
    {
        $tbl_mdb_names        = claro_sql_get_main_tbl();
        $tbl_tool_list        = $tbl_mdb_names['tool'];
        
        $sql = "UPDATE `{$tbl_tool_list}` "
            . "SET add_in_course = 'MANUAL' "
            . "WHERE claro_label = '" . claro_sql_escape( $module['label']) . "'"
            ;
        
        if (claro_sql_query($sql))
        {
            $dialogBox = get_lang('Module activation at course creation set to MANUAL');
            $module['activateInCourses']  = 'MANUAL';
        }
        else
        {
            $dialogBox = get_lang('Cannot change module activation on course creation');
        }
        break;
    }
    case 'activ' :
    {
        if (activate_module($moduleId))
        {
            $dialogBox = get_lang('Module activation succeeded');
            $module['activation']  = 'activated';
        }
        else
        {
            $dialogBox = get_lang('Cannot activate module');
        }
        break;
    }
    case 'deactiv' :
    {
        if (deactivate_module($moduleId))
        {
            $dialogBox = get_lang('Module deactivation succeeded');
            $module['activation']  = 'deactivated';
        }
        else
        {
            $dialogBox = get_lang('Cannot deactivate module');
            $module['activation']  = 'activated';
        }
        break;
    }
    case 'movedock' :
    {
        if( is_array($dockList) )
        {
            if ( isset($_REQUEST['displayDockList']) && is_array($_REQUEST['displayDockList']) )
            {
                foreach ($dockList as $dockId => $dockName)
                {

                    if ( in_array($dockId,$_REQUEST['displayDockList']) )
                    {
                        add_module_in_dock($moduleId, $dockId);
                    }
                    else
                    {
                        remove_module_dock($moduleId, $dockId);
                    }
                }
            }
            $dialogBox = get_lang('Changes in the display of the module have been applied');
        }
        break;
    }
    case 'makeVisible':
    case 'makeInvisible':
    {
        $visibility = ( 'makeVisible' == $cmd ) ? true : false;

        list ( $log, $success ) = set_module_visibility( $moduleId, $visibility );

        if ( $success )
        {
            $dialogBox = get_lang('Module visibility updated');
        }
        else
        {
            $dialogBox = get_lang('Failed to update module visibility');
        }

        break;
    }
}

// create an array with only dock names

$sql = "SELECT `name` AS `dockname`
        FROM `" . $tbl_dock        . "`
        WHERE `module_id` = " . (int) $moduleId;

$module_dock = claro_sql_query_fetch_all($sql);

$dock_checked = array();

foreach($module_dock as $thedock)
{
    $dock_checked[] = $thedock['dockname'];
}

//----------------------------------
// DISPLAY
//----------------------------------

include get_path('incRepositorySys') . '/claro_init_header.inc.php';

// find module icon, if any

if (array_key_exists('icon',$module) && !empty($module['icon'])  && file_exists(get_module_path($module['label']) . '/' .$module['icon']))
{
    $icon = '<img src="' . get_module_url($module['label']) . '/' . $module['icon'] . '" />';
}
elseif (file_exists(get_module_path($module['label']) . '/icon.png'))
{
    $icon = '<img src="' . get_module_url($module['label']) . '/icon.png" />';
}
elseif (file_exists(get_module_path($module['label']) . '/icon.gif'))
{
    $icon = '<img src="' . get_module_url($module['label']) . '/icon.gif" />';
}
else
{
    $icon = '<small>' . get_lang('No icon') . '</small>';
}

//display title

echo claro_html_tool_title($nameTools . ' : ' . get_lang($module['module_name']));

//Display Forms or dialog box(if needed)

if ( isset($dialogBox) )
{
    echo claro_html_message_box($dialogBox);
}

//display tabbed navbar

echo  '<div>'
    . '<ul id="navlist">'
    . "\n"
    ;

//display the module type tabbed naviguation bar

if ($item == 'GLOBAL')
{
    echo '<li><a href="module.php?module_id='.$moduleId
        . '&amp;item=GLOBAL" class="current">'
        . get_lang('Global settings').'</a></li>'
        . "\n"
        ;
}
else
{
    echo '<li>'
    .    '<a href="module.php?module_id='.$moduleId.'&amp;item=GLOBAL">'
    .    get_lang('Global settings').'</a>'
    .    '</li>' . "\n"
    ;
}

$config_code = $module['label'];

// new config object
require_once get_path('incRepositorySys') . '/lib/configHtml.class.php';

$config = new ConfigHtml($config_code, $_SERVER['HTTP_REFERER']);

if ( $config->load() )
{
    if ($item == 'LOCAL')
    {
        echo '<li><a href="module.php?module_id='.$moduleId
            . '&amp;item=LOCAL" class="current">'
            . get_lang('Local settings').'</a></li>'
            . "\n"
            ;
    }
    else
    {
        echo '<li><a href="module.php?module_id='.$moduleId.'&amp;item=LOCAL">'
            . get_lang('Local settings').'</a></li>'
            . "\n"
            ;
    }
}

if ($item == 'About' || is_null($item))
{
    echo '<li><a href="module.php?module_id='.$moduleId
        . '&amp;item=About" class="current">'
        . get_lang('About').'</a></li>'
        . "\n"
        ;
}
else
{
    echo '<li><a href="module.php?module_id='.$moduleId.'&amp;item=About">'
        . get_lang('About').'</a></li>'
        . "\n"
        ;
}

echo '</ul>'. "\n"
    . '</div>'. "\n"
    ;


switch ($item)
{
    case 'GLOBAL':
    {
        echo claro_html_tool_title(array('subTitle' => get_lang('Platform Settings')));

        echo '<form action="' . $_SERVER['PHP_SELF'] . '?module_id=' . $module['module_id'] . '&amp;item='.$item.'" method="post">';

        echo '<table>' . "\n";

        //Activation form
        if (in_array($module['label'],$undeactivable_tool_array))
        {
            $action_link = get_lang('This module cannot be deactivated');
        }
        elseif ( 'activated' == $module['activation'] )
        {
            $activ_form  = 'deactiv';
            $action_link = '<a href="' . $_SERVER['PHP_SELF']
                . '?cmd='.$activ_form.'&module_id='.$module['module_id']
                . '&item=GLOBAL" title="'
                . get_lang('Activated - Click to deactivate').'">'
                . '<img src="' . get_icon_url('on')
                . '" alt="'. get_lang('Activated') . '" /> '
                . get_lang('Activated') . '</a>'
                ;
        }
        else
        {
            $activ_form  = 'activ';
            $action_link = '<a href="' . $_SERVER['PHP_SELF']
                . '?cmd='.$activ_form.'&module_id='
                . $module['module_id'].'&item=GLOBAL" '
                . 'title="'.get_lang('Deactivated - Click to activate').'">'
                . '<img src="' . get_icon_url('off')
                . '" alt="'. get_lang('Deactivated') . '"/> '
                . get_lang('Deactivated') . '</a>'
                ;
        }

        echo '<td align="right" valign="top">'
          .    get_lang('Platform activation')
          .    ' : ' . "\n"
          .    '</td>' . "\n"
          .    '<td>' . "\n"
          .    $action_link . "\n"
          .    '</td>' . "\n"
          .    '</tr>' . "\n"
          .    '<tr>' . "\n"
          .    '<td colspan="2">&nbsp;</td>' . "\n"
          .    '</tr>' . "\n"
          ;

        if ($module['type'] == 'tool')
        {
            // var_dump($module['activateInCourse']);
            if (in_array($module['label'],$undeactivable_tool_array))
            {
                // do not fuck with cthulhu !
                $action_link = get_lang('Cannot be changed');
            }
            elseif ( 'AUTOMATIC' == $module['activateInCourses'] )
            {
                $activ_form  = 'coursedeactiv';
                $action_link = '<a href="' . $_SERVER['PHP_SELF']
                    . '?cmd='.$activ_form.'&module_id='.$module['module_id']
                    . '&item=GLOBAL" title="'
                    . get_lang('Automatic').'">'
                    . '<img src="' . get_icon_url('select')
                    . '" alt="'. get_lang('Automatic') . '" /> '
                    . get_lang('Automatic') . '</a>'
                    ;
            }
            else
            {
                $activ_form  = 'courseactiv';
                $action_link = '<a href="' . $_SERVER['PHP_SELF']
                    . '?cmd='.$activ_form.'&module_id='
                    . $module['module_id'].'&item=GLOBAL" '
                    . 'title="'.get_lang('Manual').'">'
                    . '<img src="' . get_icon_url('forbidden')
                    . '" alt="'. get_lang('Manual') . '"/> '
                    . get_lang('Manual') . '</a>'
                    ;
            }
                
            echo '<td align="right" valign="top">'
            .    get_lang('Activate on course creation')
            .    ' : ' . "\n"
            .    '</td>' . "\n"
            .    '<td>' . "\n"
            .    $action_link . "\n"
            .    '</td>' . "\n"
            .    '</tr>' . "\n"
            .    '<tr>' . "\n"
            .    '<td colspan="2">&nbsp;</td>' . "\n"
            .    '</tr>' . "\n"
            ;
            
            echo '<tr><td align="right" valign="top">'
                . get_lang( 'Change visibility in all courses' )
                . ' : '
                .    '</td>' . "\n"
                .    '<td>' . "\n"
                . '<small><a href="'
                . $_SERVER['PHP_SELF'] . '?module_id=' . $module['module_id'].'&amp;cmd=makeVisible&amp;item=GLOBAL"'
                . 'title="'.get_lang( 'Make module visible in all courses' ).'"'
                . ' onclick="return confirmMakeVisible();">'
                . '<img src="' . get_icon_url('visible')
                . '" alt="'. get_lang('Visible') . '"/> '
                . get_lang( 'Visible' )
                . '</a></small>'
                . " | "
                . '<small><a href="'
                . $_SERVER['PHP_SELF'] . '?module_id=' . $module['module_id'].'&amp;cmd=makeInvisible&amp;item=GLOBAL"'
                . 'title="'.get_lang( 'Make module invisible in all courses' ).'"'
                . ' onclick="return confirmMakeInVisible();">'
                . '<img src="' . get_icon_url('invisible')
                . '" alt="'. get_lang('Invisible') . '"/> '
                . get_lang( 'Invisible' )
                . '</a></small>'
                . '<td><tr>' . "\n"
                ;
        }
        elseif ($module['type'] == 'applet')
        {
            //choose the dock radio button list display
            if ( is_array($dockList) && $module['type']!='tool')
            {
                echo '<tr>' ."\n"
                .    '<td syle="align:right" colspan="2">' . get_lang('Display'). '&nbsp;:</td>' ."\n"
                .    '</tr>' ."\n"
                    ;

                $i = 1;

                //display each option
                foreach ($dockList as $dockId => $dockName)
                {
                    if (in_array($dockId,$dock_checked)) $is_checked = 'checked="checked"'; else $is_checked = "";

                    echo '<tr>' ."\n"
                    .    '<td>&nbsp;</td>' ."\n"
                    .    '<td>' ."\n"
                    .    '<input type="checkbox" name="displayDockList[]" value="' . $dockId . '" id="displayDock_' . $i . '" ' . $is_checked . ' />'
                    .    '<label for="displayDock_' . $i . '">' . $dockName . '</label>'
                    .    '</td>' ."\n"
                    .    '</tr>' ."\n"
                    ;

                    $i++;
                }
            }

            // display submit button
            echo '<tr><td colspan="2">&nbsp;</td></tr>' . "\n"
            .    '<tr>' ."\n"
            .    '<td style="text-align:right">' . get_lang('Save') . '&nbsp;:</td>' . "\n"
            .    '<td >'
            .    '<input type="hidden" name="cmd" value="movedock" />'. "\n"
            .    '<input type="submit" value="' . get_lang('Ok') . '" />&nbsp;'. "\n"
            .    claro_html_button($_SERVER['HTTP_REFERER'], get_lang('Cancel')) . '</td>' . "\n"
            .    '</tr>' . "\n"
            .    '</form>'
            ;
        }
        else // not a tool, not an applet
        {
            // nothing to do at the moment
        }

        echo '</table>' . "\n"
        .    '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '</table>' . "\n"
        ;
        break;
    }
    case 'LOCAL':
    {
        $form = '';

        $url_params = '&module_id='. $moduleId .'&item='. htmlspecialchars($item);

        $form = $config->display_section_menu($section_selected,$url_params);

           // init config name
        $config_name = $config->config_code;

        if ( isset($_REQUEST['cmd']) && isset($_REQUEST['property']) )
        {
            if ( 'save' == $_REQUEST['cmd'] )
            {
                if ( ! empty($_REQUEST['property']) )
                {
                    list($message, $error) = generate_conf($config,$_REQUEST['property']);
                }
            }
            // display form
            $form .= $config->display_form($_REQUEST['property'],$section_selected,$url_params);
        }
        else
        {
            // display form
            $form .= $config->display_form(null,$section_selected,$url_params);
        }

        echo '<div style=padding-left:1em;padding-right:1em;>';

        if ( ! empty($message) )
        {
            echo claro_html_message_box($message);
        }

        echo $form . '</div>';

        break;
    }
    default:
    {
        $moduleDescription = trim( $module['description'] );

        $moduleDescription = (empty( $moduleDescription ) )
            ? get_lang('No description given')
            : $moduleDescription
            ;

        echo claro_html_tool_title(array('subTitle' => get_lang('Description')))
        .    '<p>'
        .    htmlspecialchars( $moduleDescription )
        .    '</p>' . "\n"
        ;

        echo claro_html_tool_title(array('subTitle' => get_lang('General Informations'))) . "\n"
        .    '<table>' . "\n"
        .    '<tr>' . "\n"
        .    '<td colspan="2">' . "\n"
        .    '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">'
        .    get_lang('Icon')
        .    ' : </td>' . "\n"
        .    '<td>' . "\n"
        .    $icon . "\n"
        .    '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Module name') . ' : </td>' . "\n"
        .    '<td >' . $module['module_name'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Type') . ' : </td>' . "\n"
        .    '<td>' . $module['type'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Version') . ' : </td>' . "\n"
        .    '<td >' . $module['version'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('License') . ' : </td>' . "\n"
        .    '<td >General Public License</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Author') . ' : </td>' . "\n"
        .    '<td >' . $module['author'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Contact') . ' : </td>' . "\n"
        .    '<td >' . $module['author_email'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Website') . ' : </td>' . "\n"
        .    '<td><a href="' . $module['website'] . '">' . $module['website'] . '</a></td>' . "\n"
        .    '</tr>' . "\n"
        .    '</table>' . "\n"
        .    '</td>' . "\n"
        .    '<td>' . "\n"
        .    '<table>' . "\n"
        ;
    }
}

echo '</table>' . "\n"
.    '</td>' . "\n"
.    '</tr>' . "\n"
.    '</table>' . "\n"
;

include get_path('incRepositorySys') . '/claro_init_footer.inc.php';

?>