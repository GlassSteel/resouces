<?php
namespace glasteel;

class JSONApiController
{
	public function formatCollection($type,$ids){
		$collection = [];
		foreach ($ids as $idx => $id) {
			$collection[] = $this->getResource($type,$id);
		}
		return $collection;
	}//getCollection()

	//get single resource
	// @param $resource is instanceof $resource_class or id of same
	public function getResource($resource_class,$resource=null,$flat=false){
		if ( !( $resource instanceof $resource_class ) ){
			$resource = new $resource_class($resource);
		}
		$id = $resource->id;
		$type = $resource_class::getResourceSlug();
		$relationships = ($id) ? $resource->getResourceRelationships() : false;
		$included = ($id) ? $resource->getResourceIncluded() : false;
		
		$json = [
			'type' => $type,
			'id' => ($id) ? $id : 0,
			'attributes' => ($id) ? $resource->getResourceAttributes() : new \stdclass,
			'meta' => [
				'resource_nicename' => $resource::getResourceNicename(),
				'instance_name' => ($id) ? $resource->getInstanceName() : '',
			],

		];

		if ( $relationships ){
			$json['relationships'] = [];
			
			foreach ($relationships as $rel => $members) {
				$json['relationships'][$rel] = [];
				foreach ($members as $idx => $member) {
					$json['relationships'][$rel][] = [
						'type' => $rel,
						'id' => $member->id,
					];
				}
			}
		}

		if ( $included && $flat !== true ){
			$json['included'] = [];
			foreach ($included as $rel => $members) {
				$json['included'][$rel] = [];
				foreach ($members as $idx => $member) {
					$json['included'][$rel][] = $this->getResource($rel,$member->id,true);
				}
			}
		}
		return $json;
	}//getResource()

}//ApiController