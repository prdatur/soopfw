<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {language} function plugin
 *
 * Type:     function<br>
 * Name:     language<br>
 * Purpose:  returning language vars<br>
 * @author Christian Ackermann
 * @param array
 * @param Smarty
 */
function smarty_function_language($params)
{
    if (!isset($params['var']) || $params['var'] == '')
    {
        return "";
    }

    return $_SESSION["language"][$params['var']];
}

?>
