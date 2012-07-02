<?php

class MysqlTable extends Object
{
	const FIELD_TYPE_TINYINT = "TINYINT";
	const FIELD_TYPE_SMALLINT = "SMALLINT";
	const FIELD_TYPE_MEDIUMINT = "MEDIUMINT";
	const FIELD_TYPE_INT = "INT";
	const FIELD_TYPE_BIGINT = "BIGINT";

	const FIELD_TYPE_DECIMAL = "DECIMAL";
	const FIELD_TYPE_FLOAT = "FLOAT";
	const FIELD_TYPE_DOUBLE = "DOUBLE";
	const FIELD_TYPE_REAL = "REAL";

	const FIELD_TYPE_BIT = "BIT";
	const FIELD_TYPE_BOOL = "BOOL";
	const FIELD_TYPE_SERIAL = "SERIAL";

	const FIELD_TYPE_DATE = "DATE";
	const FIELD_TYPE_DATETIME = "DATETIME";
	const FIELD_TYPE_TIMESTAMP = "TIMESTAMP";
	const FIELD_TYPE_TIME = "TIME";
	const FIELD_TYPE_YEAR = "YEAR";

	const FIELD_TYPE_CHAR = "CHAR";
	const FIELD_TYPE_VARCHAR = "VARCHAR";

	const FIELD_TYPE_TINYTEXT = "TINYTEXT";
	const FIELD_TYPE_TEXT = "TEXT";
	const FIELD_TYPE_MEDIUMTEXT = "MEDIUMTEXT";
	const FIELD_TYPE_LONGTEXT = "LONGTEXT";

	const FIELD_TYPE_BINARY = "BINARY";
	const FIELD_TYPE_VARBINARY = "VARBINARY";

	const FIELD_TYPE_TINYBLOB = "TINYBLOB";
	const FIELD_TYPE_MEDIUMBLOB = "MEDIUMBLOB";
	const FIELD_TYPE_BLOB = "BLOB";
	const FIELD_TYPE_LONGBLOB = "LONGBLOB";

	const FIELD_TYPE_ENUM = "ENUM";
	const FIELD_TYPE_SET = "SET";

	const FIELD_TYPE_GEOMETRY = "GEOMETRY";
	const FIELD_TYPE_POINT = "POINT";
	const FIELD_TYPE_LINESTRING = "LINESTRING";
	const FIELD_TYPE_POLYGON = "POLYGON";
	const FIELD_TYPE_MULTIPOINT = "TIMULTIPOINTY";
	const FIELD_TYPE_MULTILINESTRING = "TINYMULTILINESTRING";
	const FIELD_TYPE_MULTIPOLYGON = "MULTIPOLYGON";
	const FIELD_TYPE_GEOMETRYCOLLECTION = "GEOMETRYCOLLECTION";

	const INDEX_TYPE_PRIMARY = "PRIMARY";
	const INDEX_TYPE_INDEX = "INDEX";
	const INDEX_TYPE_UNIQUE = "UNIQUE";
	const INDEX_TYPE_FULLTEXT = "FULLTEXT";

	const ATTRIBUTE_BINARY = "BINARY";
	const ATTRIBUTE_UNSIGNED = "UNSIGNED";
	const ATTRIBUTE_UNSIGNED_ZEROFILL = "UNSIGNED ZEROFILL";
	const ATTRIBUTE_CURRENT_TIMESTAMP = "on update CURRENT_TIMESTAMP";

	const GROUP_TYPE_NUMBER = "number";
	const GROUP_TYPE_BOOL = "bool";
	const GROUP_TYPE_DATE = "date";
	const GROUP_TYPE_TEXT = "text";
	const GROUP_TYPE_BLOB = "blob";
	const GROUP_TYPE_GEOMETRY = "geometry";

