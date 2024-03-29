<?php // $Id$
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 *
 * @version 1.8 $Revision$
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLWRK
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sébastien Piraux <pir@cerdecam.be>
 */


class Submission
{
    /**
     * @var $id id of submission, 0 if submission doesn't exist already
     */
    var $id;

    /**
     * @var $assignmentId id of assignment
     */
    var $assignmentId;

    /**
     * @var $userId id of author
     */
    var $userId;

    /**
     * @var $groupId id of group for which the submission was made, only if assignment is for groups
     */
    var $groupId;

    /**
     * @var $title name of the submission
     */
    var $title;

    /**
     * @var $visibility visibility of the submission
     */
    var $visibility;

    /**
     * @var $creationDate date of the creation of the submission, doesn't change
     */
    var $creationDate;

    /**
     * @var $lastEditDate date of last edition, change on each edit
     */
    var $lastEditDate;

    /**
     * @var $author name of the author(s) (submission can be made by someone different from original author)
     */
    var $author;

    /**
     * @var $submittedText text of submission
     */
    var $submittedText;

    /**
     * @var $submittedFilename name of the file that have been submitted
     */
    var $submittedFilename;

    /**
     * @var $parentId is set if the submission is a feedback of a submission and take the id of this submission as parentId
     */
    var $parentId;

    /**
     * @var $originalId keep the id of the author of parent submission (if this submission is a feedback)
     *                     if assignment is for group it will be the groupId else the userId of original submitter
     *                     mainly used in queries where we need to count the number of feedback of an author submission list
     *
     */
    var $originalId;

    /**
     * @var $privateFeedback feedback that will be visible only for course administrator(s)
     *                          (only set if submission is a feedback)
     */
    var $privateFeedback;

    /**
     * @var $score result (only set if submission is a feedback)
     */
    var $score;

    /**
     * @var $tblSubmission web path to assignment dir
     */
    var $tblSubmission;

    function Submission($course_id = null)
    {
        $this->id = -1;
        $this->assignmentId = null;
        $this->userId = null;
        $this->groupId = null;
        $this->title = '';
        $this->visibility = 'VISIBLE';
        $this->creationDate = 0;
        $this->lastEditDate = 0;
        $this->author = '';
        $this->submittedText = '';
        $this->submittedFilename = '';
        $this->parentId = null;
        $this->originalId = null;
        $this->privateFeedback = '';
        $this->score = null;

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
        $this->tblSubmission = $tbl_cdb_names['wrk_submission'];
    }

    function load($id)
    {
        $sql = "SELECT
                    `id`,
                    `assignment_id`,
                    `user_id`,
                    `group_id`,
                    `title`,
                    `visibility`,
                    UNIX_TIMESTAMP(`creation_date`) AS `unix_creation_date`,
                    UNIX_TIMESTAMP(`last_edit_date`) AS `unix_last_edit_date`,
                    `authors`,
                    `submitted_text`,
                    `submitted_doc_path`,
                       `parent_id`,
                    `original_id`,
                    `private_feedback`,
                    `score`
            FROM `".$this->tblSubmission."`
            WHERE `id` = ".(int) $id;

        $data = claro_sql_query_get_single_row($sql);

        if( !empty($data) )
        {
            // from query
            $this->id = (int) $data['id'];
            $this->assignmentId = $data['assignment_id'];
            $this->userId = $data['user_id'];
            $this->groupId = $data['group_id'];
            $this->title = $data['title'];
            $this->visibility = $data['visibility'];
            $this->creationDate = $data['unix_creation_date'];
            $this->lastEditDate = $data['unix_last_edit_date'];
            $this->author = $data['authors'];
            $this->submittedText = $data['submitted_text'];
            $this->submittedFilename = $data['submitted_doc_path'];
            $this->parentId = $data['parent_id'];
            $this->originalId = $data['original_id'];
            $this->privateFeedback = $data['private_feedback'];
            $this->score = $data['score'];

            return true;
        }
        else
        {
            return false;
        }
    }

