<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;

class AMQPConnection {

    private $host;
    private $port;
    private $vhost;
    private $login;
    private $password;

    private $connect_timeout;
    private $read_timeout;
    private $write_timeout;
    private $heartbeat = 0;
    private $keepalive = false;

    /** @var bool */
    private $persistent;

    /** @var AMQPStreamConnection */
    private $connection;



    /**
     * Create an instance of AMQPConnection.
     *
     * Creates an AMQPConnection instance representing a connection to an AMQP
     * broker. A connection will not be established until
     * AMQPConnection::connect() is called.
     *
     *  $credentials = array(
     *      'host'  => amqp.host The host to connect too. Note: Max 1024 characters.
     *      'port'  => amqp.port Port on the host.
     *      'vhost' => amqp.vhost The virtual host on the host. Note: Max 128 characters.
     *      'login' => amqp.login The login name to use. Note: Max 128 characters.
     *      'password' => amqp.password Password. Note: Max 128 characters.
     *      'read_timeout'  => Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
     *      'write_timeout' => Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.
     *      'connect_timeout' => Connection timeout. Note: 0 or greater seconds. May be fractional.
     *      'heartbeat' => Heartbeat negociation in seconds. This has been added in php-amqp 1.6beta3, so not available for 1.4 stable
     * )
     *
     * @param array $credentials Optional array of credential information for
     *                           connecting to the AMQP broker.
     */
    public function __construct(array $credentials = array()) {

        $this->host = isset($credentials['host']) ? $credentials['host'] : 'localhost';
        $this->port = isset($credentials['port']) ? $credentials['port'] : '5672';
        $this->vhost = isset($credentials['vhost']) ? $credentials['vhost'] : '/';
        $this->login = isset($credentials['login']) ? $credentials['login'] : '';
        $this->password = isset($credentials['password']) ? $credentials['password'] : '';
        $this->connect_timeout = isset($credentials['connect_timeout']) ? $credentials['connect_timeout'] : 3;
        $this->read_timeout = isset($credentials['read_timeout']) ? $credentials['read_timeout'] : 3;
        $this->write_timeout = isset($credentials['write_timeout']) ? $credentials['write_timeout'] : 3;
        $this->heartbeat = isset($credentials['heartbeat']) ? $credentials['heartbeat'] : 0;
        $this->keepalive = isset($credentials['keepalive']) ? $credentials['keepalive'] : false;

        $this->persistent = false;

    }

