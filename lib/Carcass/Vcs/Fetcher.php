<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Vcs;

use Carcass\Corelib\JsonTools;
use Carcass\Fs\Directory;
use Carcass\Process\ShellCommand;

/**
 * Base abstract VCS Fetcher class and factory
 *
 * @package Carcass\Vcs
 */
abstract class Fetcher {

    /** @var string */
    protected $repository_url;

    /** @var string */
    protected $local_root;

    /** @var string */
    protected $revision = null;

    /** @var string */
    protected $branch = null;

    /** @var bool */
    protected $verbose = false;

    /**
     * @param string $vcs_type
     * @param string $repository_url
     * @param $local_root
     * @param string $revision revision or tag
     * @param string $branch
     * @throws \InvalidArgumentException
     * @return Fetcher
     */
    public static function factory($vcs_type, $repository_url, $local_root, $revision = '', $branch = '') {
        $class_name = __NAMESPACE__ . '\\Fetcher_' . ucfirst($vcs_type);
        if (!class_exists($class_name)) {
            throw new \InvalidArgumentException("Unsupported vcs type: '$vcs_type'");
        }
        return new $class_name($repository_url, $local_root, $revision, $branch);
    }

    /**
     * @param array $vcs_data ['type' => type, 'url' => repository url, optional 'revision' => revision, optional 'branch' => branch]
     * @param string $local_root
     * @throws \InvalidArgumentException
     * @return Fetcher
     */
    public static function factoryFromArray(array $vcs_data, $local_root) {
        if (!isset($vcs_data['type'])) {
            throw new \InvalidArgumentException("Missing vcs type");
        }
        if (!isset($vcs_data['url'])) {
            throw new \InvalidArgumentException("Missing vcs url");
        }

        return static::factory(
            $vcs_data['type'],
            $vcs_data['url'],
            $local_root,
            array_key_exists('rev', $vcs_data) ? $vcs_data['rev'] : '',
            array_key_exists('branch', $vcs_data) ? $vcs_data['branch'] : ''
        );
    }

    /**
     * @param string $repository_url
     * @param string $local_root
     * @param string $revision
     * @param string $branch
     * @internal param array $args
     */
    public function __construct($repository_url, $local_root, $revision = '', $branch = '') {
        $this->repository_url = (string)$repository_url;
        $this->local_root     = rtrim($local_root, '/');
        $this->revision       = (string)$revision ? : null;
        $this->branch         = (string)$branch ? : null;
    }

    public function fetch($allow_delete = false) {
        if ($this->directoryExists($this->local_root)) {
            if ($this->doesSavedConfigurationMatchCurrent()) {
                return $this->update();
            }
            if (!$allow_delete) {
                throw new \RuntimeException("Directory {$this->local_root} already exists, "
                    . "but VCS configuration is not saved or saved data does not match current VCS settings");
            }
            Directory::deleteRecursively($this->local_root);
        }
        return $this->checkout()->update();
    }

    public function checkout() {
        $this->ensureIsReadyForCheckout();
        $this->execCheckout();
        $this->writeCheckoutConfigFile();
        return $this;
    }

    public function update() {
        $this->ensureIsReadyForUpdate();
        $this->execUpdate();
        return $this;
    }

    public function beVerbose($verbose = true) {
        $this->verbose = (bool)$verbose;
        return $this;
    }

    abstract public function execCheckout();

    abstract public function execUpdate();

    protected function ensureIsReadyForUpdate() {
        $this->ensureIsWriteableDirectory($this->local_root);
        $this->ensureConfigurationMatches();
    }

    protected function doesSavedConfigurationMatchCurrent() {
        $cfg = JsonTools::decode(file_get_contents($this->getCheckoutConfigFile()));
        return $cfg && $this->compareConfiguration($cfg);
    }

    protected function compareConfiguration(array $cfg) {
        return $cfg['fetcher'] === get_class($this) && $cfg['url'] === $this->repository_url;
    }

    protected function getConfiguration() {
        return ['fetcher' => get_class($this), 'url' => $this->repository_url];
    }

    protected function ensureConfigurationMatches() {
        if (!$this->doesSavedConfigurationMatchCurrent()) {
            throw new \RuntimeException('Configuration mismatch between saved and current repository data');
        }
    }

    protected function writeCheckoutConfigFile() {
        file_put_contents(
            $this->getCheckoutConfigFile(),
            JsonTools::encode($this->getConfiguration()),
            LOCK_EX
        );
    }

    protected function getCheckoutConfigFile() {
        return rtrim($this->local_root, '/') . '-vcs.json';
    }

    protected function ensureIsReadyForCheckout() {
        if (file_exists($this->local_root)) {
            throw new \RuntimeException("Directory '{$this->local_root}' already exists");
        }
        $dir = dirname($this->local_root);
        Directory::mkdirIfNotExists($dir);
        $this->ensureIsWriteableDirectory($dir);
    }

    protected function ensureIsWriteableDirectory($dir) {
        if (!$this->isWriteableDirectory($dir)) {
            throw new \RuntimeException("Directory '$dir' not exists or is not writeable");
        }
    }

    protected function isWriteableDirectory($dir) {
        return $this->directoryExists($dir) && is_writeable($dir);
    }

    protected function directoryExists($dir) {
        return file_exists($dir) && is_dir($dir);
    }

    protected function exec($bin, $args_template, array $args, $setcwd = false) {
        $ShellCommand = new ShellCommand($bin, $args_template);

        if ($setcwd) {
            $ShellCommand->setCwd(true === $setcwd ? $this->local_root : $setcwd);
        }

        if ($this->verbose) {
            $stdout = STDOUT;
            $stderr = STDERR;
        }

        $ShellCommand->prepare($args)->execute($stdout, $stderr);
        return true;
    }

}