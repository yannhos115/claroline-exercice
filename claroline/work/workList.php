<?php // $Id$
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision$
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/CLWRK/
 *
 * @package CLWRK
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */

$tlabelReq = 'CLWRK';
require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

require_once './lib/assignment.class.php';
//require_once './lib/submission.class.php';

include_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
include_once get_path('incRepositorySys') . '/lib/pager.lib.php';
include_once get_path('incRepositorySys') . '/lib/group.lib.inc.php';

$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_user                = $tbl_mdb_names['user'];
$tbl_rel_course_user     = $tbl_mdb_names['rel_course_user'];

$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_wrk_submission      = $tbl_cdb_names['wrk_submission'   ];
$tbl_group_team          = $tbl_cdb_names['group_team'       ];
$tbl_group_rel_team_user = $tbl_cdb_names['group_rel_team_user'];

$currentUserFirstName = claro_get_current_user_data('firstName');
$currentUserLastName  = claro_get_current_user_data('lastName');

// 'step' of pager
$usersPerPage = get_conf('usersPerPage',20);

// use viewMode
claro_set_display_mode_available(true);

/*============================================================================
    Basic Variables Definitions
  ============================================================================*/

$fileAllowedSize = get_conf('max_file_size_per_works') ;    //file size in bytes (from config file)
$maxFilledSpace  = get_conf('maxFilledSpace', 100000000);

// initialise dialog box to an empty string, all dialog will be concat to it
$dialogBox = new DialogBox();

/*============================================================================
    Clean informations sent by user
  ============================================================================*/
unset ($req);

$acceptedCmdList = array( 'rqDownload', 'exDownload' );

if( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )   $cmd = $_REQUEST['cmd'];
else                                                                            $cmd = null;

if( isset($_REQUEST['downloadMode']) )  $downloadMode = $_REQUEST['downloadMode'];
else                                                                    $downloadMode = 'all';

$req['assignmentId'] = ( isset($_REQUEST['assigId'])
                    && !empty($_REQUEST['assigId'])
                    && ctype_digit($_REQUEST['assigId'])
                    )
                    ? (int) $_REQUEST['assigId']
                    : false;

/*============================================================================
    Prerequisites
  ============================================================================*/

/*--------------------------------------------------------------------
ASSIGNMENT INFORMATIONS
--------------------------------------------------------------------*/
$assignment = new Assignment();

if ( !$req['assignmentId'] || !$assignment->load($req['assignmentId']) )
{
    // we NEED to know in which assignment we are, so if assigId is not set
    // relocate the user to the previous page
    claro_redirect('work.php');
    exit();
}

/*============================================================================
    Group Publish Option
  ============================================================================*/
// redirect to the submission form prefilled with a .url document targetting the published document

/**
 * @todo $_REQUEST['submitGroupWorkUrl'] must be treated in  filter process
 */
if ( isset($_REQUEST['submitGroupWorkUrl']) && !empty($_REQUEST['submitGroupWorkUrl']) && claro_is_in_a_group() )
{
    claro_redirect ('userWork.php?authId='
    .       claro_get_current_group_id()
    .       '&cmd=rqSubWrk'
    .       '&assigId=' . $req['assignmentId']
    .       '&submitGroupWorkUrl=' . urlencode($_REQUEST['submitGroupWorkUrl'])
    );
    exit();
}

/*============================================================================
    Permissions
  ============================================================================*/

$assignmentIsVisible = (bool) ( $assignment->getVisibility() == 'VISIBLE' );

$is_allowedToEditAll = (bool) claro_is_allowed_to_edit();

if( !$assignmentIsVisible && !$is_allowedToEditAll )
{
    // if assignment is not visible and user is not course admin or upper
    claro_redirect('work.php');
    exit();
}

// upload or update is allowed between start and end date or after end date if late upload is allowed
$uploadDateIsOk      = $assignment->isUploadDateOk();

/*============================================================================
                DOWNLOAD SUBMISSIONS UJM
  =============================================================================*/
