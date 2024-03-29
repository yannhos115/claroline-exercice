<?php // $Id$
/**
 * CLAROLINE
 *
  * This tool edit status of user in a course
 * Strangly, the is nothing to edit role and courseTutor status
 *
 * @version 1.9 $Revision$
 * @copyright 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/index.php/CLUSR
 *
 * @package CLUSR
 * @package CLCOURSES
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */

$cidReset = TRUE;$gidReset = TRUE;$tidReset = TRUE;

require '../inc/claro_init_global.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';

include claro_get_conf_repository() . 'user_profile.conf.php'; // find this file to modify values.

// used tables
$tbl_mdb_names = claro_sql_get_main_tbl();

// deal with session variables (must unset variables if come back from enroll script)
unset($_SESSION['userEdit']);

$nameTools=get_lang('User course settings');
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );


// see which user we are working with ...

if ( isset($_REQUEST['uidToEdit']) && isset($_REQUEST['cidToEdit']) )
{
    $uidToEdit = $_REQUEST['uidToEdit'];
    $cidToEdit = $_REQUEST['cidToEdit'];
}
else
{
    claro_die('Missing parameters');
}

//------------------------------------
// Execute COMMAND section
//------------------------------------


//Display "form and info" about the user

$ccfrom = isset($_REQUEST['ccfrom'])?$_REQUEST['ccfrom']:'';
$cfrom  = isset($_REQUEST['cfrom'])?$_REQUEST['cfrom']:'';

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null ;

switch ($cmd)
{
    case 'exUpdateCourseUserProperties' :

        if ( isset($_REQUEST['profileId']) )
        {
            $properties['profileId'] = $_REQUEST['profileId'];

            if ( claro_get_profile_label($properties['profileId']) == 'manager' )
            {
                $dialogBox = get_lang('User is now course manager');
            }
            else
            {
                $dialogBox = get_lang('User is now student for this course');
            }
        }

        if ( isset($_REQUEST['isTutor']) )
        {
            $properties['tutor'] = (int) $_REQUEST['isTutor'];
        }
        else
        {
            $properties['tutor'] = 0 ;
        }

        if ( isset($_REQUEST['role']) )
        {
            $properties['role'] = trim($_REQUEST['role']);
        }

        $done = user_set_course_properties($uidToEdit, $cidToEdit, $properties);

        if ( ! $done )
        {
            $dialogBox = get_lang('No change applied');
        }

    break;
}

//------------------------------------
// FIND GLOBAL INFO SECTION
//------------------------------------

if ( isset($uidToEdit) )
{
    // get course user info
    $courseUserProperties = course_user_get_properties($uidToEdit, $cidToEdit);
}

//------------------------------------
// PREPARE DISPLAY
//------------------------------------

// javascript confirm pop up declaration
$htmlHeadXtra[] =
            "<script>
            function confirmationUnReg (name)
            {
                if (confirm(\"".clean_str_for_javascript(get_lang('Are you sure you want to unregister'))." \"+ name + \"? \"))
                    {return true;}
                else
                    {return false;}
            }
            </script>";

$displayBackToCU = false;
$displayBackToUC = false;
if ( 'culist'== $ccfrom )//coming from courseuser list
{
    $displayBackToCU = TRUE;
}
elseif ('uclist'== $ccfrom)//coming from usercourse list
{
    $displayBackToUC = TRUE;
}

$cmd_menu[] = '<a class="claroCmd" href="adminuserunregistered.php'
.             '?cidToEdit=' . $cidToEdit
.             '&amp;cmd=UnReg'
.             '&amp;uidToEdit=' . $uidToEdit . '" '
.             ' onclick="return confirmationUnReg(\'' . clean_str_for_javascript(htmlspecialchars($courseUserProperties['firstName']) . ' ' . htmlspecialchars($courseUserProperties['lastName'])) . '\');">'
.             get_lang('Unsubscribe')
.             '</a>'
;

$cmd_menu[] = '<a class="claroCmd" href="adminprofile.php'
.             '?uidToEdit=' . $uidToEdit . '">'
.             get_lang('User settings')
.             '</a>'
;

//link to go back to list : depend where we come from...

if ( $displayBackToCU )//coming from courseuser list
{
    $cmd_menu[] = '<a class="claroCmd" href="admincourseusers.php'
    .             '?cidToEdit=' . $cidToEdit
    .             '&amp;uidToEdit=' . $uidToEdit . '">'
    .             get_lang('Back to list')
    .             '</a> ' ;
}
elseif ( $displayBackToUC )//coming from usercourse list
{
    $cmd_menu[] = '<a class="claroCmd" href="adminusercourses.php'
    .             '?cidToEdit=' . $cidToEdit
    .             '&amp;uidToEdit=' . $uidToEdit . '">'
    .             get_lang('Back to list')
    .             '</a> ' ;
}

//------------------------------------
// DISPLAY
//------------------------------------

include get_path('incRepositorySys') . '/claro_init_header.inc.php';

// Display tool title

echo claro_html_tool_title( array( 'mainTitle' =>$nameTools
                                 , 'subTitle' => get_lang('Course') . ' : '
                                              .  htmlspecialchars($courseUserProperties['courseName'])
                                              .  '<br />'
                                              .  get_lang('User') . ' : '
                                              .  htmlspecialchars($courseUserProperties['firstName'])
                                              .  ' '
                                              .  htmlspecialchars($courseUserProperties['lastName'])
                                 )
                          );

// Display Forms or dialog box(if needed)

if ( isset($dialogBox) )
{
    echo claro_html_message_box($dialogBox);
}

$hidden_param = array( 'uidToEdit' => $uidToEdit,
                       'cidToEdit' => $cidToEdit,
                       'cfrom' => $cfrom,
                       'ccfrom' => $ccfrom);

echo course_user_html_form ( $courseUserProperties, $cidToEdit, $uidToEdit, $hidden_param )
.    '<p>'
.    claro_html_menu_horizontal($cmd_menu)
.    '</p>'
;

include get_path('incRepositorySys') . '/claro_init_footer.inc.php';
?>
