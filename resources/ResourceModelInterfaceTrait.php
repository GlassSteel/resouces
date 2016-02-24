<?php
namespace glasteel;

trait ResourceModelInterfaceTrait
{
	use HasRedBeanAsActiveRecordTrait;
	
	/*
	protected string $name
	protected string $resource_slug
	*/

	protected static $DB = null;
	
	//DB FUNCTIONS

	public static function hasDB(){
		return (!is_null(self::$DB));
	}//hasDB()

	public static function setDB(RedBeanWrapper $db){
		if ( !self::hasDB() ){
			self::$DB = $db;
		}
	}//setDB()

	//INSTANCE FUNCTIONS

	public function getInstanceName(){
		return $this->name;
	}//getInstanceName()

	//CLASS META FUNCTIONS

	public static function getResourceSlug(){
		return $this->resource_slug;
	}//getResourceSlug()

	public static function getResourceNicename(){
		$base = get_called_class();
		$parts = explode('\\', $base);
		$last = array_pop($parts);
		return $last;
	}//getResourceNicename()

}//trait ResourceModelInterfaceTrait