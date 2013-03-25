<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;
use Carcass\Config;

/**
 * Application & Framework external dependencies
 *
 * @package Carcass\Application
 */
class Dependencies {

    /**
     * @param array $filter array of library names, all dependencies returned by default
     * @return array
     */
    public static function getInternalDependencies(array $filter = null) {
        if ($filter) {
            $libraries = [];
            foreach (self::$libraries as $lib_name => $lib_config) {
                if (in_array($lib_name, $filter)) {
                    $libraries[$lib_name] = $lib_config;
                }
            }
        } else {
            $libraries = self::$libraries;
        }
        return $libraries;
    }

    public static function getApplicationDependencies(&$path = null, Config\ItemInterface $AppDepsConfig = null) {
        if (!$AppDepsConfig) {
            if (! $AppDepsConfig = DI::getConfigReader()->getPath('dependencies')) {
                return null;
            }
        }
        $path = $AppDepsConfig->get('path', $path);
        $result = $AppDepsConfig->exportArrayFrom('libraries');
        if ($FwDeps = $AppDepsConfig->get('inherit_framework_dependencies')) {
            $filter = true === $FwDeps ? null : $AppDepsConfig->exportArrayFrom('inherit_framework_dependencies');
            $result += static::getInternalDependencies($filter);
        }
        return $result;
    }

    /**
     * @var array of 'LibName' => [
     *                  'source' => <SourceDefinition>, see Corelib\DependencyManager
     *                  'target' => string subdirectory path in 'vendor' folder
     *               ]
     */
    protected static $libraries = [
        'Twig'  => [
            'source' => [
                'type'         => 'git',
                'url'          => 'git://github.com/fabpot/Twig.git',
                'rev'          => 'v1.12.2',
                'subdirectory' => '/lib/Twig',
            ],
            'target' => 'Twig',
        ],
        'SwiftMailer' => [
            'source' => [
                'type'         => 'git',
                'url'          => 'git://github.com/swiftmailer/swiftmailer.git',
                'rev'          => 'v4.3.0',
                'subdirectory' => '/lib',
            ],
            'target' => 'Swift',
        ],
        'FirePHP' => [
            'source' => [
                'type'         => 'git',
                'url'          => 'git://github.com/firephp/firephp-core.git',
                'rev'          => 'v0.4.0rc3',
                'subdirectory' => '/lib/FirePHPCore',
            ],
            'target' => 'FirePHP',
        ],
    ];

}
