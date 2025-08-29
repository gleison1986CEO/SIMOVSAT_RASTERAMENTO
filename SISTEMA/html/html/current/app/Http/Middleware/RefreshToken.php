<?php namespace App\Http\Middleware;

use Closure;
use App;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefreshToken {

    const TOKEN = "1e64ca0efc186a999096ffd89e9b1dca";

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if (!($response instanceof BinaryFileResponse))
            $response->header('X-Refresh-Token', self::TOKEN);

        return $response;
    }

}
