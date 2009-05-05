<?php // $Id$
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * The script works with the 'annoucement' tables in the main claroline table
 *
 * DB Table structure:
 * ---
 *
 * * id         : announcement id
 * * contenu    : announcement content
 * * temps      : date of the announcement introduction / modification
 * * title      : optionnal title for an announcement
 * * ordre      : order of the announcement display
 *              (the announcements are display in desc order)
 *
 * @version 1.8 $Revision$
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLANN
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Christophe Gesch� <moosh@claroline.net>
 */


/**
 * get list of all announcements in the given or current course
 *
 * @param string $order  'ASC' || 'DESC' : ordering of the list.
 * @param  string $course_id sysCode of the course (leaveblank for current course)
 * @return array of array(id, title, content, time, visibility, rank)
 * @since  1.7
 */

function announcement_get_course_item_list($thisCourse, $limit = null, $startTime = null, $visibleOnly = true )
{    
    // **** Attention !!! A changer ...
    $tableAnn = get_conf('courseTablePrefix') . $thisCourse['db'] . get_conf('dbGlu') . 'announcement';
    // ****
    
    $sql = "SELECT '" . claro_sql_escape($thisCourse['sysCode']     ) ."' AS `courseSysCode`,\n"
            . "'" . claro_sql_escape($thisCourse['officialCode']) ."'     AS `courseOfficialCode`,\n"
            . "'CLANN'                                              AS `toolLabel`,\n"
            . "CONCAT(`temps`, ' ', '00:00:00')                     AS `date`,\n"
            . "CONCAT(`title`,' - ',`contenu`)                      AS `content`\n"
            . "FROM `" . $tableAnn . "`\n"
            . "WHERE CONCAT(`title`, `contenu`) != ''\n"
            . ( $startTime ? '' : "AND DATE_FORMAT( `temps`, '%Y %m %d') >= '".date('Y m d', (double)$startTime)."'\n" )
            . ( $visibleOnly ? "  AND visibility = 'SHOW'\n" : '' )
            . "ORDER BY `date` DESC\n"
            . ( $limit ? "LIMIT " . (int) $limit : '' )
            ;

    return claro_sql_query_fetch_all_cols($sql);
} 
 
function announcement_get_course_item_list_portlet($thisCourse, $limit = null, $startTime = null, $visibleOnly = true )
{    
    // **** Attention !!! A changer ...
    $tableAnn = get_conf('courseTablePrefix') . $thisCourse['db'] . get_conf('dbGlu') . 'announcement';
    // ****
    
    $sql = "SELECT '" . claro_sql_escape($thisCourse['sysCode']     ) ."' AS `courseSysCode`,\n"
            . "'" . claro_sql_escape($thisCourse['officialCode']) ."'     AS `courseOfficialCode`,\n"
            . "'CLANN'                                              AS `toolLabel`,\n"
            . "CONCAT(`temps`, ' ', '00:00:00')                     AS `date`,\n"
            . "CONCAT(`title`,' - ',`contenu`)                      AS `content`\n"
            . "FROM `" . $tableAnn . "`\n"
            . "WHERE CONCAT(`title`, `contenu`) != ''\n"
            . ( $startTime ? '' : "AND DATE_FORMAT( `temps`, '%Y %m %d') >= '".date('Y m d', (double)$startTime)."'\n" )
            . ( $visibleOnly ? "  AND visibility = 'SHOW'\n" : '' )
            . "ORDER BY `date` DESC\n"
            . ( $limit ? "LIMIT " . (int) $limit : '' )
            ;

    return claro_sql_query_fetch_all_rows($sql);
} 

