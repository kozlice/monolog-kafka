<?php

namespace Monolog\Handler;

use Kozlice\Monolog\Handler\KafkaHandler;
use Monolog\Logger;

class KafkaHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (!extension_loaded('rdkafka')) {
            $this->markTestSkipped('This test requires rdkafka extension to run');
        }
    }

    public function testConstruct()
    {
        $topic = $this->createPartialMock('RdKafka\\ProducerTopic', ['produce']);

        $producer = $this->createPartialMock('RdKafka\\Producer', ['newTopic']);
        $producer->expects($this->once())
            ->method('newTopic')
            ->with('test', $this->isInstanceOf('RdKafka\\TopicConf'))
            ->willReturn($topic);

        $handler = new KafkaHandler($producer, 'test');
        $this->assertInstanceOf('Kozlice\\Monolog\\Handler\\KafkaHandler', $handler);
    }

    public function testConstructWithTopicConfig()
    {
        $topic = $this->createPartialMock('RdKafka\\ProducerTopic', ['produce']);

        $topicConfig = $this->createPartialMock('RdKafka\\TopicConf', []);

        $producer = $this->createPartialMock('RdKafka\\Producer', ['newTopic']);
        $producer->expects($this->once())
            ->method('newTopic')
            ->with('test', $topicConfig)
            ->willReturn($topic);

        $handler = new KafkaHandler($producer, 'test', $topicConfig);
        $this->assertInstanceOf('Kozlice\\Monolog\\Handler\\KafkaHandler', $handler);
    }

    public function testShouldLogMessage()
    {
        $record = $this->getRecord();
        $expectedMessage = sprintf("[%s] test.WARNING: test [] []", $record['datetime']);

        $topic = $this->createPartialMock('RdKafka\\ProducerTopic', ['produce']);
        $topic->expects($this->once())
            ->method('produce')
            ->with(RD_KAFKA_PARTITION_UA, 0, $expectedMessage);

        $producer = $this->createPartialMock('RdKafka\\Producer', ['newTopic']);
        $producer->expects($this->once())
            ->method('newTopic')
            ->with('test', $this->isInstanceOf('RdKafka\\TopicConf'))
            ->willReturn($topic);

        $handler = new KafkaHandler($producer, 'test');
        $handler->handle($record);
    }

    /**
     * @return array Record
     */
    protected function getRecord($level = Logger::WARNING, $message = 'test', $context = [])
    {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => microtime(true),
            'extra' => [],
        ];
    }
}
