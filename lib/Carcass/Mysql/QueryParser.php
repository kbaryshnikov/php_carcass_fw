<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mysql;

/**
 * MySQL Query Parser
 * @package Carcass\Mysql
 */
class QueryParser {

    /**
     * @var Client
     */
    protected $Client;

    /**
     * @param Client $Client
     */
    public function __construct(Client $Client = null) {
        $Client and $this->setClient($Client);
    }

    /**
     * @param Client $Client
     * @return $this
     */
    public function setClient(Client $Client) {
        $this->Client = $Client;
        return $this;
    }

    /**
     * @return Client
     * @throws \LogicException
     */
    public function getClient() {
        if (null === $this->Client) {
            throw new \LogicException("Client is undefined");
        }
        return $this->Client;
    }

    /**
     * @param string $s
     * @return string
     */
    public function escapeString($s) {
        return $this->getClient()->escapeString($s);
    }

    /**
     * @param $template
     * @return QueryTemplate
     */
    public function getTemplate($template) {
        return new QueryTemplate($this, $template);
    }

}
