<?php

namespace Carcass\Tools;

use Carcass\Application;
use Carcass\Corelib;
use Carcass\Config;
use Carcass\Fs;
use Carcass\Vcs;

class FetchDependenciesScript extends Controller {

    const VCS_SUBDIR = '/.clones';

    protected $vendor_lib_path;
    protected $vcs_clone_dir;
    protected $quiet = false;
    protected $force = false;

    public function actionDefault(Corelib\Hash $Args) {
        $app_root        = $Args->get('app_root');
        $vendor_lib_path = null;

        $libs = Application\Dependencies::getApplicationDependencies(
            $vendor_lib_path,
            $this->getAppConfig($app_root)->getPath('dependencies')
        );

        if (!$libs) {
            throw new \RuntimeException("No dependencies configured for application");
        }

        if (!$vendor_lib_path) {
            throw new \RuntimeException("Dependencies directory is not configured");
        }

        $this->vendor_lib_path = rtrim($vendor_lib_path, '/');

        $this->vcs_clone_dir = $this->vendor_lib_path . self::VCS_SUBDIR;

        Fs\Directory::mkdirIfNotExists($this->vendor_lib_path);
        Fs\Directory::mkdirIfNotExists($this->vcs_clone_dir);

        $this->quiet = $Args->get('q');
        $this->force = $Args->get('f');

        foreach ($libs as $name => $config) {
            $this->updateDependency($name, $config);
        }

        return 0;
    }

    protected function updateDependency($name, $config) {
        $this->quiet or $this->Response->writeLn(">>> Updating '$name'...");

        $clone_target = $this->vcs_clone_dir . '/' . trim($config['target'], '/');

        Vcs\Fetcher::factoryFromArray($config['source'], $clone_target)
            ->beVerbose(!$this->quiet)
            ->fetch($this->force);

        $subdir = isset($config['source']['subdirectory']) ? trim($config['source']['subdirectory'], '/') : '';
        $this->updateSymlink(
            $clone_target . '/' . $subdir,
            $this->vendor_lib_path . '/' . trim($config['target'], '/')
        );
    }

    protected function updateSymlink($target, $link) {
        Fs\Directory::mkdirIfNotExists(dirname($link));
        if (file_exists($link)) {
            if (!is_link($link)) {
                throw new \RuntimeException("'$link' exists and is not a symlink'");
            }
            unlink($link);
        }
        symlink($target, $link);
    }

    protected function getHelp() {
        return [
            '-f' => 'Force replacement of existing repository clones on revision mismatch',
            '-q' => 'Be quiet',
        ];
    }

}