if( $cmd == 'exDownload' && $is_allowedToEditAll && get_conf('allow_download_all_submissions') ) // UJM
{
    require_once('lib/zip.lib.php');

    $zipfile = new zipfile();

    if( $downloadMode == 'from')
    {
        if( isset($_REQUEST['hour']) && is_numeric($_REQUEST['hour']) )       $hour = (int) $_REQUEST['hour'];
        else                                                                  $hour = 0;
        if( isset($_REQUEST['minute']) && is_numeric($_REQUEST['minute']) ) $minute = (int) $_REQUEST['minute'];
        else                                                                  $minute = 0;

        if( isset($_REQUEST['month']) && is_numeric($_REQUEST['month']) )   $month = (int) $_REQUEST['month'];
        else                                                                  $month = 0;
        if( isset($_REQUEST['day']) && is_numeric($_REQUEST['day']) )       $day = (int) $_REQUEST['day'];
        else                                                                  $day = 0;
        if( isset($_REQUEST['year']) && is_numeric($_REQUEST['year']) )       $year = (int) $_REQUEST['year'];
        else                                                                  $year = 0;

        $unixRequestDate = mktime( $hour, $minute, '00', $month, $day, $year );

        if( $unixRequestDate >= time() )
        {
            $dialogBox->erro(get_lang('Warning : chosen date is in the future'));
        }

        $downloadRequestDate = date('Y-m-d G:i:s', $unixRequestDate);

        $wanted = '_' . replace_dangerous_char(get_lang('From')) . '_' . date('Y_m_d', $unixRequestDate) . '_'
        . replace_dangerous_char(get_lang('to')) . '_' . date('Y_m_d')
        ;
        $sqlDateCondition = " AND `last_edit_date` >= '" . $downloadRequestDate . "' ";
    }
    else // download all
    {
        $wanted = '';

        $sqlDateCondition = '';
    }

    $sql = "SELECT `id`,
            `assignment_id`,
             `authors`,
             `submitted_text`,
             `submitted_doc_path`,
             `title`,
             `creation_date`,
             `last_edit_date`
            FROM  `" . $tbl_wrk_submission . "`
            WHERE `assignment_id` = " . (int) $req['assignmentId'] . "
            AND `parent_id` IS NULL
            " . $sqlDateCondition . "
            ORDER BY `creation_date`";


    $path = $coursesRepositorySys . $_course['path'] . '/work/assig_' . $req['assignmentId'] . '/';

    $workDir = replace_dangerous_char($_cid) . '_' . replace_dangerous_char($assignment->getTitle(), 'strict')
    . $wanted
    ;


    $results = claro_sql_query_fetch_all($sql);

    if( is_array($results) && !empty($results) )
    {
        $previousAuthors = '';
        $i = 1;

        foreach($results as $row => $result)
        {
            //  count author's submissions for the name of directory
            if( $result['authors'] != $previousAuthors )
            {
                $i = 1;
                $previousAuthors = $result['authors'];
            }
            else
            {
                $i++;
            }

            $authorsDir = replace_dangerous_char($result['authors']) . '/';

            $submissionPrefix = $authorsDir . replace_dangerous_char(get_lang('Submission')) . '_' . $i . '_';

            // attached file
            if(!empty($result['submitted_doc_path']))
            {
                if(file_exists($path . $result['submitted_doc_path']))
                    $zipfile->addFile(file_get_contents($path . $result['submitted_doc_path']),
                                    $workDir . '/' . $submissionPrefix . $result['submitted_doc_path']);
            }

            // description file
            $txtFileName = replace_dangerous_char(get_lang('Description')) . '.html';

            $htmlContent = '<html><head></head><body>' . "\n"
            .     get_lang('Title') . ' : ' . $result['title'] . '<br />' . "\n"
            .     get_lang('First submission date') . ' : ' . $result['creation_date']. '<br />' . "\n"
            .     get_lang('Last edit date') . ' : ' . $result['last_edit_date'] . '<br />' . "\n"
            ;

            if( !empty($result['submitted_doc_path']) )
            {
                $htmlContent .= get_lang('Attached file') . ' : ' . $submissionPrefix . $result['submitted_doc_path']. '<br />' . "\n";
            }

            $htmlContent .= '<div>' . "\n"
            .     '<h3>' . get_lang('Description') . '</h3>' . "\n"
            .     $result['submitted_text']
            .     '</div>' . "\n"
            .     '</body></html>';

            $zipfile->addFile($htmlContent,
                            $workDir . '/' . $submissionPrefix . $txtFileName);
        }

        // send zip file
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $workDir . '.zip');
        echo $zipfile->file();

        exit;
    }
    else
    {
        $dialogBox->error(get_lang('There is no submission available for download with these settings.'));
    }
}


