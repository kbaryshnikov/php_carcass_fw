<?php

namespace Carcass\Mail;

interface Sender_Interface {

    /**
     * __construct 
     * 
     * @param string $method  'mail' or 'smtp'
     * @param array $params   for 'smtp' method:
     *      array('host' => string hostname or IP address, 'port' => int port, 'auth' => bool auth_required, 'username' => str, 'password' => str)
     * @return void
     */
    public function __construct($method, array $params = array());

    /**
     * send
     *
     * @param Message $Message
     * @param string|array $to recipient email, or array of recipient emails
     * @return bool
     */
    public function send(Message $Message, $to);

}
