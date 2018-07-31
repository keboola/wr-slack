<?php

declare(strict_types=1);

namespace Keboola\SlackWriter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use Keboola\Component\UserException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Writer
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $token, LoggerInterface $logger)
    {
        $this->token = $token;
        $this->logger = $logger;
    }

    public function writeMessage(string $channel, string $message, ?string $attachments) : void
    {
        $client = new Client(
            [
            'handler' => $this->getHandlerStack(),
            ]
        );
        if ($attachments) {
            try {
                $attachments = \GuzzleHttp\json_decode($attachments, true);
            } catch (\InvalidArgumentException $e) {
                throw new UserException(
                    sprintf('Attachments for message "%s" is not a valid JSON (%s)', $message, $e->getMessage()),
                    0,
                    $e
                );
            }
        } else {
            $attachments = null;
        }
        $request = new Request(
            'POST',
            'https://slack.com//api/chat.postMessage',
            [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-type' =>  'application/json;  charset=utf-8',
            ],
            \GuzzleHttp\json_encode([
                'channel' => $channel,
                'text' => $message,
                'attachments' => \GuzzleHttp\json_encode($attachments),
            ])
        );

        try {
            $response = $client->send($request, []);
            $this->handleResponse($response, $message);
        } catch (ClientException $e) {
            throw new UserException($e->getMessage(), 0, $e);
        }
    }

    private function handleResponse(ResponseInterface $response, string $message) : void
    {
        if ($response->getStatusCode() != 200) {
            throw new UserException(
                sprintf(
                    'Failed to send the message, error: "%s" (code: %s)',
                    $response->getBody(),
                    $response->getStatusCode()
                )
            );
        }
        try {
            $responseData = \GuzzleHttp\json_decode($response->getBody(), true);
        } catch (\InvalidArgumentException $e) {
            throw new UserException('Failed to process response from Slack: ' . $e->getMessage(), 0, $e);
        }
        if (empty($responseData['ok'])) {
            throw new UserException(
                sprintf('Failed to send the message "%s" to slack, error: "%s"', $message, $responseData['error'])
            );
        }
        if (!empty($responseData['warning'])) {
            $this->logger->error(sprintf('Message "%s" sent with warning: "%s"', $message, $responseData['warning']));
        }
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

    private function createDefaultDecider(int $maxRetries = 3) : callable
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
}