if( $assignment->getAssignmentType() == 'INDIVIDUAL' )
{
    // user is authed and allowed
    $userCanPost = (bool) ( claro_is_user_authenticated() && claro_is_course_allowed() && claro_is_course_member() );
}
else
{
    $userGroupList = get_user_group_list(claro_get_current_user_id());
    // check if user is member of at least one group
    $userCanPost = (bool) ( !empty($userGroupList) );
}

$is_allowedToSubmit   = (bool) ( $assignmentIsVisible  && $uploadDateIsOk  && $userCanPost ) || $is_allowedToEditAll;

/*============================================================================
    Update notification
  ============================================================================*/
if (claro_is_user_authenticated())
{
    // call this function to set the __assignment__ as seen, all the submission as seen
    $claro_notifier->is_a_notified_ressource(claro_get_current_course_id(), $claro_notifier->get_notification_date(claro_get_current_user_id()), claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $req['assignmentId']);
}
/*============================================================================
    Prepare List
  ============================================================================*/
/* Prepare submission and feedback SQL filters - remove hidden item from count */

$submissionConditionList = array();
$feedbackConditionList = array();
$showOnlyVisibleCondition = '';

if( ! $is_allowedToEditAll )
{
    if( !get_conf('show_only_author') ) $submissionConditionList[] = "`S`.`visibility` = 'VISIBLE'";
    $feedbackConditionList[]   = "(`S`.`visibility` = 'VISIBLE' AND `FB`.`visibility` = 'VISIBLE')";

    if( !empty($userGroupList)  )
    {
        $userGroupIdList = array();
        foreach( $userGroupList as $userGroup )
        {
            $userGroupIdList[] = $userGroup['id'];
        }
        $submissionConditionList[] = "S.group_id IN ("  . implode(', ', array_map( 'intval', $userGroupIdList) ) . ")";
        $feedbackConditionList[]   = "FB.group_id IN (" . implode(', ', array_map( 'intval', $userGroupIdList) ) . ")";
    }
    elseif ( claro_is_user_authenticated() )
    {
        $submissionConditionList[] = "`S`.`user_id` = "      . (int) claro_get_current_user_id();
        $feedbackConditionList[]   = "`FB`.`original_id` = " . (int) claro_get_current_user_id();
    }
}

$submissionFilterSql = implode(' OR ', $submissionConditionList);
if ( !empty($submissionFilterSql) ) $submissionFilterSql = ' AND ('.$submissionFilterSql.') ';

$feedbackFilterSql = implode(' OR ', $feedbackConditionList);
if ( !empty($feedbackFilterSql) ) $feedbackFilterSql = ' AND ('.$feedbackFilterSql.')';

