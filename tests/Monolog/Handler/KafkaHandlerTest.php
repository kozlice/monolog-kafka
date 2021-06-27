<?php

namespace Monolog\Handler;

use Kozlice\Monolog\Handler\KafkaHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;

class KafkaHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('rdkafka')) {
            $this->markTestSkipped('This test requires rdkafka extension to run');
        }
    }

    public function testConstruct()
    {
        $topic = $this->createMock(ProducerTopic::class);

        $producer = $this->createMock(Producer::class);
        $producer->expects($this->once())
            ->method('newTopic')
            ->with('test', $this->isInstanceOf(TopicConf::class))
            ->willReturn($topic);

        $handler = new KafkaHandler($producer, 'test');
        $this->assertInstanceOf(KafkaHandler::class, $handler);
    }

    public function testConstructWithTopicConfig()
    {
        $topic = $this->createMock(ProducerTopic::class);

        $topicConfig = $this->createMock(TopicConf::class);

        $producer = $this->createMock(Producer::class);
        $producer->expects($this->once())
            ->method('newTopic')
            ->with('test', $topicConfig)
            ->willReturn($topic);

        $handler = new KafkaHandler($producer, 'test', $topicConfig);
        $this->assertInstanceOf(KafkaHandler::class, $handler);
    }

    public function testShouldLogMessage()
    {
        $record = $this->getRecord();
        $expectedMessage = sprintf("[%s] test.WARNING: test [] []", $record['datetime']);

        $topic = $this->createMock(ProducerTopic::class);
        $topic->expects($this->once())
            ->method('produce')
            ->with(RD_KAFKA_PARTITION_UA, 0, $expectedMessage);

        $producer = $this->createMock(Producer::class);
        $producer->expects($this->once())
            ->method('newTopic')
            ->with('test', $this->isInstanceOf(TopicConf::class))
            ->willReturn($topic);

        $handler = new KafkaHandler($producer, 'test');
        $handler->handle($record);
    }

    public function testShouldFlushOnDestructionWithGivenTimeout()
    {
        $topic = $this->createMock(ProducerTopic::class);

        $producer = $this->createMock(Producer::class);
        $producer->expects($this->once())
            ->method('newTopic')
            ->with('test', $this->isInstanceOf(TopicConf::class))
            ->willReturn($topic);
        $producer->expects($this->once())
            ->method('flush')
            ->with(500);

        $handler = new KafkaHandler($producer, 'test');
        $handler->setFlushTimeout(500);
        unset($handler);
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
