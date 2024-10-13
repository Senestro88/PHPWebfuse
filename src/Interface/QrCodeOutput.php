<?php

namespace PHPWebfuse\Interface;

use \PHPWebfuse\Utils;
use \chillerlan\QRCode\Output\QRGdImagePNG;
use \chillerlan\QRCode\Common\Version as ChillerlanVersion;

/**
 * @author Senestro
 */

class QrCodeOutput extends QRGdImagePNG {
    public function dump(string|null $file = null, string|null $logo = null): string {
        // Set returnResource to true to skip further processing for now
        $this->options->returnResource = true;
        parent::dump($file);
        if($this->validImage($logo) && $this->options->version == ChillerlanVersion::AUTO && ($im = imagecreatefrompng($logo)) instanceof \GdImage){
            // Get logo image size
            $w = imagesx($im);
            $h = imagesy($im);
            // Set new logo size, leave a border of 1 module (no proportional resize/centering)
            $lw = (($this->options->logoSpaceWidth - 1) * $this->options->scale);
            $lh = (($this->options->logoSpaceHeight - 1) * $this->options->scale);
            // Set the qrcode size
            $ql = ($this->matrix->getSize() * $this->options->scale);
            // Scale the logo and copy it over. done!
            imagecopyresampled($this->image, $im, (($ql - $lw) / 2), (($ql - $lh) / 2), 0, 0, $lw, $lh, $w, $h);
        }
        $data = $this->dumpImage();
        $this->saveToFile($data, $file);
        $data = $this->options->outputBase64 ? $this->toBase64DataURI($data) : $data;
        return $data;
    }

    private function validImage(?string $filename = null): bool {
        return \is_string($filename) ? \is_file($filename) && \is_readable($filename)  : false;
    }
}
