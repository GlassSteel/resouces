<?php
namespace glasteel;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Body;

/**
 * Default Slim application not found handler.
 *
 * It outputs a simple message in either JSON, XML or HTML based on the
 * Accept header.
 */
class Forbidden extends \Slim\Handlers\NotFound
{
    protected $flash;
    protected $displayErrorDetails;

    public function __construct($flash,$displayErrorDetails=0){
        $this->flash = $flash;
        $this->displayErrorDetails = $displayErrorDetails;
    }

    /**
     * Invoke not found handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $contentType = $this->determineContentType($request);
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJsonNotFoundOutput($request, $response);
                break;

            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXmlNotFoundOutput($request, $response);
                break;

            case 'text/html':
                $output = $this->renderHtmlNotFoundOutput($request, $response);
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response->withStatus(403)
                        ->withHeader('Content-Type', $contentType)
                        ->withBody($body);
    }

    /**
     * Determine which content type we know about is wanted using Accept header
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function determineContentType(ServerRequestInterface $request)
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(explode(',', $acceptHeader), $this->knownContentTypes);

        if (count($selectedContentTypes)) {
            return $selectedContentTypes[0];
        }

        return 'text/html';
    }

    /**
     * Return a response for application/json content not found
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    protected function renderJsonNotFoundOutput(ServerRequestInterface $request, ResponseInterface $response)
    {
        return '{"message":"Forbidden: You are not authorized to access the requested resource."}';
    }

    /**
     * Return a response for xml content not found
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    protected function renderXmlNotFoundOutput(ServerRequestInterface $request, ResponseInterface $response)
    {
        return '<root><message>Not found</message></root>';
    }

    /**
     * Return a response for text/html content not found
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    protected function renderHtmlNotFoundOutput(ServerRequestInterface $request, ResponseInterface $response)
    {
        $homeUrl = (string)($request->getUri()->withPath('')->withQuery('')->withFragment(''));
        $flash_msg = '';
        
        if ($this->displayErrorDetails){
            $messages = $this->flash->getMessages();
            foreach ($messages as $key => $value) {
                $flash_msg .= '<strong>' . $key . ':</strong> ';
                foreach ($value as $idx => $val) {
                    $flash_msg .= $val . '<br />';
                }
            }
            if ( $flash_msg ){
                $flash_msg = '<p>' . $flash_msg . '</p>';
            }
        }

        return <<<END
<html>
    <head>
        <title>Forbidden</title>
        <style>
            body{
                margin:0;
                padding:30px;
                font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
            }
            h1{
                margin:0;
                font-size:48px;
                font-weight:normal;
                line-height:48px;
            }
            strong{
                display:inline-block;
            }
        </style>
    </head>
    <body>
        <h1>Forbidden</h1>
        <p>
            You are not authorized to access the requested resource.
        </p>
        $flash_msg
        <a href='$homeUrl'>Visit the Home Page</a>
    </body>
</html>
END;
    }
}