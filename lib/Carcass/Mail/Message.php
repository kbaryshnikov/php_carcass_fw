<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mail;

/**
 * Mail Message
 * @package Carcass\Mail
 */
class Message {

    protected
        $encoding = 'utf-8',
        $sender,
        $subject,
        $body,
        $attachments = array();

    /**
     * @param string $sender
     * @param string $subject
     * @param string $body
     */
    public function __construct($sender, $subject, $body) {
        $this->sender       = (string)$sender;
        $this->subject      = (string)$subject;
        $this->body         = (string)$body;
    }

    /**
     * @param string $encoding
     */
    public function setEncoding($encoding) {
        $this->encoding = (string)$encoding;
    }

    /**
     * @param string $mime_type
     * @param string $data
     */
    public function attachString($mime_type, $data) {
        $this->attachments[] = (object)array('mime_type' => (string)$mime_type, 'contents' => (string)$data);
    }

    /**
     * @param string $mime_type
     * @param string $file
     */
    public function attachFile($mime_type, $file) {
        $this->attachments[] = (object)array('mime_type' => (string)$mime_type, 'filename' => $file);
    }

    /**
     * @param $k
     * @return bool
     */
    public function has($k) {
        return !empty($this->$k);
    }

    /**
     * @param $k
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function __get($k) {
        if (isset($this->$k)) {
            return $this->$k;
        }
        throw new \OutOfBoundsException("$k undefined");
    }

}
