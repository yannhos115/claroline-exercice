<?php // $Id$
/**
 * CLAROLINE
 *
 * This script display list of configuration file
 *
 * @version 1.8 $Revision$
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/config_def/
 *
 * @package CONFIG
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Mathieu Laurent   <mla@claroline.net>
 * @author Christophe Gesch� <moosh@claroline.net>
 *
 */

$cidReset=TRUE;
$gidReset=TRUE;

// include init and library files

require '../../inc/claro_init_global.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

//require_once get_path('incRepositorySys') . '/lib/course.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/config.lib.inc.php';

// define
$nameTools          = get_lang('Configuration');
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$noQUERY_STRING     = TRUE;

/* ************************************************************************** */
/*  INITIALISE VAR
/* ************************************************************************** */

$urlEditConf = 'config_edit.php';

// Get the list of definition files.
// Each one corresponding to a config file.

// Set  order of some know class and  set an name
$def_class_list['platform']['name'] = get_lang('Platform');
$def_class_list['course']['name']   = get_lang('Courses');
$def_class_list['user']['name']     = get_lang('Users');
$def_class_list['tool']['name']     = get_lang('Course tools');
$def_class_list['auth']['name']     = get_lang('Authentication');
$def_class_list['groups']['name']   = get_lang('Groups');
$def_class_list['kernel']['name']   = get_lang('Kernel');
$def_class_list['others']['name']   = get_lang('Others');

$def_list = get_config_code_class_list();

//group by class

if ( is_array($def_list) )
{
    foreach( $def_list as $code => $def)
    {
        if ( ! isset($def['class']) )
        {
            $def['class'] = 'other';
        }
        $def_class_list[$def['class']]['conf'][$code] = $def['name'];
    }
}

// set name to unknow class.
if ( is_array($def_class_list) )
foreach (array_keys($def_class_list) as $def_class )
{
    if (!isset($def_class_list[$def_class]['name']) )
    {
        $def_class_list[$def_class]['name']= ucwords($def_class);
    }
}

/**
 * Display
 */

include get_path('incRepositorySys') . '/claro_init_header.inc.php';

// display tool title

echo claro_html_tool_title($nameTools);

if ( is_array($def_class_list) )
{
    foreach( $def_class_list as $class_def_list)
    {
        if ( isset($class_def_list['conf']) && is_array($class_def_list['conf']) )
        {
            $sectionName = $class_def_list['name'];

            echo '<h4>' . $sectionName . '</h4>' . "\n";

            asort($class_def_list['conf']);

            echo '<ul>' . "\n";
            foreach ($class_def_list['conf'] as $code => $name)
            {
                echo '<li>'
                .    '<a href="' . $urlEditConf . '?config_code=' . $code .'">'
                .    get_lang($name)
                .    '</a>'
                .    '</li>' . "\n"
                ;
            }
            echo '</ul>' . "\n";
        }
    }
}

// Display footer
include get_path('incRepositorySys') . '/claro_init_footer.inc.php';

?>
