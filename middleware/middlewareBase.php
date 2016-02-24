<?php
namespace glasteel;

class middlewareBase
{
	protected $handler;

	public function setHandler($handler){
		$this->handler = $handler;
	}//setHandler()	

	public function handleError($code,$request,$response,$next){
		$response = $next($request, $response);
		
		$this->trace($response);
		if ( $response->getStatusCode() == $code ){
		    $handler = $this->handler;
		    return $handler($request, $response);
		}
		
		return $response;
	}//handleError()

	public function filterAttrs($attrs){
		if ( !is_array($attrs) ){
			$attrs = [];
		}
		foreach ($attrs as $key => $value) {
			if ( substr( $key, -strlen( '_id' ) ) != '_id' ){
		        unset($attrs[$key]);
		    }
		}
		return $attrs;
	}//filterAttrs()

	public function okToOverwrite($response,$code){
		if ( $code == 404 ){
			return true;
		}
		if ( $response->getStatusCode() == 404 ){
			return false;
		}
		if ( $code == 400 && $response->getStatusCode() == 403 ){
			return false;
		}
		return true;
	}//okToOverwrite()

	public function trace($response,$class = false){
		if ( defined('TRACE_MIDDLEWARE_STACK') && TRACE_MIDDLEWARE_STACK ){
			if ( $class === false ){
				$class = get_called_class();
				$class = explode('\\', $class);
				$class = array_pop($class);
			}
			$response->write($class . '</br >');
        }
	}//trace()	
}