if( $assignment->getAssignmentType() == 'INDIVIDUAL' )
{
    if( ! $is_allowedToEditAll ) $showOnlyVisibleCondition = " HAVING `submissionCount` > 0";

    $sql = "SELECT `U`.`user_id`                        AS `authId`,
                   CONCAT(`U`.`nom`, ' ', `U`.`prenom`) AS `name`,
                   `S`.`title`,
                   COUNT(`S`.`id`)                      AS `submissionCount`,
                   COUNT(`FB`.`id`)                     AS `feedbackCount`,
                   MAX(`FB`.`score`)                   AS `maxScore`,
                   MAX(`S`.`last_edit_date`)         AS `last_edit_date`

            #GET USER LIST
            FROM  `" . $tbl_user . "` AS `U`

            #ONLY FROM COURSE
            INNER JOIN  `" . $tbl_rel_course_user . "` AS `CU`
                    ON  `U`.`user_id` = `CU`.`user_id`
                   AND `CU`.`code_cours` = '" . claro_sql_escape(claro_get_current_course_id()) . "'

            # SEARCH ON SUBMISSIONS
            LEFT JOIN `" . $tbl_wrk_submission . "` AS `S`
                   ON ( `S`.`assignment_id` = " . (int) $req['assignmentId'] . " OR `S`.`assignment_id` IS NULL)
                  AND `S`.`user_id` = `U`.`user_id`
                  AND `S`.`original_id` IS NULL
            " . $submissionFilterSql . "

             # SEARCH ON FEEDBACKS
            LEFT JOIN `".$tbl_wrk_submission."` as `FB`
                   ON `FB`.`parent_id` = `S`.`id`
             " . $feedbackFilterSql . "

            GROUP BY `U`.`user_id`,
                     `S`.`original_id`
             " . $showOnlyVisibleCondition
    ;

    if ( isset($_GET['sort']) && isset($_GET['dir']) )         $sortKeyList[$_GET['sort']] = $_GET['dir'];
    elseif( isset($_GET['sort']) && isset($_GET['dir']) )     $sortKeyList[$_GET['sort']] = SORT_ASC;

    if( !isset($sortKeyList['submissionCount']) ) $sortKeyList['submissionCount'] = SORT_DESC;

    $sortKeyList['S.last_edit_date'] = SORT_DESC;
    $sortKeyList['FB.last_edit_date'] = SORT_DESC;

    $sortKeyList['CU.isCourseManager'] = SORT_ASC;
    $sortKeyList['CU.tutor']  = SORT_DESC;
    $sortKeyList['U.nom']     = SORT_ASC;
    $sortKeyList['U.prenom']  = SORT_ASC;

    // get last submission titles
    $sql2 = "SELECT `S`.`user_id` as `authId`, `S`.`title`, DATE(`S`.`last_edit_date`) as date
                FROM `" . $tbl_wrk_submission . "` AS `S`
            LEFT JOIN `" . $tbl_wrk_submission . "` AS `S2`
                ON `S`.`user_id` = `S2`.`user_id`
                AND `S2`.`assignment_id` = ". (int) $req['assignmentId']."
                AND `S`.`last_edit_date` < `S2`.`last_edit_date`
                AND `S2`.`parent_id` IS NULL
            WHERE `S2`.`user_id` IS NULL
                AND `S`.`original_id` IS NULL
                AND `S`.`assignment_id` = ". (int) $req['assignmentId']."
            " . $submissionFilterSql . "";
}
else  // $assignment->getAssignmentType() == 'GROUP'
{

    /**
     * USER GROUP INFORMATIONS
     */
    $sql = "SELECT `G`.`id`            AS `authId`,
                   `G`.`name`,
                   `S`.`title`,
                   COUNT(`S`.`id`)     AS `submissionCount`,
                   COUNT(`FB`.`id`)    AS `feedbackCount`,
                   MAX(`FB`.`score`)   AS `maxScore`,
                   MAX(`S`.`last_edit_date`)         AS `last_edit_date`

        FROM `" . $tbl_group_team . "` AS `G`

        # SEARCH ON SUBMISSIONS
        LEFT JOIN `".$tbl_wrk_submission."` AS `S`
               ON `S`.`group_id` = `G`.`id`
              AND (`S`.`assignment_id` = " . $req['assignmentId'] . " OR `S`.`assignment_id` IS NULL )
              AND `S`.`original_id` IS NULL
        " . $submissionFilterSql . "

        # SEARCH ON FEEBACKS
        LEFT JOIN `" . $tbl_wrk_submission . "` as `FB`
               ON `FB`.`parent_id` = `S`.`id`
        " . $feedbackFilterSql ."

        GROUP BY `G`.`id`,          # group by 'group'
                 `S`.`original_id`"
        ;

    if ( isset($_GET['sort']) && isset($_GET['dir']) )         $sortKeyList[$_GET['sort']] = $_GET['dir'];
    elseif( isset($_GET['sort']) && isset($_GET['dir']) )     $sortKeyList[$_GET['sort']] = SORT_ASC;

    if( !isset($sortKeyList['submissionCount']) ) $sortKeyList['submissionCount'] = SORT_DESC;

    $sortKeyList['S.last_edit_date'] = SORT_ASC;
    $sortKeyList['FB.last_edit_date'] = SORT_ASC;

    $sortKeyList['G.name'] = SORT_ASC;

    // get last submission titles
    $sql2 = "SELECT `S`.`group_id` as `authId`, `S`.`title`, DATE(`S`.`last_edit_date`) as date
                FROM `" . $tbl_wrk_submission . "` AS `S`
            LEFT JOIN `" . $tbl_wrk_submission . "` AS `S2`
                ON `S`.`group_id` = `S2`.`group_id`
                AND `S2`.`assignment_id` = ". (int) $req['assignmentId']."
                AND `S`.`last_edit_date` < `S2`.`last_edit_date`
            WHERE `S2`.`group_id` IS NULL
                AND `S`.`original_id` IS NULL
                AND `S`.`assignment_id` = ". (int) $req['assignmentId']."
            " . $submissionFilterSql . "";
}



