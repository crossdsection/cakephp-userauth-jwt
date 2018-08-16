<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Cors middleware
 */
class CorsMiddleware
{

    /**
     * Invoke method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next) {

      if( $request->is('options') ){
        return $response->withHeader('Access-Control-Allow-Origin', '*')
                            ->withHeader('Access-Control-Allow-Methods', 'DELETE, GET, OPTIONS, PATCH, POST, PUT')
                            ->withHeader('Access-Control-Allow-Headers', 'Accept, Authorization, Cache-Control, Content-Type, X-Requested-With, x-csrf-token')
                            ->withHeader('Access-Control-Allow-Credentials', 'true')
                            ->withHeader('Access-Control-Max-Age', '3600');
      } else {
        return $next($request, $response)->withHeader('Access-Control-Allow-Origin', '*')
                            ->withHeader('Access-Control-Allow-Methods', 'DELETE, GET, OPTIONS, PATCH, POST, PUT')
                            ->withHeader('Access-Control-Allow-Headers', 'Accept, Authorization, Cache-Control, Content-Type, X-Requested-With, x-csrf-token')
                            ->withHeader('Access-Control-Expose-Headers', 'Authorization, Content-Type')
                            ->withHeader('Access-Control-Allow-Credentials', 'true')
                            ->withHeader('Access-Control-Max-Age', '3600');
      }
    }
}
