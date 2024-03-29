<?php // $Id$
/**
 * CLAROLINE
 *
 * This tool list user of a course but in admin section
 *
 * @version 1.9 $Revision$
 * @copyright 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/index.php/CLUSR
 *
 * @package CLUSR
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */

$cidReset=true;$gidReset=true;$tidReset=true;

require '../inc/claro_init_global.inc.php';

/* ************************************************************************** */
/*  Security Check
/* ************************************************************************** */

if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

/* ************************************************************************** */
/*  Initialise variables and include libraries
/* ************************************************************************** */

$dialogBox = '';
// initialisation of global variables and used libraries
require_once get_path('incRepositorySys') . '/lib/pager.lib.php';
require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';

include claro_get_conf_repository() . 'user_profile.conf.php';

$tbl_mdb_names   = claro_sql_get_main_tbl();

/**
 * Manage incoming.
 */

if ((isset($_REQUEST['cidToEdit']) && $_REQUEST['cidToEdit'] == '') || !isset($_REQUEST['cidToEdit']))
{
    unset($_REQUEST['cidToEdit']);
    $dialogBox .= 'ERROR : NO COURSE SET!!!';
}
else $cidToEdit = $_REQUEST['cidToEdit'];
// See SESSION variables used for reorder criteria :
$validCmdList = array('unsub',);
$validRefererList = array('clist',);

