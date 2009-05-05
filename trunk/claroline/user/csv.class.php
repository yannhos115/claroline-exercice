<?php // $Id$
/**
 * CLAROLINE
 *
 * @version 1.9 $Revision$
 *
 * @copyright 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLUSR
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Dimitri Rambout <dimitri.rambout@uclouvain.be>
 *
 */

class csv
{
    /**
     * @var $fieldSeparator field separator
     */
    protected $fieldSeparator;
    /**
     * @var $enclosedBy field enclosed by
     */
    protected $enclosedBy;
    /**
     * @var $fieldName
     */
    protected $fileName;
    /**
     * @var $csvContent array of rows;
     */
    protected $csvContent;
    
    protected $firstLine;
    
    /**
     * constructor
     *
     * @author Dimitri Rambout <dimitri.rambout@uclouvain.be>
     * @param $fieldSeparator field separator
     * @param $enclosedBy fields encolsed by
     *
     */    
    public function __construct( $fieldSeparator = ',', $enclosedBy = '"')
    {
        $this->fieldSeparator = $fieldSeparator;
        $this->enclosedBy = $enclosedBy;
        $this->csvContent = array();
    }
    
    /**
     * load the content of a csv file 
     *
     * @author Dimitri Rambout <dimitri.rambout@uclouvain.be>
     * @param $fileName name of the csv file
     * @return boolean
     * 
     */    
    public function load( $fileName )
    {        
        $this->fileName = $fileName;
        
        if( !is_file($this->fileName) )
        {
            return false;
        }
        
        if( !$handle = fopen($this->fileName, "r") )
        {
            return false;
        }
        
        $this->firstLine = fgets( $handle);
        
        rewind( $handle);
        
        $content = array();
        while( ( $row = fgetcsv( $handle, 0, $this->fieldSeparator, $this->enclosedBy) ) !== FALSE)
        {
            $content[] = $row;
        }
        
        $this->setCSVContent( $content );
        
        return true;
    }
    
    public function getFirstLine()
    {
        return $this->firstLine;
    }
    
    /**
     * set the content of csvContent
     *
     * @author Dimitri Rambout <dimitri.rambout@uclouvain.be>
     * @param $content
     * 
     */    
    public function setCSVContent( $content )
    {
        $this->csvContent = $content;
    }
    
    /**
     * get the content of csvContent
     *
     * @author Dimitri Rambout <dimitri.rambout@uclouvain.be>
     * @return $csvContent array of rows
     * 
     */
    public function getCSVContent()
    {
        return $this->csvContent;
    }
    
    /**
     * create an usable array with all the data
     *
     * @author Dimitri Rambout <dimitri.rambout@uclouvain.be>
     * @param $content array that need to be changed in an usable array
     * @param $useFirstLine use the first line of the array to define cols
     * @param $keys 
     * @return $useableArray converted array
     * 
     */
    public function createUsableArray( $content, $useFirstLine = true, $keys = null)
    {
        if( !is_array( $content ) )
        {
            return false;
        }
        
        if( $useFirstLine )
        {
            $keys = $content[0];
            unset($content[0]);
        }
        
        if( !(!is_null( $keys ) && is_array( $keys ) && count( $keys )) )
        {
            return false;
        }
        
        $useableArray = array();
        foreach( $keys as $col )
        {
            $useableArray[$col] = array();
        }
        
        foreach( $content as $i => $row)
        {
            foreach( $row as $j => $r)
            {
                foreach($keys as $col => $val )
                {
                    if($j == $col)
                    {
                        $useableArray[$val][$i] = $r;                        
                    }                    
                }
            }
        }
        
        return $useableArray;
        
    }
    
    /**
     * check the value of user id field
     *
     * @param $data user id value
     * @return string or null
     * 
     */    
    protected function checkUserIdField( $data )
    {
        $errors = array();
        foreach( $data as $key => $value )
        {
            if( !(is_numeric( $value ) && $value >= 0) )
            {
              $errors[] =  get_lang('User ID must be a number at line %key', array( '%key' => $key )); 
            }
            elseif( array_search( $value, $data) != $key )
            {
              $errors[] = get_lang('User ID seems to be duplicate at line %key', array( '%key' => $key ));
            }
        }
        
        return $errors;
    }
    
