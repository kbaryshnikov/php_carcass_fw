<?php

namespace Carcass\Process;

use Carcass\Corelib as Corelib;

class ShellCommand {

    const
        STDOUT = 1,
        STDERR = 2;

    protected
        $cmd,
        $args_template,
        $args = [],
        $cwd = null,
        $input = null,
        $env = null;

    public function __construct($cmd, $args_template = null) {
        $this->cmd = $cmd;
        $this->args_template = $args_template;
    }

    public static function construct($cmd, $args_template = null) {
        return new static($cmd, $args_template);
    }

    public function setEnv(array $env = null) {
        $this->env = $env;
        return $this;
    }

    public function setCwd($cwd = null) {
        $this->cwd = $cwd;
        return $this;
    }

    public function setInputSource($input = null) {
        if (null !== $input && !is_array($input) && !$input instanceof Traversable) {
            throw new \InvalidArgumentException("Argument is expected to be typeof null|array|Traversable");
        }
        $this->input = $input;
        return $this;
    }

    public function prepare(array $args = []) {
        $this->args = [];
        foreach ($args as $k => $v) {
            $this->args[$k] = escapeshellarg($v);
        }
        return $this;
    }

    public function execute(&$stdout = false, &$stderr = false) {
        $args = Corelib\StringTools::parseTemplate($this->args_template, $this->args);
        $command = escapeshellcmd($this->cmd) . ' ' . $args;
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => false === $stdout ? ['file', '/dev/null', 'a'] : ["pipe", "w"],
            2 => false === $stderr ? ['file', '/dev/null', 'a'] : ["pipe", "w"],
        ];
        $process = proc_open($command, $descriptorspec, $pipes, $this->cwd, $this->env);
        if (!is_resource($process)) {
            throw new \RuntimeException("Cannot execute command: [{$command}]");
        }
        if ($this->input) {
            foreach ($this->input as $line) {
                fwrite($pipes[0], $line);
            }
        }
        fclose($pipes[0]);
        if (false !== $stdout) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }
        if (false !== $stderr) {
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        }
        return proc_close($process);
    }

}
