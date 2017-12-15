# [Apache Kafka](https://kafka.apache.org/) handler for [Monolog](https://github.com/Seldaek/monolog)

[![Build Status](https://travis-ci.org/kozlice/monolog-kafka.svg?branch=master)](https://travis-ci.org/kozlice/monolog-kafka)

Support for logging into Apache Kafka using [rdkafka extension](https://github.com/arnaud-lb/php-rdkafka) (there are a few libraries for PHP, but this once seems to be most mature and supports Kafka versions 0.8, 0.9 & 0.10).

Usage example:

    $config = new \RdKafka\Conf();
    $config->set('metadata.broker.list', '127.0.0.1');
    $producer = new \RdKafka\Producer($config);
    $logger = new Logger('my_logger');
    $logger->pushHandler(new KafkaHandler($producer, 'test'));

Works with:
- PHP 5.6 & [rdkafka 1.*](https://pecl.php.net/package/rdkafka/1.0.0)
- PHP 7+ & [rdkafka 2.*](https://pecl.php.net/package/rdkafka/2.0.0)

Manually tested with Apache Kafka 0.10.1.
