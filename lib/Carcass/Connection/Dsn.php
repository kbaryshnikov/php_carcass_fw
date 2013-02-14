<?php

namespace Carcass\Connection;

use Carcass\Corelib;

class Dsn extends Corelib\Hash {

    public static function factory($dsn) {
        if (Corelib\ArrayTools::isTraversable($dsn)) {
            return new DsnPool($dsn);
        } else {
            return new static($dsn);
        }
    }

    public function __construct($dsn_str = null) {
        parent::__construct();
        $dsn_str and $this->parseDsn($dsn_str);
    }

    public static function constructByTokens(Corelib\Hash $Tokens) {
        $self = new static;
        $self->parseDsnTokens($Tokens);
        return $self;
    }

    public function getType() {
        return $this->type;
    }

    public function __toString() {
        $array_data = $this->exportArray();
        if (isset($array_data['args'])) {
            foreach ($array_data['args'] as $key => $value) {
                $array_data['Args'][] = compact('key', 'value');
            }
        }
        return Corelib\StringTools::parseTemplate(
            '{{ type }}://{{ IF user }}{{ user }}{{ IF password }}:{{ password }}{{ END }}@{{ END }}' .
            '{{ IF socket }}unix:{{ socket }}{{ END }}' .
            '{{ UNLESS socket }}{{ hostname }}{{ IF port }}:{{ port }}{{ END }}/{{ IF name }}{{ name }}{{ END }}{{ END }}' .
            '{{ IF Args }}?{{ BEGIN Args }}{{ key }}={{ value }}{{ UNLESS _last }}&{{ END }}{{ END }}{{ END }}',
            $array_data
        );
    }

    public function parseDsn($dsn_str) {
        $tokens = parse_url($dsn_str);
        if (empty($tokens) || empty($tokens['scheme'])) {
            throw new \LogicException("Malformed dsn: '$dsn_str'");
        }
        $Tokens = new Corelib\Hash($tokens);

        return $this->parseDsnTokens($Tokens);
    }

    public function parseDsnTokens(Corelib\Hash $Tokens) {
        if ($Tokens->has('query')) {
            parse_str($Tokens->query, $args);
        } else {
            $args = [];
        }

        $this->merge([
            'type'      => $Tokens->scheme,
            'user'      => $Tokens->get('user'),
            'password'  => $Tokens->get('pass'),
            'args'      => new Corelib\Hash($args),
        ]);

        if ($Tokens->get('host') === 'unix' && $Tokens->has('path')) {
            $this->set('socket', $Tokens->path);
        } else {
            $this->merge([
                'hostname'  => $Tokens->get('host'),
                'port'      => $Tokens->get('port'),
                'name'      => ltrim($Tokens->get('path', ''), '/') ?: null,
            ]);
        }
    }

}
