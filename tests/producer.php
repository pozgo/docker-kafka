#!/opt/remi/php71/root/usr/bin/php -q
<?php
$rk = new RdKafka\Producer();
$rk->setLogLevel(LOG_DEBUG);
$rk->addBrokers("192.168.0.50:9092");
$topic = $rk->newTopic("test1");
for ($i = 0; $i < 10; $i++) {
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, "Message $i");
}
echo "10 Messages sent...";
