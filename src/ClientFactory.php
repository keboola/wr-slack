<?php

declare(strict_types=1);

namespace Keboola\SlackWriter;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientFactory
{
    /**
     * @var string
     */
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    private function getHandlerStack() : HandlerStack
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->push(Middleware::retry(
            self::createDefaultDecider(),
            self::createExponentialDelay()
        ));
        return $handlerStack;
    }

    private static function createDefaultDecider(int $maxRetries = 3) : callable
    {
        return function (
            $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            $error = null
        ) use ($maxRetries) {
            if ($retries >= $maxRetries) {
                return false;
            } elseif ($response && $response->getStatusCode() > 499) {
                return true;
            } elseif ($error) {
                return true;
            } else {
                return false;
            }
        };
    }

    private static function createExponentialDelay() : callable
    {
        return function ($retries) {
            return (int) pow(2, $retries - 1) * 1000;
        };
    }

    public function create(): Client
    {
        return new Client([
            'handler' => $this->getHandlerStack(),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-type' =>  'application/json;  charset=utf-8',
            ],
        ]);
    }
}
