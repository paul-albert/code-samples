<?php

/*
 * Logger class realization.
 */

class Logger
{
    
    // properties for work with queues
    protected $rabbitMq;
    protected $rabbitMqFile;

    public $identifier;

    // constants for type of log
    const REQUEST_TYPE = 'request';
    const RESPONSE_TYPE = 'response';

    /**
     * Class constructor.
     * 
     * @param RabbitMq|null $rabbitMq
     * @param string $rabbitMqFile File used if rabbitmq is dead
     * @param string $identifier
     */
    function __construct($rabbitMq, $rabbitMqFile, $identifier)
    {
        $this->rabbitMq = $rabbitMq;
        $this->rabbitMqFile = $rabbitMqFile;
        $this->identifier = $identifier;
    }

    /**
     * Sends log data to queue.
     * 
     * @param string $type - 'request' or 'response'
     * @param string $ip - IP address
     * @param string $data - data for logging
     * @return boolean
     */
    public function sendToQueue ($type, $ip, $data)
    {
        $msg = array(
            'date' => date('Y-m-d H:i:s'),
            'identifier' => $this->identifier,
            'type' => $type,
            'ip' => $ip,
            'log' => $data,
        );
        $msg = json_encode($msg);
        return RabbitMq::forcePublish($this->rabbitMq, $msg, $this->rabbitMqFile);
    }
}

?>
