<?php

namespace Carcass\Tools;

use Carcass\Application as Application;
use Carcass\Corelib;
use Carcass\Config;
use Carcass\Fs;

class DeployScript extends Controller {

    /**
     * @var Config\Item
     */
    protected $AppConfig;
    protected $app_root = null;

    protected function init() {
        parent::init();
        $this->AppConfig = $this->getAppConfig($this->app_root);
    }

    public function actionDefault(Corelib\Hash $Args) {
        $archive_file = $this->buildDistArchive($Args);
        if (!$archive_file) {
            return 1;
        }
        $Args->f = $archive_file;
        $Args->r = null;
        return $this->deployDistArchive($Args);
    }

    public function actionArchive(Corelib\Hash $Args) {
        $archive_file = $this->buildDistArchive($Args);
        if (!$archive_file) {
            return 1;
        }
        $this->Response->writeLn("Distribution tarball has been created:");
        $this->Response->writeLn($archive_file);
        return 0;
    }

    public function actionPut(Corelib\Hash $Args) {
        return $this->deployDistArchive($Args);
    }

    protected function buildDistArchive(Corelib\Hash $Args) {
        $target_tgz_file_basename = $Args->get('f', sys_get_temp_dir() . '/' . $this->AppConfig->getPath('application.name', 'app'));
        $dist_tarball_file = $this->generateTmpDistTarballFilename();
        $this->Response->writeLn('> Building the distribution archive in [' . $dist_tarball_file . ']...');
        $ArchiveBuilder = new ArchiveBuilder($this->Response, $this->AppConfig->deploy, $Args->get('tmpdir'), !$Args->get('noclean'));
        $revision_number = $ArchiveBuilder->build($dist_tarball_file);
        $target_tgz = $target_tgz_file_basename . '.' . $revision_number . '.tgz';
        $this->Response->writeLn("> Revision $revision_number archive has been built, moving [$dist_tarball_file] -> [$target_tgz]");
        rename($dist_tarball_file, $target_tgz);
        return $target_tgz;
    }

    protected function generateTmpDistTarballFilename() {
        $app_name = $this->AppConfig->getPath('application.name', 'app');
        do {
            $filename = sys_get_temp_dir() . '/' . 'dist_' . $app_name . '_' . microtime(true) . '.tgz';
        } while (file_exists($filename));
        return $filename;
    }

    protected function deployDistArchive(Corelib\Hash $Args) {
        try {
            $filename = $Args->get('f');
            if (!$filename) {
                throw new \LogicException("Missing -f argument");
            }
            if (!file_exists($filename)) {
                throw new \LogicException("File not found: $filename");
            }
            $revno = (int)$Args->get('r');
            if (!$revno && preg_match('/\.(\d+)\.tgz$/', $filename, $matches)) {
                $revno = (int)$matches[1];
            }
            if (!$revno) {
                throw new \LogicException("No -r argument, and revision number could not be extracted from the file name");
            }
            if ($Args->has('only')) {
                $only_to_servers = explode(',', $Args->only);
            } else {
                $only_to_servers = [];
            }
            $Deployer = new Deployer($this->AppConfig->deploy, $this->Response);
            $Deployer->deploy($filename, $revno, $only_to_servers);
            return 0;
        } catch (\Exception $e) {
            $this->Response->writeErrorLn('ERROR: ' . $e->getMessage());
            $this->Response->writeErrorLn($e->getTraceAsString());
            return 1;
        }
    }

    protected function getHelp($action = 'default') {
        $build_args = [
            '-f=<filename>'     => 'Distribution tarball path basename, default = $TMP/$APP_NAME; full name is appended with revision number and .tgz ext',
            '-tmpdir=<tmp_dir>' => 'Temporary checkout root, default = $TMP',
            '-b=<branch>'       => 'Branch to deploy, overrides the deploy config',
            '-t=<tag>'          => 'Tag to deploy, overrides the deploy config',
        ];
        $put_args = [
            '-f=<filename>'                => 'Distribution tarball path, default = autogenerated filename in the system TMP directory',
            '-r=<rev>'                     => 'Revision number, default = autodetect by the distribution tarball filename',
            '-only=<server1[,server2...]>' => 'Deploy only to these server name(s), comma-separated',
            '-clean'                       => 'Delete the distribution tarball after successful deployment',
        ];
        switch ($action) {
            case 'default':
                $this->Response->writeln("Actions:");
                $this->Response->writeLn("  archive \t - build the distribution tarball archive");
                $this->Response->writeLn("  put     \t - deploy the distribution tarball onto server(s)");
                $this->Response->writeLn("  default \t - build and deploy the distribution tarball onto server(s)\n");
                $args = $build_args + $put_args;
                unset($args['r']);
                return $args;
            case 'archive':
                return $build_args;
            case 'put':
                return $put_args;
            default:
                $this->Response->writeErrorLn("Unknown action!");
                return [];
        }
    }

}
