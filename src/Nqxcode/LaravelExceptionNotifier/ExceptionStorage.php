<?php namespace Nqxcode\LaravelExceptionNotifier;

use Throwable;
use \Illuminate\Cache\Repository as CacheRepository;

/**
 * Class ExceptionStorage
 * @package Nqxcode\LaravelErrorSender
 */
class ExceptionStorage
{
    private CacheRepository $cache;
    private int $lifeTimeInSeconds;

    public function __construct(CacheRepository $cache, int $lifeTimeInSeconds)
    {
        $this->cache = $cache;
        $this->lifeTimeInSeconds = $lifeTimeInSeconds;
    }

    public function put(Throwable $exception): void
    {
        $this->cache->put($this->uid($exception), 1, $this->lifeTimeInSeconds);
    }

    public function has(Throwable $exception): bool
    {
        return $this->cache->has($this->uid($exception));
    }

    public function forget(Throwable $exception): void
    {
        $this->cache->forget($this->uid($exception));
    }

    public function available(): bool
    {
        try {
            if (!$this->cache->put('test-key', 'test-value')) {
                return false;
            }

            return 'test-value' === $this->cache->pull('test-key');

        } catch (Throwable $exception) {
            return false;
        }
    }

    private function uid(Throwable $exception): string
    {
        return md5(json_encode(
            [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'line' => $exception->getFile(),
                'file' => $exception->getLine()
            ],
            JSON_THROW_ON_ERROR,
            512
        ));
    }
}
