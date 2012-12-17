<?php

namespace Carcass\Image;

use Carcass\Corelib as Corelib;
use Carcass\Session as Session;
use Carcass\Application as Application;

class Captcha_Imagick {

    const DEFAULT_SESSION_FIELD = 'captcha';

    protected
        $Session,
        $session_field,

        $background_color = 'white',
        $text_color = 'black',

        $noise = 150,

        $width = null,
        $height = null,

        $resize = null,

        $text,
        $text_length = 6,

        $font_file = null,
        $font_size = 42,

        $noise_chars   = '_-,.\'`/-:\\',

        $allowed_chars = 'ABCDEFGHKLNPRSTXYZ123456789';

    /**
     * __construct 
     * 
     * @param Session $Session
     * @param string $session_field
     * @return void
     */
    public function __construct(Session\Session $Session, $session_field = self::DEFAULT_SESSION_FIELD) {
        $this->Session = $Session;
        $this->session_field = $session_field;
        $this->loadText();
    }

    public function setSize($width, $height) {
        $this->width = (int)$width;
        $this->height = (int)$height;
        return $this;
    }

    /**
     * @param Application\ResponseInterface $Response 
     * @return void
     */
    public function output(Application\ResponseInterface $Response) {
        if (empty($this->font_file)) {
            throw new LogicException('setFontFile() required');
        }
        $Response->header('Content-type', 'image/jpeg');
        $Response->write($this->generateImage());
    }

    /**
     * setFontFile 
     * 
     * @param string $font_file 
     * @return self
     */
    public function setFontFile($font_file) {
        $this->font_file = $font_file;
        return $this;
    }

    /**
     * setBackgroundColor 
     * 
     * @param string $background_color color name (e.g. 'white'), or '#rrggbb'
     * @return self
     */
    public function setBackgroundColor($background_color) {
        $this->background_color = $background_color;
        return $this;
    }

    /**
     * setTextColor 
     * 
     * @param string $text_color color name (e.g. 'white'), or '#rrggbb'
     * @return self
     */
    public function setTextColor($text_color) {
        $this->text_color = $text_color;
        return $this;
    }

    /**
     * setFontSize 
     * 
     * @param int $font_size 
     * @return self
     */
    public function setFontSize($font_size) {
        $this->font_size = $font_size;
        return $this;
    }

    /**
     * @param mixed $entered_text 
     * @return bool
     */
    public function validate($entered_text) {
        $result = strtoupper($entered_text) === strtoupper($this->text);
        $this->regenerate();
        return $result;
    }

    /**
     * setNoise
     * 
     * @param int $level
     * @return self
     */
    public function setNoise($level) {
        $this->noise = $level;
        return $this;
    }

    protected function loadText() {
        $this->text = $this->Session->get($this->session_field);
        if (empty($this->text)) {
            $this->regenerate();
        }
    }

    protected function generateText() {
        $max_char_offset = strlen($this->allowed_chars)-1;
        $this->text = '';
        for ($i=0; $i<$this->text_length; ++$i) {
            $this->text .= $this->allowed_chars{Corelib\Crypter::getRandomNumber(0, $max_char_offset)};
        }
    }

    public function regenerate() {
        mt_srand();
        $this->generateText();
        $this->Session->set($this->session_field, $this->text);
        return $this;
    }

    protected function generateImage() {
        mt_srand(crc32($this->text));
        $i = new \Imagick;
        $draw = new \ImagickDraw();
        $i->newImage(380, 120, $pixel = new \ImagickPixel($this->background_color));
        $i->setFormat('jpeg');
        $draw->setFillColor($this->text_color);
        $draw->setFont($this->font_file);
        $draw->setFontSize($this->font_size);
        $n = -10;
        $chr='';
        for ($cn=0; $cn<strlen($this->text); $cn++) {
            $n += 23;
            $chr = substr($this->text,$cn,1);
            if ($cn % 2)
                $m = Corelib\Crypter::getRandomNumber(32,46);
            else
                $m = Corelib\Crypter::getRandomNumber(38,58);
            $i->annotateImage($draw, $n, 7+$m, Corelib\Crypter::getRandomNumber(-13,13), $chr);
        }
        $i->rollImage(Corelib\Crypter::getRandomNumber(0, 10), 0);
        $i->swirlImage(-Corelib\Crypter::getRandomNumber(20, 80));
        $i->setImageExtent(600, 140);
        if ($this->noise) {
            for ($cn=0; $cn<$this->noise; $cn++) {
                $draw->setFontSize(Corelib\Crypter::getRandomNumber(round($this->font_size/4), round($this->font_size/3)));
                $draw->setFillColor(!round(Corelib\Crypter::getRandomNumber(0,9)) ? $this->text_color : $this->background_color);
                $i->annotateImage($draw, Corelib\Crypter::getRandomNumber(10, 160), Corelib\Crypter::getRandomNumber(10, 60), Corelib\Crypter::getRandomNumber(0, 90), $this->noise_chars[ Corelib\Crypter::getRandomNumber(0, strlen($this->noise_chars)-1) ]);
            }
        }
        $i->motionBlurImage(.6, .9, Corelib\Crypter::getRandomNumber(40, 70));
        $i->rollImage(Corelib\Crypter::getRandomNumber(-10, 5), 0);
        $i->swirlImage(Corelib\Crypter::getRandomNumber(40, 70));
        $i->cropImage(170, 75, 6, 3);

        if ($this->width || $this->height) {
            $i->adaptiveResizeImage($this->width, $this->height);
        }

        $i->setImageFormat('jpeg');
        return $i->getImageBlob();
    }

}

