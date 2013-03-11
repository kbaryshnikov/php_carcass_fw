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
 * LogWriter Factory
 * @package Carcass\Log
 */
class WriterFactory {

    /**
     * @param string $name
     * @param string|array $arguments
     * @return WriterInterface
     * @throws \InvalidArgumentException
     */
    public static function assemble($name, $arguments) {
        $method = 'assemble' . str_replace('_', '', $name) . 'Writer';
        if (!method_exists(get_called_class(), $method)) {
            throw new \InvalidArgumentException("Unknown writer name: '$name'");
        }
        $arguments = (array)$arguments;
        return static::$method($arguments);
    }

    /**
     * @param array $arguments
     * @return FileWriter
     * @throws \InvalidArgumentException
     */
    public static function assembleFileWriter(array $arguments) {
        if (!isset($arguments['filename'])) {
            throw new \InvalidArgumentException('Required argument missing: "filename"');
        }
        return new FileWriter($arguments['filename']);
    }

    /**
     * @return ErrorLogWriter
     */
    public static function assembleErrorLogWriter() {
        return new ErrorLogWriter;
    }

    /**
     * @param array $arguments
     * @return SyslogWriter
     */
    public static function assembleSyslogWriter(array $arguments) {
        return new SyslogWriter(isset($arguments['ident']) ? $arguments['ident'] : null);
    }

    /**
     * @param array $arguments
     * @return MailWriter
     * @throws \InvalidArgumentException
     */
    public static function assembleMailWriter(array $arguments) {
        if (!isset($arguments['recipient'])) {
            throw new \InvalidArgumentException('Required argument missing: "recipient"');
        }
        if (!isset($arguments['sender'])) {
            throw new \InvalidArgumentException('Required argument missing: "sender"');
        }
        return new MailWriter(
            Mail\Factory::createMailer(),
            $arguments['recipient'],
            $arguments['sender']
        );
    }

}
