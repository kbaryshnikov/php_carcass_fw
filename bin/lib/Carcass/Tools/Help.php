<?php

namespace Carcass\Tools;

use Carcass\Application as Application;
use Carcass\Corelib as Corelib;

class Help {

    public function getCommands() {
        return [
            'help'       => 'Show this message',
            'buildngx'   => 'Build application nginx config',
        ];
    }

    public function displayTo(Corelib\ResponseInterface $Response) {
        $Response->write("Usage:\n\n");
        $commands = $this->getCommands();
        $padding = max(array_map('strlen', array_keys($commands)));
        foreach ($commands as $name => $desc) {
            $Response->write('  ' . str_pad($name, $padding, ' ') . '  ' . $desc . "\n");
        }
        $Response->write("\n");
    }

}
