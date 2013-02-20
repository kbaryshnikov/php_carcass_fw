<?php

/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Image;

/**
 * Class Converter
 * @package Carcass\Image
 */
class Converter {

    const
        THUMBNAIL_MODE_CROP_CENTERED = 1,
        THUMBNAIL_MODE_FIT_RECTANGLE = 2;

    /**
     * @var Image
     */
    protected $SourceImage;
    /**
     * @var Image
     */
    protected $ResultImage;

    protected
        $resize,
        $resize_w = null,
        $resize_h = null,
        $resize_filter = Image::DEFAULT_FILTER,

        $thumbnail_mode = self::THUMBNAIL_MODE_CROP_CENTERED,

        $result_image_format,
        $result_quality,

        $watermark,
        $watermark_image,
        $watermark_gravity,
        $watermark_offset,

        $autolevels,

        $adaptiveblur,
        $adaptiveblur_radius,
        $adaptiveblur_sigma,

        $unsharpmask,
        $unsharpmask_radius,
        $unsharpmask_sigma,
        $unsharpmask_amount,
        $unsharpmask_threshold;

    /**
     * @param Image $Image
     */
    public function __construct(Image $Image) {
        $this->SourceImage = $Image;
    }

    /**
     * @param $width
     * @param $height
     * @return $this
     */
    public function setGeometry($width, $height) {
        $this->resize = true;
        $this->resize_w = $width;
        $this->resize_h = $height;
        return $this;
    }

    /**
     * @param bool $filter
     * @return $this
     */
    public function setResizeFilter($filter = false) {
        $this->resize_filter = $filter;
        return $this;
    }

    /**
     * @return $this
     */
    public function setThumbnailCropCentered() {
        $this->thumbnail_mode = self::THUMBNAIL_MODE_CROP_CENTERED;
        return $this;
    }

    /**
     * @return $this
     */
    public function setThumbnailFitRectangle() {
        $this->thumbnail_mode = self::THUMBNAIL_MODE_FIT_RECTANGLE;
        return $this;
    }

    /**
     * @param Image $Image
     * @param int|float $gravity
     * @param int $offset
     * @return $this
     */
    public function setWatermark(Image $Image, $gravity, $offset = 0) {
        $this->watermark = true;
        $this->watermark_image = $Image;
        $this->watermark_gravity = $gravity;
        $this->watermark_offset = $offset;
        return $this;
    }

