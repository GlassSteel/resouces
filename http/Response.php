<?php
namespace glasteel;

class Response extends \Slim\Http\Response
{
	public function __construct($status = 200, $headers = null, $body = null){
	    parent::__construct($status,$headers,$body);
	}

	public function withJsonAPI($data, $status = 200, $encodingOptions = 0)
	{
	    $body = $this->getBody();
	    $body->rewind();
	    $body->write(json_encode($data, $encodingOptions));

	    return $this->withStatus($status)
	    	->withHeader('Content-Type', FrameworkInit::JSONAPI_MEDIA_TYPE)
	    ;
	}
}//Response