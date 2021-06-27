# [Apache Kafka](https://kafka.apache.org/) handler for [Monolog](https://github.com/Seldaek/monolog)

[![Build Status](https://api.travis-ci.com/kozlice/monolog-kafka.svg?branch=master)](https://travis-ci.org/kozlice/monolog-kafka)

Support for logging into Apache Kafka using [rdkafka extension](https://github.com/arnaud-lb/php-rdkafka).

Usage example:

```php
$config = new \RdKafka\Conf();
$config->set('metadata.broker.list', '127.0.0.1');
$producer = new \RdKafka\Producer($config);
$logger = new Logger('my_logger');
$logger->pushHandler(new KafkaHandler($producer, 'test'));
```

Works with:
- PHP 7.4+ with PECL rdkafka 4.x/5.x
- PHP 8+ with PECL rdkafka 5.x

Manually tested with Apache Kafka 2.8.0.

If you need support for earlier versions of PHP or rdkafka extension, please
check out the `v1.0.1` tag.
