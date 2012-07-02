<?php

/**
* Smarty plugin
* 
* @package Smarty
* @subpackage PluginsModifier
*/

/**
* Smarty highlight modifier plugin
* 
* Type:     modifier<br>
* Name:     highlight<br>
* Purpose:  highlight string for output
* 
* @author Christian Ackerman
* @param string $string input string
* @return string escaped input string
*/
function smarty_modifier_highlight($string)
{
	$string = preg_replace("@".quote(solr::HIGHLIGHT_PRE,"@")."(.*)".quote(solr::HIGHLIGHT_POST,"@")."@iUs","<em class=\"hl\">\\1</em>", $string);
	$tmp = solr::HIGHLIGHT_POST;
	$len = strlen($tmp);
	for($i = $len; $i > 2; $i--)
	{
		$string = str_replace(substr($tmp, 0, $i),"",$string);
	}

	$tmp = solr::HIGHLIGHT_PRE;
	$len = strlen($tmp);
	for($i = $len; $i > 0; $i--)
	{
		$string = str_replace(substr($tmp, 0, $i),"",$string);
	}
	return $string;
} 

?>
