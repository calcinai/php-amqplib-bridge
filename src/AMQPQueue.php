<?php
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * stub class representing AMQPQueue from pecl-amqp
 */
class AMQPQueue {

    /** @var AMQPChannel  */
    private $channel;

    private $name;
    private $flags;
    private $arguments;

    private $consuming;
    private $messages;

    /**
     * Create an instance of an AMQPQueue object.
     *
     * @param AMQPChannel $amqp_channel The amqp channel to use.
     *
     * @throws AMQPQueueException      When amqp_channel is not connected to a
     *                                 broker.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     */
    public function __construct(AMQPChannel $amqp_channel) {
        $this->channel = $amqp_channel;
        $this->arguments = array();
        $this->flags = AMQP_NOPARAM;
        $this->consuming = false;
    }

    /**
     * Consume messages from a queue.
     *
     * Blocking function that will retrieve the next message from the queue as
     * it becomes available and will pass it off to the callback.
     *
     * @param callable | null $callback A callback function to which the
     *                              consumed message will be passed. The
     *                              function must accept at a minimum
     *                              one parameter, an AMQPEnvelope object,
     *                              and an optional second parameter
     *                              the AMQPQueue object from which callback
     *                              was invoked. The AMQPQueue::consume() will
     *                              not return the processing thread back to
     *                              the PHP script until the callback
     *                              function returns FALSE.
     *                              If the callback is omitted or null is passed,
     *                              then the messages delivered to this client will
     *                              be made available to the first real callback
     *                              registered. That allows one to have a single
     *                              callback consuming from multiple queues.
     * @param integer $flags A bitmask of any of the flags: AMQP_AUTOACK.
     * @param string $consumer_tag A string describing this consumer. Used
     *                              for canceling subscriptions with cancel().
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return void
     */
    public function consume(callable $callback = null, $flags = AMQP_NOPARAM, $consumer_tag = null) {

        $this->setupConsume($flags, $consumer_tag);

        while(count($this->channel->_getChannel()->callbacks) > 0)
            $this->channel->_getChannel()->wait();

    }

    /**
     * Retrieve the next message from the queue.
     *
     * Retrieve the next available message from the queue. If no messages are
     * present in the queue, this function will return FALSE immediately. This
     * is a non blocking alternative to the AMQPQueue::consume() method.
     * Currently, the only supported flag for the flags parameter is
     * AMQP_AUTOACK. If this flag is passed in, then the message returned will
     * automatically be marked as acknowledged by the broker as soon as the
     * frames are sent to the client.
     *
     * @param integer $flags A bitmask of supported flags for the
     *                       method call. Currently, the only the
     *                       supported flag is AMQP_AUTOACK. If this
     *                       value is not provided, it will use the
     *                       value of ini-setting amqp.auto_ack.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return AMQPEnvelope|boolean
     */
    public function get($flags = AMQP_NOPARAM) {

        if($this->consuming === false)
            $this->setupConsume($flags);

        //non-blocking read from the underlying socket.
        $read = array($this->getConnection()->_getConnection()->getSocket());
        $write = null;
        $except = null;
        while(stream_select($read, $write, $except, 0) > 0) {
            $this->channel->_getChannel()->wait();
            echo "data\n\n";
        }

        return false;

    }

    private function setupConsume($flags, $consumer_tag = null){
//foreach(debug_backtrace() as $bt){
//    unset($bt['object']);
//    print_r($bt);
//}
        echo $this->name."\n";
        print_r(func_get_args());

        $auto_ack = 0 !== $flags & AMQP_AUTOACK;

        if($consumer_tag === null)
            $consumer_tag = '';

        //Man-in-the-middle callback to transform response to an envelope.
        //AMQPProtocolChannelException
        print_r($this->channel->_getChannel()->basic_consume($this->name, $consumer_tag, $no_local = false, $auto_ack, $exclusive = false, $nowait = false,
            function(AMQPMessage $message) {

                $envelope = new AMQPEnvelope();

                print_r($message);

            }));

        $this->consuming = true;
    }

    /**
     * Declare a new queue on the broker.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return integer the message count.
     */
    public function declareQueue() {

        $durable = 0 !== $this->flags & AMQP_DURABLE;
        $passive = 0 !== $this->flags & AMQP_PASSIVE;
        $exclusive = 0 !== $this->flags & AMQP_EXCLUSIVE;
        $auto_delete = 0 !== $this->flags & AMQP_AUTODELETE;

        try {
            list($num_messages) = $this->channel->_getChannel()->queue_declare($this->name, $passive, $durable, $exclusive, $auto_delete, $nowait = false, $this->arguments);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return 0;
        }

        return $num_messages;
    }

    /**
     * Delete a queue from the broker.
     *
     * This includes its entire contents of unread or unacknowledged messages.
     *
     * @param integer $flags Optionally AMQP_IFUNUSED can be specified
     *                              to indicate the queue should not be
     *                              deleted until no clients are connected to
     *                              it.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return integer The number of deleted messages.
     */
    public function delete($flags = AMQP_NOPARAM) {

        $if_unused = 0 !== $flags & AMQP_IFUNUSED;

        try {
            list($num_deleted) = $this->channel->_getChannel()->queue_delete($this->name, $if_unused);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return 0;
        }

        //Will always be defined if an exception isn't thrown
        return $num_deleted;
    }


    /**
     * Get the argument associated with the given key.
     *
     * @param string $key The key to look up.
     *
     * @return string|integer|boolean The string or integer value associated
     *                                with the given key, or false if the key
     *                                is not set.
     */
    public function getArgument($key) {
        if(!isset($this->arguments[$key]))
            return false;

        return $this->arguments[$key];
    }

