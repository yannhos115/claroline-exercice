<?php // $Id$
/**
 * CLAROLINE
 *
 * this tool manage the
 *
 * @version 1.8 $Revision$
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Guillaume Lederer <lederer@cerdecam.be>
 * @author Christophe Gesch� <moosh@claroline.net>
 */

require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';

include claro_get_conf_repository() . 'user_profile.conf.php'; // find this file to modify values.

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

//bredcrump
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Class registered');

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;
$class_id = isset($_REQUEST['class_id'])?$_REQUEST['class_id']:0;
$course_id = isset($_REQUEST['course_id'])?$_REQUEST['course_id']:null;

//------------------------------------
// Execute COMMAND section
//------------------------------------

if (isset($cmd) && claro_is_platform_admin())
{
    if ($cmd == 'exReg')
    {
        $resultLog = register_class_to_course($class_id,$course_id);
        $outputResultLog = '';

        if ( isset($resultLog['OK']) && is_array($resultLog['OK']) )
        {
            foreach($resultLog['OK'] as $thisUser)
            {
                $outputResultLog .= '[<font color="green">OK</font>] ' . get_lang('<i>%firstname %lastname</i> has been sucessfully registered to the course',array('%firstname'=>$thisUser['firstname'], '%lastname'=>$thisUser['lastname'])) . '<br />';
            }
        }

        if ( isset($resultLog['KO']) && is_array($resultLog['KO']) )
        {
            foreach($resultLog['KO'] as $thisUser)
            {
                $outputResultLog .= '[<font color="red">KO</font>] ' . get_lang('<i>%firstname %lastname</i> has not been sucessfully registered to the course',array('%firstname'=>$thisUser['firstname'], '%lastname'=>$thisUser['lastname'])) . '<br />';
            }
        }
    }

}

/**
 * PREPARE DISPLAY
 */

$classinfo = class_get_properties($class_id);

if ( !empty($outputResultLog) ) $dialogBox = $outputResultLog;
$cmdList[] =  '<a class="claroCmd" href="index.php">' . get_lang('Back to administration page') . '</a>';
$cmdList[] =  '<a class="claroCmd" href="' . 'admin_class_user.php?class_id=' . $classinfo['id'] . '">' . get_lang('Back to class members') . '</a>';
$cmdList[] =  '<a class="claroCmd" href="' . get_path('clarolineRepositoryWeb') . 'auth/courses.php?cmd=rqReg&amp;fromAdmin=class' . '">' . get_lang('Register class for course') . '</a>';

/**
 * DISPLAY
 */
include get_path('incRepositorySys') . '/claro_init_header.inc.php';

echo claro_html_tool_title(get_lang('Class registered') . ' : ' . $classinfo['name']);

if ( !empty($dialogBox) ) echo claro_html_message_box($dialogBox);

echo '<p>'
.    claro_html_menu_horizontal($cmdList)
.    '</p>'
;

include get_path('incRepositorySys') . '/claro_init_footer.inc.php';

?>