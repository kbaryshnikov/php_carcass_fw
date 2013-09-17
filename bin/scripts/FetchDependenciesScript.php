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
    protected $copy_mode = false;
    protected $remove_vcs_subdir_after_copy = false;

    public function actionDefault(Corelib\Hash $Args) {
        $app_root = $Args->get('app_root');
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
        $this->copy_mode = $Args->get('c');

        foreach ($libs as $name => $config) {
            if (!empty($config['source']) && !$this->isLocal($config['source'])) {
                $this->updateDependency($name, $config);
            }
        }

        if ($this->copy_mode && $Args->get('x')) {
            Fs\Directory::deleteRecursively($this->vcs_clone_dir);
        }

        return 0;
    }

    protected function isLocal(array $source_config) {
        return $source_config['type'] === 'local';
    }

    protected function updateDependency($name, array $config) {
        $this->quiet or $this->Response->writeLn(">>> Updating '$name'...");

        $clone_target = $this->vcs_clone_dir . '/' . trim($config['target'], '/');

        Vcs\Fetcher::factoryFromArray($config['source'], $clone_target)
            ->beVerbose(!$this->quiet)
            ->fetch($this->force);

        $subdir = isset($config['source']['subdirectory']) ? trim($config['source']['subdirectory'], '/') : '';

        if ($this->copy_mode) {
            Fs\Directory::copyRecursively(
                $clone_target . '/' . $subdir,
                $this->vendor_lib_path . '/' . trim($config['target'], '/'),
                true
            );
        } else {
            $this->updateSymlink(
                $clone_target . '/' . $subdir,
                $this->vendor_lib_path . '/' . trim($config['target'], '/')
            );
        }
    }

    protected function updateSymlink($target, $link) {
        $target = $this->normalizePath($target);
        $link = $this->normalizePath($link);
        Fs\Directory::mkdirIfNotExists(dirname($link));
        if (file_exists($link)) {
            if (!is_link($link)) {
                throw new \RuntimeException("'$link' exists and is not a symlink'");
            }
            unlink($link);
        }
        $common_subdir = $this->findCommonPrefix($target, $link);
        $this->makeSymlink($target, $link, $common_subdir);
    }

    protected function makeSymlink($target, $link, $chdir = null) {
        $cwd = null;
        if ($chdir) {
            $cwd = getcwd();
            if (!chdir($chdir)) {
                throw new \RuntimeException("Failed to chdir('$chdir')");
            }
            $target = ltrim(substr($target, strlen($chdir)), '/');
            $link = ltrim(substr($link, strlen($chdir)), '/');
        }
        $result = symlink($target, $link);
        if ($cwd) {
            chdir($cwd);
        }
        return $result;
    }

    protected function findCommonPrefix($target, $link) {
        $subdir_path = [];
        $target_path = explode('/', trim($target, '/'));
        $link_path = explode('/', trim($link, '/'));
        foreach ($target_path as $idx => $element) {
            if (!isset($link_path[$idx]) || $element !== $link_path[$idx]) {
                break;
            }
            $subdir_path[] = $element;
        }
        if (!$subdir_path) {
            return null;
        }
        $subdir = '/' . join('/', $subdir_path);
        if (!is_writable($subdir)) {
            return null;
        }
        return $subdir;
    }

    protected function normalizePath($link) {
        return preg_replace('#/{2,}#', '/', trim($link));
    }

    protected function getHelp($action = 'default') {
        return [
            '-f' => 'Force replacement of existing repository clones on revision mismatch',
            '-c' => 'Copy to target directory instead of creating symlinks',
            '-x' => 'Remove the cloned repository directories. Has effect only in the -c mode',
            '-q' => 'Be quiet',
        ];
    }

}
