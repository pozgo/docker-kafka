<?php

/**
 * This class handles Kafka specific abstraction allowing any queuing system to be swapped in
 */
class Kafka
{
    /**
     * Holds the current configuration
     *
     * @var array
     */
    private $config = array();

    /**
     * Create the comma separate list of brokers, we save this
     * in a local variable so we can compare to see if the
     * config has changed
     *
     * @var string
     */
    private $brokerList = '';

    /**
     * The consumer object that does all the work
     *
     * @var object
     */
    private $consumer;

    /**
     * Holds the queue to be returned to the calling code
     *
     * @var object
     */
    private $queue;

    /**
     * Stores all topic's that have been added to the queue
     *
     * @var array
     */
    private $topics = array();

    /**
     * Holds the last error if something goes wrong
     *
     * @var string
     */
    public $error = '';

    /**
     * The last message to be retrieved
     *
     * @var object
     */
    private $message;

    /**
     * Kafka constructor.
     *
     * Fires up the connection and prepares the topics and partitions ready for
     * consumption by the calling code.
     *
     * @param $config
     */
    public function __construct($config) {
        $this->config = $config;
        $this->brokerList = implode(',', $config['brokers']);

        $this->consumer = new RdKafka\Consumer();
        $this->consumer->setLogLevel($this->config['loglevel']);
        $this->consumer->addBrokers($this->brokerList);

        $this->prepareQueue();
    }

    /**
     * Retrieves a single message from the queue and returns it so that it can be processed
     * as required by the calling process.
     *
     * @return string|null|false
     */
    public function getMessage() {
        // Default the return value to null
        $return = null;
        $this->message = $this->queue->consume($this->config['timeout']);
        if($this->message != null) {
            var_export($this->message);
            if ($this->message->err) {
                // error -191 is not really, it means there are no messages in the queue
                if ($this->message->err != -191) {
                    // When there is a real error we return false so we can tell the difference to a null result
                    $return = false;
                    $this->error = rd_kafka_err2str($this->message->err) . '(' . $this->message->err . ')';
                } else {
                    // If we get a -191 this is the end of the queue and we should save the offset for this topic/partition
                    $this->lastMessageComplete();
                }
            } else {
                // Return the content of the message to the caller and let them deal with how its encoded
                $return = $this->message->payload;
            }
        }
        return $return;
    }

    /**
     * Marks the last message as complete and stores the offset so on future start ups we don't need to process
     * the message again.
     */
    public function lastMessageComplete() {
        $topic     = $this->message->topic_name;
        $partition = $this->message->partition;
        $offset    = $this->message->offset;
        $filename  = $topic.'-'.$partition.'.txt';
        file_put_contents($this->config['state_dir'].'/'.$filename, $offset);
    }

    /**
     * Queues up all topics and partitions that this instance should be processing
     * and returning messages for.  Needs to be reconstructed each time the queue is emptied out
     */
    public function prepareQueue() {
        // Initialise the queue object
        $this->queue = $this->consumer->newQueue();
        // Loop through all of the configured topics
        foreach($this->config['topics'] as $topic) {
            $this->topics[$topic['name']] = $this->consumer->newTopic($topic['name']);
            foreach($topic['partitions'] as $partition) {
                $offset = $this->getOffset($topic['name'], $partition);
                echo 'Will start processing topic:'.$topic['name'].' partition:'.$partition.' at offset '.PHP_EOL;
                $this->topics[$topic['name']]->consumeQueueStart($partition, $offset, $this->queue);
            }
        }
    }

    /**
     * Gets the current offset for the chosen topic and parition, returns zero if there is no offset file
     * or something goes wrong.
     *
     * @param $topic
     * @param $partition
     * @return int
     */
    private function getOffset($topic, $partition) {
        $filename  = $topic.'-'.$partition.'.txt';
        if(is_readable($this->config['state_dir'].'/'.$filename)) {
            $offset = intval(file_get_contents($this->config['state_dir'].'/'.$filename));
            if($offset > 0) {
                echo 'Loaded stored offset of: '.$offset.PHP_EOL;
                return $offset;
            } else {
                echo 'Non-positive integer loaded from stored offset of: '.$offset.PHP_EOL;
                return 0;
            }
        } else {
            echo 'Unable to open offset file, defaulting to zero'.PHP_EOL;
            return 0;
        }
    }
}
