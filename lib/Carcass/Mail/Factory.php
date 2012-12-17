<?php

namespace Carcass\Mail;

class Factory {

    const
        DEFAULT_SENDER = 'swift',
        DEFAULT_METHOD = 'mail';

    static private $senders = array(
        'swift' => '\\Carcass\\Mail\\Sender_Swift',
    );

    public static function createMailer($sender_type = null) {
        return self::createSenderForCurrentConfig($sender_type);
    }

    public static function createSenderForCurrentConfig($type = null) {
        $config = ConfigReader()->mail;
        $method = isset($config->method) ? $config->method : self::DEFAULT_METHOD;
        $params = isset($config->params) ? $config->params->exportArray() : array();
        $sender_type = empty($type) ? ( isset($config->sender_type) ? $config->sender_type : self::DEFAULT_SENDER ) : $type;
        return self::createSender($sender_type, $method, $params);
    }

    public static function createSender($sender, $method, array $params = array()) {
        if (!isset(self::$senders[$sender])) {
            throw new LogicException("Do not known implementation of sender '$sender'");
        }
        $sender_impl_name = self::$senders[$sender];
        return new $sender_impl_name($method, $params);
    }

    public static function createMessage($from, $subject, $body) {
        return new Message($from, $subject, $body);
    }

}
