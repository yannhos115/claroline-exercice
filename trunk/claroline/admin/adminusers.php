<?php //$Id$
/**
 * CLAROLINE
 * @version 1.9 $Revision$
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package ADMIN
 *
 * @author Guillaume Lederer <lederer@claroline.net>
 */
$cidReset = TRUE; $gidReset = TRUE; $tidReset = TRUE;

require '../inc/claro_init_global.inc.php';

$userPerPage = get_conf('userPerPage',20); // numbers of user to display on the same page

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

require_once get_path('incRepositorySys') . '/lib/pager.lib.php';
require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';

// CHECK INCOMING DATAS
if ((isset($_REQUEST['cidToEdit'])) && ($_REQUEST['cidToEdit']=='')) {unset($_REQUEST['cidToEdit']);}

$validCmdList = array('delete');
$cmd = (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'],$validCmdList)? $_REQUEST['cmd'] : null);
$userIdReq = (int) (isset($_REQUEST['user_id']) ? $_REQUEST['user_id']: null);
// USED SESSION VARIABLES
// clean session if needed

if (isset($_REQUEST['newsearch']) && $_REQUEST['newsearch'] == 'yes')
{
    unset($_SESSION['admin_user_search'   ]);
    unset($_SESSION['admin_user_firstName']);
    unset($_SESSION['admin_user_lastName' ]);
    unset($_SESSION['admin_user_userName' ]);
    unset($_SESSION['admin_user_officialCode' ]);
    unset($_SESSION['admin_user_mail'     ]);
    unset($_SESSION['admin_user_action'   ]);
    unset($_SESSION['admin_order_crit'    ]);
}

// deal with session variables for search criteria, it depends where we come from :
// 1 ) we must be able to get back to the list that concerned the criteria we previously used (with out re entering them)
// 2 ) we must be able to arrive with new critera for a new search.

if (isset($_REQUEST['search'    ])) $_SESSION['admin_user_search'    ] = trim($_REQUEST['search'    ]);
if (isset($_REQUEST['firstName' ])) $_SESSION['admin_user_firstName' ] = trim($_REQUEST['firstName' ]);
if (isset($_REQUEST['lastName'  ])) $_SESSION['admin_user_lastName'  ] = trim($_REQUEST['lastName'  ]);
if (isset($_REQUEST['userName'  ])) $_SESSION['admin_user_userName'  ] = trim($_REQUEST['userName'  ]);
if (isset($_REQUEST['officialCode'  ])) $_SESSION['admin_user_officialCode'  ] = trim($_REQUEST['officialCode'  ]);
if (isset($_REQUEST['mail'      ])) $_SESSION['admin_user_mail'      ] = trim($_REQUEST['mail'      ]);
if (isset($_REQUEST['action'    ])) $_SESSION['admin_user_action'    ] = trim($_REQUEST['action'    ]);

if (isset($_REQUEST['order_crit'])) $_SESSION['admin_user_order_crit'] = trim($_REQUEST['order_crit']);
if (isset($_REQUEST['dir'       ])) $_SESSION['admin_user_dir'       ] = ($_REQUEST['dir'] == 'DESC' ? 'DESC' : 'ASC' );
$addToURL = ( isset($_REQUEST['addToURL']) ? $_REQUEST['addToURL'] : '');


//TABLES
//declare needed tables

// Deal with interbredcrumps

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('User list');

//TABLES

//------------------------------------
// Execute COMMAND section
//------------------------------------
switch ( $cmd )
{
    case 'delete' :
    {
        $dialogBox = ( user_delete($userIdReq) ? get_lang('Deletion of the user was done sucessfully') : get_lang('You can not change your own settings!'));
    }   break;
}
$searchInfo = prepare_search();

$isSearched    = $searchInfo['isSearched'];
$addtoAdvanced = $searchInfo['addtoAdvanced'];

if(count($searchInfo['isSearched']) )
{
    $isSearched = array_map( 'strip_tags', $isSearched );
    $isSearchedHTML = implode('<br />', $isSearched);
}
else
{
    $isSearchedHTML = '';
}

//get the search keyword, if any
$search  = (isset($_REQUEST['search']) ? $_REQUEST['search'] : '');

$sql = get_sql_filtered_user_list();

$offset       = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0 ;
$myPager      = new claro_sql_pager($sql, $offset, $userPerPage);

if ( array_key_exists( 'sort', $_GET ) )
{
    $dir = array_key_exists( 'dir', $_GET ) && $_GET['dir'] == SORT_DESC
        ? SORT_DESC
        : SORT_ASC
        ;

    $sortKey = strip_tags( $_GET['sort'] );
        
    $myPager->add_sort_key( $sortKey, $dir );
}

$defaultSortKeyList = array ('isPlatformAdmin' => SORT_DESC,
                             'name'          => SORT_ASC,
                             'firstName'       => SORT_ASC);

foreach($defaultSortKeyList as $thisSortKey => $thisSortDir)
{
    $myPager->add_sort_key( $thisSortKey, $thisSortDir);
}

$userList = $myPager->get_result_list();
if (is_array($userList))
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    foreach ($userList as $userKey => $user)
    {
        $sql ="SELECT count(DISTINCT code_cours) AS qty_course
                 FROM `" . $tbl_mdb_names['rel_course_user'] . "`
                 WHERE user_id = '". (int) $user['user_id'] ."'
                 GROUP BY user_id";
        $userList[$userKey]['qty_course'] = (int) claro_sql_query_get_single_value($sql);
    }
}

