<?php // $Id$
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
 * @package CLAUTH
 *
 * @author Claro Team <cvs@claroline.net>
 */

/*

CAS stands for 'Central Authentication Service' and is Single sign On (SSO)
system originally developed by the Yale University. SSO is an authentication
process enabling user to authenticate once and gain access to multiple systems.
For example, once authenticated in the library catalog, students don't have to
re-enter their password to access their Claroline courses or their Web mail.

The CAS system of Claroline is based on the free phpCAS library available at
http://esup-phpcas.sourceforge.net .

IMPORTANT NOTE. CAS system only achieves user authentication, and doesn't permit
to retrieve additional user information like name, surname or e-mail address.
To get this information available on Claroline, you have to record them
previously in the Claroline 'user' table.

 */


if ((bool) stristr($_SERVER['PHP_SELF'], basename(__FILE__))) die();

if (   ! isset($_SESSION['init_CasCheckinDone'] )
    || $logout
    || ( basename($_SERVER['SCRIPT_NAME']) == 'login.php' && isset($_REQUEST['authModeReq']) && $_REQUEST['authModeReq'] == 'CAS' )
    || isset($_REQUEST['fromCasServer']) )
{
    include_once dirname(__FILE__) . '/../../inc/lib/cas/CAS.php';
    phpCAS::client(CAS_VERSION_2_0, get_conf('claro_CasServerHostUrl'), get_conf('claro_CasServerHostPort',443) , get_conf('claro_CasServerRoot','') );

    if ($logout)
    {
        $userLoggedOnCas = false;
    }
    elseif( basename($_SERVER['SCRIPT_NAME']) == 'login.php' )
    {
        // set the call back url
        if     (   isset($_REQUEST['sourceUrl'])     ) $casCallBackUrl = base64_decode($_REQUEST['sourceUrl']);
        elseif ( ! is_null($_SERVER['HTTP_REFERER']) ) $casCallBackUrl = $_SERVER['HTTP_REFERER'];
        else
        {
            $casCallBackUrl = (isset( $_SERVER['HTTPS']) && ($_SERVER['HTTPS']=='on'||$_SERVER['HTTPS']==1) ? 'https://' : 'http://')
                    . $_SERVER['HTTP_HOST'] . get_conf('urlAppend').'/';
        } 
        
        $casCallBackUrl .= ( strstr( $casCallBackUrl, '?' ) ? '&' : '?')
                        .  'fromCasServer=true';

        if ( $_SESSION['_cid'] )
        {
            $casCallBackUrl .= ( strstr( $casCallBackUrl, '?' ) ? '&' : '?')
                            .  'cidReq='.urlencode($_SESSION['_cid']);
        }

        if ( $_SESSION['_gid'] )
        {
            $casCallBackUrl .= ( strstr( $casCallBackUrl, '?' ) ? '&' : '?')
                         .  'gidReq='.urlencode($_SESSION['_gid']);
        }

        $_SESSION['casCallBackUrl'] = base64_encode($casCallBackUrl); // we record callback url in session
        phpCAS::forceAuthentication();

        $userLoggedOnCas                  = true;
        $_SESSION['init_CasCheckingDone'] = true;
    }
    elseif( ! isset($_SESSION['init_CasCheckinDone']) || $_REQUEST['fromCasServer'] == true )
    {

        if ( phpCAS::checkAuthentication() ) $userLoggedOnCas = true;
        else                                 $userLoggedOnCas = false;

        $_SESSION['init_CasCheckinDone'] = true;
    }

    if ($userLoggedOnCas)
    {
            $sql = "SELECT user_id  AS userId
                FROM `" . $tbl_user . "`
                WHERE username = '" . claro_sql_escape(phpCAS::getUser()) . "'
                AND   authSource = 'CAS'";

        $uData = claro_sql_query_fetch_all($sql);

        if( count($uData) > 0)
        {
            $_uid                 = $uData[0]['userId'];
            $uidReset             = true;

            $claro_loginRequested = true;
            $claro_loginSucceeded = true;
        }
        else
        {
            $_uid                 = null;

            $claro_loginRequested = true;
            $claro_loginSucceeded = false;
        }
    } // end if userLoggedOnCas


} // end if init_CasCheckinDone' || logout _SERVER['SCRIPT_NAME']) == 'login.php'

?>
