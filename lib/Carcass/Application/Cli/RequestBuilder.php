<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;

/**
 * Request builder for Cli applications.
 * @package Carcass\Application
 */
class Cli_RequestBuilder implements RequestBuilderInterface {

    /**
     * Uses Cli_ArgsParser to parse argv array into Request->Args.
     * @param array $app_env
     * @return \Carcass\Corelib\Request
     */
    public static function assembleRequest(array $app_env = []) {
        if (!empty($_SERVER['argv']) && count($_SERVER['argv']) > 1) {
            $args = Cli_ArgsParser::parse(array_slice($_SERVER['argv'], 1));
        } else {
            $args = [];
        }
        return new Corelib\Request([
            'Args' => $args,
            'Env'  => static::setupCliEnv($_SERVER),
        ]);
    }

    protected static function setupCliEnv(array $env) {
        $env['HOST'] = static::detectHostname($env);
        return $env;
    }

    protected static function detectHostname($env) {
        $host = null;
        if (!empty($env['HOST'])) {
            $host = $env['HOST'];
        } else {
            $host = php_uname('h');
        }
        return rtrim( strtolower($host), '.' );
    }

}
