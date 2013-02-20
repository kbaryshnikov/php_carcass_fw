<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mail;

use Carcass\Corelib;

require_once 'Swift/lib/swift_required.php';

/**
 * Mail sender via the Swift Mailer library
 * @package Carcass\Mail
 */
class Sender_Swift implements Sender_Interface {

    /** @var \Swift_Transport */
    protected $SwiftTransport;
    protected $method;
    protected $params;

    /**
     * @param string $method
     * @param array $params
     */
    public function __construct($method, array $params = array()) {
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * @param Message $Message
     * @param array|string $to
     * @return bool
     * @throws \LogicException
     * @throws \Exception
     */
    public function send(Message $Message, $to) {
        if (!is_array($to)) {
            $to = $to instanceof Corelib\ExportableInterface ? $to->exportArray() : array($to);
        }

        $SwiftMessage = \Swift_Message::newInstance()
            ->setSubject($Message->subject)
            ->setFrom(array($Message->sender))
            ->setTo(is_array($to) ? $to : array($to))
            ->setBody($Message->body);

        if ($Message->has('attachments')) {
            foreach ($Message->attachments as $attachment) {
                if (isset($attachment->contents)) {
                    $SwiftAttachment = \Swift_Attachment::newInstance()->setBody($attachment->contents);
                } elseif (isset($attachment->filename)) {
                    $SwiftAttachment = \Swift_Attachment::fromPath($attachment->filename);
                } else {
                    throw new \LogicException("Invalid attachment");
                }
                $SwiftAttachment->setContentType($attachment->mime_type);
                $SwiftMessage->attach($SwiftAttachment);
            }
        }

        if ($Message->has('encoding')) {
            $SwiftMessage->setCharset($Message->encoding);
        }

        $SwiftTransport = $this->getSwiftTransport();
        $SwiftTransport->start();

        $result = false;
        try {
            $result = $SwiftTransport->send($SwiftMessage);
        } catch (\Exception $e) {
            // pass
        }

        // finally:
        $SwiftTransport->stop();
        if (isset($e)) {
            throw $e;
        }

        return (bool)$result;
    }

    /**
     * @return \Swift_Transport
     */
    protected function getSwiftTransport() {
        if (!isset($this->SwiftTransport)) {
            $this->SwiftTransport = $this->assembleSwiftTransport();
        }
        return $this->SwiftTransport;
    }

    /**
     * @return \Swift_Transport
     * @throws \LogicException
     */
    protected function assembleSwiftTransport() {
        switch ($this->method) {
            case 'mail':
                return \Swift_MailTransport::newInstance();
            case 'smtp':
                $SwiftTransport = \Swift_SmtpTransport::newInstance(
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
                throw new \LogicException("Invalid configuration: unknown mail.method '{$this->method}'");
        }
    }

}
