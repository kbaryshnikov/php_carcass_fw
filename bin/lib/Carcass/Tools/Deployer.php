<?php

namespace Carcass\Tools;

use Carcass\Process\ShellCommand;
use Carcass\Config;
use Carcass\Corelib;

class Deployer {

    protected $Config;
    protected $Response;

    public function __construct(Config\ItemInterface $Config, Corelib\ResponseInterface $Response) {
        $this->Config = $Config;
        $this->Response = $Response;
    }

    public function deploy($tarball, $revision, array $only_to_servers = null) {
        if (!file_exists($tarball)) {
            throw new \LogicException("File not found: $tarball");
        }
        $tarball_stream = fopen($tarball, 'r');
        flock($tarball_stream, LOCK_SH);
        $revision = (int)$revision;
        if ($revision < 1) {
            throw new \LogicException("Bad revision number: [$revision]");
        }
        $servers = $this->Config->exportHashFrom('servers');
        if ($only_to_servers) {
            $servers = $servers->exportFilteredHash($only_to_servers);
        }
        if (!count($servers)) {
            throw new \LogicException("No servers to deploy to");
        }
        foreach ($servers as $name => $server) {
            try {
                if (!$server instanceof Corelib\Hash) {
                    throw new \LogicException("Configuration is incorrect: server item in deploy.servers section is scalar");
                }
                fseek($tarball_stream, 0);
                $this->deployTo($server, $tarball_stream, $revision);
            } catch (\Exception $e) {
                $this->Response->writeErrorLn("[!] Deployment to server $name failed: " . $e->getMessage());
            }
        }
        flock($tarball_stream, LOCK_UN);
        fclose($tarball_stream);
    }

    protected function deployTo(Corelib\Hash $server, $tarball_stream, $revision) {
        if (!$server->has('hostname')) {
            throw new \LogicException("Server configuration has no hostname");
        }
        if (!$server->has('target_path')) {
            throw new \LogicException("Server configuration has no target_path");
        }
        $SshCmd = new ShellCommand('ssh', join(
            '', [
                $server->ssh_opts,
                '{{ IF identity }} -i {{ identity }}{{ END }}',
                '{{ IF username }} -l {{ username }}{{ END }}',
                '{{ IF port }} -p {{ port }}{{ END }}',
                '{{ hostname }}',
                '-c {{ command }}'
            ]
        ));
        $stdout = '';
        $stderr = STDERR;
        $command = $this->buildInstallShellLine($server, $revision);
        $SshCmd->setInputSource($tarball_stream);
        $SshCmd->prepare(['command' => $command] + $server->exportArray())->execute($stdout, $stderr);
        if (!preg_match('/DEPLOYED OK\s*$/', $stdout)) {
            throw new \LogicException("Installation script failed: " . $stdout);
        }
    }

    protected function buildInstallShellLine(Corelib\Hash $server, $revision) {
        $target_dir = $server->target_path . '/' . $revision;
        $target_dir_e = escapeshellarg($target_dir);
        $commands = [
            "test -d $target_dir_e && echo Directory already exists: $target_dir_e && false",
            "mkdir -p $target_dir_e",
            "chdir $target_dir_e",
            "tar xf -",
        ];
        foreach ($this->Config->exportArrayFrom('post_install') as $cmd) {
            $commands[] = $cmd;
        }
        $rotate = (int)$this->Config->getPath('rotate');
        if ($rotate > 0) {
            $commands[] = Corelib\StringTemplate::parseString(
                'cnt=`expr $( {{ find_cmd }} | wc -l ) - {{ rotate }}` && test $cnt -gt 0'
                . ' && {{ find_cmd }} | sort -g | head -n $cnt | xargs rm -rf',
                [
                    'find_cmd' => 'find . -maxdepth 1 -regex "./[0-9][0-9]*" -type d',
                    'rotate'   => $rotate
                ]
            );
        }
        $commands[] = 'echo DEPLOYED OK';
        $result = join(
            ' && ', array_map(
                function ($cmd) {
                    return '( ' . $cmd . ')';
                }, $commands
            )
        );
        if ($this->Config->getPath('clean_on_error')) {
            $result = "( $result ) || ( chdir / && rm -rf $target_dir_e )";
        }
        return $result;
    }

}