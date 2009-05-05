<?php // $Id$
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision$
 *
 * @copyright (c) 2001-2007 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 *
 * @package CLLNP
 *
 */
// document browser vars

// Update infos about asset
$sql = "SELECT `path`
         FROM `".$TABLEASSET."`
        WHERE `module_id` = ". (int)$_SESSION['module_id'];
$assetPath = claro_sql_query_get_single_value($sql);

$courseDir = claro_get_course_path() . '/document';
$baseWorkDir = get_path('coursesRepositorySys').$courseDir;
$file = $baseWorkDir.$assetPath;
$fileSize = format_file_size(filesize($file));
$fileDate = format_date(filectime($file));


//####################################################################################\\
//######################## DISPLAY DETAILS ABOUT THE DOCUMENT ########################\\
//####################################################################################\\
echo "\n\n".'<hr noshade="noshade" size="1" />'."\n\n"
    .'<h4>'.get_lang('Document in module').'</h4>'."\n\n"
    .'<table class="claroTable" width="100%" border="0" cellspacing="2">'."\n"
    .'<thead>'."\n"
    .'<tr class="headerX">'."\n"
    .'<th>'.get_lang('Filename').'</th>'."\n"
    .'<th>'.get_lang('Size').'</th>'."\n"
    .'<th>'.get_lang('Date').'</th>'."\n"
    .'</tr>'."\n"
    .'</thead>'."\n"
    .'<tbody>'."\n"
    .'<tr align="center">'."\n"
    .'<td align="left">'.basename($file).'</td>'."\n"
    .'<td>'.$fileSize.'</td>'."\n"
    .'<td>'.$fileDate.'</td>'."\n"
    .'</tr>'."\n"
    .'</tbody>'."\n"
    .'</table>'."\n";
?>