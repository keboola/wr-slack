<?php

declare(strict_types=1);

namespace Keboola\SlackWriter\Tests\Functional;

use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;

class DatadirTest extends AbstractDatadirTestCase
{
    public function setup(): void
    {
        parent::setUp();
        if (empty(getenv('SLACK_TEST_TOKEN')) || empty(getenv('SLACK_TEST_CHANNEL'))) {
            throw new \Exception("SLACK_TEST_TOKEN or SLACK_TEST_CHANNEL is empty");
        }
    }

    public function testBasic(): void
    {
        $specification = new DatadirTestSpecification(
            __DIR__ . '/basic-data/source/data',
            0,
            'Sent 2 messages for table "in.c-main.messages"' . "\n",
            '',
            __DIR__ . '/basic-data/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'parameters' => [
                'channel' => getenv('SLACK_TEST_CHANNEL'),
                '#token' => getenv('SLACK_TEST_TOKEN'),
            ],
            'storage' => [
                'input' => [
                    'tables' => [
                        [
                            'source' => 'in.c-main.messages',
                            'destination' => 'messages.csv',
                        ],
                    ],
                ],
            ],
            'action' => 'run',
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testRich(): void
    {
        $specification = new DatadirTestSpecification(
            __DIR__ . '/rich-data/source/data',
            0,
            implode(
                "\n",
                [
                    'Sent 2 messages for table "in.c-main.messages"',
                    'Sent 1 messages for table "in.c-main.messages-attachments"',
                    '',
                ]
            ),
            null,
            __DIR__ . '/rich-data/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'parameters' => [
                'channel' => getenv('SLACK_TEST_CHANNEL'),
                '#token' => getenv('SLACK_TEST_TOKEN'),
            ],
            'storage' => [
                'input' => [
                    'tables' => [
                        [
                            'source' => 'in.c-main.messages',
                            'destination' => 'message.csv',
                        ],
                        [
                            'source' => 'in.c-main.messages-attachments',
                            'destination' => 'message-attachment.csv',
                        ],
                    ],
                ],
            ],
            'action' => 'run',
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testInvalidTable(): void
    {
        $specification = new DatadirTestSpecification(
            __DIR__ . '/invalid-table/source/data',
            1,
            null,
            'The table "in.c-main.messages" contains 3 columns. Every table must contain at most 2 columns.' . "\n",
            __DIR__ . '/invalid-table/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'parameters' => [
                'channel' => getenv('SLACK_TEST_CHANNEL'),
                '#token' => getenv('SLACK_TEST_TOKEN'),
            ],
            'storage' => [
                'input' => [
                    'tables' => [
                        [
                            'source' => 'in.c-main.messages',
                            'destination' => 'messages.csv',
                        ],
                    ],
                ],
            ],
            'action' => 'run',
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testInvalidAttachments(): void
    {
        $specification = new DatadirTestSpecification(
            __DIR__ . '/invalid-attachments/source/data',
            1,
            null,
            'Attachments for message "hello" is not a valid JSON (json_decode error: Syntax error)' . "\n",
            __DIR__ . '/invalid-attachments/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'parameters' => [
                'channel' => getenv('SLACK_TEST_CHANNEL'),
                '#token' => getenv('SLACK_TEST_TOKEN'),
            ],
            'storage' => [
                'input' => [
                    'tables' => [
                        [
                            'source' => 'in.c-main.messages',
                            'destination' => 'messages.csv',
                        ],
                    ],
                ],
            ],
            'action' => 'run',
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testInvalidToken(): void
    {
        $specification = new DatadirTestSpecification(
            __DIR__ . '/basic-data/source/data',
            1,
            null,
            'Failed to send the message "hello" to slack, error: "invalid_auth"' . "\n",
            __DIR__ . '/basic-data/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'parameters' => [
                'channel' => getenv('SLACK_TEST_CHANNEL'),
                '#token' => 'token',
            ],
            'storage' => [
                'input' => [
                    'tables' => [
                        [
                            'source' => 'in.c-main.messages',
                            'destination' => 'messages.csv',
                        ],
                    ],
                ],
            ],
            'action' => 'run',
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }
}