	const TABLE_ENGINE_MYISAM = "MyISAM";
	const TABLE_ENGINE_MEMORY = "MEMORY";
	const TABLE_ENGINE_INNODB = "InnoDB";
	const TABLE_ENGINE_ARCHIVE = "ARCHIVE";
	const TABLE_ENGINE_CSV = "CSV";
	const TABLE_ENGINE_FEDERATED = "FEDERATED";
	const TABLE_ENGINE_MRG_MYISAM = "MRG_MYISAM";

	public $fields = array();

	public $indexe = array("PRIMARY" => array(), "UNIQUE" => array(), "INDEX" => array(), "FULLTEXT" => array());

	public $table = array();

	function __construct($table_name = "", $engine = self::TABLE_ENGINE_INNODB) {
		parent::__construct();
		$this->table['engine'] = $engine;
		$this->table['name'] = $table_name;
	}

	public function field($name, $type, $len = "", $default = "", $attributes = "", $auto_increment = false, $comment = "") {
		$this->fields[$name] = array(
			"type" => $type,
			"len" => $len,
			"default" => $default,
			"attribute" => $attributes,
			"auto_increment" => $auto_increment,
			"comment" => $comment
		);
	}

	public function index($field_arr, $type = self::INDEX_TYPE_INDEX) {
		if (!is_array($field_arr)) {
			$field_arr = array($field_arr);
		}
		switch ($type) {
			case self::INDEX_TYPE_PRIMARY: {
					$this->indexe[$type] = $field_arr;
					break;
				}
			default: {
					$this->indexe[$type][] = $field_arr;
					break;
				}
		}
	}

	public function create_database_get_line($field, $data, $autoincrement, $for_change = false) {
		$line = "`".$field."` ";
		if ($for_change == true) {
			if (!empty($data['new_field'])) {
				$line .= "`".$data['new_field']."` ";
			}
			else {
				$line .= "`".$field."` ";
			}
		}
		$prefix = "";
		switch ($data['typ']) {
			case PDT_TINYINT:
				$prefix = "TINY";

			case PDT_MEDIUMINT:
				if (empty($prefix)) {
					$prefix = "MEDIUM";
				}

			case PDT_BIGINT:
				if (empty($prefix)) {
					$prefix = "BIG";
				}

			case PDT_SMALLINT:
				if (empty($prefix)) {
					$prefix = "SMALL";
				}

			case PDT_INT:
				$ai = "";
				if (empty($data['additional'])) {
					$signed = " UNSIGNED";
				}
				else {
					$signed = " ".$data['additional'];
				}

				if ($autoincrement) {
					$ai = " AUTO_INCREMENT";
				}
				$line .= $prefix."INT".$signed." NOT NULL".$ai;
				break;

			case PDT_FILE:
				$line .= "INT UNSIGNED NOT NULL";
				break;

			case PDT_FLOAT:
				if (empty($data['additional'])) {
					$len = "9,2";
				}
				else {
					$len = $data['additional'];
				}
				$line .= "FLOAT ( ".$len." ) NOT NULL";
				break;

			case PDT_LANGUAGE :
			case PDT_LANGUAGE_ENABLED :
				$len = (empty($data['additional'])) ? '4' : $data['additional'];
				$line .= "VARCHAR( ".$len." ) NOT NULL";
				break;

			case PDT_STRING:
			case PDT_PASSWORD:
				$len = (empty($data['additional'])) ? '255' : $data['additional'];
				$line .= "VARCHAR( ".$len." ) NOT NULL";
				break;

			case PDT_DATE:
				$line .= "DATE NOT NULL";
				break;

			case PDT_TIME:
				$line .= "TIME NOT NULL";
				break;

			case PDT_DATETIME:
				$line .= "DATETIME NOT NULL";
				break;

			case PDT_SERIALIZED:
			case PDT_TEXT:
				$line .= "TEXT NOT NULL";
				break;

			case PDT_BOOL:
				$line .= "BOOL NOT NULL";
				break;

			case PDT_ENUM:
				$vals = array();
				if (!is_array($data['additional'])) {
					$data['additional'] = array($data['additional']);
				}
				foreach ($data['additional'] AS $val) {
					$vals[] = "'".$val."'";
				}
				$line .= "ENUM( ".implode(", ", $vals)." ) NOT NULL";
				break;
		}

		if ($data['typ'] != PDT_DATE && $data['typ'] != PDT_DATETIME && $data['typ'] != PDT_TIME && !empty($data['default'])) {
			$line .= " DEFAULT '".$data['default']."'";
		}
		return $line;
	}

