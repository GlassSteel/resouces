<?php
namespace glasteel;

interface ResourceModelInterface
{
	
	/**
	 * Return a natural language identifier for the instance,
	 * i.e. $Userinstance->getInstanceName() returns 'John Doe'
	 */
	public function getInstanceName();


	/**
	 * 
	 */
	public function getResourceRelationships($key=false);


	/**
	 * 
	 */
	public static function getResourceRelationshipClasses();
	

	/**
	 * 
	 */
	public function validateResource($input);


	/**
	 * 
	 */
	public function saveResource($input);


	/**
	 * Don't add to any traits - must be implemented in child model class
	 */
	public function getResourceAttributes();

	
	/**
	 * 
	 */
	public static function getActiveCollection();


	/**
	 * Return a natural language identifer for the instance class,
	 * i.e. $Userinstance->getResourceNicename() returns 'User'
	 */
	public static function getResourceNicename();

	
	/**
	 * Return a natural language identifer for the plural of instance class,
	 * i.e. $Userinstance->getResourcesNicename() returns 'Users'
	 */
	public static function getResourcesNicename();

	
	/**
	 * Return a brief unique identifer for the instance class,
	 * i.e. $Userinstance->getResourceSlug() returns 'user'
	 */
	public static function getResourceSlug();

}//interface ResourceModelInterface