$userGrid = array();
if (is_array($userList))
foreach ($userList as $userKey => $user)
{

    $userGrid[$userKey]['user_id']   = $user['user_id'];
    $userGrid[$userKey]['name']      = $user['name'];
    $userGrid[$userKey]['firstname'] = $user['firstname'];
    $userEmailLabel=null;
    if ( !empty($_SESSION['admin_user_search']) )
    {
        $bold_search = str_replace('*','.*',$_SESSION['admin_user_search']);

        $userGrid[$userKey]['name'] = eregi_replace('(' . $bold_search . ')' , '<b>\\1</b>', $user['name']);
        $userGrid[$userKey]['firstname'] = eregi_replace('(' . $bold_search . ')' , '<b>\\1</b>', $user['firstname']);
        $userEmailLabel  = eregi_replace('(' . $bold_search . ')', '<b>\\1</b>' , $user['email']);
    }
    
    $userGrid[$userKey]['officialCode'] = empty($user['officialCode']) ? ' - ' : $user['officialCode'];
    $userGrid[$userKey]['email'] = claro_html_mailTo($user['email'], $userEmailLabel);

    $userGrid[$userKey]['isCourseCreator'] =  ( $user['isCourseCreator']?get_lang('Course creator'):get_lang('User'));

    if ( $user['isPlatformAdmin'] )
    {
        $userGrid[$userKey]['isCourseCreator'] .= '<br /><span class="highlight">' . get_lang('Administrator').'</span>';
    }
    $userGrid[$userKey]['settings'] = '<a href="adminprofile.php'
    .                                 '?uidToEdit=' . $user['user_id']
    .                                 '&amp;cfrom=ulist' . $addToURL . '">'
    .                                 '<img src="' . get_icon_url('usersetting') . '" alt="' . get_lang('User settings') . '" />'
    .    '</a>';



    $userGrid[$userKey]['qty_course'] = '<a href="adminusercourses.php?uidToEdit=' . $user['user_id']
    .                                   '&amp;cfrom=ulist' . $addToURL . '">' . "\n"
    .                                   get_lang('%nb course(s)', array('%nb' => $user['qty_course'])) . "\n"
    .                                   '</a>' . "\n"
    ;

    $userGrid[$userKey]['delete'] = '<a href="' . $_SERVER['PHP_SELF']
    .                               '?cmd=delete&amp;user_id=' . $user['user_id']
    .                               '&amp;offset=' . $offset . $addToURL . '" '
    .                               ' onclick="return confirmation(\'' . clean_str_for_javascript(' ' . $user['firstname'] . ' ' . $user['name']).'\');">' . "\n"
    .                               '<img src="' . get_icon_url('deluser') . '" alt="' . get_lang('Delete') . '" />' . "\n"
    .                               '</a> '."\n"
    ;

}
$sortUrlList = $myPager->get_sort_url_list($_SERVER['PHP_SELF']);
$userDataGrid = new claro_datagrid();
$userDataGrid->set_grid($userGrid);
$userDataGrid->set_colHead('name') ;
$userDataGrid->set_colTitleList(array (
                 'user_id'=>'<a href="' . $sortUrlList['user_id'] . '">' . get_lang('Numero') . '</a>'
                ,'name'=>'<a href="' . $sortUrlList['name'] . '">' . get_lang('Last name') . '</a>'
                ,'firstname'=>'<a href="' . $sortUrlList['firstname'] . '">' . get_lang('First name') . '</a>'
                ,'officialCode'=>'<a href="' . $sortUrlList['officialCode'] . '">' . get_lang('Administrative code') . '</a>'
                ,'email'=>'<a href="' . $sortUrlList['email'] . '">' . get_lang('Email') . '</a>'
                ,'isCourseCreator'=>'<a href="' . $sortUrlList['isCourseCreator'] . '">' . get_lang('Status') . '</a>'
                ,'settings'=> get_lang('User settings')
                ,'qty_course' => get_lang('Courses')
                ,'delete'=>get_lang('Delete') ));