    /**
     * Establish a transient connection with the AMQP broker.
     *
     * This method will initiate a connection with the AMQP broker.
     *
     * @throws AMQPConnectionException
     * @return boolean TRUE on success or throw an exception on failure.
     */
    public function connect() {
        $readWriteTimeout = max($this->read_timeout, $this->write_timeout); //?
        try {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->login,
                $this->password,
                $this->vhost,
                false,                      //insist
                'AMQPLAIN',                 //login method
                null,                       //login response
                'en_US',                    //locale
                $this->connect_timeout,     // connect_timeout
                $readWriteTimeout,          // read_write_timeout
                null,                       // context
                $this->keepalive,           // keepalive
                $this->heartbeat            // heartbeat configuration
            );

            //Several exception types might be thrown
        } catch (Exception $e){
            throw new AMQPConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Closes the transient connection with the AMQP broker.
     *
     * This method will close an open connection with the AMQP broker.
     *
     * @return boolean true if connection was successfully closed, false otherwise.
     */
    public function disconnect() {

        if(!$this->isConnected())
            return false;

        try {
            $this->connection->close();
        } catch (Exception $e){
            return false;
        }

        return true;
    }

    /**
     * Get the configured host.
     *
     * @return string The configured hostname of the broker
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * Get the configured login.
     *
     * @return string The configured login as a string.
     */
    public function getLogin() {
        return $this->login;
    }

    /**
     * Get the configured password.
     *
     * @return string The configured password as a string.
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Get the configured port.
     *
     * @return int The configured port as an integer.
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * Get the configured vhost.
     *
     * @return string The configured virtual host as a string.
     */
    public function getVhost() {
        return $this->vhost;
    }

    /**
     * Check whether the connection to the AMQP broker is still valid.
     *
     * It does so by checking the return status of the last connect-command.
     *
     * @return boolean True if connected, false otherwise.
     */
    public function isConnected() {
        return isset($this->connection);
    }

    /**
     * Establish a persistent connection with the AMQP broker.
     *
     * This method will initiate a connection with the AMQP broker
     * or reuse an existing one if present.
     *
     * @throws AMQPNotImplementedException
     *
     * @throws AMQPConnectionException
     * @return boolean TRUE on success or throws an exception on failure.
     */
    public function pconnect() {
        throw new AMQPNotImplementedException();
    }

    /**
     * Closes a persistent connection with the AMQP broker.
     *
     * This method will close an open persistent connection with the AMQP
     * broker.
     *
     * @throws AMQPNotImplementedException

     * @return boolean true if connection was found and closed,
     *                 false if no persistent connection with this host,
     *                 port, vhost and login could be found,
     */
    public function pdisconnect() {
        throw new AMQPNotImplementedException();
    }

    /**
     * Close any open transient connections and initiate a new one with the AMQP broker.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function reconnect() {

        try {
            $this->disconnect();
            $this->connect();
        } catch (AMQPConnectionException $e){
            return false;
        }

        return true;
    }

    /**
     * Close any open persistent connections and initiate a new one with the AMQP broker.
     *
     * @throws AMQPNotImplementedException
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function preconnect() {
        throw new AMQPNotImplementedException();
    }


    /**
     * Set the hostname used to connect to the AMQP broker.
     *
     * @param string $host The hostname of the AMQP broker.
     *
     * @throws AMQPConnectionException If host is longer then 1024 characters.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setHost($host) {
        $this->host = $host;
        return true;
    }

    /**
     * Set the login string used to connect to the AMQP broker.
     *
     * @param string $login The login string used to authenticate
     *                      with the AMQP broker.
     *
     * @throws AMQPConnectionException If login is longer then 32 characters.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setLogin($login) {
        $this->login = $login;
        return true;
    }

    /**
     * Set the password string used to connect to the AMQP broker.
     *
     * @param string $password The password string used to authenticate
     *                         with the AMQP broker.
     *
     * @throws AMQPConnectionException If password is longer then 32characters.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setPassword($password) {
        $this->password = $password;
        return true;
    }

    /**
     * Set the port used to connect to the AMQP broker.
     *
     * @param integer $port The port used to connect to the AMQP broker.
     *
     * @throws AMQPConnectionException If port is longer not between
     *                                 1 and 65535.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setPort($port) {
        $this->port = $port;
        return true;
    }

    /**
     * Sets the virtual host to which to connect on the AMQP broker.
     *
     * @param string $vhost The virtual host to use on the AMQP
     *                      broker.
     *
     * @throws AMQPConnectionException If host is longer then 32 characters.
     *
     * @return boolean true on success or false on failure.
     */
    public function setVhost($vhost) {
        $this->vhost = $vhost;
        return true;
    }

    /**
     * Sets the interval of time to wait for income activity from AMQP broker
     *
     * @deprecated use AMQPConnection::setReadTimout($timeout) instead
     *
     * @param int $timeout
     *
     * @return bool
     */
    public function setTimeout($timeout) {
        return $this->setReadTimeout($timeout);
    }

    /**
     * Get the configured interval of time to wait for income activity
     * from AMQP broker
     *
     * @deprecated use AMQPConnection::getReadTimout() instead
     *
     * @return float
     */
    public function getTimeout() {
        return $this->getReadTimeout();
    }

    /**
     * Sets the interval of time to wait for income activity from AMQP broker
     *
     * @param int $timeout
     *
     * @return bool
     */
    public function setReadTimeout($timeout) {
        $this->read_timeout = $timeout;
        return true;
    }

    /**
     * Get the configured interval of time to wait for income activity
     * from AMQP broker
     *
     * @return float
     */
    public function getReadTimeout() {
        return $this->read_timeout;
    }

    /**
     * Sets the interval of time to wait for outcome activity to AMQP broker
     *
     * @param int $timeout
     *
     * @return bool
     */
    public function setWriteTimeout($timeout) {
        $this->write_timeout = $timeout;
        return true;
    }

    /**
     * Get the configured interval of time to wait for outcome activity
     * to AMQP broker
     *
     * @return float
     */
    public function getWriteTimeout() {
        return $this->write_timeout;
    }

    /**
     * Return last used channel id during current connection session.
     *
     * @return int
     */
    public function getUsedChannels() {
        if(!$this->isConnected())
            return 0;

        return $this->connection->getChannelId();
    }

    /**
     * Get the maximum number of channels the connection can handle.
     *
     * @return int|null
     */
    public function getMaxChannels() {
        return null;
    }

    /**
     * Whether connection persistent.
     *
     * @return bool|null
     */
    public function isPersistent() {
        return $this->persistent;
    }

    /**
     * Get the underlying php-amqplib connection
     *
     * @return AMQPStreamConnection
     */
    public function _getConnection(){
        return $this->connection;
    }
}
