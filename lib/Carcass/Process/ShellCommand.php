<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Process;

use Carcass\Corelib;

/**
 * Shell command executor
 * @package Carcass\Process
 */
class ShellCommand {

    const
        STDIN  = 0,
        STDOUT = 1,
        STDERR = 2;

    protected
        $cmd,
        $args_template,
        $args = [],
        $cwd = null,
        $input = null,
        $env = null;

    /**
     * @param string $cmd command name
     * @param null $args_template
     */
    public function __construct($cmd, $args_template = null) {
        $this->cmd = $cmd;
        $this->args_template = $args_template;
    }

    /**
     * @param string $cmd command name
     * @param string $args_template
     * @param array $args
     * @param string|null $stdout returned by ref
     * @param string|null $stderr returned by ref
     * @return int
     */
    public static function run($cmd, $args_template = null, array $args = [], &$stdout = null, &$stderr = null) {
        return (new static($cmd, $args_template))->prepare($args)->execute($stdout, $stderr);
    }

    /**
     * @param array $env
     * @return $this
     */
    public function setEnv(array $env = null) {
        $this->env = $env;
        return $this;
    }

    /**
     * @param string|null $cwd
     * @return $this
     */
    public function setCwd($cwd = null) {
        $this->cwd = $cwd;
        return $this;
    }

    /**
     * @param $input
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setInputSource($input = null) {
        if (null !== $input && !Corelib\ArrayTools::isTraversable($input)) {
            throw new \InvalidArgumentException("Argument is expected to be typeof null|array|Traversable");
        }
        $this->input = $input;
        return $this;
    }

    /**
     * @param array $args
     * @return $this
     */
    public function prepare(array $args = []) {
        $this->args = [];
        foreach ($args as $k => $v) {
            $this->args[$k] = escapeshellarg($v);
        }
        return $this;
    }

    /**
     * @param $stdout
     * @param $stderr
     * @return int
     * @throws \RuntimeException
     */
    public function execute(&$stdout = null, &$stderr = null) {
        $args = Corelib\StringTools::parseTemplate($this->args_template, $this->args);
        $command = escapeshellcmd($this->cmd) . ' ' . $args;
        $descriptorspec = [
            self::STDIN  => ["pipe", "r"],
            self::STDOUT => null === $stdout ? ['file', '/dev/null', 'a'] : ["pipe", "w"],
            self::STDERR => null === $stderr ? ['file', '/dev/null', 'a'] : ["pipe", "w"],
        ];
        $process = proc_open($command, $descriptorspec, $pipes, $this->cwd, $this->env);
        if (!is_resource($process)) {
            throw new \RuntimeException("Cannot execute command: [{$command}]");
        }
        if ($this->input) {
            foreach ($this->input as $line) {
                fwrite($pipes[self::STDIN], $line);
            }
        }
        fclose($pipes[self::STDIN]);
        if (null !== $stdout) {
            $stdout = stream_get_contents($pipes[self::STDOUT]);
            fclose($pipes[self::STDOUT]);
        }
        if (null !== $stderr) {
            $stderr = stream_get_contents($pipes[self::STDERR]);
            fclose($pipes[self::STDERR]);
        }
        return proc_close($process);
    }

}
