<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Database;

use Carcass\Connection\Dsn;
use Carcass\Connection\XaTransactionalConnectionTrait;
use Carcass\DevTools;
use Carcass\Corelib;

/**
 * Abstract Database XA Connection
 * @package Carcass\Database
 */
abstract class XaConnection extends Connection {
    use XaTransactionalConnectionTrait;
}