if ( count($userGrid)==0 )
{
    $userDataGrid->set_noRowMessage( '<center>'.get_lang('No user to display') . "\n"
    .    '<br />' . "\n"
    .    '<a href="advancedUserSearch.php' . $addtoAdvanced . '">' . get_lang('Search again (advanced)') . '</a></center>' . "\n"
    );
}
else
{
    $userDataGrid->set_colAttributeList(array ( 'user_id'      => array ('align' => 'center')
                                              , 'officialCode' => array ('align' => 'center')
                                              , 'settings'     => array ('align' => 'center')
                                              , 'delete'       => array ('align' => 'center')
    ));
}

//---------
// DISPLAY
//---------


//PREPARE
// javascript confirm pop up declaration
$htmlHeadXtra[] =
'<script type="text/javascript">
        function confirmation (name)
        {
            if (confirm("'.clean_str_for_javascript(get_lang('Are you sure to delete')).'" + name + "? "))
                {return true;}
            else
                {return false;}
        }'
."\n".'</script>'."\n";




//Header
include get_path('incRepositorySys') . '/claro_init_header.inc.php';

// Display tool title
echo claro_html_tool_title($nameTools) . "\n\n";

//Display Forms or dialog box(if needed)

if( isset($dialogBox) ) echo claro_html_message_box($dialogBox);

//Display selectbox and advanced search link

//TOOL LINKS

//Display search form

if ( !empty($isSearchedHTML) )
{
    echo claro_html_message_box ('<b>' . get_lang('Search on') . '</b> : <small>' . $isSearchedHTML . '</small>') ;
}

echo '<table width="100%">' . "\n"
.    '<tr>' . "\n"
.    '<td>' . '<a class="claroCmd" href="adminaddnewuser.php">'
.    '<img src="' . get_icon_url('user') . '" alt="" />'
.    get_lang('Create user')
.    '</a>'
.     '</td>' . "\n"
.     '<td>' . ''
.    '<td align="right">' . "\n"
.    '<form action="' . $_SERVER['PHP_SELF'] . '">' . "\n"
.    '<label for="search">' . get_lang('Make new search') . '  </label>' . "\n"
.    '<input type="text" value="' . htmlspecialchars($search).'" name="search" id="search" />' . "\n"
.    '<input type="submit" value=" ' . get_lang('Ok') . ' " />' . "\n"
.    '<input type="hidden" name="newsearch" value="yes" />' . "\n"
.    '&nbsp;[<a class="claroCmd" href="advancedUserSearch.php' . $addtoAdvanced . '" >' . get_lang('Advanced') . '</a>]' . "\n"
.    '</form>' . "\n"
.    '</td>' . "\n"
.    '</tr>' . "\n"
.    '</table>' . "\n\n"
;

