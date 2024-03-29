<?php
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision$
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLTRACK
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 */
/*
 * This class is the manager for course access
 * 
 */
class CLTRACK_CourseAccessTrackingManager extends TrackingManager
{
    private $tbl_course_tracking_event;
    
    public function __construct($courseId)
    {
        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($courseId));
        $this->tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];
    }
    
    public function deleteAll()
    {
        $sql = "DELETE
                FROM `".$this->tbl_course_tracking_event."`
                WHERE `type` = 'course_access'";
        
        if( claro_sql_query($sql) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function deleteBefore( $date )
    {
        $sql = "DELETE
                FROM `".$this->tbl_course_tracking_event."`
                WHERE `type` = 'course_access'
                  AND `date` < FROM_UNIXTIME('" . (int) $date ."')";
        
        if( claro_sql_query($sql) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function deleteForUser( $userId, $date = null )
    {
        if( !is_null($date) && !empty($date) )
        {
            $dateCondition = " AND `T`.`date` < FROM_UNIXTIME('" . (int) $date . "')";            
        }
        
        $sql = "DELETE
                FROM `".$this->tbl_course_tracking_event."`
                WHERE `type` = 'course_access'
                  AND `user_id` = ".(int) $userId
                  . $dateCondition;
        
        if( claro_sql_query($sql) )
        {
            return true;
        }
        else
        {
            return false;
        }            
    }
}

TrackingManagerRegistry::register('CLTRACK_CourseAccessTrackingManager');



/*
 * This class is the manager for tool access
 */

class CLTRACK_ToolAccessTrackingManager extends TrackingManager
{
    private $tbl_course_tracking_event;
    
    public function __construct($courseId)
    {
        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($courseId));
        $this->tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];
    }
    
    public function deleteAll()
    {
        $sql = "DELETE
                FROM `".$this->tbl_course_tracking_event."`
                WHERE `type` = 'tool_access'";
        
        if( claro_sql_query($sql) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function deleteBefore( $date )
    {
        $sql = "DELETE
                FROM `".$this->tbl_course_tracking_event."`
                WHERE `type` = 'tool_access'
                  AND `date` < FROM_UNIXTIME('" . (int) $date ."')";
        
        if( claro_sql_query($sql) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function deleteForUser( $userId, $date = null )
    {
        if( !is_null($date) && !empty($date) )
        {
            $dateCondition = " AND `date` < FROM_UNIXTIME('" . (int) $date . "')";            
        }
        
        $sql = "DELETE
                FROM `".$this->tbl_course_tracking_event."`
                WHERE `type` = 'tool_access'
                  AND `user_id` = ".(int) $userId
                  . $dateCondition;
        
        if( claro_sql_query($sql) )
        {
            return true;
        }
        else
        {
            return false;
        }            
    }
}

TrackingManagerRegistry::register('CLTRACK_ToolAccessTrackingManager');

?>