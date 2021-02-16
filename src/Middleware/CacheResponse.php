<?php

namespace Silber\PageCache\Middleware;

use Closure;
use Illuminate\Support\Facades\File;
use Silber\PageCache\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * The cache instance.
     *
     * @var \Silber\PageCache\Cache
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @var \Silber\PageCache\Cache  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET')) {

            $segments = explode('/', ltrim($request->getPathInfo(), '/'));
            $filename = array_pop($segments);
            $filename = $this->cache->aliasFilename($filename);
            $filename = md5($filename);
            $extension = $this->cache->guessFileExtension($request);

            $path = $this->cache->getCachePath(implode('/', $segments));

            $file = "{$filename}.{$extension}";

            $fullpath = $this->cache->join([$path, $file]);

            if (file_exists( $fullpath)) {
//                echo "cache hint";
                return response(File::get( $fullpath));
            }
        }
            $response = $next($request);

        if ($this->shouldCache($request, $response)) {
            $this->cache->cache($request, $response);
        }

        return $response;
    }

    /**
     * Determines whether the given request/response pair should be cached.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function shouldCache(Request $request, Response $response)
    {
        return $request->isMethod('GET') && $response->getStatusCode() == 200;
    }
}
