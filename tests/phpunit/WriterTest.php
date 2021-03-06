<?php

declare(strict_types=1);

namespace Keboola\SlackWriter\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Keboola\Component\UserException;
use Keboola\SlackWriter\Writer;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class WriterTest extends MockeryTestCase
{

    /** @var Client|MockInterface */
    private $clientMock;

    /** @var LoggerInterface|MockInterface */
    private $loggerMock;

    /** @var Writer */
    private $writer;

    public function setUp(): void
    {
        parent::setUp();

        $this->clientMock = \Mockery::mock(Client::class);
        $this->loggerMock = \Mockery::mock(LoggerInterface::class);
        $this->writer = new Writer($this->clientMock, $this->loggerMock);
    }

    public function wrongAttachmentsProvider(): array
    {
        return [
            [
                'invalid JSON',
                '{"file":"file.name"',
                'Attachments for message "invalid JSON" is not a valid JSON (json_decode error: Syntax error)',
            ],
            [
                'invalid root',
                '{"file":"file.name"}',
                'Attachments for message "invalid root" is not an array.',
            ],
            [
                'unexpected array',
                '[[{"file":"file.name"}]]',
                'Attachments for message "unexpected array" is not an array of objects.',
            ],
            [
                'unexpected object',
                '["file","file.name"]',
                'Attachments for message "unexpected object" is not an array of objects.',
            ],
        ];
    }

    /**
     * @dataProvider wrongAttachmentsProvider
     */
    public function testWriteMessageWithWrongAttachment(string $message, string $attachments, string $error): void
    {
        $channel = 'spam';

        $this->expectException(UserException::class);
        $this->expectExceptionMessage($error);
        $this->writer->writeMessage($channel, $message, $attachments);
    }

    public function testWriteMessageWithAttachmentSuccessful(): void
    {
        $streamInterfaceMock = \Mockery::mock(StreamInterface::class);
        $streamInterfaceMock->shouldReceive('getContents')
            ->once()
            ->withNoArgs()
            ->andReturn(json_encode([
                'error' => '',
                'ok' => ['Success'],
                'warning' => [],
            ]));

        $responseMock = \Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')
            ->once()
            ->withNoArgs()
            ->andReturn(200);
        $responseMock->shouldReceive('getBody')
            ->once()
            ->withNoArgs()
            ->andReturn($streamInterfaceMock);

        $this->clientMock->shouldReceive('post')
            ->once()
            ->withArgs([
                'https://slack.com/api/chat.postMessage',
                [
                    'body' => \GuzzleHttp\json_encode([
                        'channel' => 'spam',
                        'text' => 'Bad attachments.',
                        'attachments' => '[{"file":"file.name"}]',
                    ]),
                ],
            ])
            ->andReturn($responseMock);

        $channel = 'spam';
        $message = 'Bad attachments.';
        $attachments = '[{"file":"file.name"}]';

        $this->writer->writeMessage($channel, $message, $attachments);
    }

    public function testWriteMessageSuccessful(): void
    {
        $streamInterfaceMock = \Mockery::mock(StreamInterface::class);
        $streamInterfaceMock->shouldReceive('getContents')
            ->once()
            ->withNoArgs()
            ->andReturn(\GuzzleHttp\json_encode([
                'error' => '',
                'ok' => ['Success'],
                'warning' => [],
            ]));

        $responseMock = \Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')
            ->once()
            ->withNoArgs()
            ->andReturn(200);
        $responseMock->shouldReceive('getBody')
            ->once()
            ->withNoArgs()
            ->andReturn($streamInterfaceMock);

        $this->clientMock->shouldReceive('post')
            ->once()
            ->withArgs([
                'https://slack.com/api/chat.postMessage',
                [
                    'body' => \GuzzleHttp\json_encode([
                        'channel' => 'spam',
                        'text' => 'Hello world!',
                        'attachments' => 'null',
                    ]),
                ],
            ])
            ->andReturn($responseMock);

        $channel = 'spam';
        $message = 'Hello world!';

        $this->writer->writeMessage($channel, $message, null);
    }

    public function testWriteMessageThrowsException(): void
    {
        $this->clientMock->shouldReceive('post')
            ->once()
            ->withArgs([
                'https://slack.com/api/chat.postMessage',
                [
                    'body' => \GuzzleHttp\json_encode([
                        'channel' => 'spam',
                        'text' => 'Throw me an exception.',
                        'attachments' => 'null',
                    ]),
                ],
            ])
            ->andThrow(
                new ClientException(
                    'Client exception thrown.',
                    \Mockery::mock(RequestInterface::class)
                )
            );

        $channel = 'spam';
        $message = 'Throw me an exception.';

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Client exception thrown.');
        $this->writer->writeMessage($channel, $message, null);
    }

    public function testWriteMessageEndsWithError(): void
    {
        $streamInterfaceMock = \Mockery::mock(StreamInterface::class);
        $streamInterfaceMock->shouldReceive('getContents')
            ->once()
            ->withNoArgs()
            ->andReturn(\GuzzleHttp\json_encode([
                'error' => 'An error has occurred.',
                'ok' => [],
                'warning' => [],
            ]));

        $responseMock = \Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')
            ->once()
            ->withNoArgs()
            ->andReturn(200);
        $responseMock->shouldReceive('getBody')
            ->once()
            ->withNoArgs()
            ->andReturn($streamInterfaceMock);

        $this->clientMock->shouldReceive('post')
            ->once()
            ->withArgs([
                'https://slack.com/api/chat.postMessage',
                [
                    'body' => \GuzzleHttp\json_encode([
                        'channel' => 'spam',
                        'text' => 'Error occurred!',
                        'attachments' => 'null',
                    ]),
                ],
            ])
            ->andReturn($responseMock);

        $channel = 'spam';
        $message = 'Error occurred!';

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Failed to send the message "Error occurred!" to slack,' .
            ' error: "An error has occurred."');
        $this->writer->writeMessage($channel, $message, null);
    }
}