    /**
     * Get all set arguments as an array of key/value pairs.
     *
     * @return array An array containing all of the set key/value pairs.
     */
    public function getArguments() {
        return $this->arguments;
    }

    /**
     * Get all the flags currently set on the given queue.
     *
     * @return int An integer bitmask of all the flags currently set on this
     *             exchange object.
     */
    public function getFlags() {
        return $this->flags;
    }

    /**
     * Get the configured name.
     *
     * @return string The configured name as a string.
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Acknowledge the receipt of a message.
     *
     * This method allows the acknowledgement of a message that is retrieved
     * without the AMQP_AUTOACK flag through AMQPQueue::get() or
     * AMQPQueue::consume()
     *
     * @param string $delivery_tag The message delivery tag of which to
     *                              acknowledge receipt.
     * @param integer $flags The only valid flag that can be passed is
     *                              AMQP_MULTIPLE.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function ack($delivery_tag, $flags = AMQP_NOPARAM) {

        $multiple = 0 !== $flags & AMQP_MULTIPLE;

        try {
            $this->channel->_getChannel()->basic_ack($delivery_tag, $multiple);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return false;
        }

        return true;

    }

    /**
     * Bind the given queue to a routing key on an exchange.
     *
     * @param string $exchange_name Name of the exchange to bind to.
     * @param string $routing_key Pattern or routing key to bind with.
     * @param array $arguments Additional binding arguments.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function bind($exchange_name, $routing_key = null, array $arguments = array()) {

        if($routing_key === null)
            $routing_key = '';

        $bind_arguments = new AMQPTable($arguments);

        try {
            $this->channel->_getChannel()->queue_bind($this->name, $exchange_name, $routing_key, $nowait = false, $bind_arguments);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return false;
        }

        return true;

    }

    /**
     * Cancel a queue that is already bound to an exchange and routing key.
     *
     * @param string $consumer_tag The queue name to cancel, if the queue
     *                             object is not already representative of
     *                             a queue.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return bool;
     */
    public function cancel($consumer_tag = '') {

        try {
            $this->channel->_getChannel()->basic_cancel($consumer_tag);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return false;
        }

        return true;
    }


    /**
     * Mark a message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * AMQPQueue::consume() and AMQPQueue::get() and using the AMQP_AUTOACK
     * flag are not eligible. When called, the broker will immediately put the
     * message back onto the queue, instead of waiting until the connection is
     * closed. This method is only supported by the RabbitMQ broker. The
     * behavior of calling this method while connected to any other broker is
     * undefined.
     *
     * @param string $delivery_tag Delivery tag of last message to reject.
     * @param integer $flags AMQP_REQUEUE to requeue the message(s),
     *                              AMQP_MULTIPLE to nack all previous
     *                              unacked messages as well.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function nack($delivery_tag, $flags = AMQP_NOPARAM) {

        $requeue = 0 !== $flags & AMQP_REQUEUE;
        $multiple = 0 !== $flags & AMQP_MULTIPLE;

        try {
            $this->channel->_getChannel()->basic_nack($delivery_tag, $multiple, $requeue);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Mark one message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * AMQPQueue::consume() and AMQPQueue::get() and using the AMQP_AUTOACK
     * flag are not eligible.
     *
     * @param string $delivery_tag Delivery tag of the message to reject.
     * @param integer $flags AMQP_REQUEUE to requeue the message(s).
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function reject($delivery_tag, $flags = AMQP_NOPARAM) {

        $requeue = 0 !== $flags & AMQP_REQUEUE;

        try {
            $this->channel->_getChannel()->basic_reject($delivery_tag, $requeue);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Purge the contents of a queue.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function purge() {

        try {
            $this->channel->_getChannel()->queue_purge($this->name);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Set a queue argument.
     *
     * @param string $key The key to set.
     * @param mixed $value The value to set.
     *
     * @return boolean
     */
    public function setArgument($key, $value) {
        $this->arguments[$key] = $value;
    }

    /**
     * Set all arguments on the given queue.
     *
     * All other argument settings will be wiped.
     *
     * @param array $arguments An array of key/value pairs of arguments.
     *
     * @return boolean
     */
    public function setArguments(array $arguments) {
        $this->arguments = $arguments;
        return true;
    }

    /**
     * Set the flags on the queue.
     *
     * @param integer $flags A bitmask of flags:
     *                       AMQP_DURABLE, AMQP_PASSIVE,
     *                       AMQP_EXCLUSIVE, AMQP_AUTODELETE.
     *
     * @return boolean
     */
    public function setFlags($flags) {
        if($flags !== $flags & (AMQP_DURABLE | AMQP_PASSIVE | AMQP_EXCLUSIVE | AMQP_AUTODELETE))
            return false;

        $this->flags = $flags;
        return true;
    }

    /**
     * Set the queue name.
     *
     * @param string $queue_name The name of the queue.
     *
     * @return boolean
     */
    public function setName($queue_name) {
        $this->name = $queue_name;
        return true;
    }

    /**
     * Remove a routing key binding on an exchange from the given queue.
     *
     * @param string $exchange_name The name of the exchange on which the
     *                              queue is bound.
     * @param string $routing_key The binding routing key used by the
     *                              queue.
     * @param array $arguments Additional binding arguments.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function unbind($exchange_name, $routing_key = null, array $arguments = array()) {

        if($routing_key === null)
            $routing_key = '';

        $bind_arguments = new AMQPTable($arguments);

        try {
            $this->channel->_getChannel()->queue_unbind($this->name, $exchange_name, $routing_key, $bind_arguments);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the AMQPChannel object in use
     *
     * @return AMQPChannel
     */
    public function getChannel() {
        return $this->channel;
    }

    /**
     * Get the AMQPConnection object in use
     *
     * @return AMQPConnection
     */
    public function getConnection() {
        return $this->channel->getConnection();
    }
}
