<?php
namespace glasteel;

class Role extends ModelBase
{
	protected $primary_bean_table = 'role';
	protected static $pbt = 'role';

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
		if ( $key && $key = 'capabilities' ){
			return $this->getAllCapabilities();
		}
		return [
			'capabilities' => $this->getAllCapabilities(),
		];
	}//getResourceRelationships()

	public static function getResourceRelationshipClasses(){
		return [
			'capabilities' => __NAMESPACE__ . '\\Capability',
		];
	}//getResourceRelationshipClasses()

	//TODO error checking
	protected function addRelated($relationship,$obj){
		switch ( $relationship ){
			case 'capabilities':
				return $this->addCap($obj->slug);
			break;
		}
		return false;
	}//addRelated()

	//TODO error checking
	protected function removeRelated($relationship,$obj){
		switch ( $relationship ){
			case 'capabilities':
				return $this->removeCap($obj->slug);
			break;
		}
		return false;
	}//removeRelated()

	private function getAllCapabilities(){
		$sql = 
			"SELECT c.*

			FROM capability AS c

				JOIN {$this->primary_bean_table}_capability AS jr ON jr.capability_slug = c.slug
			
			WHERE jr.role_slug = :this_slug
				AND c.active = 1
				AND jr.active = 1;
		";//sql

		$rows = $this->db->getAll($sql,[
			':this_slug' => $this->slug,
		]);

		$beans = $this->db->convertToBeans('role',$rows);
		$objs = [];
		foreach ($beans as $idx => $bean) {
			$objs[] = new Capability($bean);
		}
		return $objs;
	}//getAllRoles()
	
	public static function rolesUsingCap($cap_slug){
		$roles = [];
		$_roles = self::$DB->getAll('
			SELECT r.* 
			FROM role AS r
			JOIN role_capability AS rc ON r.slug = rc.role_slug
				AND rc.capability_slug = :cap_slug
			',
			[':cap_slug' => $cap_slug]
		);
		$_roles = self::$DB->convertToBeans('role', $_roles);
		foreach ($_roles as $idx => $role){
			$roles[$role->slug] = new Role($role);
		}
		return $roles;
	}//rolesUsingCap()

	public function hasCap($cap_slug){
		return !is_null($this->getCapLink($cap_slug));
	}//hasCap()

	public function getCapLink($cap_slug){
		return $this->db->findOne(
			'role_capability',
			'role_slug = :role_slug AND capability_slug = :capability_slug',
			[
				':role_slug' => $this->slug,
				':capability_slug' => $cap_slug,
			]
		);
	}//getCapLink()

	public function addCap($cap_slug){
		if ( $this->hasCap($cap_slug) ){
			return true;
		}
		return $this->db->exec(
			'INSERT INTO role_capability (role_slug, capability_slug, active) 
				VALUES (:role_slug, :capability_slug, 1)',
			[
				':role_slug' => $this->slug,
				':capability_slug' => $cap_slug,
			]
		);
	}//addCap()

	public function removeCap($cap_slug){
		if ( !$this->hasCap($cap_slug) ){
			return true;
		}
		$link = $this->getCapLink($cap_slug);
		$this->db->trash($link);
		return !($this->hasCap($cap_slug));
	}//removeCap()

}//class User