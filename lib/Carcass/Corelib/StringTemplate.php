<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

if (class_exists('\\Blitz', false)) {
    include_once __DIR__ . '/StringTemplate_Blitz.php';
} else {
    include_once __DIR__ . '/StringTemplate_BlitzEmulator.php';
}
