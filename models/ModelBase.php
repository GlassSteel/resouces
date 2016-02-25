<?php
namespace glasteel

abstract class ModelBase implements ResourceModelInterface
{
	use ResourceModelInterfaceTrait;
	use HasRedBeanAsActiveRecordTrait;

	protected $db;
	protected static $DB = null;

	public function __construct($primary_bean=null){
		$this->db = self::$DB;
		if ( is_null($this->db) ){
			throw new \InvalidArgumentException(
				'Null $db in ' . __METHOD__
			);
		}
		$this->setPrimaryBean($primary_bean);
	}//__construct()
	
	//DB FUNCTIONS

	public static function hasDB(){
		return (!is_null(self::$DB));
	}//hasDB()

	public static function setDB(RedBeanWrapper $db){
		if ( !self::hasDB() ){
			self::$DB = $db;
		}
	}//setDB()

}//class ModelBase