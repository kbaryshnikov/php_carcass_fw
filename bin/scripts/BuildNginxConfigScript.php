<?php

namespace Carcass\Tools;

use Carcass\Corelib;
use Carcass\Config;

class BuildNginxConfigScript extends Controller {

    public function actionDefault(Corelib\Hash $Args) {
        $target = $Args->get('o', null);

        $app_root = $Args->get('app_root');
        $Config = $this->getAppConfig($app_root);

        try {
            $vars             = $this->buildVars($Config);
            $vars['app_root'] = rtrim($app_root, '/');
        } catch (\Exception $e) {
            $this->Response->writeLn("Configuration failure: " . $e->getMessage());
            return 1;
        }

        $ngx_cfg      = "{$app_root}config/nginx.conf";
        $ngx_fcgi_cfg = "{$app_root}config/nginx-fastcgi.conf";

        if (file_exists($ngx_fcgi_cfg)) {
            $vars['FASTCGI'] = $this->parse($ngx_fcgi_cfg, $vars);
        }

        if (!file_exists($ngx_cfg)) {
            $this->Response->writeErrorLn("File not found: '$ngx_cfg'");
            return 1;
        }

        $result = $this->parse($ngx_cfg, $vars);

        if ($target) {
            file_put_contents($target, $result, LOCK_EX);
        } else {
            $this->Response->write($result);
        }

        return 0;
    }

    protected function parse($file, array $args) {
        return Corelib\StringTemplate::constructFromFile($file)->parse($args);
    }

    protected function buildVars(Config\ItemInterface $Config) {
        $Site = $Config->getPath('web.site');
        if (!$Site) {
            throw new \RuntimeException('Missing web.site configuration');
        }

        $Server = $Config->getPath('web.server');
        if (!$Server) {
            throw new \RuntimeException('Missing web.server configuration');
        }

        $vars = [
            'app_name' => $Config->getPath('application.name')
        ];

        foreach ($this->Request->Env->exportArray() as $k => $v) {
            $vars["ENV_$k"] = $v;
        }

        $vars['fcgi_addr'] = $Config->getPath('web.server.socket');

        $vars['listen'] = self::assocToValArray($Server->get('listen'));

        $server_names = [];
        if ($Site->has('domain')) {
            $server_names[] = $Site->get('domain');
        }
        if ($Site->has('aliases')) {
            foreach ($Site->exportArrayFrom('aliases') as $alias) {
                $server_names[] = $alias;
            }
        }
        $vars['server_names'] = $server_names ? join(' ', $server_names) : null;

        if ($Server->has('ssl')) {
            $vars['ssl'] = static::assocToValArray($Server->get('ssl'));
        }

        if ($Server->has('realip')) {
            $vars['realip_header'] = $Server->get('realip.header');
            $vars['realip_from']   = static::assocToValArray($Server->get('realip.from'));
        }

        foreach ($Config->exportArrayFrom('web') as $k => $v) {
            if (substr($k, 0, 4) == 'web_') {
                $vars[$k] = $v;
            }
        }

        return $vars;
    }

    protected static function assocToValArray($array) {
        if (null === $array) {
            return null;
        }

        if (!Corelib\ArrayTools::isTraversable($array)) {
            $array = [$array];
        }

        $result = [];

        foreach ($array as $key => $value) {
            $result[] = ['key' => $key, 'value' => $value];
        }
        return $result;
    }

    protected function getHelp() {
        return [
            '-o filename' => 'Output to < filename >, default: stdout',
        ];
    }

}
