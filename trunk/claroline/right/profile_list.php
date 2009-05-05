<?php // $Id$
/**
 * CLAROLINE
 *
 * This tool manage profile of the course
 *
 * @version 1.8 $Revision$
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author claroline Team <cvs@claroline.net>
 *
 * @package RIGHT
 *
 */

require '../inc/claro_init_global.inc.php';

$nameTools = get_lang('Course profile list');

$dialogBox = '';
$tidReset = true;

if ( ! claro_is_in_a_course() || ! claro_is_user_authenticated()) claro_disp_auth_form(true);

$is_allowedToEdit = claro_is_course_manager();

if ( ! $is_allowedToEdit )
{
    claro_die(get_lang('Not allowed'));
}

require_once get_path('incRepositorySys') . '/lib/right/profile.class.php' ;
require_once get_path('incRepositorySys') . '/lib/pager.lib.php';

// Main section


// Build profile list

$itemPerPage = 10;

$tbl_mdb_names = claro_sql_get_main_tbl();
$tblProfile = $tbl_mdb_names['right_profile'];

$sql = " SELECT profile_id as id, name, description, locked, required
         FROM `" . $tblProfile . "`
         WHERE type = 'COURSE' ";

$offset = (isset($_REQUEST['offset']) && !empty($_REQUEST['offset']) ) ? $_REQUEST['offset'] : 0;
$profilePager = new claro_sql_pager($sql,$offset, $itemPerPage);
$profileList = $profilePager->get_result_list();

// Display section

include get_path('incRepositorySys') . '/claro_init_header.inc.php';

echo claro_html_tool_title($nameTools);

// Display table header

echo '<table class="claroTable emphaseLine" >' . "\n"
    . '<thead>' . "\n"
    . '<tr class="headerX">' . "\n"
    . '<th>' . get_lang('Name') . '</th>' . "\n"
    . '<th>' . get_lang('Rights') .'</th>' . "\n"
    . '</tr>' . "\n"
    . '</thead>' . "\n"
    . '<tbody>' ;

foreach ( $profileList as $thisProfile )
{
    echo '<tr align="center">' . "\n"
        . '<td align="left">' . get_lang($thisProfile['name']) ;

    if ( $thisProfile['locked'] == '1' )
    {
        echo '&nbsp;<img src="' . get_icon_url('locked') . '" alt="' . get_lang('Lock') . '" />';
    }

    echo '<br />' . "\n"
    .    '<em>' . get_lang($thisProfile['description']) . '</em>' . "\n"
    .    '<td>' . "\n"
    .    '<a href="profile.php?cmd=rqEdit&display_profile='. $thisProfile['id'].'">' 
    .    '<img src="' .  get_icon_url('settings') . '" alt="' . get_lang('Rights') . '" />' . "\n"
    .    '</a>' . "\n" 
    .    '</td>' . "\n" 
    .    '</tr>' . "\n\n"
    ;
}

echo '</tbody></table>';


include get_path('incRepositorySys') . '/claro_init_footer.inc.php';

?>
