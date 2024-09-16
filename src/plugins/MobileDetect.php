<?php

use Detection\MobileDetect as DetectionMobileDetect;

/**
 *
 */
if (!class_exists("\MobileDetect")) {

    class MobileDetect
    {
        private \Detection\MobileDetect $detection;
        private string $IPAddress = "";
        private string $IPUrl = "";

        public function __construct()
        {
            $this->detection = new DetectionMobileDetect();
            $this->setIPAddress();
        }

        public function getBrowser()
        {
            $useragent = getenv("HTTP_USER_AGENT");
            $browser = 'Unknown Browser';
            if (preg_match('/Edge\/\d+/', $useragent)) {
                $browser = 'Microsoft Edge ' . str_replace('12', '20', $this->detection->version('Edge'));
            } elseif ($this->detection->version('Trident') !== false && preg_match('/rv:11.0/', $useragent)) {
                $browser = 'Internet Explorer 11';
            } else {
                foreach ($this->detection->getBrowsers() as $name => $regex) {
                    $version = $this->detection->version($name);
                    if ($version !== false) {
                        $browser = $name . ' ' . $version;
                        break;
                    }
                }
            }
            return $browser;
        }

        public function getDevice()
        {
            $this->detection->setUserAgent(getenv("HTTP_USER_AGENT"));
            return ($this->detection->isMobile() ? ($this->detection->isTablet() ? 'Tablet' : 'Phone') : 'Computer');
        }

        public function getDeviceOsName()
        {
            $useragent = getenv('HTTP_USER_AGENT');
            $version = '';
            $codeName = '';
            $os = 'Unknown OS';
            foreach ($this->detection->getOperatingSystems() as $name => $regex) {
                $version = $this->detection->version($name);
                if ($version !== false) {
                    $os = $name . ' ' . $version;
                }
                break;
            }
            if ($this->detection->isAndroidOS()) {
                if ($this->detection->version('Android') !== false) {
                    $version = ' ' . $this->detection->version('Android');
                    switch (true) {
                        case $this->detection->version('Android') >= 14:$codeName = ' (Upside Down Cake)';
                            break;
                        case $this->detection->version('Android') >= 13:$codeName = ' (Tiramisu)';
                            break;
                        case $this->detection->version('Android') >= 12:$codeName = ' (Snow Cone)';
                            break;
                        case $this->detection->version('Android') >= 11:$codeName = ' (Red Velvet Cake)';
                            break;
                        case $this->detection->version('Android') >= 10:$codeName = ' (Quince Tart)';
                            break;
                        case $this->detection->version('Android') >= 9.0:$codeName = ' (Pie)';
                            break;
                        case $this->detection->version('Android') >= 8.0:$codeName = ' (Oreo)';
                            break;
                        case $this->detection->version('Android') >= 7.0:$codeName = ' (Nougat)';
                            break;
                        case $this->detection->version('Android') >= 6.0:$codeName = ' (Marshmallow)';
                            break;
                        case $this->detection->version('Android') >= 5.0:$codeName = ' (Lollipop)';
                            break;
                        case $this->detection->version('Android') >= 4.4:$codeName = ' (KitKat)';
                            break;
                        case $this->detection->version('Android') >= 4.1:$codeName = ' (Jelly Bean)';
                            break;
                        case $this->detection->version('Android') >= 4.0:$codeName = ' (Ice Cream Sandwich)';
                            break;
                        case $this->detection->version('Android') >= 3.0:$codeName = ' (Honeycomb)';
                            break;
                        case $this->detection->version('Android') >= 2.3:$codeName = ' (Gingerbread)';
                            break;
                        case $this->detection->version('Android') >= 2.2:$codeName = ' (Froyo)';
                            break;
                        case $this->detection->version('Android') >= 2.0:$codeName = ' (Eclair)';
                            break;
                        case $this->detection->version('Android') >= 1.6:$codeName = ' (Donut)';
                            break;
                        case $this->detection->version('Android') >= 1.5:$codeName = ' (Cupcake)';
                            break;
                        default:$codeName = '';
                            break;
                    }
                }
                $os = 'Android' . $version . $codeName;
            } elseif (preg_match('/Linux/', $useragent)) {
                $os = 'Linux';
            } elseif (preg_match('/Mac OS X/', $useragent)) {
                if (preg_match('/Mac OS X 10_14/', $useragent) || preg_match('/Mac OS X 10.14/', $useragent)) {
                    $os = 'OS X (Mojave)';
                } elseif (preg_match('/Mac OS X 10_13/', $useragent) || preg_match('/Mac OS X 10.13/', $useragent)) {
                    $os = 'OS X (High Sierra)';
                } elseif (preg_match('/Mac OS X 10_12/', $useragent) || preg_match('/Mac OS X 10.12/', $useragent)) {
                    $os = 'OS X (Sierra)';
                } elseif (preg_match('/Mac OS X 10_11/', $useragent) || preg_match('/Mac OS X 10.11/', $useragent)) {
                    $os = 'OS X (El Capitan)';
                } elseif (preg_match('/Mac OS X 10_10/', $useragent) || preg_match('/Mac OS X 10.10/', $useragent)) {
                    $os = 'OS X (Yosemite)';
                } elseif (preg_match('/Mac OS X 10_9/', $useragent) || preg_match('/Mac OS X 10.9/', $useragent)) {
                    $os = 'OS X (Mavericks)';
                } elseif (preg_match('/Mac OS X 10_8/', $useragent) || preg_match('/Mac OS X 10.8/', $useragent)) {
                    $os = 'OS X (Mountain Lion)';
                } elseif (preg_match('/Mac OS X 10_7/', $useragent) || preg_match('/Mac OS X 10.7/', $useragent)) {
                    $os = 'Mac OS X (Lion)';
                } elseif (preg_match('/Mac OS X 10_6/', $useragent) || preg_match('/Mac OS X 10.6/', $useragent)) {
                    $os = 'Mac OS X (Snow Leopard)';
                } elseif (preg_match('/Mac OS X 10_5/', $useragent) || preg_match('/Mac OS X 10.5/', $useragent)) {
                    $os = 'Mac OS X (Leopard)';
                } elseif (preg_match('/Mac OS X 10_4/', $useragent) || preg_match('/Mac OS X 10.4/', $useragent)) {
                    $os = 'Mac OS X (Tiger)';
                } elseif (preg_match('/Mac OS X 10_3/', $useragent) || preg_match('/Mac OS X 10.3/', $useragent)) {
                    $os = 'Mac OS X (Panther)';
                } elseif (preg_match('/Mac OS X 10_2/', $useragent) || preg_match('/Mac OS X 10.2/', $useragent)) {
                    $os = 'Mac OS X (Jaguar)';
                } elseif (preg_match('/Mac OS X 10_1/', $useragent) || preg_match('/Mac OS X 10.1/', $useragent)) {
                    $os = 'Mac OS X (Puma)';
                } elseif (preg_match('/Mac OS X 10/', $useragent)) {
                    $os = 'Mac OS X (Cheetah)';
                }
            } elseif ($this->detection->isWindowsPhoneOS()) {
                if ($this->detection->version('WindowsPhone') !== false) {
                    $version = ' ' . $this->detection->version('WindowsPhoneOS');
                }
                $os = 'Windows Phone' . $version;
            } elseif ($this->detection->version('Windows NT') !== false) {
                switch ($this->detection->version('Windows NT')) {
                    case 10.0:$codeName = ' 10';
                        break;
                    case 6.3:$codeName = ' 8.1';
                        break;
                    case 6.2:$codeName = ' 8';
                        break;
                    case 6.1:$codeName = ' 7';
                        break;
                    case 6.0:$codeName = ' Vista';
                        break;
                    case 5.2:$codeName = ' Server 2003; Windows XP x64 Edition';
                        break;
                    case 5.1:$codeName = ' XP';
                        break;
                    case 5.01:$codeName = ' 2000, Service Pack 1 (SP1)';
                        break;
                    case 5.0:$codeName = ' 2000';
                        break;
                    case 4.0:$codeName = ' NT 4.0';
                        break;
                    default:$codeName = ' NT v' . $this->detection->version('Windows NT');
                        break;
                }
                $os = 'Windows' . $codeName;
            } elseif ($this->detection->isiOS()) {
                if ($this->detection->isTablet()) {
                    $version = ' ' . $this->detection->version('iPad');
                } else {
                    $version = ' ' . $this->detection->version('iPhone');
                }
                $os = 'iOS' . $version;
            }
            return $os;
        }

        public function getDeviceBrand()
        {
            $brand = 'Unknown Brand';
            switch ($this->getDevice()) {
                case 'Phone':
                    foreach ($this->detection->getPhoneDevices() as $name => $regex) {
                        $check = $this->detection->{'is' . $name}();
                        if ($check !== false) {
                            $brand = $name;
                        }
                    }
                    break;
                case 'Tablet':
                    foreach ($this->detection->getTabletDevices() as $name => $regex) {
                        $check = $this->detection->{'is' . $name}();
                        if ($check !== false) {
                            $brand = str_replace('Tablet', '', $name);
                        }
                    }
                    break;
            }
            return $brand;
        }

        public function getIPInfo()
        {
            $info = array();
            try {
                $info = (array) json_decode(@file_get_contents('https://ipinfo.io/json?token=d4e2c91d08f44e'), JSON_OBJECT_AS_ARRAY);
            } catch (\Throwable $e) {
                try {
                    $data = (array) json_decode(@file_get_contents('https://ipapi.co/' . $this->ipAddress . '/json/'), JSON_OBJECT_AS_ARRAY);
                    if (isset($data['error']) && $data['error'] !== true) {
                        $info = $data;
                    }
                } catch (\Throwable $e) {

                }
            }
            return $info;
        }

        public function isMobile()
        {
            return $this->detection->isMobile();
        }

        public function isTablet()
        {
            return $this->detection->isTablet();
        }

        public function IPAddress()
        {
            return $this->ipAddress;
        }

        public function isPhone()
        {
            $this->detection->setUserAgent(getenv("HTTP_USER_AGENT"));
            return $this->detection->isMobile() ? ($this->detection->isTablet() ? false : true) : false;
        }

        public function isComputer()
        {
            $this->detection->setUserAgent(getenv("HTTP_USER_AGENT"));
            return !$this->detection->isMobile();
        }

        public function isEdge()
        {
            return preg_match('/Edge\/\d+/', getenv("HTTP_USER_AGENT"));
        }

        public function isIEOld()
        {
            return $this->detection->version('IE') !== false && $this->detection->version('IE') <= 9;
        }

        // PRIVATE
        private function setIPAddress()
        {
            $this->IPAddress= "";
            if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
                $this->IPAddress = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->IPAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
                $this->IPAddress = $_SERVER['REMOTE_ADDR'];
            }
            if (in_array($this->IPAddress, array('::1', '127.0.0.1', 'localhost'))) {
                $this->IPAddress = $this->IPAddress;
                $this->IPUrl = '';
            } else {
                $this->IPUrl = '/' . $this->IPAddress;
            }
        }
    }

}
