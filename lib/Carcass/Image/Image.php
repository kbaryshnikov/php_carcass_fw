<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Image;

/**
 * Image: pecl/imagick wrapper
 * @package Carcass\Image
 */
class Image extends \Imagick {

    const DEFAULT_FILTER = \Imagick::FILTER_LANCZOS;

    /**
     * @param array|string|resource $src
     */
    public function __construct($src) {
        if (is_resource($src)) {
            parent::__construct();
            $this->readImageFile($src);
        } else {
            parent::__construct($src);
        }
    }

}
