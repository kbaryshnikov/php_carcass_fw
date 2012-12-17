<?php

namespace Carcass\Mail;

class Message {

    protected
        $encoding = 'utf-8',
        $sender,
        $subject,
        $body,
        $attachments = array();

    public function __construct($sender, $subject, $body) {
        $this->sender       = (string)$sender;
        $this->subject      = (string)$subject;
        $this->body         = (string)$body;
    }

    public function setEncoding($encoding) {
        $this->encoding = $encoding;
    }

    public function attachString($mime_type, $data) {
        $this->attachments[] = (object)array('mime_type' => (string)$mime_type, 'contents' => (string)$data);
    }

    public function attachFile($mime_type, $file) {
        $this->attachments[] = (object)array('mime_type' => (string)$mime_type, 'filename' => $file);
    }

    public function __get($k) {
        if (isset($this->$k)) {
            return $this->$k;
        }
        throw new \OutOfBoundsException("$k undefined");
    }

}
