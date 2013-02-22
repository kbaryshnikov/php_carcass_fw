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
        $_encoding = 'utf-8',
        $_sender,
        $_subject,
        $_body,
        $_attachments = array();

    /**
     * @param string $sender
     * @param string $subject
     * @param string $body
     */
    public function __construct($sender, $subject, $body) {
        $this->_sender       = (string)$sender;
        $this->_subject      = (string)$subject;
        $this->_body         = (string)$body;
    }

    /**
     * @param string $encoding
     */
    public function setEncoding($encoding) {
        $this->_encoding = (string)$encoding;
    }

    /**
     * @param string $mime_type
     * @param string $data
     */
    public function attachString($mime_type, $data) {
        $this->_attachments[] = (object)array('mime_type' => (string)$mime_type, 'contents' => (string)$data);
    }

    /**
     * @param string $mime_type
     * @param string $file
     */
    public function attachFile($mime_type, $file) {
        $this->_attachments[] = (object)array('mime_type' => (string)$mime_type, 'filename' => $file);
    }

    /**
     * @param $k
     * @return bool
     */
    public function has($k) {
        $key = '_' . $k;
        return !empty($this->$key);
    }

    /**
     * @param $k
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function __get($k) {
        $key = '_' . $k;
        if (isset($this->$key)) {
            return $this->$key;
        }
        throw new \OutOfBoundsException("$k undefined");
    }

}