    /**
     * check the value of the email field
     *
     * @param $data email value
     * @return string or null
     *
     **/    
    protected function checkEmailField( $data )
    {
        $errors = array();
        foreach( $data as $key => $value )
        {
            if( !empty( $value ) )
            {
               if( !is_well_formed_email_address( $value ) )
               {
                    $errors[] = get_lang('Invalid email address at line %key', array( '%key' => $key ));
               }
               elseif( array_search( $value, $data) != $key )
               {
                    $errors[] = get_lang('Email address seems to be duplicate at line %key', array( '%key' => $key ));
               }
            }
        }
        
        return $errors;
    }
    
    /**
     * check the defined format
     * 
     * @author Dimitri Rambout <dimitri.rambout@uclouvain.be>
     * @param $format format used in the csv
     * @param $delim field delimiter
     * @param $enclosedBy char used to enclose fields
     *
     * @return boolean if all requiered fields are defined, return true
     *
     */    
    public function format_ok($format, $delim, $enclosedBy)
    {
        $fieldarray = explode($delim,$format);
        if ($enclosedBy == 'dbquote') $enclosedBy = '"';
    
        $username_found = FALSE;
        $password_found = FALSE;
        $firstname_found  = FALSE;
        $lastname_found     = FALSE;
        
        foreach ($fieldarray as $field)
        {
    
            if (!empty($enclosedBy))
            {
                $fieldTempArray = explode($enclosedBy,$field);
                if (isset($fieldTempArray[1])) $field = $fieldTempArray[1];
            }
            if ( trim($field) == 'firstname' )
            {
                $firstname_found = TRUE;
            }
            if (trim($field)=='lastname')
            {
                $lastname_found = TRUE;
            }
            if (trim($field)=='username')
            {
                $username_found = TRUE;
            }            
            /*if ( trim($field) == 'password' )
            {
                $password_found = TRUE;
            }*/
            
        }
        return ($username_found && $firstname_found && $lastname_found);
    }
    
    
}

class csvImport extends csv
{
    
    
    /*public function __construct( $fieldSeparator = ',', $enclosedBy = '"')
    {
        $this->fieldSeparator = $fieldSeparator;
        $this->enclosedBy = $enclosedBy;
        $this->csvContent = array();
    }*/
    
    
    
    /**
     * check each field content based on the key of the array
     *
     * @param $content array of values from the csv file
     *
     * @return boolean
     *
     */    
    public function checkFieldsErrors( $content )
    {
      $errors = array();
      
      foreach( $content as $key => $values )
      {
        switch( $key )
        {
            case 'userId' :
            {
                $error = $this->checkUserIdField( $values );
                if( !is_null( $error ) )
                {
                    $errors[$key] = $error;    
                }                
            }
            break;
            case 'email' :
            {
                $error = $this->checkEmailField( $values );
                if( !empty( $error ) )
                {
                    $errors[$key] = $error;    
                }                
            }
            break;
            case 'username' :
            {                
                $error = $this->checkUserNameField( $values );
                if( !is_null( $error ) )
                {
                    $errors[$key] = $error;    
                }                
            }
            break;
            case 'groupName' :
            {
                $error = $this->checkUserGroup( $values );
                if( !is_null( $error ) )
                {
                    $errors[$key] = $error;
                }
            }
        }
      }
      return $errors;
    }
    
    
    
    private function checkUserNameField( $data )
    {
      $errors = array();
      
      $tbl_mdb_names = claro_sql_get_main_tbl();
      $tbl_user      = $tbl_mdb_names['user'];
      
      foreach( $data as $key => $value )
      {
        if( empty( $value) )
        {
          $errors[] = get_lang('Username is empty at line %key', array( '%key' => $key ));
        }
        elseif( array_search( $value, $data) != $key )
        {
          $errors[] = get_lang('Username seems to be duplicate at line %key', array( '%key' => $key ));
        }
        else
        {
          $sql = "SELECT `user_id` FROM `". $tbl_user ."` WHERE 1=0 ";
          $sql .= " OR `username` like '" . claro_sql_escape( $value ) . "'";
          $userId = claro_sql_query_fetch_single_value( $sql );
          if( $userId && !is_null( $userId ) )
          {
            $errors[] = get_lang('Username already exists in the database at line %key', array( '%key' => $key));
          }
        }
      }
      
      
      return $errors;
    }
    
    
    private function checkUserGroup( $groupNames )
    {
        
        return null;
    }
    