$cmd = (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'],$validCmdList) ? $_REQUEST['cmd'] : null);
$cfrom = (isset($_REQUEST['cfrom']) && in_array($_REQUEST['cfrom'],$validRefererList) ? $_REQUEST['cfrom'] : null);

$pager_offset =  isset($_REQUEST['pager_offset'])?$_REQUEST['pager_offset'] :'0';
$addToURL = '';
$do=null;

/**
 * COMMAND
 */

if ( $cmd == 'unsub' )
{
    $do = 'unsub';
}

if ( $do == 'unsub' )
{
    if ( user_remove_from_course($_REQUEST['user_id'], $_REQUEST['cidToEdit'], true, true, false) )
    {
        $dialogBox .= get_lang('The user has been successfully unregistered');
    }
    else
    {
        switch ( claro_failure::get_last_failure() )
        {
            case 'cannot_unsubscribe_the_last_course_manager' :
            {
                $dialogBox .= get_lang('You cannot unsubscribe the last course manager of the course');
            }   break;
            case 'course_manager_cannot_unsubscribe_himself' :
            {
                $dialogBox .= get_lang('Course manager cannot unsubscribe himself');
            }   break;
            default :
        }
    }
}
// build and call DB to get info about current course (for title) if needed :
$courseData = claro_get_course_data($cidToEdit);

//----------------------------------
// Build query and find info in db
//----------------------------------
$sql = "SELECT U.user_id  AS user_id,
               U.nom      AS name,
               U.prenom   AS firstname,
               U.username AS username,
               CU.profile_id AS profileId,
               CU.isCourseManager
        FROM  `" . $tbl_mdb_names['user'] . "` AS U
            , `" . $tbl_mdb_names['rel_course_user'] . "` AS CU
          WHERE CU.`user_id` = U.`user_id`
            AND CU.`code_cours` = '" . claro_sql_escape($cidToEdit) . "'";

$myPager = new claro_sql_pager($sql, $pager_offset, get_conf('userPerPage',20));

$sortKey = isset($_GET['sort']) ? $_GET['sort'] : 'user_id';
$sortDir = isset($_GET['dir' ]) ? $_GET['dir' ] : SORT_ASC;
$myPager->set_sort_key($sortKey, $sortDir);
$myPager->set_pager_call_param_name('pager_offset');

$userList = $myPager->get_result_list();

// Start the list of users...
$userDataList = array();

foreach($userList as $lineId => $user)
{
    $userDataList[$lineId]['user_id']         = $user['user_id'];
    $userDataList[$lineId]['name']            = $user['name'];
    $userDataList[$lineId]['firstname']       = $user['firstname'];

    $userDataList[$lineId]['profileId']       = claro_get_profile_name($user['profileId']);

    if ( $user['isCourseManager'] )
    {
        $userDataList[$lineId]['isCourseManager'] = '<img src="' . get_icon_url('manager') . '" '
                                                  . ' alt="' . get_lang('Course manager') . '" hspace="4" '
                                                  . ' title="' . get_lang('Course manager') . '" />' ;
    }
    else
    {
        $userDataList[$lineId]['isCourseManager'] = '<img src="' . get_icon_url('user') . '" '
                                                  . ' alt="' . get_lang('Student') . '" hspace="4" '
                                                  . ' title="' . get_lang('Student') . '" />' ;
    }

    $userDataList[$lineId]['cmd_cu_edit'] = '<a href="adminUserCourseSettings.php'
                                            . '?cidToEdit=' . $cidToEdit
                                            . '&amp;uidToEdit=' . $user['user_id'] . '&amp;ccfrom=culist">'
                                            . '<img src="' . get_icon_url('edit') .'" alt="' . get_lang('Edit') . '"/>'
                                            . '</a>';

    $userDataList[$lineId]['cmd_cu_unenroll']  = '<a href="' . $_SERVER['PHP_SELF']
    .                                            '?cidToEdit=' . $cidToEdit
    .                                            '&amp;cmd=unsub&amp;user_id=' . $user['user_id']
    .                                            '&amp;pager_offset=' . $pager_offset . '" '
    .                                            ' onclick="return confirmationReg(\'' . clean_str_for_javascript($user['username']) . '\');">' . "\n"
    .                                            '<img src="' . get_icon_url('unenroll') . '" alt="' . get_lang('Unregister user') . '" />' . "\n"
    .                                            '</a>' . "\n";

} // end display users table

/****************
 * Prepare output
 */

// javascript confirm pop up declaration
$htmlHeadXtra[] =
         "<script>
         function confirmationReg (name)
         {
             if (confirm(\"".clean_str_for_javascript(get_lang('Are you sure you want to unregister'))." \"+ name + \" ? \"))
                 {return true;}
             else
                 {return false;}
         }
         </script>";

// Config Datagrid

$sortUrlList = $myPager->get_sort_url_list($_SERVER['PHP_SELF'] . '?cidToEdit=' . $cidToEdit);

$dg_opt_list['idLineShift'] = $myPager->offset + 1;
$dg_opt_list['colTitleList'] = array ( 'user_id'  => '<a href="' . $sortUrlList['user_id'] . '">' . get_lang('User id') . '</a>'
                                     , 'name'     => '<a href="' . $sortUrlList['name'] . '">' . get_lang('Last name') . '</a>'
                                     , 'firstname'=> '<a href="' . $sortUrlList['firstname'] . '">' . get_lang('First name') . '</a>'
                                     , 'profileId'=> '<a href="' . $sortUrlList['profileId'] . '">' . get_lang('Profile') . '</a>'
                                     , 'isCourseManager'  => '<a href="' . $sortUrlList['isCourseManager'] . '">' . get_lang('Course manager') . '</a>'
                                     , 'cmd_cu_edit'  => get_lang('Edit')
                                     , 'cmd_cu_unenroll' => get_lang('Unregister user')
);

$dg_opt_list['colAttributeList'] = array ( 'user_id'   => array ('align' => 'center')
                                         , 'isCourseManager'    => array ('align' => 'center')
                                         , 'cmd_cu_edit'    => array ('align' => 'center')
                                         , 'cmd_cu_unenroll' => array ('align' => 'center')
);

$dg_opt_list['caption'] = '<img src="' . get_icon_url('user') . '" '
.                         ' alt="' . get_lang('Student') . '" title="' . get_lang('Student') . '" />'
.                         get_lang('Student')
.                         ' - <img src="' . get_icon_url('manager') . '" '
.                         ' alt="' . get_lang('Course manager') . '" title="' . get_lang('Course manager') . '" />'
.                         get_lang('Course manager')
;

$nameTools = get_lang('Course members');
$nameTools .= " : ".$courseData['name'];
// Deal with interbredcrumps
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$command_list[] = '<a class="claroCmd" href="adminregisteruser.php'
.    '?cidToEdit=' . $cidToEdit . '">'
.    get_lang('Enroll a user')
.    '</a>'
;
if ($cfrom=='clist')
{
    $command_list[] = '<a class="claroCmd" href="admincourses.php">' . get_lang('Back to course list') . '</a>';
}

/*********
 * DISPLAY
 */

include get_path('incRepositorySys') . '/claro_init_header.inc.php';
echo claro_html_tool_title($nameTools);
if ( !empty($dialogBox) ) echo claro_html_message_box($dialogBox);

$userDataGrid = new claro_datagrid($userDataList);
$userDataGrid->set_option_list($dg_opt_list);

echo '<p>' . claro_html_menu_horizontal($command_list) . '</p>'
.    $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'] . '?cidToEdit=' . $cidToEdit)
.    $userDataGrid->render()
.    $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'] . '?cidToEdit=' . $cidToEdit)
;

include get_path('incRepositorySys') . '/claro_init_footer.inc.php';
?>