<?php
namespace glasteel;

trait ResourceModelInterfaceTrait
{
	/*
	protected string $name
	protected string $resource_slug
	*/
	
	public function getInstanceName(){
		return $this->name;
	}//getInstanceName()

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