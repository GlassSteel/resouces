<?php
namespace glasteel;

class User extends ModelBase
{
	use RollsCapsTrait;

	protected $primary_bean_table = 'user';
	protected static $pbt = 'user';
	
	public function getInstanceName(){
		return ucfirst( trim($this->first_name) ) . ' ' . ucfirst( trim($this->last_name) );
	}//getInstanceName()

	public function getResourceAttributes(){
		return [
			'first_name' => $this->first_name, //required
			'last_name' => $this->last_name, //required
			'onyen' => $this->onyen, //unique
			'unc_pid' => $this->unc_pid, //unique
			'email' => $this->email, //required, unique, format
		];
	}//getResourceAttributes()

	protected function validateOwnAttributes(){
		$this->validateRequired('first_name');
		$this->validateRequired('last_name');
		$this->validateUnique('onyen');
		$this->validateUnique('unc_pid');
		if ( $this->validateRequired('email') ){
			$this->validateUnique('email');
		}
		//TODO validate email format
	}//validateOwnAttributes()

	public function getResourceRelationships($key=false){
		if ( $key && $key = 'roles' ){
			return $this->getAllRoles();
		}
		return [
			'roles' => $this->getAllRoles(),
		];
	}//getResourceRelationships()

	public static function getResourceRelationshipClasses(){
		return [
			'roles' => __NAMESPACE__ . '\\Role',
		];
	}//getResourceRelationshipClasses()

	//TODO error checking
	protected function addRelated($relationship,$obj){
		switch ( $relationship ){
			case 'roles':
				if ( $this->is($obj->slug) ){
					return true;
				}
				return $this->db->exec(
					'INSERT INTO user_role (user_id, role_slug, active) 
						VALUES (:user_id, :role_slug, 1)',
					[
						':user_id' => $this->id,
						':role_slug' => $obj->slug,
					]
				);
			break;
		}
		return false;
	}//addRelated()

	//TODO error checking
	protected function removeRelated($relationship,$obj){
		switch ( $relationship ){
			case 'roles':
				if ( !($this->is($obj->slug)) ){
					return true;
				}
				$link = $this->db->findOne(
					'user_role',
					'user_id = :user_id AND role_slug = :role_slug',
					[
						':user_id' => $this->id,
						':role_slug' => $obj->slug,
					]
				);
				if( $link ){
					$this->db->trash($link);
				}
				return !($this->is($obj->slug));
			break;
		}
		return false;
	}//removeRelated()

	public function setUserByPID($pid){
		$user_bean = $this->db->findOne(
			'user',
			'unc_pid = :unc_pid',
			[
				':unc_pid' => $pid,
			]
		);
		if ( $user_bean ){
	        $this->setPrimaryBean($user_bean);
	        return true;
		}
		return false;
	}//getUserByPID()

}//class User