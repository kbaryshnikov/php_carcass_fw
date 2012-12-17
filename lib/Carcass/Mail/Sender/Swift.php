<?php

class Carcass_Mail_Sender_Swift implements Carcass_Mail_Sender_Interface {

    protected static $initialized = false;

    protected
        $SwiftTransport,
        $method,
        $params;

    protected static function initialize() {
        require 'Swift/lib/swift_required.php';
    }

    public function __construct($method, array $params = array()) {
        if (!static::$initialized) {
            static::initialize();
        }
        $this->method = $method;
        $this->params = $params;
    }

    public function send(Carcass_Mail_Message $Message, $to) {
        if (!is_array($to)) {
            $to = array($to);
        }

        $SwiftMessage = Swift_Message::newInstance()
            ->setSubject($Message->subject)
            ->setFrom(array($Message->sender))
            ->setTo(is_array($to) ? $to : array($to))
            ->setBody($Message->body);

        if ($Message->attachments) {
            foreach ($Message->attachments as $attachment) {
                if (isset($attachment->contents)) {
                    $SwiftAttachment = Swift_Attachment::newInstance()->setBody($attachment->contents);
                } elseif (isset($attachment->filename)) {
                    $SwiftAttachment = Swift_Attachment::fromPath($attachment->filename);
                } else {
                    throw new LogicException("Invalid attachment");
                }
                $SwiftAttachment->setContentType($attachment->mime_type);
                $SwiftMessage->attach($SwiftAttachment);
            }
        }

        if ($Message->encoding) {
            $SwiftMessage->setCharset($Message->encoding);
        }

        $SwiftTransport = $this->getSwiftTransport();
        $SwiftTransport->start();

        try {
            if (count($to) == 1) {
                $result = $SwiftTransport->send($SwiftMessage);
            } else {
                $result = $SwiftTransport->batchSend($SwiftMessage);
            }
        } catch (Exception $e) {
            // pass
        }

        // finally:
        $SwiftTransport->stop();
        if (isset($e)) {
            throw $e;
        }

        return (bool)$result;
    }

    protected function getSwiftTransport() {
        if (!isset($this->SwiftTransport)) {
            $this->SwiftTransport = $this->assembleSwiftTransport();
        }
        return $this->SwiftTransport;
    }

    protected function assembleSwiftTransport() {
        switch ($this->method) {
            case 'mail':
                return Swift_MailTransport::newInstance();
            case 'smtp':
                $SwiftTransport = Swift_SmtpTransport::newInstance(
                    !empty( $this->params['host'] ) ? $this->params['host'] : '127.0.0.1',
                    !empty( $this->params['port'] ) ? $this->params['port'] : 25
                );
                if (!empty( $this->params['encryption'] )) {
                    $SwiftTransport->setEncryption($this->params['encryption']);
                }
                if (!empty( $this->params['auth'] )) {
                    $SwiftTransport->setUsername($this->params['username'])->setPassword($this->params['password']);
                }
                return $SwiftTransport;
            default:
                throw new LogicException("Invalid configuration: unknown mail.method '{$this->method}'");
        }
    }

}