    function save()
    {
        // TODO method to validate data
        if( $this->id == -1 )
        {
            // insert
            $sql = "INSERT INTO `".$this->tblSubmission."`
                    SET `assignment_id` = '".claro_sql_escape($this->assignmentId)."',
                        `user_id` = ".(is_null($this->userId)?'NULL':$this->userId).",
                        `group_id` = ".(is_null($this->groupId)?'NULL':$this->groupId).",
                        `title` = '".claro_sql_escape($this->title)."',
                        `visibility` = '".claro_sql_escape($this->visibility)."',
                        `creation_date` = NOW(),
                        `last_edit_date` = NOW(),
                        `authors` = '".claro_sql_escape($this->author)."',
                        `submitted_text` = '".claro_sql_escape($this->submittedText)."',
                        `submitted_doc_path` = '".claro_sql_escape($this->submittedFilename)."',
                        `parent_id` = ".(is_null($this->parentId)?'NULL':$this->parentId).",
                        `original_id` = ".(is_null($this->originalId)?'NULL':$this->originalId).",
                        `private_feedback` = '".claro_sql_escape($this->privateFeedback)."',
                        `score` = ".(is_null($this->score)?'NULL':$this->score);

            // execute the creation query and get id of inserted assignment
            $insertedId = claro_sql_query_insert_id($sql);

            if( $insertedId )
            {
                $this->id = (int) $insertedId;

                return $this->id;
            }
            else
            {
                return false;
            }
        }
        else
        {
            // update
            $sql = "UPDATE `".$this->tblSubmission."`
                    SET `assignment_id` = '".claro_sql_escape($this->assignmentId)."',
                        `user_id` = ".(is_null($this->userId)?'NULL':$this->userId).",
                        `group_id` = ".(is_null($this->groupId)?'NULL':$this->groupId).",
                        `title` = '".claro_sql_escape($this->title)."',
                        `visibility` = '".claro_sql_escape($this->visibility)."',
                        `last_edit_date` = NOW(),
                        `authors` = '".claro_sql_escape($this->author)."',
                        `submitted_text` = '".claro_sql_escape($this->submittedText)."',
                        `submitted_doc_path` = '".claro_sql_escape($this->submittedFilename)."',
                        `parent_id` = ".(is_null($this->parentId)?'NULL':$this->parentId).",
                        `original_id` = ".(is_null($this->originalId)?'NULL':$this->originalId).",
                        `private_feedback` = '".claro_sql_escape($this->privateFeedback)."',
                        `score` = ".(is_null($this->score)?'NULL':$this->score)."
                    WHERE `id` = '".$this->id."'";

            // execute and return main query
            if( claro_sql_query($sql) )
            {
                return $this->id;
            }
            else
            {
                return false;
            }
        }
    }

