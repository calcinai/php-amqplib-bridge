<?php
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * stub class representing AMQPExchange from pecl-amqp
 */
class AMQPExchange {

    /**
     * The current channel
     * @var AMQPChannel
     */
    private $channel;

    /**
     * Exchange name
     * @var string
     */
    private $name;

    /**
     * Exchange type
     * @var int
     */
    private $type;

    /**
     * The flags
     * @var int
     */
    private $flags;

    /**
     * Exchange arguments
     * @var array
     */
    private $arguments;

    /**
     * Enable publisher acknowledgements
     * @var bool
     */
    private $publisherAcks = true;

    /**
     * Create an instance of AMQPExchange.
     *
     * Returns a new instance of an AMQPExchange object, associated with the
     * given AMQPChannel object.
     *
     * @param AMQPChannel $amqp_channel A valid AMQPChannel object, connected
     *                                  to a broker.
     *
     * @throws AMQPExchangeException   When amqp_channel is not connected to
     *                                 a broker.
     * @throws AMQPConnectionException If the connection to the broker was
     *                                 lost.
     */
    public function __construct(AMQPChannel $amqp_channel) {
        $this->channel = $amqp_channel;
        $this->arguments = array();
        $this->flags = AMQP_NOPARAM;
    }

    /**
     * Declare a new exchange on the broker.
     *
     * @throws AMQPExchangeException   On failure.
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function declareExchange() {

        $durable = boolval($this->flags & AMQP_DURABLE);
        $passive = boolval($this->flags & AMQP_PASSIVE);
        $auto_delete = boolval($this->flags & AMQP_AUTODELETE);

        try {
            $this->channel->_getChannel()->exchange_declare($this->name, $this->type, $passive, $durable, $auto_delete);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            throw new AMQPExchangeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return true;
    }

    /**
     * Bind to another exchange.
     *
     * Bind an exchange to another exchange using the specified routing key.
     *
     * @param string $exchange_name Name of the exchange to bind.
     * @param string $routing_key The routing key to use for binding.
     * @param array $arguments Additional binding arguments.
     *
     * @throws AMQPExchangeException   On failure.
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     * @return boolean true on success or false on failure.
     */
    public function bind($exchange_name, $routing_key = '', array $arguments = array()) {

        try {
            $this->channel->_getChannel()->exchange_bind($exchange_name, $this->name, $routing_key, $nowait = false, $arguments);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            throw new AMQPExchangeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return true;

    }

    /**
     * Remove binding to another exchange.
     *
     * Remove a routing key binding on an another exchange from the given exchange.
     *
     * @param string $exchange_name Name of the exchange to bind.
     * @param string $routing_key The routing key to use for binding.
     * @param array $arguments Additional binding arguments.
     *
     * @throws AMQPExchangeException   On failure.
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     * @return boolean true on success or false on failure.
     */
    public function unbind($exchange_name, $routing_key = '', array $arguments = array()) {

        try {
            $this->channel->_getChannel()->exchange_unbind($exchange_name, $this->name, $routing_key, $nowait = false, $arguments);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            throw new AMQPExchangeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return true;
    }

    /**
     * Delete the exchange from the broker.
     *
     * @param string $exchangeName Optional name of exchange to delete.
     * @param integer $flags Optionally AMQP_IFUNUSED can be specified
     *                              to indicate the exchange should not be
     *                              deleted until no clients are connected to
     *                              it.
     *
     * @throws AMQPExchangeException   On failure.
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean true on success or false on failure.
     */
    public function delete($exchangeName = null, $flags = AMQP_NOPARAM) {

        $if_unused = boolval($flags & AMQP_IFUNUSED);

        if($exchangeName === null)
            $exchangeName = $this->name;

        try {
            $this->channel->_getChannel()->exchange_delete($exchangeName, $if_unused);
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            throw new AMQPExchangeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return true;

    }

    /**
     * Get the argument associated with the given key.
     *
     * @param string $key The key to look up.
     *
     * @return string|integer|boolean The string or integer value associated
     *                                with the given key, or FALSE if the key
     *                                is not set.
     */
    public function getArgument($key) {
        if(!isset($this->arguments[$key]))
            return false;

        return $this->arguments[$key];
    }

    /**
     * Get all arguments set on the given exchange.
     *
     * @return array An array containing all of the set key/value pairs.
     */
    public function getArguments() {
        return $this->arguments;
    }

    /**
     * Get all the flags currently set on the given exchange.
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
     * Get the configured type.
     *
     * @return string The configured type as a string.
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Publish a message to an exchange.
     *
     * Publish a message to the exchange represented by the AMQPExchange object.
     *
     * @param string $message The message to publish.
     * @param string $routing_key The optional routing key to which to
     *                             publish to.
     * @param integer $flags One or more of AMQP_MANDATORY and
     *                             AMQP_IMMEDIATE.
     * @param array $attributes One of content_type, content_encoding,
     *                             message_id, user_id, app_id, delivery_mode,
     *                             priority, timestamp, expiration, type
     *                             or reply_to, headers.
     *
     * @throws AMQPExchangeException   On failure.
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function publish($message, $routing_key = null, $flags = AMQP_NOPARAM, array $attributes = array()) {


        $mandatory = boolval($flags & AMQP_MANDATORY);
        $immediate = boolval($flags & AMQP_IMMEDIATE);

        if($routing_key === null)
            $routing_key = '';

        //Why different keys?
        if(isset($attributes['headers'])){
            $attributes['application_headers'] = new AMQPTable($attributes['headers']);
            unset($attributes['headers']);
        }

        $amqp_message = new AMQPMessage($message, $attributes);

        try {
            $this->channel->_getChannel()->basic_publish($amqp_message, $this->name, $routing_key, $mandatory, $immediate);

            if ($this->publisherAcks === true) {
                $this->channel->_getChannel()->wait_for_pending_acks_returns(1);
            }
        } catch (AMQPRuntimeException $e) {
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            throw new AMQPExchangeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return true;
    }

    /**
     * Set the value for the given key.
     *
     * @param string $key Name of the argument to set.
     * @param string|integer $value Value of the argument to set.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setArgument($key, $value) {
        $this->arguments[$key] = $value;
    }

    /**
     * Set all arguments on the exchange.
     *
     * @param array $arguments An array of key/value pairs of arguments.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setArguments(array $arguments) {
        $this->arguments = $arguments;
        return true;
    }

    /**
     * Set the flags on an exchange.
     *
     * @param integer $flags A bitmask of flags. This call currently only
     *                       considers the following flags:
     *                       AMQP_DURABLE, AMQP_PASSIVE
     *                       (and AMQP_DURABLE & AMQP_AUTODELETE, if librabbitmq version >= 0.5.3)
     *
     * @return boolean True on success or false on failure.
     */
    public function setFlags($flags) {

        if($flags !== $flags & (AMQP_DURABLE | AMQP_PASSIVE | AMQP_AUTODELETE))
            return false;

        $this->flags = $flags;
        return true;
    }

    /**
     * Set the name of the exchange.
     *
     * @param string $exchange_name The name of the exchange to set as string.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setName($exchange_name) {
        $this->name = $exchange_name;
        return true;
    }

    /**
     * Set the type of the exchange.
     *
     * Set the type of the exchange. This can be any of AMQP_EX_TYPE_DIRECT,
     * AMQP_EX_TYPE_FANOUT, AMQP_EX_TYPE_HEADERS or AMQP_EX_TYPE_TOPIC.
     *
     * @param string $exchange_type The type of exchange as a string.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setType($exchange_type) {

        if(!in_array($exchange_type, array(AMQP_EX_TYPE_DIRECT, AMQP_EX_TYPE_FANOUT, AMQP_EX_TYPE_HEADERS, AMQP_EX_TYPE_TOPIC)))
            return false;

        $this->type = $exchange_type;
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
