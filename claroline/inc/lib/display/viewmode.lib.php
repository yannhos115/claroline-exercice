<?php // $Id$

    // vim: expandtab sw=4 ts=4 sts=4:
    
    if ( count( get_included_files() ) == 1 )
    {
        die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
    }

    /**
     * View mode block
     *
     * @version     1.9 $Revision$
     * @copyright   2001-2008 Universite catholique de Louvain (UCL)
     * @author      Claroline Team <info@claroline.net>
     * @author      Frederic Minne <zefredz@claroline.net>
     * @license     http://www.gnu.org/copyleft/gpl.html
     *              GNU GENERAL PUBLIC LICENSE version 2 or later
     * @package     display
     */

    class ClaroViewMode implements Display
    {
        private static $instance = false;

        private function __construct()
        {
        }
        
        public function render()
        {
            $out = '';
            
            if ( !claro_is_user_authenticated() )
            {
                if( get_conf('claro_displayLocalAuthForm',true) == true )
                {
                    $out .= $this->renderLoginLink();   
                }                
            }
            elseif ( ( !claro_is_platform_admin() )
                && ( claro_is_in_a_course() && !claro_is_course_member() )
                && claro_get_current_course_data('registrationAllowed') )
            {
                $out .= $this->renderRegistrationLink();
            }
            elseif ( claro_is_display_mode_available() )
            {
                $out .= $this->renderViewModeSwitch();
            }
            
            return $out;
        }
        
        private function renderViewModeSwitch()
        {
            $out = '';

            if ( isset($_REQUEST['View mode']) )
            {
                $out .= claro_html_tool_view_option($_REQUEST['View mode']);
            }
            else
            {
                $out .= claro_html_tool_view_option();
            }

            if ( claro_is_in_a_course() && ! claro_is_platform_admin() && ! claro_is_course_member() )
            {
                $out .= ' | <a href="' . get_path('clarolineRepositoryWeb')
                    . 'auth/courses.php?cmd=exReg&course='
                    . claro_get_current_course_id().'">'
                    . claro_html_icon( 'enroll' )
                    . '<b>' . get_lang('Enrolment') . '</b>'
                    . '</a>'
                    ;
            }

            $out .= "\n";
            
            return $out;
        }
        
        private function renderRegistrationLink()
        {
            return '<a href="'
                . get_path('clarolineRepositoryWeb')
                . 'auth/courses.php?cmd=exReg&course='.claro_get_current_course_id()
                . '">'
                . claro_html_icon( 'enroll' )
                . '<b>' . get_lang('Enrolment') . '</b>'
                . '</a>'  
                ;
        }
        
        private function renderLoginLink()
        {
            return '<a href="' . get_path('clarolineRepositoryWeb') . 'auth/login.php'
                . '?sourceUrl='
                . urlencode( base64_encode(
                    ( isset( $_SERVER['HTTPS'])
                        && ($_SERVER['HTTPS']=='on'||$_SERVER['HTTPS']==1)
                        ? 'https://'
                        : 'http://' )
                    . $_SERVER['HTTP_HOST'] . htmlspecialchars( strip_tags( $_SERVER['REQUEST_URI'] ) ) ) )
                . '" target="_top">'
                . get_lang('Login')
                . '</a>'
                ;
        }

        public static function getInstance()
        {
            if ( ! ClaroViewMode::$instance )
            {
                ClaroViewMode::$instance = new ClaroViewMode;
            }

            return ClaroViewMode::$instance;
        }
    }
?>