if ( count($userGrid) > 0 ) echo $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);

echo $userDataGrid->render();

if ( count($userGrid) > 0 ) echo $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);

include get_path('incRepositorySys') . '/claro_init_footer.inc.php';

/**
 *
 * @todo: the  name would  be review  befor move to a lib
 * @todo: eject usage  in function of  $_SESSION
 *
 * @return sql statements
 */
function get_sql_filtered_user_list()
{
    if ( isset($_SESSION['admin_user_action']) )
    {
        switch ($_SESSION['admin_user_action'])
        {
            case 'plateformadmin' :
            {
                $filterOnStatus = 'plateformadmin';
            }  break;
            case 'createcourse' :
            {
               $filterOnStatus= 'createcourse';
            }  break;
            case 'followcourse' :
            {
                $filterOnStatus='followcourse';
            }  break;
            case 'all' :
            {
                $filterOnStatus='';
            }  break;
            default:
            {
                trigger_error('admin_user_action value unknow : '.var_export($_SESSION['admin_user_action'],1),E_USER_NOTICE);
                $filterOnStatus='followcourse';
            }
        }
    }
    else $filterOnStatus='';

    $tbl_mdb_names   = claro_sql_get_main_tbl();

    $sql = "SELECT U.user_id                     AS user_id,
                   U.nom                         AS name,
                   U.prenom                      AS firstname,
                   U.authSource                  AS authSource,
                   U.email                       AS email,
                   U.officialCode                AS officialCode,
                   U.phoneNumber                 AS phoneNumber,
                   U.pictureUri                  AS pictureUri,
                   U.creatorId                   AS creator_id,
                   U.isCourseCreator ,
                   U.isPlatformAdmin             AS isPlatformAdmin
           FROM  `" . $tbl_mdb_names['user'] . "` AS U
           WHERE 1=1 ";

    //deal with admin user search only

    if ($filterOnStatus=='plateformadmin')
    {
        $sql .= " AND U.isPlatformAdmin = 1";
    }

    //deal with KEY WORDS classification call

    if (isset($_SESSION['admin_user_search']))
    {
        $sql .= " AND (U.nom LIKE '%". claro_sql_escape(pr_star_replace($_SESSION['admin_user_search'])) ."%'
                  OR U.prenom LIKE '%".claro_sql_escape(pr_star_replace($_SESSION['admin_user_search'])) ."%' ";
        $sql .= " OR U.email LIKE '%". claro_sql_escape(pr_star_replace($_SESSION['admin_user_search'])) ."%'";
        $sql .= " OR U.username LIKE '". claro_sql_escape(pr_star_replace($_SESSION['admin_user_search'])) ."%'";        
        $sql .= " OR U.officialCode = '". claro_sql_escape(pr_star_replace($_SESSION['admin_user_search'])) ."')";
    }

    //deal with ADVANCED SEARCH parameters call

    if ( isset($_SESSION['admin_user_firstName']) && !empty($_SESSION['admin_user_firstname']) )
    {
        $sql .= " AND (U.prenom LIKE '%". claro_sql_escape(pr_star_replace($_SESSION['admin_user_firstName'])) ."%') ";
    }

    if ( isset($_SESSION['admin_user_lastName']) && !empty($_SESSION['admin_user_lastName']) )
    {
        $sql .= " AND (U.nom LIKE '%". claro_sql_escape(pr_star_replace($_SESSION['admin_user_lastName']))."%') ";
    }

    if ( isset($_SESSION['admin_user_userName']) && !empty($_SESSION['admin_user_userName']) )
    {
        $sql.= " AND (U.username LIKE '%". claro_sql_escape(pr_star_replace($_SESSION['admin_user_userName'])) ."%') ";
    }
    
    if ( isset($_SESSION['admin_user_officialCode'])  && !empty($_SESSION['admin_user_officialCode']) )
    {
        $sql.= " AND (U.officialCode LIKE '%". claro_sql_escape(pr_star_replace($_SESSION['admin_user_officialCode'])) ."%') ";
    }

    if ( isset($_SESSION['admin_user_mail']) && !empty($_SESSION['admin_user_mail']) )
    {
        $sql.= " AND (U.email LIKE '%". claro_sql_escape(pr_star_replace($_SESSION['admin_user_mail'])) ."%') ";
    }

    if ($filterOnStatus== 'createcourse' )
    {
        $sql.=" AND (U.isCourseCreator=1)";
    }
    elseif ($filterOnStatus=='followcourse' )
    {
        $sql.=" AND (U.isCourseCreator=0)";
    }

        return $sql;
}



