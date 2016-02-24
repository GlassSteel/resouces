<?php
namespace glasteel;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class IdParamsAreInt extends middlewareBase
{
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->trace($response);

        $route = $request->getAttribute('route');
        $route_args = $route->getArguments();
        
        foreach ( $this->filterAttrs($route_args)  as $key => $value) {
            if ( !preg_match('/^[1-9]+[0-9]*$/',$value) && $this->okToOverwrite($response,404) ){
                $response = $response->withStatus(404);
            }
        }
        return $next($request, $response);
    }//__invoke
}//class IdParamsAreInt