    public function importUsers( $class_id )
    {
        $csvContent = $this->getCSVContent();
        if( empty( $csvContent ) )
        {
            return false;
        }
        
        if( !(isset($_REQUEST['users']) && count($_REQUEST['users']) ) )
        {
            return false;
        }
        
        $csvUseableArray = $this->createUsableArray( $csvContent );
        
        $fields = $csvContent[0];
        unset( $csvContent[0] );
        
        $logs = array();
        
        $tbl_mdb_names  = claro_sql_get_main_tbl();
        $tbl_user       = $tbl_mdb_names['user'];
        $tbl_course_user = $tbl_mdb_names['rel_course_user'];
        
        $tbl_cdb_names = claro_sql_get_course_tbl();
        $tbl_group_rel_team_user     = $tbl_cdb_names['group_rel_team_user'];
        
        $groupsImported = array();
            
        foreach( $_REQUEST['users'] as $user_id )
        {
            if(!isset($csvUseableArray['username'][$user_id]))
            {
                $logs['errors'][] = get_lang('Unable to find the user in the csv');
            }
            else
            {
                $userInfo['username'] = $csvUseableArray['username'][$user_id];
                $userInfo['firstname'] = $csvUseableArray['firstname'][$user_id];
                $userInfo['lastname'] = $csvUseableArray['lastname'][$user_id];
                $userInfo['email'] = $csvUseableArray['email'][$user_id];
                $userInfo['password'] = '';
                $userInfo['officialCode'] = $csvUseableArray['officialCode'][$user_id];
                
                //check user existe if not create is asked                
                $resultSearch = user_search( array( 'username' => $userInfo['username'] ), null, true, true );
                if( !empty($resultSearch))
                {
                  $userId = $resultSearch[0]['uid'];
                  $logs['errors'][] = get_lang( 'User %username not created because it already exists in the database', array( '%username' => $userInfo['username'] ) );
                }
                else
                {
                    
                    $userId = user_create( $userInfo );
                    if( $userId != 0 )
                    {
                        $logs['success'][] = get_lang( 'User %username created successfully', array( '%username' => $userInfo['username'] ) );                        
                    }
                    else
                    {
                        $logs['errors'][] = get_lang( 'Unable to create user %username', array('%username' => $userInfo['username'] ) );
                    }
                }
                
                if( $userId )
                {
                  //join class if needed
                  if( $class_id )
                  {
                    if( ! $return = user_add_to_class( $userId, $class_id ) )
                    {
                      $logs['errors'][] = get_lang( 'Unable to add %username in the selected class', array( '%username' => $userInfo['username'] ) );
                    }
                    else
                    {
                      $logs['success'][] = get_lang( 'User %username added in the selected class', array( '%username' => $userInfo['username'] ) );
                    }
                  }
                }
            }
        }
        
        return $logs;
    }
    /**
     * import users in course
     *
     * @author Dimitri Rambout <dimitri.rambout@gmail.com>
     * @param $courseId id of the course
     *
     * @return boolean
     *
     */
    public function importUsersInCourse( $courseId, $canCreateUser = true, $enrollUserInCourse = true, $class_id = 0 )
    {
        $csvContent = $this->getCSVContent();
        if( empty( $csvContent ) )
        {
            return false;
        }
        
        if( !(isset($_REQUEST['users']) && count($_REQUEST['users']) ) )
        {
            return false;
        }
        
        $csvUseableArray = $this->createUsableArray( $csvContent );
        
        $fields = $csvContent[0];
        unset( $csvContent[0] );       
        
        $logs = array();
        
        $tbl_mdb_names  = claro_sql_get_main_tbl();
        $tbl_user       = $tbl_mdb_names['user'];
        $tbl_course_user = $tbl_mdb_names['rel_course_user'];
        
        $tbl_cdb_names = claro_sql_get_course_tbl();
        $tbl_group_rel_team_user     = $tbl_cdb_names['group_rel_team_user'];
        
        $groupsImported = array();
            
        foreach( $_REQUEST['users'] as $user_id )
        {
            if(!isset($csvUseableArray['username'][$user_id]))
            {
                $logs['errors'][] = get_lang('Unable to find the user in the csv');
            }
            else
            {
                $userInfo['username'] = $csvUseableArray['username'][$user_id];
                $userInfo['firstname'] = $csvUseableArray['firstname'][$user_id];
                $userInfo['lastname'] = $csvUseableArray['lastname'][$user_id];
                $userInfo['email'] = $csvUseableArray['email'][$user_id];
                $userInfo['password'] = '';
                $userInfo['officialCode'] = $csvUseableArray['officialCode'][$user_id];
                if( isset( $csvUseableArray['groupName'][$user_id] ) )
                {
                  $groupNames = $csvUseableArray['groupName'][$user_id];
                }
                else
                {
                  $groupNames = null;
                }
                
                
                //check user existe if not create is asked                
                $resultSearch = user_search( array( 'username' => $userInfo['username'] ), null, true, true );
                
                if( empty($resultSearch))
                {
                  if( !$canCreateUser )
                  {
                    $userId = 0;
                    $logs['errors'][] = get_lang( 'Unable to create user %username, option is disabled in configuration', array('%username' => $userInfo['username'] ) );
                  }
                  else
                  {
                    $userId = user_create( $userInfo );
                    if( $userId != 0 )
                    {
                        $logs['success'][] = get_lang( 'User %username created successfully', array( '%username' => $userInfo['username'] ) );
                    }
                    else
                    {
                        $logs['errors'][] = get_lang( 'Unable to create user %username', array('%username' => $userInfo['username'] ) );
                    } 
                  }
                }
                else
                {
                  $userId = $resultSearch[0]['uid'];
                  $logs['errors'][] = get_lang( 'User %username not created because it already exists in the database', array( '%username' => $userInfo['username'] ) );
                }                
                
                if( $userId == 0)
                {
                    $logs['errors'][] = get_lang( 'Unable to add user %username in this course', array('%username' => $userInfo['username'] ) );
                }
                else
                {
                  if( !$enrollUserInCourse )
                  {
                    $logs['errors'][] = get_lang( 'Unable to add user %username in this course, option is disabled in configuration', array('%username' => $userInfo['username'] ) );
                  }
                  else
                  {
                    if( !user_add_to_course( $userId, $courseId, false, false, false) )
                    {
                      $logs['errors'][] = get_lang( 'Unable to add user %username in this course', array('%username' => $userInfo['username'] ) );
                    }
                    else
                    {
                      $logs['success'][] = get_lang( 'User %username added in course %courseId', array('%username' => $userInfo['username'], '%courseId' => $courseId ));
                      //join class if needed
                      if( $class_id )
                      {
                        if( ! $return = user_add_to_class( $userId, $class_id ) )
                        {
                          $logs['errors'][] = get_lang( 'Unable to add %username in the selected class', array( '%username' => $userInfo['username'] ) );
                        }
                        else
                        {
                          $logs['success'][] = get_lang( 'User %username added in the selected class', array( '%username' => $userInfo['username'] ) );
                        }
                      }
                      //join group
                      $groups = split(',', $groupNames);
                      if( is_array( $groups ) )
                      {
                        foreach( $groups as $group)
                        {
                          $group = trim($group);
                          if( !empty($group) )
                          {
                            $groupsImported[$group][] = $userId;
                          }
                          
                        }
                      }
                    }
                  }
                  
                }
            }
        }
        
        foreach( $groupsImported as $group => $users)
        {
            $GLOBALS['currentCourseRepository'] = claro_get_course_path( $courseId );
            $groupId = create_group($group, null);
            if( $groupId == 0 )
            {
                $logs['errors'][] = get_lang( 'Unable to create group %groupname', array( '%groupname' => $group) );
                
            }
            else
            {
                foreach( $users as $userId)
                {
                    
                    $sql = "INSERT INTO `" . $tbl_group_rel_team_user . "`
                            SET user = " . (int) $userId . ",
                                team = " . (int) $groupId ;
                    if( !claro_sql_query( $sql ) )
                    {
                        $logs['errors'][] = get_lang( 'Unable to add user in group %groupname', array('%groupname' => $group) );
                    }
                }
            }
        }
        
        return $logs;
        
    }
    
    
}

?>