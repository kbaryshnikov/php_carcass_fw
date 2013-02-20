<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Log;

use Carcass\Mail;

/**
 * MailWriter
 * @package Carcass\Log
 */
class MailWriter implements WriterInterface {

    /** @var \Carcass\Mail\Sender_Interface */
    protected $Mailer;
    protected $sender;
    protected $recipient;

    /**
     * @param $Mailer
     * @param $recipient
     * @param $sender
     */
    public function __construct(Mail\Sender_Interface $Mailer, $recipient, $sender) {
        $this->Mailer    = $Mailer;
        $this->recipient = $recipient;
        $this->sender    = $sender;
    }

    /**
     * @param Message $Message
     * @return $this
     */
    public function log(Message $Message) {
        $this->Mailer->send($this->getMailMessage($Message), $this->recipient);
        return $this;
    }

    /**
     * @param Message $Message
     * @return \Carcass\Mail\Message
     */
    protected function getMailMessage(Message $Message) {
        $subject = $Message->getLevel() . ': ' . strtok(trim(substr($Message->getMessage(), 0, 80)), "\n");
        $body    = $Message->getFormattedString();
        return Mail\Factory::createMessage($this->sender, $subject, $body);
    }

}
