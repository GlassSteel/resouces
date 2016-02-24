<?php
namespace glasteel;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class catch403 extends middlewareBase
{
    public function __construct($hander){
    	$this->setHandler($hander);
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $this->handleError(403,$request,$response,$next);
    }//__invoke

}//class catch403