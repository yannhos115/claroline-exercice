<?php // $Id$
/**
 * CLAROLINE
 *
 * This script list member of campus and  propose to subscribe it to the given course
 *
 * @version 1.8 $Revision$
 *
 * @copyright 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/CLADMIN/
 *
 * @author Claro Team <cvs@claroline.net>
 *
 * @package CLUSR
 *
 */

$cidReset = TRUE; $gidReset = TRUE; $tidReset = TRUE;

// initialisation of global variables and used libraries
require '../inc/claro_init_global.inc.php';

include_once get_path('incRepositorySys') . '/lib/pager.lib.php';
include_once get_path('incRepositorySys') . '/lib/course_user.lib.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

if ((isset($_REQUEST['cidToEdit']) && $_REQUEST['cidToEdit']=='') || !isset($_REQUEST['cidToEdit']))
{
    unset($_REQUEST['cidToEdit']);
    $dialogBox = 'ERROR : NO COURSE SET!!!';

}
else
{
   $cidToEdit = $_REQUEST['cidToEdit'];
}
$userPerPage = 20; // numbers of user to display on the same page

//get needed parameter from URL

$user_id = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null ;

if ($cidToEdit=='') { $dialogBox ='ERROR : NO USER SET!!!'; }

// Deal with interbredcrumps
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Enroll a user');

//TABLES
$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_user          = $tbl_mdb_names['user'            ];
$tbl_courses       = $tbl_mdb_names['course'          ];
$tbl_course_user   = $tbl_mdb_names['rel_course_user' ];

// See SESSION variables used for reorder criteria :

if (isset($_REQUEST['dir']))       {$_SESSION['admin_register_dir']        = $_REQUEST['dir'];       }
if (isset($_REQUEST['order_crit'])){$_SESSION['admin_register_order_crit'] = $_REQUEST['order_crit'];}

//------------------------------------
// Execute COMMAND section
//------------------------------------

$cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : null;

switch ( $cmd )
{
    case 'sub' : //execute subscription command...

        $done = user_add_to_course($user_id, $cidToEdit, false, false, false);

        // Set status requested

        if ( $_REQUEST['isCourseManager'] )     // ... as teacher
        {
            $properties['isCourseManager'] = 1;
            $properties['tutor']  = 1;
        }
        else // ... as student
        {
            $properties['isCourseManager'] = 0;
            $properties['tutor']  = 0;
        }

        user_set_course_properties($user_id, $cidToEdit, $properties);

        //set dialogbox message

        if ( $done )
        {
           $dialogBox = get_lang('The user has been enroled to the course');
        }
        break;

}

//build and call DB to get info about current course (for title) if needed :

$courseData = claro_get_course_data($cidToEdit);

//----------------------------------
// Build query and find info in db
//----------------------------------

$sql = "
SELECT
    U.nom, U.prenom, U.`user_id` AS ID,
    CU.*,
    CU.`user_id` AS Register
FROM  `" . $tbl_user . "` AS U";

$toAdd = "
LEFT JOIN `" . $tbl_course_user . "` AS CU
    ON             CU.`user_id`=U.`user_id`
            AND CU.`code_cours` = '" . claro_sql_escape($cidToEdit) . "'
        ";

$sql.=$toAdd;

//deal with LETTER classification call

if (isset($_GET['letter']))
{
    $toAdd = "
            AND U.`nom` LIKE '" . claro_sql_escape($_GET['letter']) . "%' ";
    $sql .= $toAdd;
}

//deal with KEY WORDS classification call

if ( isset( $_REQUEST['search'] ) && $_REQUEST['search'] != '' )
{
    $toAdd = " WHERE (U.`nom` LIKE '" . claro_sql_escape($_REQUEST['search']) . "%'
              OR U.`username` LIKE '" . claro_sql_escape($_REQUEST['search']) . "%'
              OR U.`prenom` LIKE '" . claro_sql_escape($_REQUEST['search']) . "%') " ;

    $sql .= $toAdd;
}

// deal with REORDER

//first see is direction must be changed

