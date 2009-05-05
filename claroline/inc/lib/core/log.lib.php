<?php // $Id$

class Logger
{
    private $tbl_log;
    
    public function __construct()
    {
        $tbl_mdb_names = claro_sql_get_main_tbl();
        $this->tbl_log  = $tbl_mdb_names['log'];
    }
    
    public function log( $type, $data )
    {
        $cid        = claro_get_current_course_id();
        $tid        = claro_get_current_tool_id();
        $uid        = claro_get_current_user_id();
        $date       = claro_date("Y-m-d H:i:s");

        $ip         = !empty( $_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

        $data = serialize( $data );

        $sql = "INSERT INTO `" . $this->tbl_log . "`
                SET `course_code` = " . ( is_null($cid) ? "NULL" : "'" . claro_sql_escape($cid) . "'" ) . ",
                    `tool_id` = ". ( is_null($tid) ? "NULL" : "'" . claro_sql_escape($tid) . "'" ) . ",
                    `user_id` = ". ( is_null($uid) ? "NULL" : "'" . claro_sql_escape($uid) . "'" ) . ",
                    `ip` = ". ( is_null($ip) ? "NULL" : "'" . claro_sql_escape($ip) . "'" ) . ",
                    `date` = '" . $date . "',
                    `type` = '" . claro_sql_escape($type) . "',
                    `data` = '" . claro_sql_escape($data) . "'";

        return claro_sql_query($sql);
    }
}
