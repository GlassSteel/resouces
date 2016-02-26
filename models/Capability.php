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
		// switch ( $relationship ){
		// 	case 'roles':
		// 		if ( $this->is($to_add->slug) ){
		// 			return true;
		// 		}
		// 		return $this->db->exec(
		// 			'INSERT INTO user_role (user_id, role_slug, active) 
		// 				VALUES (:user_id, :role_slug, 1)',
		// 			[
		// 				':user_id' => $this->id,
		// 				':role_slug' => $to_add->slug,
		// 			]
		// 		);
		// 	break;
		// }
		return false;
	}//addRelated()

	//TODO error checking
	protected function removeRelated($relationship,$obj){
		// switch ( $relationship ){
		// 	case 'roles':
		// 		if ( !($this->is($to_remove->slug)) ){
		// 			return true;
		// 		}
		// 		$link = $this->db->findOne(
		// 			'user_role',
		// 			'user_id = :user_id AND role_slug = :role_slug',
		// 			[
		// 				':user_id' => $this->id,
		// 				':role_slug' => $to_remove->slug,
		// 			]
		// 		);
		// 		if( $link ){
		// 			$this->db->trash($link);
		// 		}
		// 		return !($this->is($to_remove->slug));
		// 	break;
		// }
		return false;
	}//removeRelated()

}//class Capability