<?php

// ************************************************************************************//
// * Air Content Managment System v1
// ************************************************************************************//
// * Copyright (c) 2008-2009 Air-Unlimited
// * Web           http://www.air-unlimited.de/
// ************************************************************************************//
// * Air Content Managment System v1 is NOT free software.
// * You may not redistribute this package or any of it's files.
// ************************************************************************************//
// * $Date: 2008-03-31 20:32:15 +0100 (Mo, 31 Mrz 2008) $
// * $Author: Christian Ackermann $
// * $Rev: 1 $
// ************************************************************************************//

    /**
     * This class provides a Button input<br>
     * @package    libs.form.inputs
     * @author     Christian Ackermann <webmaster@air-unlimited.de>
     * @copyright  2008-2009 Air-Unlimited
     * @version    Release: 1.1
     * @class Button
     */
    class InnerButton extends Button
    {
		/**
         * constructor
         *
         * @param String $name the input name
         * @param String $id the input id
         * @param Boolean $readonly true or false if input is readonly
         */
        function __construct ($name, $value = "", $label = "", $class = "", $id = "") {
			parent::__construct($name, $value, $class, $id);
			$this->config("label",$label);
		}
        /** @method init
         * init the input
         */
        public function init () {
			$this->config_array("css_class", "form_button");
            $this->config("template", "<div {value}{id}{class}{style}{other}>{value_button}</div>");
            $this->config("type", "inner_button");

        }
    }

?>