<?php
namespace glasteel;

class Capability extends ModelBase
{
	protected $primary_bean_table = 'capability';
	protected static $pbt = 'capability';
	protected static $active_caps = null;

	public static function getResourcesNicename(){
		return 'Capabilities';
	}//getResourceNicename()

	public function getResourceAttributes(){
		return [
			'name' => $this->name, //required, unique
			'description' => $this->description,
			'slug' => $this->slug, //required, unique
		];
	}//getResourceAttributes()

	protected function validateOwnAttributes(){
		if ( $this->validateRequired('name') ){
			$this->validateUnique('name');
		}
		if ( $this->validateRequired('slug') ){
			$this->validateUnique('slug');
		}
	}//validateOwnAttributes()

	public function getResourceRelationships($key=false){
		if ($key && $key == 'roles' ){
			return $this->getRolesUsingThis();
		}
		return [
			'roles' => $this->getRolesUsingThis(),
		];
	}//getResourceRelationships()

	public static function getResourceRelationshipClasses(){
		return [
			'roles' => __NAMESPACE__ . '\\Role',
		];
	}//getResourceRelationshipClasses()

	protected function getRolesUsingThis(){
		return Role::rolesUsingCap($this->slug);
	}//getRolesUsingThis()

	//TODO error checking
	protected function addRelated($relationship,$obj){
		switch ( $relationship ){
			case 'roles':
				return $obj->addCap($this->slug);
			break;
		}
		return false;
	}//addRelated()

	//TODO error checking
	protected function removeRelated($relationship,$obj){
		switch ( $relationship ){
			case 'roles':
				return $obj->removeCap($this->slug);
			break;
		}
		return false;
	}//removeRelated()

}//class Capability