    function delete($assigDirSys)
    {
        $sql = "DELETE FROM `".$this->tblSubmission."`
                WHERE `id` = '".$this->id."'";

        if( claro_sql_query($sql) )
        {
            if( !empty($this->submittedFilename) && file_exists($assigDirSys.$this->submittedFilename) )
            {
                claro_delete_file($assigDirSys.$this->submittedFilename);
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * update visibility of an submission
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param integer $submissionId
     * @param string $visibility
     * @return boolean
     */
    function updateSubmissionVisibility($submissionId, $visibility)
    {
        // this method is not used in object context so we cannot access $this->$tblAssignment
        $tbl_cdb_names = claro_sql_get_course_tbl();
        $tblSubmission = $tbl_cdb_names['wrk_submission'];

        $acceptedValues = array('VISIBLE', 'INVISIBLE');

        if( in_array($visibility, $acceptedValues) )
        {
            $sql = "UPDATE `" . $tblSubmission . "`
                       SET `visibility` = '" . $visibility . "'
                     WHERE `id` = " . (int) $submissionId . "
                       AND `visibility` != '" . $visibility . "'";

            return  claro_sql_query($sql);
        }

        return false;
    }
    /**
     * get assignment id
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return integer
     */
    function getAssignmentId()
    {
        return $this->assignmentId;
    }

    /**
     * set assignment id
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param integer $value
     */
    function setAssignmentId($value)
    {
        $this->assignmentId = $value;
    }

    /**
     * get user id
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return integer
     */
    function getUserId()
    {
        return $this->userId;
    }

    /**
     * set user id
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param integer $value
     */
    function setUserId($value)
    {
        $this->userId = (int) $value;
    }

    /**
     * get group id
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return integer
     */
    function getGroupId()
    {
        return (int) $this->groupId;
    }

    /**
     * set group id
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param integer $value
     */
    function setGroupId($value)
    {
        $this->groupId = (int) $value;
    }

    /**
     * get title
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return string
     */
    function getTitle()
    {
        return $this->title;
    }

    /**
     * set title
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param string $value
     */
    function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * get visibility ('VISIBLE', 'INVISIBLE')
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return string
     */
    function getVisibility()
    {
        return $this->visibility;
    }

    function setVisibility($value)
    {
        $acceptedValues = array('VISIBLE', 'INVISIBLE');

        if( in_array($value, $acceptedValues) )
        {
            $this->visibility = $value;
            return true;
        }
        return false;
    }

    /**
     * get creationDate
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return integer unix timestamp
     */
    function getCreationDate()
    {
        return (int) $this->creationDate;
    }

    /**
     * set creationDate
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param integer $value unix timestamp
     */
    function setCreationDate($value)
    {
        $this->creationDate = (int) $value;
    }

    /**
     * get creationDate
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return integer unix timestamp
     */
    function getLastEditDate()
    {
        return (int) $this->lastEditDate;
    }

    /**
     * set creationDate
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param integer $value unix timestamp
     */
    function setLastEditDate($value)
    {
        $this->lastEditDate = (int) $value;
    }

    /**
     * get author
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return string
     */
    function getAuthor()
    {
        return $this->author;
    }

    /**
     * set author
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param string $value
     */
    function setAuthor($value)
    {
        $this->author = $value;
    }


    /**
     * get submitted text
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return string
     */
    function getSubmittedText()
    {
        return $this->submittedText;
    }

    /**
     * set submitted text
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param string $value
     */
    function setSubmittedText($value)
    {
        $this->submittedText = $value;
    }

    /**
     * get submitted filename
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return string
     */
    function getSubmittedFilename()
    {
        return $this->submittedFilename;
    }

    /**
     * set submitted filename
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param string $value
     */
    function setSubmittedFilename($value)
    {
        $this->submittedFilename = $value;
    }

    /**
     * get parentId
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return int
     */
    function getParentId()
    {
        return (int) $this->parentId;
    }

    /**
     * set parentId
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param int $value
     */
    function setParentId($value)
    {
        $this->parentId = (int) $value;
    }

    /**
     * get originalId
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return int
     */
    function getOriginalId()
    {
        return (int) $this->originalId;
    }

    /**
     * set originalId
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param int $value
     */
    function setOriginalId($value)
    {
        $this->originalId = (int) $value;
    }

    /**
     * get private feedback
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return string
     */
    function getPrivateFeedback()
    {
        return $this->privateFeedback;
    }

    /**
     * set private feedback
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param string $value
     */
    function setPrivateFeedback($value)
    {
        $this->privateFeedback = $value;
    }

    /**
     * get score
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @return int
     */
    function getScore()
    {
        return (int) $this->score;
    }

    /**
     * set score
     *
     * @author Sébastien Piraux <pir@cerdecam.be>
     * @param int $value
     */
    function setScore($value)
    {
        $this->score = (int) $value;
    }
}
?>