<?php // $Id$
//----------------------------------------------------------------------
// CLAROLINE
//----------------------------------------------------------------------
// Copyright (c) 2001-2005 Universite catholique de Louvain (UCL)
//----------------------------------------------------------------------
// This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
// as published by the FREE SOFTWARE FOUNDATION. The GPL is available
// through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
//----------------------------------------------------------------------
// Authors: see 'credits' file
//----------------------------------------------------------------------
$englishLangName = "Japanese";
$localLangName = "Japanese";

$iso639_1_code = "ja";
$iso639_2_code = "jpn";

$langNameOfLang['brazilian']="brazilian";
$langNameOfLang['english']="english";
$langNameOfLang['finnish']="finnish";
$langNameOfLang['french']="french";
$langNameOfLang['german']="german";
$langNameOfLang['italian']="italian";
$langNameOfLang['japanese']="japanese";
$langNameOfLang['polish']="polish";
$langNameOfLang['simpl_chinese']="simplified chinese";
$langNameOfLang['spanish']="spanish";
$langNameOfLang['swedish']="swedish";
$langNameOfLang['thai']="thai";


$charset = 'EUC-JP';
$text_dir = 'ltr';
$left_font_family = 'sans-serif';
$right_font_family = 'sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('�Х���', 'KB', 'MB', 'GB');

$day_of_week = array('��', '��', '��', '��', '��', '��', '��');
$month = array('1��','2��','3��','4��','5��','6��','7��','8��','9��','10��','11��','12��');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below

$langDay_of_weekNames['init'] = array('��', '��', '��', '��', '��', '��', '��'); // 1 letter
$langDay_of_weekNames['short'] = array('��', '��', '��', '��', '��', '��', '��');
$langDay_of_weekNames['long'] = array('��', '��', '��', '��', '��', '��', '��'); // complete word

$langMonthNames['init']  = array('1��','2��','3��','4��','5��','6��','7��','8��','9��','10��','11��','12��');
$langMonthNames['short'] = array('1��','2��','3��','4��','5��','6��','7��','8��','9��','10��','11��','12��');
$langMonthNames['long'] = array('1��','2��','3��','4��','5��','6��','7��','8��','9��','10��','11��','12��');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%Yǯ%b%e��";
$dateFormatLong  = '%Yǯ%B%e��';
$dateTimeFormatLong  = '%Yǯ%B%e�� %H:%M';
$timeNoSecFormat = '%H:%M';

?>