<?php

namespace Monolog\Handler;

use Kozlice\Monolog\Handler\KafkaHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RdKafka\Conf;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;
use Symfony\Component\Process\Process;

class KafkaHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('rdkafka')) {
            $this->markTestSkipped('This test requires rdkafka extension to run');
        }
    }

    /**
     * @group Unit
     */
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

    /**
     * @group Unit
     */
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

    /**
     * @group Unit
     */
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

    /**
     * @group Unit
     */
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
     * @group Integration
     */
    public function testShouldFindMessagesInKafkaTopic()
    {
        $cwd = realpath(getenv('KAFKA_PATH'));

        $zookeeper = new Process(['bin/zookeeper-server-start.sh', 'config/zookeeper.properties'], $cwd);
        $zookeeper->start();
        $zookeeper->waitUntil(function ($descriptor, $data) {
            return $descriptor === Process::OUT && false !== strpos($data, 'INFO binding to port');
        });

        $kafka = new Process(['bin/kafka-server-start.sh', 'config/server.properties'], $cwd);
        $kafka->start();
        $kafka->waitUntil(function ($descriptor, $data) {
            return $descriptor === Process::OUT && 0 !== preg_match('~INFO \[KafkaServer id=\d+\] started~', $data);
        });

        $createTopic = new Process(['bin/kafka-topics.sh', '--create', '--topic', 'monolog', '--bootstrap-server', 'localhost:9092'], $cwd);
        $createTopic->run();

        $config = new Conf();
        $config->set('metadata.broker.list', '127.0.0.1');
        $producer = new Producer($config);
        $logger = new Logger('test');
        $handler = new KafkaHandler($producer, 'monolog');
        $handler->setFlushTimeout(5000);
        $logger->pushHandler($handler);

        $logger->info('Some info', ['message' => 'Hello']);
        $logger->error('Some error');
        // There will be flush to Kafka on handler destruction.
        unset($logger, $handler);
        sleep(10);

        $consume = new Process(['bin/kafka-console-consumer.sh', '--topic', 'monolog', '--from-beginning', '--bootstrap-server', 'localhost:9092', '--timeout-ms', '1000'], $cwd);
        $consume->run();

        $kafka->stop();
        $zookeeper->stop();

        $this->assertStringContainsString('test.INFO: Some info {"message":"Hello"} []', $consume->getOutput());
        $this->assertStringContainsString('test.ERROR: Some error [] []', $consume->getOutput());
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
