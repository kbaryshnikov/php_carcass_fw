<?php

namespace Carcass\Log;

use Carcass\Mail;

class MailWriter implements WriterInterface {

    protected
        $Mailer,
        $sender,
        $recipient;

    public function __construct($Mailer, $recipient, $sender) {
        $this->Mailer = $Mailer;
        $this->recipient = $recipient;
        $this->sender = $sender;
    }

    public function log(Message $Message) {
        $this->Mailer->send($this->getMailMessage($Message), $this->recipient);
        return $this;
    }

    protected function getMailMessage(Message $Message) {
        $subject = $Message->getLevel() . ': ' . strtok(trim(substr($Message->getMessage(), 0, 80)), "\n");
        $body = $Message->getFormattedString();
        return Mail\Factory::createMessage($this->sender, $subject, $body);
    }

}
