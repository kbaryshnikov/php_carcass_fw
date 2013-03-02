<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

use Carcass\Corelib;

/**
 * Represents a connection DSN.
 *
 * @package Carcass\Connection
 */
class Dsn extends Corelib\Hash implements DsnInterface {

    /**
     * Factory method. If an array/traversable object is given, returns DsnPool, otherwise returns Dsn
     *
     * @param mixed $dsn
     * @return DsnPool|Dsn
     */
    public static function factory($dsn) {
        if ($dsn instanceof self) {
            return $dsn;
        } elseif (Corelib\ArrayTools::isTraversable($dsn)) {
            return new DsnPool($dsn);
        } else {
            return new static((string)$dsn);
        }
    }

    /**
     * @param string|null $dsn_str
     */
    public function __construct($dsn_str = null) {
        parent::__construct();
        $dsn_str and $this->parseDsn($dsn_str);
    }

    /**
     * Constructs Dsn from tokens (see the parseDsnTokens() implementation for details)
     *
     * @param \Carcass\Corelib\Hash $Tokens
     * @return Dsn
     */
    public static function constructByTokens(Corelib\Hash $Tokens) {
        /** @var $self Dsn */
        $self = new static;
        return $self->parseDsnTokens($Tokens);
    }

    /**
     * Constructs Dsn from tokens array (see the parseDsnTokens() implementation for details)
     *
     * @param array $tokens
     * @return Dsn
     */
    public static function constructByTokensArray(array $tokens) {
        /** @var $self Dsn */
        $self = new static;
        return $self->parseDsnTokens(new Corelib\Hash($tokens));
    }

    /**
     * @return string|null
     */
    public function getType() {
        return $this->get('type');
    }

    /**
     * @return string
     */
    public function __toString() {
        $array_data = $this->exportArray();
        if (isset($array_data['args'])) {
            foreach ($array_data['args'] as $key => $value) {
                $array_data['Args'][] = compact('key', 'value');
            }
        }
        return (string)Corelib\StringTools::parseTemplate(
            '{{ type }}://{{ IF user }}{{ user }}{{ IF password }}:{{ password }}{{ END }}@{{ END }}' .
                '{{ IF socket }}unix:{{ socket }}{{ END }}' .
                '{{ UNLESS socket }}{{ hostname }}{{ IF port }}:{{ port }}{{ END }}/{{ IF name }}{{ name }}{{ END }}{{ END }}' .
                '{{ IF Args }}?{{ BEGIN Args }}{{ key }}={{ value }}{{ UNLESS _last }}&{{ END }}{{ END }}{{ END }}',
            $array_data
        );
    }

    /**
     * @param string $dsn_str
     * @return $this
     * @throws \LogicException
     */
    public function parseDsn($dsn_str) {
        $tokens = parse_url($dsn_str);
        if (empty($tokens) || empty($tokens['scheme'])) {
            throw new \LogicException("Malformed dsn: '$dsn_str'");
        }
        $Tokens = new Corelib\Hash($tokens);

        return $this->parseDsnTokens($Tokens);
    }

    /**
     * @param \Carcass\Corelib\Hash $Tokens
     * @return $this
     */
    public function parseDsnTokens(Corelib\Hash $Tokens) {
        if ($Tokens->has('query')) {
            parse_str($Tokens->query, $args);
        } else {
            $args = [];
        }

        $this->merge(
            [
                'type'     => $Tokens->scheme,
                'user'     => $Tokens->get('user'),
                'password' => $Tokens->get('pass'),
                'args'     => new Corelib\Hash($args),
            ]
        );

        if ($Tokens->get('host') === 'unix' && $Tokens->has('path')) {
            $this->set('socket', $Tokens->get('path'));
        } else {
            $this->merge(
                [
                    'hostname' => $Tokens->get('host'),
                    'port'     => $Tokens->get('port'),
                    'name'     => ltrim($Tokens->get('path', ''), '/') ? : null,
                ]
            );
        }

        return $this;
    }

}
