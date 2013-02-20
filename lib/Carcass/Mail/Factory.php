<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mail;

use Carcass\Application\Injector;

/**
 * Mail Factory
 * @package Carcass\Mail
 */
class Factory {

    const
        DEFAULT_SENDER = 'swift',
        DEFAULT_METHOD = 'mail';

    /**
     * @var array
     */
    private static $senders = [
        'swift' => '\Carcass\Mail\Sender_Swift',
    ];

    /**
     * @param null $sender_type
     * @return Sender_Interface
     */
    public static function createMailer($sender_type = null) {
        return self::createSenderForCurrentConfig($sender_type);
    }

    /**
     * @param string|null $type
     * @throws \RuntimeException
     * @return Sender_Interface
     */
    public static function createSenderForCurrentConfig($type = null) {
        $config = Injector::getConfigReader()->get('mail');
        if (!$config) {
            throw new \RuntimeException('mail configuration missing')
        }
        $method = $config->get('method', self::DEFAULT_METHOD);
        $params = $config->exportArrayFrom('params', []);
        if (null === $type) {
            $type = $config->get('sender_type', self::DEFAULT_SENDER);
        }
        return self::createSender($type, $method, $params);
    }

    /**
     * @param string $sender
     * @param string $method
     * @param array $params
     * @return Sender_Interface
     * @throws \LogicException
     */
    public static function createSender($sender, $method, array $params = []) {
        if (!isset(self::$senders[$sender])) {
            throw new \LogicException("Do not known implementation of sender '$sender'");
        }
        $sender_impl_name = self::$senders[$sender];
        return new $sender_impl_name($method, $params);
    }

    /**
     * @param string $from
     * @param string $subject
     * @param string $body
     * @return Message
     */
    public static function createMessage($from, $subject, $body) {
        return new Message($from, $subject, $body);
    }

}