	public function create_database_from_object(AbstractDataManagment $obj) {
		$struct = $obj->get_dbstruct();

		$ref = $rows = array();
		$ref_keys = $struct->get_reference_key();
		if (!empty($ref_keys)) {

			foreach ($ref_keys AS $refkey) {

				$k = "`".$refkey."`";

				if ($struct->get_field_type($refkey) == PDT_TEXT) {
					$k .= " (255)";
				}

				$ref[] = $k;
			}
		}

		$struct_data = $struct->get_struct();
		foreach ($struct_data AS $field => $data) {
			$rows[] = $this->create_database_get_line($field, $data, ($struct->get_auto_increment() == $field));
		}

		if (!empty($ref)) {
			$rows[] = "PRIMARY KEY ( ".implode(", ", $ref)." )";
		}

		if ($this->db->query_master("CREATE TABLE `".$struct->get_table()."` (".implode(" ,\n", $rows).") ENGINE = InnoDB  DEFAULT CHARSET=utf8;")) {
			return true;
		}
		else {
			return false;
		}
	}

	private function get_type_group($type) {
		switch ($type) {
			case self::FIELD_TYPE_TINYINT:
			case self::FIELD_TYPE_SMALLINT:
			case self::FIELD_TYPE_MEDIUMINT:
			case self::FIELD_TYPE_INT:
			case self::FIELD_TYPE_BIGINT:

			case self::FIELD_TYPE_DECIMAL:
			case self::FIELD_TYPE_FLOAT:
			case self::FIELD_TYPE_DOUBLE:
			case self::FIELD_TYPE_REAL: return self::GROUP_TYPE_NUMBER;
				break;

			case self::FIELD_TYPE_BIT:
			case self::FIELD_TYPE_BOOL:
			case self::FIELD_TYPE_SERIAL: return self::GROUP_TYPE_BOOL;
				break;

			case self::FIELD_TYPE_DATE:
			case self::FIELD_TYPE_DATETIME:
			case self::FIELD_TYPE_TIMESTAMP:
			case self::FIELD_TYPE_TIME:
			case self::FIELD_TYPE_YEAR: return self::GROUP_TYPE_DATE;
				break;

			case self::FIELD_TYPE_CHAR:
			case self::FIELD_TYPE_VARCHAR:

			case self::FIELD_TYPE_TINYTEXT:
			case self::FIELD_TYPE_TEXT:
			case self::FIELD_TYPE_MEDIUMTEXT:
			case self::FIELD_TYPE_LONGTEXT: return self::GROUP_TYPE_TEXT;
				break;

			case self::FIELD_TYPE_BINARY:
			case self::FIELD_TYPE_VARBINARY:

			case self::FIELD_TYPE_TINYBLOB:
			case self::FIELD_TYPE_MEDIUMBLOB:
			case self::FIELD_TYPE_BLOB:
			case self::FIELD_TYPE_LONGBLOB: return self::GROUP_TYPE_BLOB;
				break;

			case self::FIELD_TYPE_ENUM:
			case self::FIELD_TYPE_SET: return self::GROUP_TYPE_TEXT;
				break;

			case self::FIELD_TYPE_GEOMETRY:
			case self::FIELD_TYPE_POINT:
			case self::FIELD_TYPE_LINESTRING:
			case self::FIELD_TYPE_POLYGON:
			case self::FIELD_TYPE_MULTIPOINT:
			case self::FIELD_TYPE_MULTILINESTRING:
			case self::FIELD_TYPE_MULTIPOLYGON:
			case self::FIELD_TYPE_GEOMETRYCOLLECTION: return self::GROUP_TYPE_GEOMETRY;
				break;
		}
	}

}

?>