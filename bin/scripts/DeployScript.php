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
    protected $AppConfig = null;
    protected $app_root = null;

    public function actionDefault(Corelib\Hash $Args) {
        if (!$this->loadAppConfig($Args)) {
            return 1;
        }
        $archive_file = $this->buildDistArchive($Args);
        if (!$archive_file) {
            return 1;
        }
        $Args->f = $archive_file;
        $Args->r = null;
        return $this->deployDistArchive($Args);
    }

    public function actionArchive(Corelib\Hash $Args) {
        if (!$this->loadAppConfig($Args)) {
            return 1;
        }
        $archive_file = $this->buildDistArchive($Args);
        if (!$archive_file) {
            return 1;
        }
        $this->Response->writeLn("Distribution tarball has been created:");
        $this->Response->writeLn($archive_file);
        return 0;
    }

    public function actionPut(Corelib\Hash $Args) {
        if (!$this->loadAppConfig($Args)) {
            return 1;
        }
        return $this->deployDistArchive($Args);
    }

    protected function loadAppConfig(Corelib\Hash $Args) {
        if (null === $this->AppConfig) {
            $configuration_name = $Args->get(1);
            if (!$configuration_name) {
                $this->Response->writeErrorLn("No configuration name given. Use -h for help");
                return false;
            }
            $env_override = compact('configuration_name');
            $this->AppConfig = $this->getAppConfig($this->app_root, $env_override);
        }
        return true;
    }

    protected function buildDistArchive(Corelib\Hash $Args) {
        $build_dir = $Args->get('p');
        if ($build_dir) {
            if ($build_dir === true) {
                $build_dir = $this->AppConfig->getPath('deploy.dist_dir');
                if (!$build_dir) {
                    throw new \Exception("-p without value is given, and deploy.dist_dir is not configured");
                }
            }
            if (!is_dir($build_dir)) {
                throw new \Exception("Not a directory: $build_dir");
            }
            $target_tgz_file_basename = rtrim($build_dir, '/') . '/';
            if (!$filename = $Args->get('f')) {
                $filename = $this->AppConfig->getPath('application.name', 'app');
            }
            $target_tgz_file_basename .= $filename;
        } else {
            $target_tgz_file_basename = $Args->get(
                'f',
                sys_get_temp_dir() . '/' . $this->AppConfig->getPath('application.name', 'app')
            );
        }
        $dist_tarball_file = $this->generateTmpDistTarballFilename();
        $this->Response->writeLn(
            '> Distrubution destination archive template: [' . $target_tgz_file_basename . '.${revisionNumber}.tgz]'
        );
        $this->Response->writeLn(
            '> Building the distribution archive in temporary file [' . $dist_tarball_file . ']...'
        );
        $deploy_overrides = array_filter($Args->exportFilteredArray(['branch', 'rev']));
        $ArchiveBuilder = new ArchiveBuilder(
            $this->Response,
            $this->AppConfig->deploy,
            $Args->get('tmpdir'),
            !$Args->get('noclean'),
            $deploy_overrides
        );
        $revision_number = $ArchiveBuilder->build($dist_tarball_file, $this->AppConfig);
        $target_tgz = $target_tgz_file_basename . '.' . $revision_number . '.tgz';
        $this->Response->writeLn(
            "> Revision $revision_number archive has been built, moving [$dist_tarball_file] -> [$target_tgz]"
        );
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
                throw new \LogicException(
                    "No -r argument, and revision number could not be extracted from the file name"
                );
            }
            if ($Args->has('only')) {
                $only_to_servers = explode(',', $Args->only);
            } else {
                $only_to_servers = [];
            }
            $Deployer = new Deployer($this->AppConfig->deploy, $this->Response);
            $Deployer->deploy($filename, $revno, $only_to_servers, $Args->get('keep'));
            return 0;
        } catch (\Exception $e) {
            $this->Response->writeErrorLn('ERROR: ' . $e->getMessage());
            $this->Response->writeErrorLn($e->getTraceAsString());
            return 1;
        }
    }

    protected function getHelp($action = 'default') {
        $build_args = [
            '-p[=<dir>]'        => 'Distrubution tarball directory, if <dir> ommitted - taken from deploy.dist_dir config',
            '-f=<filename>'     => 'Distribution tarball path basename, default = $TMP/$APP_NAME; full name is appended with revision number and .tgz ext',
            '-tmpdir=<tmp_dir>' => 'Temporary checkout root, default = $TMP',
            '-branch=<branch>'  => 'VCS branch to deploy, overrides the deploy config',
            '-rev=<revspec>'    => 'VCS revision specification to deploy, overrides the deploy config',
        ];
        $put_args = [
            '-f=<filename>'                => 'Distribution tarball path or filename if used together with -p, autogenerated by default',
            '-r=<rev>'                     => 'Revision number, default = autodetect by the distribution tarball filename',
            '-only=<server1[,server2...]>' => 'Deploy only to these server name(s), comma-separated',
            '-clean'                       => 'Delete the distribution tarball after successful deployment',
            '-keep'                        => 'Keep deployed files on post_install failure and suppress post_clean_on_error commands. Overrides deploy.clean_on_error config',
        ];
        switch ($action) {
            case 'default':
                $this->Response->writeln("Actions:");
                $this->Response->writeLn("  archive <configuration_name> \t - build the distribution tarball archive");
                $this->Response->writeLn(
                    "  put     <configuration_name> \t - deploy the distribution tarball onto <configuration_name> server(s)"
                );
                $this->Response->writeLn(
                    "  default <configuration_name> \t - build and deploy the distribution tarball onto <configuration_name> server(s)\n"
                );
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
