<?php

namespace ThemeHouse\Topics\Util;

/**
 * Class Color
 * @package ThemeHouse\Topics\Util
 */
class Color extends \XF\Util\Color
{
    /**
     * Return true if the given background color is light
     * and we need a dark text color to make it readable
     *
     * @param $backgroundColor
     * @return bool
     */
    public static function isColorLight($backgroundColor)
    {
        $rgbColor = self::colorToRgb($backgroundColor);

        $lightness = 1 - ($rgbColor[0] * 0.299 + $rgbColor[1] * 0.587 + $rgbColor[2] * 0.114) / 255;

        if ($lightness < 0.3) {
            return true;
        }

        return false;
    }

    /**
     * @param $color
     * @return bool|string
     */
    public static function getColorType($color)
    {
        if (strpos($color, 'rgb') === 0) {
            return 'rgb';
        }

        if (strpos($color, '#') === 0) {
            return 'hex';
        }

        return false;
    }
}
