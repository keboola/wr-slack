<?php

declare(strict_types=1);

namespace Keboola\SlackWriter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Keboola\Component\UserException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Writer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    private function processAttachments(string $message, ?string $attachments): ?array
    {
        if ($attachments) {
            try {
                $attachments = \GuzzleHttp\json_decode($attachments, false);
                if ($attachments) {
                    if (!is_array($attachments)) {
                        throw new UserException(
                            sprintf('Attachments for message "%s" is not an array.', $message)
                        );
                    }
                    foreach ($attachments as $attachment) {
                        if (!is_object($attachment)) {
                            throw new UserException(
                                sprintf('Attachments for message "%s" is not an array of objects.', $message)
                            );
                        }
                    }
                } else {
                    $attachments = null;
                }
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
        return $attachments;
    }

    public function writeMessage(string $channel, string $message, ?string $attachments): void
    {
        $attachments = $this->processAttachments($message, $attachments);
        try {
            $response = $this->client->post(
                'https://slack.com/api/chat.postMessage',
                [
                    'body' => \GuzzleHttp\json_encode([
                        'channel' => $channel,
                        'text' => $message,
                        'attachments' => \GuzzleHttp\json_encode($attachments),
                    ]),
                ]
            );
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
            $responseData = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
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
}
