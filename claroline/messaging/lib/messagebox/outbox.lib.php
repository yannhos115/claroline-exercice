<?php // $Id$

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * out box  class
 *
 * @version     1.9 $Revision$
 * @copyright   2001-2008 Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

//load messagebox class
require_once dirname(__FILE__) . '/messagebox.lib.php';
//load sentMessage class
require_once dirname(__FILE__).'/../message/sentmessage.lib.php';
//load outboxstrategy class
require_once dirname(__FILE__).'/../selectorstrategy/outboxstrategy.lib.php';


class OutBox extends MessageBox
{

    /**
     * create an object outbox for a user and with some stragegies
     *
     * @param int $userId user identification
     * @param SelectorStrategy $messageStrategy strategy to apply 
     */
    public function __construct($userId = NULL, $messageStrategy = NULL)
    {
        if (is_null($messageStrategy))
        {
            $messageStrategy = new OutBoxStrategy();
        }
        
        parent::__construct($messageStrategy,$userId);
    }
    
    /**
     * @see MessageBox
     *
     */
    public function loadMessageList()
    {
        if (!$this->messageList)
        {
            if ( !is_null($this->messageFilter) )
            {
                $strategy = $this->messageFilter->getStrategy();
                $order = $this->messageFilter->getOrder();
                $limit = $this->messageFilter->getLimit();
            }
            else
            {
                $strategy = "";
                $order = "";
                $limit = "";
            }
            
            $tableName = get_module_main_tbl(array('im_message'));
            
            $sql =
                "SELECT message_id, sender, subject, message, send_time, course, `group`, tools \n"
                . "FROM `".$tableName['im_message']."` \n"
                . "WHERE sender = ".(int)$this->userId . "\n"
                .    " " . $strategy
                .    " " . $order
                .    " " . $limit
            ;

            $this->messageList = claro_sql_query_fetch_all_rows($sql);
            
            //load number of message if its necessary
            $this->loadNumberOfMessage();
        }
    }
    
    /**
     * return the number of message in the current box (with strategy applied)
     *
     * @return int number of message in the current box
     */
    public function getNumberOfMessage()
    {
        //load number of message if its necessary
        $this->loadNumberOfMessage();
        
        return $this->numberOfMessage;
    }

    protected function loadNumberOfMessage()
    {
        if (!$this->numberOfMessage)
        {
            if ( ! is_null( $this->messageFilter ) )
            {
                $strategy = $this->messageFilter->getStrategy();
            }
            else
            {
                $strategy = "";
            }
            
            $tableName = get_module_main_tbl(array('im_message'));
            
            $readSQL =
                "SELECT count(*) \n"
                . "FROM `".$tableName['im_message']."` \n"
                . "WHERE sender = ".(int)$this->userId . "\n"
                .    " " . $strategy
                ;
    
            $this->numberOfMessage = claro_sql_query_fetch_single_value($readSQL);
        }
    }
    
    /**
     * return the number of page in the current box(with strategy applied)
     *
     * @return int number of page 
     */
    public function getNumberOfPage()
    {
        return ceil($this->getNumberOfMessage() / $this->getMessageStrategy()->getNumberOfMessagePerPage());
    }
    
    /**
     * return the current message
     *
     * @return SentMessage current message
     */
    public function current()
    {
        return SentMessage::fromArray($this->messageList[$this->index]);
    }
}