    /**
     * @param bool $bool_mode
     * @return $this
     */
    public function setAutoLevels($bool_mode = true) {
        $this->autolevels = $bool_mode;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableAdaptiveBlur() {
        $this->adaptiveblur = false;
        $this->adaptiveblur_radius = false;
        $this->adaptiveblur_sigma = false;
        return $this;
    }

    /**
     * @param int|float $radius
     * @param int|float $sigma
     * @return $this
     */
    public function setAdaptiveBlur($radius, $sigma) {
        $this->adaptiveblur = true;
        $this->adaptiveblur_radius = $radius;
        $this->adaptiveblur_sigma = $sigma;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableUnsharpMask() {
        $this->unsharpmask = false;
        $this->unsharpmask_radius = false;
        $this->unsharpmask_sigma = false;
        $this->unsharpmask_amount = false;
        $this->unsharpmask_threshold = false;
        return $this;
    }

    /**
     * @param int|float $radius
     * @param int|float $sigma
     * @param int|float $amount
     * @param int|float $threshold
     * @return $this
     */
    public function setUnsharpMask($radius, $sigma, $amount, $threshold) {
        $this->unsharpmask = true;
        $this->unsharpmask_radius = $radius;
        $this->unsharpmask_sigma = $sigma;
        $this->unsharpmask_amount = $amount;
        $this->unsharpmask_threshold = $threshold;
        return $this;
    }

    /**
     * @param int $source_w
     * @param int $source_h
     * @param int $target_w
     * @param int $target_h
     * @return array|null
     */
    protected function calcCropCentered($source_w, $source_h, $target_w, $target_h) {
        $source_ratio = $source_w / $source_h;
        $target_ratio = $target_w / $target_h;
        if (abs($source_ratio - $target_ratio) < .01) {
            return null;
        }
        if ($source_ratio < $target_ratio) {
            $w = $source_w;
            $x = 0;
            $h = $source_h * $source_ratio / $target_ratio;
            $y = round( abs($source_h - $h) / 2 );
        } else {
            $h = $source_h;
            $y = 0;
            $w = $source_w * $target_ratio / $source_ratio;
            $x = round( abs($source_w - $w) / 2 );
        }
        return compact('w', 'h', 'x', 'y');
    }

    /**
     * @param mixed $real_w returned by ref
     * @param mixed $real_h returned by ref
     * @return $this
     * @throws \LogicException
     */
    public function resize(&$real_w = null, &$real_h = null) {
        if ($this->resize_w === null && $this->resize_h == null) {
            throw new \LogicException("Resize width or height not specified.");
        }

        $source_w = $this->SourceImage->getImageWidth();
        $source_h = $this->SourceImage->getImageHeight();

        $source_ratio = $source_w / $source_h;

        $target_w = $this->resize_w ?: ( $this->resize_h * $source_ratio );
        $target_h = $this->resize_h ?: ( $this->resize_w / $source_ratio );

        $this->ResultImage = $this->SourceImage->clone();

        switch ($this->thumbnail_mode) {
            case self::THUMBNAIL_MODE_CROP_CENTERED:
                $crop = $this->calcCropCentered($source_w, $source_h, $target_w, $target_h);
                $scale = false;
                break;
            case self::THUMBNAIL_MODE_FIT_RECTANGLE:
                $crop = null;
                $scale = true;
                break;
            default:
                throw new \LogicException("Invalid thumbnail_mode: '{$this->thumbnail_mode}'");
        }

        if (!empty($crop)) {
            $this->ResultImage->cropImage($crop['w'], $crop['h'], $crop['x'], $crop['y']);
        }

        if ($this->resize) {
            if ($scale) {
                $this->ResultImage->scaleImage($target_w + .4, $target_h + .4, true);
            } else {
                $this->ResultImage->resizeImage($target_w, $target_h, $this->resize_filter, 1, true);
            }
        }

        if ($this->watermark) {
            $this->ResultImage->compositeImage($this->watermark_image, \Imagick::COMPOSITE_OVER, $this->watermark_x, $this->watermark_y);
        }

        if ($this->autolevels) {
            $this->ResultImage->normalizeImage();
        }

        if ($this->adaptiveblur) {
            $this->ResultImage->adaptiveBlurImage($this->adaptiveblur_radius, $this->adaptiveblur_sigma);
        }

        if ($this->unsharpmask) {
            $this->ResultImage->unsharpMaskImage($this->unsharpmask_radius, $this->unsharpmask_sigma, $this->unsharpmask_amount,
                $this->unsharpmask_threshold);
        }

        $real_w = $this->ResultImage->getImageWidth();
        $real_h = $this->ResultImage->getImageHeight();

        return $this;
    }

    /**
     * @param $format
     * @param null $quality
     * @return $this
     */
    public function setImageFormat($format, $quality = null) {
        $this->result_image_format = $format;
        $this->result_quality = $quality;
        return $this;
    }

    /**
     * @param $quality
     * @return $this
     */
    public function setImageQuality($quality) {
        $this->result_quality = $quality;
        return $this;
    }

    /**
     * @param $target
     * @return mixed
     */
    public function saveTo($target) {
        if (isset($this->result_quality)) {
            $this->ResultImage->setCompressionQuality($this->result_quality);
            if (!isset($this->result_image_format)) {
                $this->result_image_format = 'jpeg';
            }
        }
        if (isset($this->result_image_format)) {
            $this->ResultImage->setImageFormat($this->result_image_format);
        }

        if (is_resource($target)) {
            $this->ResultImage->writeImageFile($target); 
        } else {
            $this->ResultImage->writeImage($target);
        }


        return $this->ResultImage;
    }

}
