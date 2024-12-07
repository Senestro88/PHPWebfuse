<?php

namespace PHPWebfuse\Instance;

use \PHPWebfuse\Utils;
use \Endroid\QrCode\Builder\Builder;
use \Endroid\QrCode\Encoding\Encoding;
use \Endroid\QrCode\ErrorCorrectionLevel as EndroidErrorCorrectionLevel;
use \Endroid\QrCode\Label\LabelAlignment;
use \Endroid\QrCode\Label\Font\NotoSans;
use \Endroid\QrCode\Label\Font\Font;
use \Endroid\QrCode\RoundBlockSizeMode;
use \Endroid\QrCode\Writer\PngWriter;
use \Endroid\QrCode\QrCode as EndroidQrCode;
use \Endroid\QrCode\Color\Color;
use \Endroid\QrCode\Label\Label;
use \Endroid\QrCode\Logo\Logo;
use \Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Matrix\MatrixInterface;

/**
 * @author Senestro
 */
class QrCode {
    // PRIVATE VARIABLE
    private string $data;
    private string $encoding = "UTF-8";
    private ErrorCorrectionLevel $eccLevel = ErrorCorrectionLevel::Q;
    private int $size = 300;
    private int $margin = 10;
    private ?Logo $logo;
    private ?Label $label;

    // PUBLIC VARIABLES

    // PUBLIC METHODS

    /**
     * Constructor to initialize the QR code generation with various options.
     *
     * @param string $data The data to be encoded in the QR code.
     * @param string $encoding The character encoding (default is "UTF-8").
     * @param ErrorCorrectionLevel $eccLevel The error correction level for the QR code (default is ErrorCorrectionLevel::Q).
     * @param int $size The size of the QR code (default is 300).
     * @param int $margin The margin around the QR code (default is 10).
     */
    public function __construct(string $data, string $encoding = "UTF-8", ErrorCorrectionLevel $eccLevel = ErrorCorrectionLevel::Q, int $size = 300, int $margin = 10) {
        $this->data = $data;
        $this->encoding = $encoding;
        $this->eccLevel = $eccLevel;
        $this->size = $size;
        $this->margin = $margin;
        $this->logo = null;
        $this->label = null;
    }

    /**
     * Set the data for the QR code.
     *
     * @param string $data The data to encode in the QR code.
     */
    public function setData(string $data): void {
        $this->data = $data;
    }

    /**
     * Set the encoding for the QR code data.
     *
     * @param string $encoding The character encoding.
     */
    public function setEncoding(string $encoding): void {
        $this->encoding = $encoding;
    }

    /**
     * Set the error correction level for the QR code.
     *
     * @param ErrorCorrectionLevel $eccLevel The error correction level.
     */
    public function setEccLevel(ErrorCorrectionLevel $eccLevel): void {
        $this->eccLevel = $eccLevel;
    }

    /**
     * Set the size of the QR code.
     *
     * @param int $size The size of the QR code.
     */
    public function setSize(int $size): void {
        $this->size = $size;
    }

    /**
     * Set the margin around the QR code.
     *
     * @param int $margin The margin size.
     */
    public function seMargin(int $margin): void {
        $this->margin = $margin;
    }

    /**
     * Set the logo to be embedded in the center of the QR code.
     *
     * @param string $logo The path to the logo file.
     * @param int $resizeToWidth The width to resize the logo (default is 50).
     * @param bool $punchoutBackground Whether to remove the background of the logo (default is false).
     */
    public function setLogo(string $logo, int $resizeToWidth = 50, bool $punchoutBackground = false): void {
        if (\is_file($logo)) {
            $this->logo = Logo::create($logo);
            $this->logo->setResizeToWidth($resizeToWidth);
            $this->logo->setPunchoutBackground($punchoutBackground);
        }
    }

    /**
     * Set the label to be displayed below the QR code.
     *
     * @param string $label The text label to display.
     * @param int $size The font size for the label (default is 18).
     * @param array $color An array representing the RGB color for the label (default is black).
     */
    public function setLabel(string $label, int $size = 18, array $color = array(0, 0, 0)): void {
        if (!empty($label)) {
            $this->label = Label::create($label);
            $this->label->setTextColor(new Color($color[0], $color[1], $color[2]));
            $this->label->setFont(new Font(PHPWEBFUSE['DIRECTORIES']['FONTS'] . "bookman.ttf", $size));
            $this->label->setAlignment(LabelAlignment::Center);
        }
    }

    /**
     * Create the final QR code result, including data, error correction, size, margin, logo, and label.
     *
     * @return QrCodeResult The result containing the generated QR code image.
     */
    public function createResult(): QrCodeResult {
        $writer = new PngWriter();
        $qrcode = EndroidQrCode::create($this->data);
        $qrcode->setEncoding(new Encoding($this->encoding));
        $qrcode->setErrorCorrectionLevel($this->getEccLevel($this->eccLevel));
        $qrcode->setSize($this->size);
        $qrcode->setMargin($this->margin);
        $qrcode->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
        $qrcode->setForegroundColor(new Color(0, 0, 0));
        $qrcode->setBackgroundColor(new Color(255, 255, 255));
        $result = $writer->write($qrcode, $this->logo, $this->label);
        return new QrCodeResult($result);
    }



    // PRIVATE METHODS

    private function getEccLevel(ErrorCorrectionLevel $eccLevel): EndroidErrorCorrectionLevel {
        switch ($eccLevel) {
            case 'L':
                return EndroidErrorCorrectionLevel::Low;
                break;
            case 'M':
                return EndroidErrorCorrectionLevel::Medium;
                break;
            case 'Q':
                return EndroidErrorCorrectionLevel::Quartile;
                break;
            default:
                return EndroidErrorCorrectionLevel::High;
                break;
        }
    }
}

enum ErrorCorrectionLevel: string {
    case L = EndroidErrorCorrectionLevel::Low;
    case M = EndroidErrorCorrectionLevel::Medium;
    case H = EndroidErrorCorrectionLevel::High;
    case Q = EndroidErrorCorrectionLevel::Quartile;
}


final class QrCodeResult implements ResultInterface {
    private ResultInterface $result;
    public function __construct(ResultInterface $result) {
        $this->result = $result;
    }
    public function getMatrix(): MatrixInterface {
        return $this->result->getMatrix();
    }
    public function getString(): string {
        return $this->result->getString();
    }
    public function getDataUri(): string {
        return $this->result->getDataUri();
    }
    public function saveToFile(string $path): void {
        $this->result->saveToFile($path);
    }
    public function getMimeType(): string {
        return $this->result->getDataUri();
    }
}
