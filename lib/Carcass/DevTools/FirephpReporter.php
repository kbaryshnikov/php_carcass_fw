<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\DevTools;

/**
 * FirePHP Reporter.
 *
 * If no FirePHP instance passed directly to the constructor,
 * relies on 'FirePHP/FirePHP.class.php' in the include path.
 *
 * @package Carcass\DevTools
 */
class FirephpReporter extends BaseReporter {

    /**
     * @var \FirePHP
     */
    protected $FirePhp = null;

    /**
     * @param \FirePHP|null $FirePhp
     */
    public function __construct(\FirePHP $FirePhp = null) {
        if (null !== $FirePhp) {
            $this->setFirePhp($FirePhp);
        }
    }

    /**
     * @return \FirePHP
     */
    protected function getFirePhp() {
        if (null === $this->FirePhp) {
            self::ensureFirePhpLibraryIsLoaded();
            $this->setFirePhp(new \FirePHP);
        }
        return $this->FirePhp;
    }

    /**
     * @param mixed $value
     * @param $severity
     * @return $this
     */
    public function dump($value, $severity = null) {
        $this->getFirePhp()->fb($value, null, self::getFirePhpLevel($severity, $value));
        return $this;
    }

    /**
     * @param \Exception $Exception
     * @return $this
     */
    public function dumpException(\Exception $Exception) {
        $this->getFirePhp()->error($Exception);
        return $this;
    }

    /**
     * @param \FirePhp $FirePhp
     * @return $this
     */
    public function setFirePhp(\FirePhp $FirePhp) {
        $this->FirePhp = $FirePhp;
        $this->FirePhp->setEnabled(true);
        return $this;
    }

    /**
     * @param $severity
     * @param $value
     * @return string
     */
    protected static function getFirePhpLevel($severity, $value) {
        if (!$severity) {
            $severity = self::detectSeverity($value);
        }
        if (!$severity || !array_key_exists($severity = strtolower($severity), self::$severity_map)) {
            return 'LOG';
        }
        return self::$severity_map[$severity];
    }

    /**
     * @param $value
     * @return bool
     */
    protected static function detectSeverity($value) {
        $txt = strtolower(serialize($value));
        foreach (self::$markers as $substring => $severity) {
            if (false !== strpos($txt, $substring)) {
                return $severity;
            }
        }
        return false;
    }

    protected static function ensureFirePhpLibraryIsLoaded() {
        if (!class_exists('\FirePHP', true)) {
            include_once 'FirePHP/FirePHP.class.php';
        }
    }

    /**
     * @var array
     */
    protected static $severity_map = [
        'critical' => 'ERROR',
        'error'    => 'ERROR',
        'warning'  => 'WARN',
        'info'     => 'INFO'
    ];

    /**
     * @var array
     */
    protected static $markers = [
        'exception' => 'EXCEPTION',
        'critical'  => 'ERROR',
        'error'     => 'ERROR',
        'failed'    => 'WARN',
        'warning'   => 'WARN',
        'timers'    => 'INFO',
    ];

}