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
                $this->Response->writeLn("> Deploying to '$name'...");
                $this->deployTo($server, $tarball_stream, $revision);
                $this->Response->writeLn("> Deployed to '$name' OK");
            } catch (\Exception $e) {
                $this->Response->writeErrorLn("[!] Deployment to server $name failed: " . $e->getMessage());
                $this->Response->writeErrorLn($e->getTraceAsString());
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
            ' ', [
                $server->ssh_opts,
                '{{ IF identity }}-i {{ identity }}{{ END }}',
                '{{ IF username }}-l {{ username }}{{ END }}',
                '{{ IF port }}-p {{ port }}{{ END }}',
                '{{ hostname }}',
                '{{ command }}'
            ]
        ));
        $stdout = '';
        $stderr = '';
        $command = $this->buildInstallShellLine($server, $revision);
        $SshCmd->setInputSource($tarball_stream);
        $SshCmd->prepare(['command' => $command] + array_filter($server->exportArray()))->execute($stdout, $stderr);
        if (!preg_match('/DEPLOYED OK\s*$/', $stdout)) {
            throw new \LogicException("Installation script failed.\n\nSTDOUT:\n$stdout\n\nSTDERR:\n$stderr\n");
        }
    }

    protected function buildInstallShellLine(Corelib\Hash $server, $revision) {
        $target_dir = $server->target_path . '/' . $revision;
        $target_dir_e = escapeshellarg($target_dir);
        $commands = [
            "test ! -d $target_dir_e || (echo Directory already exists: $target_dir_e && false)",
            "mkdir -p $target_dir_e || (echo Failed to created directory $target_dir_e && false)",
            "tar xzf - -C $target_dir_e || (echo Failed to extract the stdin tarball && false)",
        ];
        $cd = "cd $target_dir_e && ";
        foreach ($this->Config->exportArrayFrom('post_install') as $idx => $cmd) {
            $commands[] = "( $cd ( $cmd )) || (echo post_install command at offset $idx failed && false)";
        }
        $rotate = (int)$this->Config->getPath('rotate');
        if ($rotate > 0) {
            $prefix = rtrim($server->target_path, '/') . '/';
            $rotate_cmd = Corelib\StringTemplate::parseString(
                'cnt=`expr $( {{ find_cmd }} | wc -l ) - {{ rotate }}` && test $cnt -gt 0'
                . ' && {{ find_cmd }} | sed \'s@^.*{{prefix}}@@\' | sort -g | head -n $cnt | sed \'s@^@{{prefix}}@\' | xargs rm -rf',
                [
                    'find_cmd' => "find $prefix -maxdepth 1 -regex '.*/[0-9][0-9]*$' -type d",
                    'rotate'   => $rotate,
                    'prefix'   => $prefix,
                ]
            );
            $commands[] = "($rotate_cmd) || true";
        }
        $commands[] = 'echo DEPLOYED OK';
        $result = join(
            ' && ', array_map(
                function ($cmd) {
                    return '( ' . $cmd . ' )';
                }, $commands
            )
        );
        if ($this->Config->getPath('clean_on_error')) {
            $result = "( $result ) || ( cd / && rm -rf $target_dir_e )";
        }
        return $result;
    }

}
