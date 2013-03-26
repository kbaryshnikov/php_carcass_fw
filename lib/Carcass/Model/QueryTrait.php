<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */// PHPStorm bug: @method not resolved without FQ spec
namespace Carcass\Model;

use Carcass\Query;

/**
 * Query methods trai
 *
 * A user must implement
 * protected method \Carcass\Query\BaseDispatcher assembleQueryDispatcher()
 * protected method \Carcass\Query\BaseDispatcher prepareQueryDispatcher()
 *
 * @package Carcass\Model
 */
trait QueryTrait {

    /**
     * @var Query\BaseDispatcher
     */
    protected $QueryDispatcher = null;

    /**
     * @return \Carcass\Query\BaseDispatcher
     */
    protected function getQueryDispatcher() {
        if (null === $this->QueryDispatcher) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->QueryDispatcher = $this->assembleQueryDispatcher();
        }
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->prepareQueryDispatcher($this->QueryDispatcher);
    }

    /*
    protected function prepareQueryDispatcher($QueryDispatcher) {
        return $QueryDispatcher;
    }

    /*
     * @return \Carcass\Query\BaseDispatcher
     *
    protected function assembleQueryDispatcher() {
        return $this->configureAssembledQueryDispatcher($this->constructQueryDispatcher());
    }

    /**
     * @return Query\BaseDispatcher
     *
    protected function constructQueryDispatcher() {
        return new Query\BaseDispatcher;
    }
    */

    /**
     * @param Query\BaseDispatcher $QueryDispatcher
     * @return Query\BaseDispatcher
     */
    protected function configureBaseQueryDispatcher(Query\BaseDispatcher $QueryDispatcher) {
        if (null !== $config_path = $this->getConfigDatabaseDsnPath()) {
            $QueryDispatcher->setConfigDatabaseDsnPath($config_path);
        }
        return $QueryDispatcher;
    }

    /**
     * @return string|null
     */
    protected function getConfigDatabaseDsnPath() {
        return null;
    }

}