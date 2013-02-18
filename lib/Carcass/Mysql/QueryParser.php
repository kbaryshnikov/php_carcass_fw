<?php

namespace Carcass\Mysql;

class QueryParser {

    protected $Client;

    public function __construct(Client $Client = null) {
        $Client and $this->setClient($Client);
    }

    public function setClient(Client $Client) {
        $this->Client = $Client;
        return $this;
    }

    public function getClient() {
        if (null === $this->Client) {
            throw new \LogicException("Client is undefined");
        }
        return $this->Client;
    }

    public function escapeString($s) {
        return $this->getClient()->escapeString($s);
    }

    public function getTemplate($template) {
        return new QueryTemplate($this, $template);
    }

}
