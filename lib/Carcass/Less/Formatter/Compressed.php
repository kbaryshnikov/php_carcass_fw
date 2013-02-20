<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Less;

/**
 * lessphp v0.3.8
 * http://leafo.net/lessphp
 *
 * less css compiler, adapted from http://lesscss.org
 *
 * copyright 2012, leaf corcoran <leafot@gmail.com>
 * licensed under mit or gplv3, see license
 */

class Formatter_Compressed extends Formatter_Classic {

    public $disableSingle = true;
    public $open = "{";
    public $selectorSeparator = ",";
    public $assignSeparator = ":";
    public $break = "";
    public $compressColors = true;

    public function indentStr($n = 0) {
        return "";
    }

}