if ( isset( $_REQUEST['chdir'] ) && ( $_REQUEST['chdir'] == 'yes' ) )
{
    if ( $_SESSION['admin_register_dir'] == 'ASC' )
    {
        $_SESSION['admin_register_dir'] = 'DESC';
    }
    else
    {
        $_SESSION['admin_register_dir'] = 'ASC';
    }
}

if (isset($_SESSION['admin_register_order_crit']))
{
    if ($_SESSION['admin_register_order_crit'] == 'user_id' )
    {
        $toAdd = " ORDER BY `U`.`user_id` " . $_SESSION['admin_register_dir'];
    }
    else
    {
        $toAdd = " ORDER BY `" . $_SESSION['admin_register_order_crit'] . "` " . $_SESSION['admin_register_dir'];
    }
    $sql .= $toAdd;
}

//Build pager with SQL request

if ( !isset( $_REQUEST['offset'] ) )
{
    $offset = '0';
}
else
{
    $offset = $_REQUEST['offset'];
}

$myPager = new claro_sql_pager($sql, $offset, $userPerPage);
$userList = $myPager->get_result_list();

$isSearched = '';

//get the search keyword, if any

if ( !isset( $_REQUEST['search']) )
{
   $search = '';
}
else
{
   $search = $_REQUEST['search'];
}


$addToURL = ( isset($_REQUEST['addToURL']) ? $_REQUEST['addToURL'] : '');

$nameTools .= ' : ' . $courseData['name'];

// search form

if ( isset( $search ) && $search != '' )         { $isSearched .= $search . '* '; }
if (($isSearched == '') || !isset($isSearched) ) { $title = ''; }
                                            else { $title = get_lang('Search on') . ' : '; }

//Pager

if (isset($_REQUEST['order_crit']))
{
    $addToURL = '&amp;order_crit=' . $_SESSION['admin_register_order_crit']
              . '&amp;dir=' . $_SESSION['admin_register_dir']
              ;
}

//------------------------------------
// DISPLAY
//------------------------------------

// Display tool title

//Header
include get_path('incRepositorySys') . '/claro_init_header.inc.php';

echo claro_html_tool_title( $nameTools );

// Display Forms or dialog box(if needed)

if( isset($dialogBox) ) echo claro_html_message_box($dialogBox);


echo '<table width="100%" class="claroTableForm" >'
.    '<tr>'
.    '<td align="left">' . "\n"
.    '<b>' . $title . '</b>' . "\n"
.    '<small>' . "\n"
.    $isSearched . "\n"
.    '</small>' . "\n"
.    '</td>' . "\n"
.    '<td align="right">' . "\n"
.    '<form action="' . $_SERVER['PHP_SELF'] . '" >' . "\n"
.    '<label for="search">' . get_lang('Make search') . '</label> :' . "\n"
.    '<input type="text" value="' . htmlspecialchars($search) . '" name="search" id="search" />' . "\n"
.    '<input type="submit" value=" ' . get_lang('Ok') . ' "/>' . "\n"
.    '<input type="hidden" name="newsearch" value="yes" />' . "\n"
.    '<input type="hidden" name="cidToEdit" value="' . $cidToEdit . '" />' . "\n"
.    '</form>' . "\n"
.    '</td>' . "\n"
.    '</tr>' . "\n"
.    '</table>' . "\n"
//TOOL LINKS
.    '<a class="claroCmd" href="admincourseusers.php?cidToEdit='.$cidToEdit.'">'
.    get_lang('Course members')
.    '</a><br /><br />'
.    $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'] . '?cidToEdit=' . $cidToEdit . $addToURL)

// Display list of users
// start table...
//columns titles...

.    '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">' . "\n"
.    '<thead>' . "\n"
.    '<tr class="headerX" align="center" valign="top">' . "\n"
.    '<th>'

.    '<a href="' . $_SERVER['PHP_SELF']
.    '?order_crit=user_id&amp;chdir=yes&amp;search=' . $search . '&amp;cidToEdit=' . $cidToEdit . '">'
.    get_lang('User id')
.    '</a>'
.    '</th>' . "\n"

