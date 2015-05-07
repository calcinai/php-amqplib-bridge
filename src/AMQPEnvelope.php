<?php
use PhpAmqpLib\Message\AMQPMessage;

/**
 * stub class representing AMQPEnvelope from pecl-amqp
 */
class AMQPEnvelope {

    private $app_id;
    private $body;
    private $content_encoding;
    private $content_type;
    private $correlation_id;
    private $delivery_mode;
    private $delivery_tag;
    private $exchange_name;
    private $expiration;
    private $headers;
    private $message_id;
    private $priority;
    private $reply_to;
    private $routing_key;
    private $timestamp;
    private $type;
    private $user_id;
    private $is_redelivery;


    /**
     * Get the application id of the message.
     *
     * @return string The application id of the message.
     */
    public function getAppId() {
        return $this->app_id;
    }

    /**
     * Get the body of the message.
     *
     * @return string The contents of the message body.
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Get the content encoding of the message.
     *
     * @return string The content encoding of the message.
     */
    public function getContentEncoding() {
        return $this->content_encoding;
    }

    /**
     * Get the message content type.
     *
     * @return string The content type of the message.
     */
    public function getContentType() {
        return $this->content_type;
    }

    /**
     * Get the message correlation id.
     *
     * @return string The correlation id of the message.
     */
    public function getCorrelationId() {
        return $this->correlation_id;
    }

    /**
     * Get the delivery mode of the message.
     *
     * @return integer The delivery mode of the message.
     */
    public function getDeliveryMode() {
        return $this->delivery_mode;
    }

    /**
     * Get the delivery tag of the message.
     *
     * @return string The delivery tag of the message.
     */
    public function getDeliveryTag() {
        return $this->delivery_tag;
    }

    /**
     * Get the exchange name on which the message was published.
     *
     * @return string The exchange name on which the message was published.
     */
    public function getExchangeName() {
        return $this->exchange_name;
    }

    /**
     * Get the expiration of the message.
     *
     * @return string The message expiration.
     */
    public function getExpiration() {
        return $this->expiration;
    }

    /**
     * Get a specific message header.
     *
     * @param string $header_key Name of the header to get the value from.
     *
     * @return string|boolean The contents of the specified header or FALSE
     *                        if not set.
     */
    public function getHeader($header_key) {
        if(!isset($this->headers[$header_key]))
            return false;

        return $this->headers[$header_key];
    }

    /**
     * Get the headers of the message.
     *
     * @return array An array of key value pairs associated with the message.
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Get the message id of the message.
     *
     * @return string The message id
     */
    public function getMessageId() {
        return $this->message_id;
    }

    /**
     * Get the priority of the message.
     *
     * @return int The message priority.
     */
    public function getPriority() {
        return $this->priority;
    }

    /**
     * Get the reply-to address of the message.
     *
     * @return string The contents of the reply to field.
     */
    public function getReplyTo() {
        return $this->reply_to;
    }

    /**
     * Get the routing key of the message.
     *
     * @return string The message routing key.
     */
    public function getRoutingKey() {
        return $this->routing_key;
    }

    /**
     * Get the timestamp of the message.
     *
     * @return string The message timestamp.
     */
    public function getTimeStamp() {
        return $this->timestamp;
    }

    /**
     * Get the message type.
     *
     * @return string The message type.
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get the message user id.
     *
     * @return string The message user id.
     */
    public function getUserId() {
        return $this->user_id;
    }

    /**
     * Whether this is a redelivery of the message.
     *
     * Whether this is a redelivery of a message. If this message has been
     * delivered and AMQPEnvelope::nack() was called, the message will be put
     * back on the queue to be redelivered, at which point the message will
     * always return TRUE when this method is called.
     *
     * @return bool TRUE if this is a redelivery, FALSE otherwise.
     */
    public function isRedelivery() {
        return $this->is_redelivery;
    }


    /**
     * Build AMQPEnvelope from a php-amqplib message;
     *
     * @param AMQPMessage $message
     * @return AMQPEnvelope
     */
    public static function fromAMQPMessage(AMQPMessage $message) {

        $envelope = new self();
        $envelope->app_id = $message->has('app_id') ? $message->get('app_id') : '';
        $envelope->body = $message->body;
        $envelope->content_encoding  = $message->has('content_encoding') ? $message->get('content_encoding') : '';
        $envelope->content_type = $message->has('content_type') ? $message->get('content_type') : '';
        $envelope->correlation_id = $message->has('correlation_id') ? $message->get('correlation_id') : '';
        $envelope->delivery_mode = $message->has('delivery_mode') ? $message->get('delivery_mode') : '';
        $envelope->delivery_tag = $message->has('delivery_tag') ? $message->get('delivery_tag') : '';
        $envelope->exchange_name = $message->has('exchange') ? $message->get('exchange') : '';
        $envelope->expiration = $message->has('expiration') ? $message->get('expiration') : '';
        $envelope->headers = $message->has('application_headers') ? $message->get('application_headers')->getNativeData() : '';
        $envelope->message_id = $message->has('message_id') ? $message->get('message_id') : '';
        $envelope->priority = $message->has('priority') ? $message->get('priority') : '';
        $envelope->reply_to = $message->has('reply_to') ? $message->get('reply_to') : '';
        $envelope->routing_key = $message->has('routing_key') ? $message->get('routing_key') : '';
        $envelope->timestamp = $message->has('timestamp') ? $message->get('timestamp') : '';
        $envelope->type = $message->has('type') ? $message->get('type') : '';
        $envelope->user_id = $message->has('user_id') ? $message->get('user_id') : '';
        $envelope->is_redelivery = $message->has('redelivered') ? $message->get('redelivered') : '';

        return $envelope;
    }
}
