<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */


/**
 * Smarty count_characters modifier plugin
 *
 * Type:     modifier<br>
 * Name:     count_characteres<br>
 * Purpose:  count the number of characters in a text
 * @link http://smarty.php.net/manual/en/language.modifier.count.characters.php
 *          count_characters (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string $string input string
 * @param string $menu the menu
 * @return integer number of characters
 */
function smarty_modifier_get_menu($string, $menu = '')
{
	if(!empty($menu)) {
		$menu_obj = new MenuObj($menu);
		if($menu_obj->load_success()) {
			return $menu_obj->get_menu_tree(true);
		}

	}
	return array();
}
?>
