<?php

namespace Carcass\Tools;

use Carcass\Application as Application;
use Carcass\Corelib as Corelib;

class Help {

    protected $commands = [
        'build-nginx-config'                               => 'Build application nginx config',
        'fetch-dependencies'                               => 'Fetch application dependencies',
        'deploy'                                           => 'Deploy application',
        ''                                                 => null,
        'Use <command> -h for detailed help on a command.' => null,
    ];

    protected $title = 'Commands:';

    public function __construct(array $commands = null, $title = null) {
        if ($title) {
            $this->title = $title;
        }
        if ($commands) {
            $this->commands = $commands;
        }
    }

    public function getCommands() {
        return $this->commands;
    }

    public function displayTo(Corelib\ResponseInterface $Response) {
        $Response->writeLn("{$this->title}\n");
        $commands = $this->getCommands();
        $padding = max(array_map('strlen', array_keys(array_filter($commands))));
        foreach ($commands as $name => $desc) {
            if ($desc === null) {
                $Response->writeLn($name);
            } else {
                $Response->writeLn('  ' . str_pad($name, $padding, ' ') . '  ' . $desc);
            }
        }
        $Response->writeLn('');
    }

}
