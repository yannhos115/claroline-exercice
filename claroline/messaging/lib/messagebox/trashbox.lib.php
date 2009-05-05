<?php // $Id$

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Trash box class (helper)
 *
 * @version     1.9 $Revision$
 * @copyright   2001-2008 Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

//load receivedmessagebox class
require_once dirname(__FILE__).'/receivedmessagebox.lib.php';
//load trashboxstrategy class
require_once dirname(__FILE__) . '/../selectorstrategy/trashboxstrategy.lib.php';

class TrashBox extends ReceivedMessageBox
{
    /**
     * construct the trash box
     *
     * @param int $userId user identification
     * if it is not defined it use the current user id
     * @param MessageFilter $messageFilter
     * if it not defined it use default value for the stratgy
     */
    public function __construct($userId = NULL, $messageStrategy = NULL)
    {
        if (is_null($messageStrategy))
        {
            $messageStrategy = new TrashBoxStrategy();
        }
        parent::__construct($messageStrategy,$userId);
    }
}
