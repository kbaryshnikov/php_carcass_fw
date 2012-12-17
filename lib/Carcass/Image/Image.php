<?php

namespace Carcass\Image;

class Image extends \Imagick {

    const DEFAULT_FILTER = \Imagick::FILTER_LANCZOS;

    public function __construct($files) {
        if (is_resource($files)) {
            parent::__construct();
            $this->readImageFile($files);
        } else {
            parent::__construct($files);
        }
    }

}
