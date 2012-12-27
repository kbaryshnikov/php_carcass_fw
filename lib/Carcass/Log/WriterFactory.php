<?php

namespace Carcass\Log;

use Carcass\Mail;

class WriterFactory {

    public static function assemble($name, $arguments) {
        $method = 'assemble' . str_replace('_', '', $name) . 'Writer';
        if (!method_exists(get_called_class(), $method)) {
            throw new \InvalidArgumentException("Unknown writer name: '$name'");
        }
        $arguments = (array)$arguments;
        return static::$method((array)$arguments);
    }

    public static function assembleFileWriter(array $arguments) {
        if (!isset($arguments['filename'])) {
            throw new \InvalidArgumentException('Required argument missing: "filename"');
        }
        return new FileWriter($arguments['filename']);
    }

    public static function assembleErrorLogWriter() {
        return new ErrorLogWriter;
    }

    public static function assembleSyslogWriter(array $arguments) {
        return new SyslogWriter(isset($arguments['ident']) ? $arguments['ident'] : null);
    }

    public static function assembleMailWriter(array $arguments) {
        if (!isset($arguments['recipient'])) {
            throw new InvalidArgumentException('Required argument missing: "recipient"');
        }
        if (!isset($arguments['sender'])) {
            throw new InvalidArgumentException('Required argument missing: "sender"');
        }
        return new MailWriter(
            Mail\Factory::assembleMailer(),
            $arguments['recipient'],
            $arguments['sender']
        );
    }

}