/*--------------------------------------------------------------------
WORK LIST
--------------------------------------------------------------------*/
$offset = (isset($_REQUEST['offset']) && !empty($_REQUEST['offset']) ) ? $_REQUEST['offset'] : 0;
$workPager = new claro_sql_pager($sql,$offset, $usersPerPage);

foreach($sortKeyList as $thisSortKey => $thisSortDir)
{
    $workPager->add_sort_key( $thisSortKey, $thisSortDir);
}


$workList = $workPager->get_result_list();

// add the title of the last submission in each displayed line
$results = claro_sql_query_fetch_all($sql2);

$lastWorkTitleList = array();
$last_edit_date_list = array();

foreach( $results as $result )
{
    $lastWorkTitleList[$result['authId']] = $result['title'];
    $last_edit_date_list[$result['authId']] = $result['date'];
}

if( !empty($lastWorkTitleList) )
{
    for( $i = 0; $i < count($workList); $i++ )
    {
        if( isset($lastWorkTitleList[$workList[$i]['authId']]) )
            $workList[$i]['title'] = $lastWorkTitleList[$workList[$i]['authId']];

        if( isset($last_edit_date_list[$workList[$i]['authId']]) )
            $workList[$i]['last_edit_date'] = $last_edit_date_list[$workList[$i]['authId']];
    }
}

// build link to submissions page
foreach ( $workList as $workId => $thisWrk )
{

    $thisWrk['is_mine'] = (  ($assignment->getAssignmentType() == 'INDIVIDUAL' && $thisWrk['authId'] == claro_get_current_user_id())
                          || ($assignment->getAssignmentType() == 'GROUP'      && in_array($thisWrk['authId'], $userGroupList)));

    if ($thisWrk['is_mine']) $workList[$workId]['name'] = '<b>' . $thisWrk['name'] . '</b>';

    $workList[$workId]['name'] = '<a class="item" href="userWork.php'
    .                            '?authId=' . $thisWrk['authId']
    .                            '&amp;assigId=' . $req['assignmentId']
    .                            claro_url_relay_context('&amp;')
    .                            '">'
    .                            $workList[$workId]['name']
    .                            '</a>'
    ;

}

/**
 * HEADER
 */
$nameTools = get_lang('Assignment');

ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize($_SERVER['PHP_SELF'] . '?assigId=' . (int) $req['assignmentId'] ) );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Assignments'), Url::Contextualize('../work/work.php') );
/**
 * TOOL TITLE
 */
$pageTitle['mainTitle'] = $nameTools;
$pageTitle['subTitle' ] = $assignment->getTitle();


// SHOW FEEDBACK
// only if :
//      - there is a text OR a file in automatic feedback
//    AND
//          feedback must be shown after end date and end date is past
//      OR  feedback must be shown directly after a post (from the time a work was uploaded by the student)

// there is a prefill_ file or text, so there is something to show
$textOrFilePresent = (bool) $assignment->getAutoFeedbackText() != '' || $assignment->getAutoFeedbackFilename() != '';

// feedback must be shown after end date and end date is past
$showAfterEndDate = (bool) (  $assignment->getAutoFeedbackSubmitMethod() == 'ENDDATE'
                           && $assignment->getEndDate() < time()
                           );


// feedback must be shown directly after a post
// check if user has already posted a work
// do not show to anonymous users because we can't know
// if the user already uploaded a work
$showAfterPost = (bool)
                 claro_is_user_authenticated()
                 &&
                 (  $assignment->getAutoFeedbackSubmitMethod() == 'AFTERPOST'
                    &&
                    count($assignment->getSubmissionList(claro_get_current_user_id())) > 0
                 );




 /**
  * OUTPUT
  *
  * 3 parts in this output
  * - A detail about the current assignment
  * - "Command" links to commands
  * - A list of user relating submission and feedback
  *
  */

include get_path('incRepositorySys') . '/claro_init_header.inc.php';
echo claro_html_tool_title($pageTitle);

/**
 * ASSIGNMENT INFOS
 */

echo '<p>' . "\n" . '<small>' . "\n"
.    '<b>' . get_lang('Title') . '</b> : ' . "\n"
.    $assignment->getTitle() . '<br />'  . "\n"
.    get_lang('<b>From</b> %startDate <b>until</b> %endDate', array('%startDate' => claro_html_localised_date(get_locale('dateTimeFormatLong'), $assignment->getStartDate()), '%endDate' => claro_html_localised_date(get_locale('dateTimeFormatLong'), $assignment->getEndDate()) ) )

