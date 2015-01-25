<?php
/**
 * Work with RabbitMQ
 */

require_once 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMq
{
    protected $connection;
    protected $channel;
    protected $queue;

    /**
     * Creates connection and channel, declares persistent queue
     * @param array $config connection params
     * @param string $queue queue name
     */
    public function __construct($config, $queue)
    {
        $this->queue = $queue;
        $this->connection = new AMQPConnection($config['host'], $config['port'], $config['username'], $config['password']);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queue, false, true, false, false);
    }
    
    /**
     * Closes channel and connection
     */
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
    
    /**
     * Publishes persistent message to queue
     * @param string $message
     */
    public function publish($message)
    {
        $msg = new AMQPMessage($message, array('delivery_mode' => 2));
        $this->channel->basic_publish($msg, '', $this->queue);
    }

    /**
     * Consumes messages and sends them to callback
     * @param callable $callback
     */
    public function consume($callback)
    {
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($this->queue, '', false, false, false, false, $callback);
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
    
    /**
     * Purges queue
     */
    public function purge()
    {
        $this->channel->queue_purge($this->queue);
    }

    /**
     * Remove message from persistent queue
     * @param AMQPMessage $msg
     */
    public static function removeFromQueue($msg)
    {
        // TODO: message if not 'basic_ack' is owned by process until it dies - make possibility to release and requeue message on error in callback
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    /**
     * Tries to publish to queue or save to file if failed
     * @param RabbitMq|null $rabbitmq
     * @param string $message
     * @param string $file backup file
     */
    public static function forcePublish($rabbitmq, $message, $file)
    {
        if ($rabbitmq) {
            $rabbitmq->publish($message);
        } else {
            return FileHandler::putToFile($file, $message."\n");
        }
        
        return true;
    }
}