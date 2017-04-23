<?php
$config = array(
    'service'   => 'Kafka',
    'brokers'   => array('192.168.0.50:9092'),
    'state_dir' => '/workdir/state',
    'loglevel'  => LOG_DEBUG,
    'timeout'   => 1000,
    'topics'    => array(
        array('name' => 'test1', 'partitions' => array(0)),
    ),
    'config_check_interval' => 6,
    'queue_check_interval'  => 2,
);