.    '<br />'  .  "\n"

.    '<b>' . get_lang('Submission type') . '</b> : ' . "\n";

if( $assignment->getSubmissionType() == 'TEXT'  )
    echo get_lang('Text only (text required, no file)');
elseif( $assignment->getSubmissionType() == 'TEXTFILE' )
    echo get_lang('Text with attached file (text required, file optional)');
else
    echo get_lang('File (file required, description text optional)');


echo '<br />'  .  "\n"

.    '<b>' . get_lang('Submission visibility') . '</b> : ' . "\n"
.    ($assignment->getDefaultSubmissionVisibility() == 'VISIBLE' ? get_lang('Visible for all users') : get_lang('Only visible for teacher(s) and submitter(s)'))

.    '<br />'  .  "\n"

.    '<b>' . get_lang('Assignment type') . '</b> : ' . "\n"
.    ($assignment->getAssignmentType() == 'INDIVIDUAL' ? get_lang('Individual') : get_lang('Groups') )

.    '<br />'  .  "\n"

.    '<b>' . get_lang('Allow late upload') . '</b> : ' . "\n"
.    ($assignment->getAllowLateUpload() == 'YES' ? get_lang('Users can submit after end date') : get_lang('Users can not submit after end date') )

.    '</small>' . "\n" . '</p>' . "\n";

// description of assignment
if( $assignment->getDescription() != '' )
{
    echo '<b><small>' . get_lang('Description') . '</small></b>' . "\n"
    .    '<blockquote>' . "\n" . '<small>' . "\n"
    .    claro_parse_user_text($assignment->getDescription())
    .    '</small>' . "\n" . '</blockquote>' . "\n"
    .    '<br />' . "\n"
    ;
}

// show to authenticated and anonymous users

if( $textOrFilePresent &&  ( $showAfterEndDate || $showAfterPost ) )
{
    echo '<fieldset>' . "\n"
    .    '<legend>'
    .    '<b>' . get_lang('Feedback') . '</b>'
    .    '</legend>'
    ;

    if( $assignment->getAutoFeedbackText() != '' )
    {
        echo claro_parse_user_text($assignment->getAutoFeedbackText());
    }

    if( $assignment->getAutoFeedbackFilename() != '' )
    {
        $target = ( get_conf('open_submitted_file_in_new_window') ? 'target="_blank"' : '');
        echo  '<p><a href="' . $assignment->getAssigDirWeb() . $assignment->getAutoFeedbackFilename() . claro_url_relay_context('&amp;') . '" ' . $target . '>'
        .     $assignment->getAutoFeedbackFilename()
        .     '</a></p>'
        ;
    }

    echo '</fieldset>'
    .    '<br />' . "\n"
    ;
}

/**
 * COMMAND LINKS
 */
$cmdMenu = array();
if ( $is_allowedToSubmit && $assignment->getAssignmentType() != 'GROUP' )
{
    // link to create a new assignment
    $cmdMenu[] = claro_html_cmd_link( 'userWork.php?authId=' . claro_get_current_user_id()
                                    . '&amp;cmd=rqSubWrk'
                                    . '&amp;assigId=' . $req['assignmentId']
                                    . claro_url_relay_context('&amp;')
                                    , get_lang('Submit a work'));
}