function announcement_get_items_portlet($personnalCourseList)
{
    
    $courseDigestList = array();
    
    foreach($personnalCourseList as $thisCourse)
    {
        $courseEventList = announcement_get_course_item_list_portlet($thisCourse, get_conf('announcementPortletMaxItems', 3));

        if ( is_array($courseEventList) )
        {
            foreach($courseEventList as $thisEvent)
            {
                
                $eventTitle = trim(strip_tags($thisCourse['title']));
                if ( $eventTitle == '' )
                {
                    $eventTitle    = substr($eventTitle, 0, 60) . (strlen($eventTitle) > 60 ? ' (...)' : '');
                }
                
                $eventContent = trim(strip_tags($thisEvent['content']));
                if ( $eventContent == '' )
                {
                    $eventContent    = substr($eventContent, 0, 60) . (strlen($eventContent) > 60 ? ' (...)' : '');
                }
              
                $courseOfficialCode = $thisEvent['courseOfficialCode'];

                if(!array_key_exists($courseOfficialCode, $courseDigestList))
                {
                    $courseDigestList[$courseOfficialCode] = array();
                    $courseDigestList[$courseOfficialCode]['eventList'] = array();
                    $courseDigestList[$courseOfficialCode]['courseOfficialCode'] = $courseOfficialCode;
                    $courseDigestList[$courseOfficialCode]['title'] = $eventTitle;
                    $courseDigestList[$courseOfficialCode]['url'] = get_path('url').'/claroline/announcements/announcements.php?cidReq=' . $thisEvent['courseSysCode'];
                } 
                
                $courseDigestList[$courseOfficialCode]['eventList'][] =
                    array(
                        'courseSysCode' => $thisEvent['courseSysCode'],
                        'toolLabel' => $thisEvent['toolLabel'],
                        'content' => $eventContent,
                        'date' => $thisEvent['date']
                    );
                
            }
        }
    }  
    
    return $courseDigestList;
}
 
function announcement_get_item_list($context, $order='DESC')
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($context[CLARO_CONTEXT_COURSE]));

    $sql = "SELECT            id,
                              title,
                   contenu AS content,
                   temps   AS `time`,
                              visibility,
                   ordre AS   rank
            FROM `" . $tbl['announcement'] . "`
            ORDER BY ordre " . ($order == 'DESC' ? 'DESC' : 'ASC');
    return claro_sql_query_fetch_all($sql);
}

/**
 * Delete an announcement in the given or current course
 *
 * @param integer $announcement_id id the requested announcement
 * @param string $course_id  sysCode of the course (leaveblank for current course)
 * @return result of deletion query
 * @since  1.7
 */
function announcement_delete_item($id, $course_id=NULL)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    $sql = "DELETE FROM  `" . $tbl['announcement'] . "`
            WHERE id='" . (int) $id . "'";
    return claro_sql_query($sql);
}


/**
 * Delete an announcement in the given or current course
 *
 * @param integer $announcement_id id the requested announcement
 * @param string $course_id        sysCode of the course (leaveblank for current course)
 * @return result of deletion query
 * @since  1.7
 */
function announcement_delete_all_items($course_id=NULL)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    $sql = "DELETE FROM  `" . $tbl['announcement'] . "`";
    return claro_sql_query($sql);
}

/**
 * add an new announcement in the given or current course
 *
 * @param string $title title of the new item
 * @param string $content   content of the new item
 * @param date   $time  publication dat of the item def:now
 * @param course_code $course_id sysCode of the course (leaveblank for current course)
 * @return id of the new item
 * @since  1.7
 * @todo convert to param date  timestamp
 */

function announcement_add_item($title='',$content='', $visibility='SHOW', $time=NULL, $course_id=NULL)
{
    $tbl= claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    if(is_null($time))
    {
        $sqlTime = " temps = NOW(), ";
    }
    else
    {
        $sqlTime = " temps = from_unixtime('". (int)$time ."'), ";
    }

    // DETERMINE THE ORDER OF THE NEW ANNOUNCEMENT
    $sql = "SELECT (MAX(ordre) + 1) AS nextRank
            FROM  `" . $tbl['announcement'] . "`";

    $nextRank = claro_sql_query_get_single_value($sql);
    // INSERT ANNOUNCEMENT

    $sql = "INSERT INTO `" . $tbl['announcement'] . "`
            SET title ='" . claro_sql_escape(trim($title)) . "',
                contenu = '" . claro_sql_escape(trim($content)) . "',
                visibility = '" . ($visibility=='HIDE'?'HIDE':'SHOW') . "',
             ". $sqlTime ."
            ordre ='" . (int) $nextRank . "'";
    return claro_sql_query_insert_id($sql);
}

/**
 * Update an announcement in the given or current course
 *
 * @param string $title     title of the new item
 * @param string $content   content of the new item
 * @param date   $time      publication dat of the item def:now
 * @param string $course_id sysCode of the course (leaveblank for current course)
 * @return handler of query
 * @since  1.7
 * @todo convert to param date  timestamp
 */