function prepare_search()
{
    $queryStringElementList = array();
    $isSearched = array();

    if ( !empty($_SESSION['admin_user_search']) )
    {
        $isSearched[] =  $_SESSION['admin_user_search'];
    }

    if ( !empty($_SESSION['admin_user_firstName']) )
    {
        $isSearched[] = get_lang('First name') . '=' . $_SESSION['admin_user_firstName'];
        $queryStringElementList [] = 'firstName=' . urlencode($_SESSION['admin_user_firstName']);
    }

    if ( !empty($_SESSION['admin_user_lastName']) )
    {
        $isSearched[] = get_lang('Last name') . '=' . $_SESSION['admin_user_lastName'];
        $queryStringElementList[] = 'lastName=' . urlencode($_SESSION['admin_user_lastName']);
    }

    if ( !empty($_SESSION['admin_user_userName']) )
    {
        $isSearched[] = get_lang('Username') . '=' . $_SESSION['admin_user_userName'];
        $queryStringElementList[] = 'userName=' . urlencode($_SESSION['admin_user_userName']);
    }
    if ( !empty($_SESSION['admin_user_officialCode']) )
    {
        $isSearched[] = get_lang('Official code') . '=' . $_SESSION['admin_user_officialCode'];
        $queryStringElementList[] = 'userName=' . urlencode($_SESSION['admin_user_officialCode']);
    }
    if ( !empty($_SESSION['admin_user_mail']) )
    {
        $isSearched[] = get_lang('Email') . '=' . $_SESSION['admin_user_mail'];
        $queryStringElementList[] = 'mail=' . urlencode($_SESSION['admin_user_mail']);
    }

    if ( !empty($_SESSION['admin_user_action']) && ($_SESSION['admin_user_action'] == 'followcourse'))
    {
        $isSearched[] = '<b>' . get_lang('Follow courses') . '</b>';
        $queryStringElementList[] = 'action=' . urlencode($_SESSION['admin_user_action']);
    }
    elseif ( !empty($_SESSION['admin_user_action']) && ($_SESSION['admin_user_action'] == 'createcourse'))
    {
        $isSearched[] = '<b>' . get_lang('Course creator') . '</b>';
        $queryStringElementList[] = 'action=' . urlencode($_SESSION['admin_user_action']);
    }
    elseif (isset($_SESSION['admin_user_action']) && ($_SESSION['admin_user_action']=='plateformadmin'))
    {
        $isSearched[] = '<b>' . get_lang('Platform administrator') . '  </b> ';
        $queryStringElementList[] = 'action=' . urlencode($_SESSION['admin_user_action']);
    }
    else $queryStringElementList[] = 'action=all';

    if ( count($queryStringElementList) > 0 ) $queryString = '?' . implode('&amp;',$queryStringElementList);
    else                                      $queryString = '';

    $searchInfo['isSearched'] = $isSearched;
    $searchInfo['addtoAdvanced'] = $queryString;

    return $searchInfo;
}
?>