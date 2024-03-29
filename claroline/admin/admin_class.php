<?php // $Id$
/**
 * CLAROLINE
 *
 * this tool manage the
 *
 * @version 1.8 $Revision$
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author  Guillaume Lederer <lederer@cerdecam.be>
 */

//used libraries

require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';

// Security check

if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

// DB tables definition

$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_class      = $tbl_mdb_names['user_category'];

// USED SESSION VARIABLES

if ( !isset($_SESSION['admin_visible_class']))
{
    $_SESSION['admin_visible_class'] = array();
}

// Deal with interbredcrumps  and title variable

$nameTools = get_lang('Classes');
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

// javascript confirm pop up declaration for header

$htmlHeadXtra[] =
'<script>
function confirmation (name)
{
    if (confirm("' . clean_str_for_javascript(get_lang('Are you sure to delete')) . '"+\' \'+ name + "? "))
        {return true;}
    else
        {return false;}
}
</script>';

//-------------------------------------------------------
// Main section
//-------------------------------------------------------

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;

$form_data['class_id'] = isset($_REQUEST['class_id'])?(int)$_REQUEST['class_id']:0;
$form_data['class_name'] = isset($_REQUEST['class_name'])?trim($_REQUEST['class_name']):'';
$form_data['class_parent_id'] = isset($_REQUEST['class_parent_id'])?$_REQUEST['class_parent_id']:0;

switch ( $cmd )
{
    // Delete an existing class
    case 'exDelete' :

        if ( delete_class($form_data['class_id']) )
        {
            $dialogBox = get_lang('Class deleted');
        }
        else
        {
            switch ( claro_failure::get_last_failure() )
            {
                case 'class_not_found' :
                    $dialogBox = get_lang('Error : Class not found');
                    break;
                
                case 'class_has_sub_classes' :
                    $dialogBox = get_lang('Error : Class has sub-classes');
                    break;
            }
        }

        break;

    // Display form to create a new class
    case 'rqAdd' :

        $dialogBox = '<form action="'.$_SERVER['PHP_SELF'].'" method="post" >' . "\n"
        .            '<input type="hidden" name="cmd" value="exAdd" />' . "\n"
        .            '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />'
        .            '<table>' . "\n"
        .            '<tr>' . "\n"
        .            '<td>' . get_lang('New Class name').' : ' . '</td>' . "\n"
        .            '<td>' . "\n"
        .            '<input type="text" name="class_name" />' . "\n"
        .            '</td>' . "\n"
        .            '</tr>' . "\n"
        .            '<tr>' . "\n"
        .            '<td>'. get_lang('Location').' :' . '</td>' . "\n"
        .            '<td>' . "\n"
        .            displaySelectBox()
        .            '<input type="submit" value=" Ok " />' . "\n"
        .            '</td>' . "\n"
        .            '</tr>' . "\n"
        .            '</table>' . "\n"
        .            '</form>'."\n "
        ;

        break;

    // Create a new class
    case 'exAdd' :
        
        if ( empty($form_data['class_name']) )
        {
            $dialogBox = get_lang('You cannot give a blank name to a class');
        }
        else
        {
            
            if ( class_create($form_data['class_name'],$form_data['class_parent_id']) )
            {
                $dialogBox = get_lang('The new class has been created');
            }

        }

        break;

    // Edit class properties with posted form
    case 'exEdit' :

        if ( empty($form_data['class_name']) )
        {
            $dialogBox = get_lang('You cannot give a blank name to a class');
        }
        else
        {
            if ( class_set_properties($form_data['class_id'],$form_data['class_name']) ) 
            {
                $dialogBox = get_lang('Name of the class has been changed');
            }
        }

    break;

    // Show form to edit class properties (display form)
    case 'rqEdit' :

        if ( false !== ($thisClass = class_get_properties($form_data['class_id']) ))
        {
            $dialogBox= '<form action="'.$_SERVER['PHP_SELF'].'" method="post" >' . "\n"
            .           '<input type="hidden" name="cmd" value="exEdit" />' . "\n"
            .           '<input type="hidden" name="class_id" value="' . $thisClass['id'] . '" />' . "\n"
            .           '<table>' . "\n"
            .           '<tr>' . "\n"
            .           '<td>' . "\n"
            .           get_lang('Name').' : ' . "\n"
            .           '</td>' . "\n"
            .           '<td>' . "\n"
            .           '<input type="text" name="class_name" value="' . htmlspecialchars($thisClass['name']) . '" />' . "\n"
            .           '<input type="submit" value=" ' . get_lang('Ok') . ' " />' . "\n"
            .           '</td>' . "\n"
            .           '</tr>' . "\n"
            .           '</table>' . "\n"
            .           '</form>'."\n "
            ;
        }
        else
        {
            switch ( claro_failure::get_last_failure() )
            {
                case 'class_not_found' :
                default :
                    $dialogBox = get_lang('Error : Class not found');
                    break;
            }
        }
        break;

    // Open a class in the tree
    case 'exOpen' :

        $_SESSION['admin_visible_class'][$form_data['class_id']] = 'open';
        break;

    // Close a class in the tree
    case 'exClose' :

        $_SESSION['admin_visible_class'][$form_data['class_id']] = 'close';
        break;

    // Move a class in the tree (do it from posted info)
    case 'exMove' :


        if ( move_class($form_data['class_id'],$form_data['class_parent_id']) )
        {
            $dialogBox = get_lang('The class has been moved');
        }
        else
        {
            switch ( claro_failure::get_last_failure() )
            {
                case 'class_not_found' :
                    $dialogBox = get_lang('Error : Class not found');
                    break;
                case 'move_same_class' :
                    // nothing to do
                    break;
            }
        }

    // Move a class in the tree (display form)
    case "rqMove" :

        $dialogBox = '<form action="'.$_SERVER['PHP_SELF'].'">'
        .            '<table>'
        .            '<tr>' . "\n"
        .            '<td>' . "\n"
        .            get_lang('Move') ." ". htmlspecialchars($form_data['class_name']) .' : '
        .            '</td>' . "\n"
        .            '<td>' . "\n"
        .            '<input type="hidden" name="cmd" value="exMove" />' . "\n"
        .            '<input type="hidden" name="class_id" value="'. $form_data['class_id'] .'" />' . "\n"
        .            displaySelectBox()
        .            '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
        .            '</td>' . "\n"
        .            '</tr>' . "\n"
        .            '</table>'
        .            '</form>' ;
        break;

}

// Get all classes

$sql = "SELECT id,
               class_parent_id,
               name
        FROM `" . $tbl_class . "`
        ORDER BY `name`";

$class_list = claro_sql_query_fetch_all($sql);

//-------------------------------------------------------
// Display section
//-------------------------------------------------------

// Display Header

include get_path('incRepositorySys') . '/claro_init_header.inc.php';

// Display title

echo claro_html_tool_title($nameTools);

// display dialog Box (or any forms)

if ( isset($dialogBox) ) echo claro_html_message_box($dialogBox);

//display tool links

echo '<p>'
.    '<a class="claroCmd" href="' . $_SERVER['PHP_SELF'] . '?cmd=rqAdd">'
.    '<img src="' . get_icon_url('class') . '" />' . get_lang('Create a new class')
.    '</a>'
.    '</p>' . "\n";

//display cols headers
echo '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">' . "\n"
.    '<thead>' . "\n"
.    '<tr class="headerX">'
.    '<th>' . get_lang('Classes') . '</th>'
.    '<th>' . get_lang('Users') . '</th>'
.    '<th>' . get_lang('Courses') . '</th>'
.    '<th>' . get_lang('Edit settings') . '</th>'
.    '<th>' . get_lang('Move') . '</th>'
.    '<th>' . get_lang('Delete') . '</th>'
.    '</tr>' . "\n"
.    '</thead>' . "\n"
.    '<tbody>' . "\n" ;

//display Class list
if(display_tree_class_in_admin($class_list))
{
    echo display_tree_class_in_admin($class_list);
}
else
{
    echo "\n"
    .    '<tr class="headerX">'
    .    '<td colspan="6" class="centerContent">' . get_lang('Empty') . '</td>'
    .    '</tr>' . "\n"
    ;
}

echo '</tbody>' . "\n"
.    '</table>' ;

// Display footer
include get_path('incRepositorySys') . '/claro_init_footer.inc.php';

?>