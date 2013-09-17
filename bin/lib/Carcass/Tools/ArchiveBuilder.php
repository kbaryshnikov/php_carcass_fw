<?php

namespace Carcass\Tools;

use Carcass\Fs;
use Carcass\Corelib;
use Carcass\Config;
use Carcass\Vcs;
use Carcass\Process\ShellCommand;

class ArchiveBuilder {

    protected $Response;
    protected $Config;
    protected $co_dir;
    protected $has_carcass;
    protected $carcass_dir;
    protected $app_rev_ts;
    protected $app_dir;
    protected $clean_on_destruct = false;

    public function __construct(Corelib\ResponseInterface $Response, Config\ItemInterface $Config, $tmp_dir = null, $clean_on_destruct = false) {
        $this->Response = $Response;
        $this->Config = $Config;
        $this->co_dir = $this->prepareCoDir($tmp_dir ? : sys_get_temp_dir());
        $this->clean_on_destruct = $clean_on_destruct;
        $this->has_carcass = $Config->has('source.carcass');
    }

    public function build($target_tarball_path) {
        $this->carcass_dir = $this->checkoutCarcass();
        $this->app_dir = $this->checkoutApp();
        $this->injectCarcassToApp();
        $this->prepareEnv();
        $this->fetchAppDependencies();
        $this->cleanDotFiles();
        $this->archiveTo($target_tarball_path);
        return $this->app_rev_ts;
    }

    protected function archiveTo($target_tgz) {
        $stdout = null;
        $stderr = '';
        (new ShellCommand('tar', 'czf {{ target }} .'))->prepare(['target' => $target_tgz])
            ->setCwd($this->app_dir)->execute($stdout, $stderr);
        if ($stderr) {
            $this->sayError($stderr);
            throw new \LogicException("Failed to create archive. Aborting");
        }
    }

    protected function checkoutCarcass() {
        if (!$this->has_carcass) {
            return null;
        }
        return $this->doCheckout($this->Config->exportArrayFrom('source.carcass'), 'carcass');
    }

    protected function checkoutApp() {
        return $this->doCheckout($this->Config->exportArrayFrom('source.app'), 'app', $this->app_rev_ts);
    }

    protected function injectCarcassToApp() {
        if (!$this->has_carcass) {
            return;
        }
        $carcass_lib_dir = $this->carcass_dir . '/lib/Carcass';
        if (!file_exists($carcass_lib_dir) || !is_dir($carcass_lib_dir)) {
            throw new \RuntimeException("Carcass directory not exists in '$carcass_lib_dir'. Aborting");
        }
        $app_lib_dir = $this->app_dir . '/lib';
        if (!file_exists($app_lib_dir) || !is_dir($app_lib_dir)) {
            throw new \RuntimeException("Application 'lib' directory not exists in '$app_lib_dir'. Aborting");
        }
        if (file_exists($carcass_app_lib_dir = $app_lib_dir . '/Carcass')) {
            throw new \RuntimeException("lib/Carcass already exists in the application directory: '$carcass_app_lib_dir'. Aborting");
        }
        rename($carcass_lib_dir, $carcass_app_lib_dir);
        if (!file_exists($app_bin_dir = $this->app_dir . '/bin')) {
            mkdir($app_bin_dir);
        }
        Fs\Directory::copyRecursively($this->carcass_dir . '/bin', $app_bin_dir);
    }

    protected function prepareEnv() {
        $env = ['revision' => $this->app_rev_ts] + $this->Config->exportArrayFrom('env');
        Corelib\ArrayTools::exportToFile($this->app_dir . '/env.php', null, $env);
    }

    protected function fetchAppDependencies() {
        $stdout = STDOUT;
        $stderr = STDERR;
        (new ShellCommand('carcass', 'fetch-dependencies -c -x'))
            ->setCwd($this->app_dir)
            ->execute($stdout, $stderr);
    }

    protected function cleanDotFiles() {
        $FI = new Fs\Iterator($this->app_dir);
        $FI->setRecurse(false)->setFilterMask('.*')->setIncludeHidden()->setIncludeFolders()->setReturnFullPath();
        foreach ($FI as $file) {
            if (is_dir($file)) {
                Fs\Directory::deleteRecursively($file);
            } else {
                unlink($file);
            }
        }
    }

    protected function clean() {
        if ($this->co_dir) {
            Fs\Directory::deleteRecursively($this->co_dir);
            $this->co_dir = null;
        }
    }

    public function __destruct() {
        if ($this->clean_on_destruct) {
            $this->clean();
        }
    }

    protected function doCheckout(array $config, $target, &$ts = null) {
        $target_dir = $this->co_dir . '/' . $target;
        $Fetcher = Vcs\Fetcher::factoryFromArray($config, $target_dir);
        $Fetcher->beVerbose();
        $this->say("Fetching $config[url] to $target_dir...");
        $Fetcher->execCheckout();
        if (func_num_args() > 2) {
            $ts = $Fetcher->getRevisionTimestamp();
        }
        return $target_dir;
    }

    protected function prepareCoDir($basedir) {
        do {
            $dir = $basedir . '/CarcassDeploy-' . Corelib\Crypter::getRandomString();
        } while (file_exists($dir));
        Fs\Directory::mkdirIfNotExists($dir);
        return $dir;
    }

    protected function say($message) {
        $this->Response->writeLn('>>> ' . $message);
    }

    protected function sayError($error) {
        $this->Response->writeErrorLn($error);
    }

}