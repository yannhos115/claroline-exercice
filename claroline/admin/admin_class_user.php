<?php //$Id$
/**
 * CLAROLINE
 *
 * this tool manage the
 *
 * @version 1.9 $Revision$
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 */

// initialisation of global variables and used libraries
require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/pager.lib.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

// DB tables definition

$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_user       = $tbl_mdb_names['user'];
$tbl_class      = $tbl_mdb_names['user_category'];
$tbl_class_user = $tbl_mdb_names['user_rel_profile_category'];

// javascript confirm pop up declaration

$htmlHeadXtra[] =
         "<script>
         function confirmationUnReg (name)
         {
             if (confirm(\"".clean_str_for_javascript(get_lang('Are you sure you want to unregister'))."\"+ name + \"? \"))
                 {return true;}
             else
                 {return false;}
         }
         </script>";

//------------------------------------
// Main section
//------------------------------------

$cmd = isset($_REQUEST['cmd'])?trim($_REQUEST['cmd']):null;
$user_id = isset($_REQUEST['user_id'])?(int)$_REQUEST['user_id']:0;
$class_id = isset($_REQUEST['class_id'])?(int)$_REQUEST['class_id']:0;
$search = isset($_REQUEST['search'])?trim($_REQUEST['search']):null;

// find info about the class

if ( ($classinfo = class_get_properties ($class_id)) === false )
{
    $class_id = 0;
}

if ( !empty($class_id) )
{

    switch ($cmd)
    {
        case 'unsubscribe' :

            if ( user_remove_to_class($user_id,$class_id) )
            {
                $dialogBox = get_lang('User has been sucessfully unregistered from the class');
            }
            break;

        case 'unsubscribe_all' :

            if ( class_remove_all_users($class_id) )
            {
                $dialogBox = get_lang('All users have been sucessfully unregistered from the class');
            }
            break;        

        default :
            // No command
    }

    //----------------------------------
    // Build query and find info in db
    //----------------------------------

    // find this class current content

    $classes_list = getSubClasses($class_id);
    $classes_list[] = $class_id;

    $sql = "SELECT distinct U.user_id      AS user_id,
                            U.nom          AS nom,
                            U.prenom       AS prenom,
                            U.nom          AS lastname,
                            U.prenom       AS firstname,
                            U.email        AS email,
                            U.officialCode AS officialCode
            FROM `" . $tbl_user . "` AS U
            LEFT JOIN `" . $tbl_class_user . "` AS CU
                ON U.`user_id`= CU.`user_id`
            WHERE `CU`.`class_id`
                in (" . implode($classes_list,",") . ")";
    
    // if user search exist
    
    if (isset($search))
    {
        $sql .= " AND (U.nom LIKE '%". $search ."%'
                  OR U.prenom LIKE '%". $search ."%' ";
        $sql .= " OR U.email LIKE '%".  $search ."%'";
        $sql .= " OR U.username LIKE '".  $search ."%'";        
        $sql .= " OR U.officialCode = '".  $search ."')";
    }

    // deal with session variables for search criteria

    if (isset($_REQUEST['dir']))
    {
        $_SESSION['admin_user_class_dir']  = ($_REQUEST['dir']=='DESC'?'DESC':'ASC');
    }

    // first see if direction must be changed

    if ( isset($_REQUEST['chdir']) && ($_REQUEST['chdir']=='yes') )
    {
        if     ($_SESSION['admin_user_class_dir'] == 'ASC')  {$_SESSION['admin_user_class_dir']='DESC';}
        elseif ($_SESSION['admin_user_class_dir'] == 'DESC') {$_SESSION['admin_user_class_dir']='ASC';}
    }
    elseif ( !isset($_SESSION['admin_user_class_dir']) )
    {
        $_SESSION['admin_user_class_dir'] = 'DESC';
    }

    // deal with REORDER

    if ( isset($_REQUEST['order_crit']) )
    {
        $_SESSION['admin_user_class_order_crit'] = $_REQUEST['order_crit'];
        if ($_REQUEST['order_crit']=='user_id')
        {
            $_SESSION['admin_user_class_order_crit'] = 'U`.`user_id';
        }
    }

    if ( ! isset($_SESSION['admin_user_class_order_crit']))
    {
        $_SESSION['admin_user_class_dir'] = 'ASC';
        $_SESSION['admin_user_class_order_crit'] = 'nom'; 
    }

    $toAdd = " ORDER BY `".$_SESSION['admin_user_class_order_crit'] . "` " . $_SESSION['admin_user_class_dir'];
    $sql.=$toAdd;

    //Build pager with SQL request
    if (!isset($_REQUEST['offset'])) $offset = '0';
    else                             $offset = $_REQUEST['offset'];

    $myPager = new claro_sql_pager($sql, $offset, get_conf('userPerPage', 20) );
    $resultList = $myPager->get_result_list();

}

// PREPARE DISPLAY

// Deal with interbredcrumps
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Classes'), get_path('rootAdminWeb'). 'admin_class.php' );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Class members');

$cmdList[] = '<a class="claroCmd" href="' . get_path('clarolineRepositoryWeb') . 'admin/admin_class_register.php'
.             '?class_id=' . $classinfo['id'] . '">'
.             '<img src="' . get_icon_url('enroll') . '" /> '
.             get_lang('Register a user for this class') . '</a>'
;
$cmdList[] = '<a class="claroCmd" href="' . get_path('clarolineRepositoryWeb').'auth/courses.php'
.             '?cmd=rqReg&amp;fromAdmin=class&amp;class_id='.$class_id.'">'
.             '<img src="' . get_icon_url('enroll') . '" /> '
.             get_lang('Register class for course')
.             '</a>'
;

$cmdList[] = '<a class="claroCmd" href="' . get_path('clarolineRepositoryWeb').'user/AddCSVusers.php'
.             '?AddType=adminClassTool&amp;class_id='.$class_id.'">'
.             '<img src="' . get_icon_url('import_list') . '" /> '
.             get_lang('Add a user list in class')
.             '</a>'
;
if ( !empty($resultList) )
{
    $cmdList[] = '<a class="claroCmd" href="'.$_SERVER['PHP_SELF'] . '?cmd=unsubscribe_all&amp;class_id='.$class_id.'"'
    .    ' onclick="if (confirm(\'' . clean_str_for_javascript(get_lang('Unregister all users ?')) . '\')){return true;}else{return false;}">'
    .             '<img src="' . get_icon_url('deluser') . '" /> '
    .             get_lang('Unregister all users')
    .             '</a>'
    ;
}
else
{
    $cmdList[] = '<span class="claroCmdDisabled" >'
    .    '<img src="' . get_icon_url('deluser') . '" alt="" />'
    .    get_lang('Unregister all users')
    .    '</span>'
    ;
}


//------------------------------------
// Display section
//------------------------------------

// Dispay Header
include get_path('incRepositorySys') . '/claro_init_header.inc.php';

if ( !empty($class_id) )
{
    echo claro_html_tool_title($nameTools . ' : ' . $classinfo['name']);

    if (isset($dialogBox))  echo claro_html_message_box($dialogBox). '<br />';

    // Display menu
    echo '<p>' . claro_html_menu_horizontal($cmdList) . '</p>' ;

    // Display pager
    echo $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'].'?class_id='.$class_id);

    //Display search form
    echo '<div style="text-align:right"><form action="' . $_SERVER['PHP_SELF'] . '">' . "\n"
    .    '<label for="search">' . get_lang('Make new search') . '  </label>' . "\n"
    .    '<input type="text" value="' . htmlspecialchars($search).'" name="search" id="search" />' . "\n"
    .    '<input type="submit" value=" ' . get_lang('Ok') . ' " />' . "\n"
    .    '<input type="hidden" name="class_id" value="'.$class_id. '" />' . "\n"
    .    '</form></div>' . "\n"
    ;
    
    // Display list of users

    // start table...
    echo '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'
    .    '<thead>'
    .    '<tr class="headerX" align="center" valign="top">'
    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=user_id&amp;chdir=yes">' . get_lang('User id') . '</a></th>'
    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=nom&amp;chdir=yes">' . get_lang('Last name') . '</a></th>'
    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=prenom&amp;chdir=yes">' . get_lang('First name') . '</a></th>'
    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=officialCode&amp;chdir=yes">' . get_lang('Administrative code') . '</a></th>'
    .    '<th>' . get_lang('Email') . '</th>'
    .    '<th>' . get_lang('Unregister from class') . '</th>'
    .    '</tr>'
    .    '</thead>'
    .    '<tbody>'
    ;

    // Start the list of users...

    foreach($resultList as $list)
    {
         $list['officialCode'] = (isset($list['officialCode']) ? $list['officialCode'] :' - ');

         echo '<tr>'
         .    '<td align="center" >' . $list['user_id']      . '</td>'
         .    '<td align="left" >'   . $list['nom']          . '</td>'
         .    '<td align="left" >'   . $list['prenom']       . '</td>'
         .    '<td align="center">'  . $list['officialCode'] . '</td>'
         .    '<td align="left">'    . $list['email']        . '</td>'
         .    '<td align="center">'  ."\n"
         .    '<a href="'.$_SERVER['PHP_SELF']
         .    '?cmd=unsubscribe&amp;offset='.$offset.'&amp;user_id='.$list['user_id'].'&amp;class_id='.$class_id.'" '
         .    ' onclick="return confirmationUnReg(\''.clean_str_for_javascript($list['prenom'] . ' ' . $list['nom']).'\');">' . "\n"
         .    '<img src="' . get_icon_url('unenroll') . '" alt="" />' . "\n"
         .    '</a>' . "\n"
         .    '</td></tr>' . "\n"
         ;
    }

    // end display users table

    if ( empty($resultList) )
    {
        echo '<tr>'
        .    '<td colspan="6" align="center">'
        .    get_lang('No user to display')
        .    '<br />'
        .    '<a href="' . get_path('clarolineRepositoryWeb') . 'admin/admin_class.php">'
        .    get_lang('Back')
        .    '</a>'
        .    '</td>'
        .    '</tr>'
        ;
    }

    echo '</tbody>'."\n"
    .    '</table>'."\n"
    ;

    //Pager

    echo $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'].'?class_id='.$class_id);
}
else
{
    echo claro_html_message_box(get_lang('Class not found'));
}

include get_path('incRepositorySys') . '/claro_init_footer.inc.php';

?>