function announcement_update_item($announcement_id, $title=NULL, $content=NULL, $visibility=NULL, $time=NULL, $course_id=NULL)
{
    $tbl= claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $sqlSet = array();
    if(!is_null($title))      $sqlSet[] = " title = '" . claro_sql_escape(trim($title)) . "' ";
    if(!is_null($content))    $sqlSet[] = " contenu = '" . claro_sql_escape(trim($content)) . "' ";
    if(!is_null($visibility)) $sqlSet[] = " visibility = '" . ($visibility=='HIDE'?'HIDE':'SHOW') . "' ";
    if(!is_null($time))       $sqlSet[] = " temps = from_unixtime('".(int)$time."') ";

    if (count($sqlSet)>0)
    {
        $sql = "UPDATE  `" . $tbl['announcement'] . "`
                SET " . implode(', ',$sqlSet) . "
                WHERE id='" . (int) $announcement_id . "'";
        return claro_sql_query($sql);
    }
    else return NULL;
}

/**
 * return data for the announcement  of the given id of the given or current course
 *
 * @param integer $announcement_id id the requested announcement
 * @param string  $course_id       sysCode of the course (leaveblank for current course)
 * @return array(id, title, content, visibility, rank) of the announcement
 * @since  1.7
 */

function announcement_get_item($announcement_id, $course_id=NULL)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    $sql = "SELECT                id,
                                  title,
                   contenu     AS content,
                                  visibility,
                   ordre       AS rank
            FROM  `" . $tbl['announcement'] . "`
            WHERE id=" . (int) $announcement_id ;

    $announcement = claro_sql_query_get_single_row($sql);

    if ($announcement) return $announcement;
    else               return claro_failure::set_failure('ANNOUNCEMENT_UNKNOW');
}

function announcement_set_item_visibility($announcement_id, $visibility, $course_id=NULL)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    if (!in_array($visibility, array ('HIDE','SHOW')))
     trigger_error('ANNOUNCEMENT_VISIBILITY_UNKNOW', E_USER_NOTICE);
    $sql = "UPDATE `" . $tbl['announcement'] . "`
            SET   visibility = '" . ($visibility=='HIDE'?'HIDE':'SHOW') . "'
                  WHERE id =  '" . (int) $announcement_id . "'";
    return  claro_sql_query($sql);
}

/**
 * function move_entry($entryId,$cmd)
 *
 * @param  integer $entryId  an valid id of announcement.
 * @param  string $cmd       'UP' or 'DOWN'
 * @return true;
 *
 * @author Christophe Gesch� <moosh@claroline.net>
 */
function move_entry($item_id, $cmd, $course_id=NULL)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    if ( $cmd == 'DOWN' )
    {
        $thisAnnouncementId = $item_id;
        $sortDirection      = 'DESC';
    }
    elseif ( $cmd == 'UP' )
    {
        $thisAnnouncementId = $item_id;
        $sortDirection      = 'ASC';
    }
    else
        return false;

    if ( $sortDirection )
    {
        $sql = "SELECT          id,
                       ordre AS rank
            FROM `" . $tbl['announcement'] . "`
            ORDER BY `ordre` " . $sortDirection;

        $result = claro_sql_query($sql);
        $thisAnnouncementRankFound = false;
        $thisAnnouncementRank = '';
        while ( (list ($announcementId, $announcementRank) = mysql_fetch_row($result)) )
        {
            // STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER.
            //          COMMIT ORDER SWAP ON THE DB

            if ($thisAnnouncementRankFound == true)
            {
                $nextAnnouncementId    = $announcementId;
                $nextAnnouncementRank  = $announcementRank;

                $sql = "UPDATE `" . $tbl['announcement'] . "`
                    SET ordre = '" . (int) $nextAnnouncementRank . "'
                    WHERE id =  '" . (int) $thisAnnouncementId . "'";

                claro_sql_query($sql);

                $sql = "UPDATE `" . $tbl['announcement'] . "`
                    SET ordre = '" . $thisAnnouncementRank . "'
                    WHERE id =  '" . $nextAnnouncementId . "'";
                claro_sql_query($sql);

                break;
            }

            // STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT

            if ( $announcementId == $thisAnnouncementId )
            {
                $thisAnnouncementRank      = $announcementRank;
                $thisAnnouncementRankFound = true;
            }
        }
    }
    return true;
}

?>