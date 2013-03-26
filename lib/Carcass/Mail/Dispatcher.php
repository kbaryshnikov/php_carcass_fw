<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mail;

use Carcass\Corelib;

/**
 * Mail Dispatcher. Takes application configuration and provides SwiftMailer factories.
 *
 * Requires SwiftMailer.
 *
 * @package Carcass\Mail
 */
class Dispatcher {

    /**
     * @var \Carcass\Corelib\DatasourceInterface
     */
    protected $MailConfiguration;

    /**
     * @var \Swift_Transport
     */
    protected $Transport = null;

    /**
     * @var array
     */
    protected $args = ["" => []];

    /**
     * @param Corelib\DatasourceInterface $MailConfiguration
     */
    public function __construct(Corelib\DatasourceInterface $MailConfiguration = null) {
        static::requireSwift();
        $MailConfiguration and $this->configure($MailConfiguration);
    }

    /**
     * @param Corelib\DatasourceInterface $MailConfiguration
     * @return $this
     */
    public function configure(Corelib\DatasourceInterface $MailConfiguration) {
        $this->Transport = null;
        $this->MailConfiguration = $MailConfiguration;
        return $this;
    }

    /**
     * @param \Swift_Message $Message
     * @param array $failed_recp by-ref array of failures
     * @return int # of messages sent
     */
    public function send(\Swift_Message $Message, array &$failed_recp = []) {
        return $this->getMailer($Message->getTo())->send($Message, $failed_recp);
    }

    public function sendBatch(\Swift_Message $Message, array $recp_list, array &$failed_recp = []) {
        $sent_count = 0;
        foreach ($recp_list as $recp_key => $recp_value) {
            is_int($recp_key) ? $Message->setTo($recp_value) : $Message->setTo($recp_key, $recp_value);
            $sent_count += $this->send($Message, $failed_recp);
        }
        return $sent_count;
    }

    public function setGlobalReplacements(array $args) {
        return $this->setReplacementsFor("", $args);
    }

    public function setReplacements(array $recp_args) {
        foreach ($recp_args as $recp_addr => $args) {
            $this->setReplacementsFor($recp_addr, $args);
        }
        return $this;
    }

    public function setReplacementsFor($recp_addr, array $args) {
        $this->args[$recp_addr] = [];
        array_walk(
            $args, function ($value, $key) use ($recp_addr) {
                $this->args[$recp_addr]['{' . $key . '}'] = $value;
            }
        );
        return $this;
    }

    /**
     * @param string $subject
     * @param string $body
     * @param string $content_type
     * @param string $charset
     * @return \Swift_Message
     */
    public function newMessage($subject = null, $body = null, $content_type = null, $charset = null) {
        return \Swift_Message::newInstance($subject, $body, $content_type, $charset);
    }

    /**
     * @param null $attach_file_path
     * @param null $content_type
     * @return \Swift_Mime_Attachment
     */
    public function newAttachment($attach_file_path = null, $content_type = null) {
        return null === $attach_file_path ? \Swift_Attachment::newInstance() : \Swift_Attachment::fromPath($attach_file_path, $content_type);
    }

    /**
     * @param null $file_path
     * @param null $content_type
     * @return \Swift_Mime_EmbeddedFile
     */
    public function newEmbeddedFile($file_path = null, $content_type = null) {
        return null === $file_path ? \Swift_EmbeddedFile::newInstance() : \Swift_EmbeddedFile::fromPath($file_path, $content_type);
    }

    /**
     * @param $method
     * @param array $args
     * @return object
     * @throws \BadMethodCallException
     */
    public function __call($method, array $args) {
        if ('new' === substr($method, 0, 3)) {
            $swift_class = '\\Swift_' . substr($method, 3);
            if (class_exists($swift_class)) {
                return Corelib\ObjectTools::construct($swift_class, $args);
            }
        }
        throw new \BadMethodCallException("Unknown method: $method");
    }

    /**
     * @param array $recp_list
     * @return \Swift_Mailer
     */
    protected function getMailer(array $recp_list = []) {
        $Mailer = \Swift_Mailer::newInstance($this->getTransport());
        if ($this->args) {
            $Mailer->registerPlugin(new \Swift_Plugins_DecoratorPlugin($this->getReplacements($recp_list)));
        }
        return $Mailer;
    }

    protected function getReplacements(array $recp_list) {
        $result = [];
        foreach ($recp_list as $recp_key => $recp_value) {
            $recp = is_int($recp_key) ? $recp_value : $recp_key;
            $result[$recp] = (isset($this->args[$recp]) ? $this->args[$recp] : []) + $this->args[""];
        }
        return $result;
    }

    /**
     * @return \Swift_Transport
     */
    protected function getTransport() {
        if (null === $this->Transport) {
            $this->Transport = $this->assembleTransport();
        }
        return $this->Transport;
    }

    /**
     * @return mixed
     * @throws \LogicException
     */
    protected function assembleTransport() {
        Corelib\Assert::that(__CLASS__ . ' is configured')->isNotEmpty($this->MailConfiguration);
        Corelib\Assert::that('transport type is configured')->isNotEmpty($this->MailConfiguration->getPath('transport.type'));
        $method = 'assemble' . $this->MailConfiguration->getPath('transport.type') . 'Transport';
        if (!method_exists($this, $method)) {
            throw new \LogicException("Not implemented: $method");
        }
        return $this->$method();
    }

    /**
     * @return \Swift_SmtpTransport
     */
    protected function assembleSmtpTransport() {
        $TransportConfig = $this->MailConfiguration->get('transport');
        $Transport = new \Swift_SmtpTransport($TransportConfig->get('host', 'localhost'), $TransportConfig->get('port', 25));
        $TransportConfig->has('encryption') and $Transport->setEncryption($TransportConfig->encryption);
        /** @noinspection PhpUndefinedMethodInspection */ // __call magic
        $TransportConfig->has('username') and $Transport->setUsername($TransportConfig->username);
        /** @noinspection PhpUndefinedMethodInspection */ // __call magic
        $TransportConfig->has('password') and $Transport->setPassword($TransportConfig->password);
        return $Transport;
    }

    /**
     * @return \Swift_SendmailTransport
     */
    protected function assembleSendmailTransport() {
        return \Swift_SendmailTransport::newInstance($this->MailConfiguration->getPath('transport.command', '/usr/sbin/sendmail -bs'));
    }

    /**
     * @return \Swift_MailTransport
     */
    protected function assemblePhpMailTransport() {
        return \Swift_MailTransport::newInstance();
    }

    /**
     * @return void
     */
    protected static function requireSwift() {
        static $swift_library_loaded = false;
        if (!$swift_library_loaded) {
            include_once 'Swift/swift_required.php';
            $swift_library_loaded = true;
        }
    }

}