.    '<th>'
.    '<a href="' . $_SERVER['PHP_SELF'] . '?order_crit=nom'
.    '&amp;chdir=yes&amp;search=' . $search
.    '&amp;cidToEdit=' . $cidToEdit . '">' . get_lang('Last name') . '</a>'
.    '</th>' . "\n"

.    '<th>'
.    '<a href="' . $_SERVER['PHP_SELF']
.    '?order_crit=prenom'
.    '&amp;chdir=yes'
.    '&amp;search=' . $search
.    '&amp;cidToEdit=' . $cidToEdit . '">'
.    get_lang('First name')
.    '</a>'
.    '</th>' . "\n"

.    '<th>' . get_lang('Enrol as student') . '</th>' . "\n"
.    '<th>' . get_lang('Enrol as course manager') . '</th>' . "\n"
.    '</tr>' . "\n"
.    '</thead>' . "\n"
.    '<tbody>'
;

// Start the list of users...
$addToURL = ( isset($_REQUEST['addToURL']) ? $_REQUEST['addToURL'] : '');

if (isset($_REQUEST['order_crit']))
{
    $addToURL = '&amp;order_crit=' . $_REQUEST['order_crit'];
}
if (isset($_REQUEST['offset']))
{
    $addToURL = '&amp;offset=' . $_REQUEST['offset'];
}
foreach($userList as $user)
{
    if (isset($_REQUEST['search'])&& ($_REQUEST['search'] != ''))
    {
        $user['nom'] = eregi_replace("^(".$_REQUEST['search'].")",'<b>\\1</b>', $user['nom']);
        $user['prenom'] = eregi_replace("^(".$_REQUEST['search'].")","<b>\\1</b>", $user['prenom']);
    }

    echo '<tr>' . "\n"
    //  Id
    .   '<td align="center">'
    .   $user['ID']
    .   '</td>'."\n"
    // name
    .   '<td align="left">'
    .   $user['nom']
    .   '</td>'
    //  First name
    .   '<td align="left">'
    .   $user['prenom']
    .   '</td>'
    ;

    if ( !is_null($user['isCourseManager']) && $user['isCourseManager'] == 0 )  // user is already enrolled but as student
    {
        // already enrolled as student
        echo '<td align="center" >' . "\n"
        .    '<small>'
        .    get_lang('Already enroled')
        .    '</small>'
        .    '</td>' . "\n"
        ;

    }
    else
    {
        // Register as user
        echo '<td align="center">' . "\n"
            .'<a href="' . $_SERVER['PHP_SELF']
            .'?cidToEdit=' . $cidToEdit
            .'&amp;cmd=sub&amp;search='.$search
            .'&amp;user_id=' . $user['ID']
            .'&amp;isCourseManager=0' . $addToURL . '">'
            .'<img src="' . get_icon_url('enroll') . '" alt="' . get_lang('Register user') . '" />' . "\n"
            .'</a>'
            .'</td>'."\n"
            ;
    }

    if ( !is_null($user['isCourseManager']) && $user['isCourseManager'] == 1 )  // user is not enrolled
    {
        // already enrolled as teacher
        echo '<td align="center" >'."\n"
        .    '<small>'
        .    get_lang('Already enroled')
        .    '</small>'
        .    '</td>'."\n"
        ;
    }
    else
    {
        //register as teacher
        echo '<td align="center">' . "\n"
        .    '<a href="' . $_SERVER['PHP_SELF']
        .    '?cidToEdit=' . $cidToEdit
        .    '&amp;cmd=sub&amp;search='.$search
        .    '&amp;user_id=' . $user['ID']
        .    '&amp;isCourseManager=1' . $addToURL . '">'
        .    '<img src="' . get_icon_url('enroll') . '" alt="' . get_lang('Register user') . '" />'
        .    '</a>' . "\n"
        .    '</td>' . "\n"
        ;
    }
    echo '</tr>';
}
// end display users table
echo '</tbody></table>'
.    $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'] . '?cidToEdit=' . $cidToEdit . $addToURL);

include get_path('incRepositorySys') . '/claro_init_footer.inc.php';
?>