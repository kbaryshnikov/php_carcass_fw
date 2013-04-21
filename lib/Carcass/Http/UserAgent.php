<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Http;

use \Carcass\Corelib\Request;

/**
 * UserAgent related tools
 * @package Carcass\Http
 */
class UserAgent {

    protected $Request;

    /**
     * @param Request $Request
     */
    public function __construct(Request $Request) {
        $this->Request = $Request;
    }

    /**
     * @return bool true if user-agent is a known crawler
     */
    public function isCrawler() {
        $user_agent = $this->getUserAgent();
        return (bool)(!$user_agent || strpos(str_replace(static::$known_crawlers, '**BOT**', $user_agent), '**BOT**') !== false);
    }

    protected function getUserAgent() {
        return $this->Request->Env->get('HTTP_USER_AGENT');
    }

    protected static $known_crawlers = [
        '+http://www.google.com/', 'Adsbot', 'MSNBot', 'WISENutbot', 'msnbot', 'livebot', 'Googlebot', 'Baiduspider',
        '+http://www.baidu.com', 'Slurp', 'Seekbot', 'YandexBot', 'GSiteCrawler', '+http://www.bing.com', 'bingbot',
        '+http://www.exabot.com', 'Exabot', 'ia_archiver', 'Gigabot', 'Mediapartners', 'Ezooms', 'ezooms.bot', 'MJ12bot',
        'Sosospider', '+http://yandex.com', 'MJ12bot', 'TopSitesService', 'findlinks', '+http://search.msn.com', 'Sogou',
        '+http://www.sogou.com', 'StackRambler', 'TurnitinBot', 'holmes', 'Aport', 'eStyle', 'Mail.Ru', 'Scrubby', 'GeonaBot',
        'Lycos', 'WebAlta', 'Dumbot', 'Altavista', 'ID-Search', 'MSRBOT',
    ];

}