if ( $is_allowedToEditAll )
{
    // Submission download requested
    if( $cmd == 'rqDownload' && get_conf('allow_download_all_submissions') ) // UJM
    {
        require_once($includePath . '/lib/form.lib.php');

         $downloadForm = '<strong>' . get_lang('Download').'</strong>' . "\n"
         .        '<form action="' . $_SERVER['PHP_SELF'] . '?assigId=' . $req['assignmentId'] . '" method="POST">' . "\n"
         .    claro_form_relay_context()
         .    '<input type="hidden" name="cmd" value="exDownload" />' . "\n"
         .        '<input type="radio" name="downloadMode" id="downloadMode_from" value="from" checked /><label for="downloadMode_from">' . get_lang('Submissions posted or modified after date :') . '</label><br />' . "\n"
         .        claro_html_date_form('day', 'month', 'year', time(), 'long') . ' '
         .        claro_html_time_form('hour', 'minute', time() - fmod(time(), 86400) - 3600) . '<small>' . get_lang('(d/m/y hh:mm)') . '</small>' . '<br /><br />' . "\n"
         .        '<input type="radio" name="downloadMode" id="downloadMode_all" value="all" /><label for="downloadMode_all">' . get_lang('All submissions') . '</label><br /><br />' . "\n"
         .        '<input type="submit" value="'.get_lang('OK').'" />&nbsp;' . "\n"
         .    claro_html_button('workList.php?assigId='.$req['assignmentId'], get_lang('Cancel'))
         .        '</form>'."\n"
        ;

        echo $dialogBox->form($downloadForm);
    }

    $cmdMenu[] = claro_html_cmd_link( 'feedback.php?cmd=rqEditFeedback'
                                    . '&amp;assigId=' . $req['assignmentId']
                                    . claro_url_relay_context('&amp;')
                                    , get_lang('Edit automatic feedback')
                                    );
                                    
    if( get_conf('allow_download_all_submissions') )
    {
        $cmdMenu[] = claro_html_cmd_link( $_SERVER['PHP_SELF'] . '?cmd=rqDownload&amp;assigId=' . $req['assignmentId'] . claro_url_relay_context('&amp;')
                                    , '<img src="' . get_icon_url('save') . '" alt="" />' . get_lang('Download submissions')
                                    );
    }

}

/*--------------------------------------------------------------------
                        DIALOG BOX SECTION
  --------------------------------------------------------------------*/

echo $dialogBox->render();

if( !empty($cmdMenu) ) echo '<p>' . claro_html_menu_horizontal($cmdMenu) . '</p>' . "\n";


/**
 * Submitter (User or group) listing
 */
$headerUrl = $workPager->get_sort_url_list($_SERVER['PHP_SELF'] . '?assigId=' . $req['assignmentId'] );

echo $workPager->disp_pager_tool_bar($_SERVER['PHP_SELF']."?assigId=".$req['assignmentId'])

.    '<table class="claroTable emphaseLine" width="100%">' . "\n"
.    '<thead>' . "\n"
.    '<tr class="headerX">' . "\n"
.    '<th>'
.    '<a href="' . $headerUrl['name'] . '">'
.    get_lang('Author(s)')
.    '</a>'
.    '</th>' . "\n"
.    '<th>'
.    '<a href="' . $headerUrl['last_edit_date'] . '">'
.    get_lang('Last submission')
.     '</a>'
.    '</th>' . "\n"
.    '<th>'
.    '<a href="' . $headerUrl['submissionCount'] . '">'
.    get_lang('Submissions')
.    '</a>'
.    '</th>' . "\n"
.    '<th>'
.    '<a href="' . $headerUrl['feedbackCount'] . '">'
.    get_lang('Feedbacks')
.    '</a>'
.    '</th>' . "\n";

if( $is_allowedToEditAll )
{
    echo '<th>'
    .    '<a href="' . $headerUrl['maxScore'] . '">'
    .    get_lang('Best score')
    .    '</a>'
    .    '</th>' . "\n";
}

echo '</tr>' . "\n"
.    '</thead>' . "\n"
.    '<tbody>'
;


foreach ( $workList as $thisWrk )
{

    echo '<tr align="center">' . "\n"
    .    '<td align="left">'
    .     $thisWrk['name']
    .    '</td>' . "\n"
    .    '<td>'
    .    ( !empty($thisWrk['title']) ? $thisWrk['title'] . '<small> ( ' . $thisWrk['last_edit_date'] . ' )</small>'  : '&nbsp;' )
    .    '</td>' . "\n"
    .    '<td>'
    .    $thisWrk['submissionCount']
    .    '</td>' . "\n"
    .    '<td>'
    .    $thisWrk['feedbackCount']
    .    '</td>' . "\n";

    if( $is_allowedToEditAll )
    {
        echo '<td>'
        .    ( ( !is_null($thisWrk['maxScore']) && $thisWrk['maxScore'] > -1 )? $thisWrk['maxScore'] : get_lang('No score') )
        .    '</td>' . "\n";
    }

    echo '</tr>' . "\n\n"
    ;
}

echo '</tbody>' . "\n"
.    '</table>' . "\n\n"

.    $workPager->disp_pager_tool_bar($_SERVER['PHP_SELF']."?assigId=".$req['assignmentId']);

include get_path('incRepositorySys') . '/claro_init_footer.inc.php';

?>