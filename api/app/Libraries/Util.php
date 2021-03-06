<?php

namespace App\Libraries;

use Adbar\Dot;

class Util
{

    public static function calendarColor(string $type, bool $secret): array
    {
        if ($secret) {
            $background = 'FFF7EA';
            $tag = 'F38230';
        } else {
            switch ($type) {
                case 'CONSIDER': {
                        $background = 'EAF3FF';
                        $tag = '105ED2';
                    }
                    break;
                case 'CONT': {
                        $background = 'F3FBFB';
                        $tag = '3DC2C6';
                    }
                    break;
                case 'NOTICE': {
                        $background = 'FFF3F9';
                        $tag = 'F66CB4';
                    }
                    break;
            }
        }
        return [
            'tag' => '#' . $tag,
            'background' => '#' . $background
        ];
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function trim($value)
    {
        $value = (empty($value)) ? null : trim(preg_replace('/\s+/', ' ', $value));
        return (empty($value)) ? null : $value;
    }

    public static function cast(string $type, $value)
    {
        switch ($type) {
            case 'bool': {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                break;
        }
        return $value;
    }

    public static function rule(string $module, bool $must, string $path): string
    {

		$rule = include __DIR__.'/../Modules/'.$module.'/Rule.php';
		$dot = new Dot;
		$dot->set($rule);
		$data = ($must) ? 'required' : 'nullable';
        $temp = $dot->get($path);
        if (is_null($temp)) {
            return null; // Force
        }
        return $data.'|'.$temp;
    }

    public static function genStr(string $type): string
    {
        $data = null;
        switch ($type) {
                // REGEX /^[0-9a-zA-Z]{256}$/s
            case 'INVITE':
                $data = self::randomStr(256, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
                break;
            case 'ATTENDEE':
                $data = self::randomStr(256, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
                break;
            case 'MEETING':
                $data = self::randomStr(10, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
                break;
            case 'FILE':
                $data = self::randomStr(16, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
                break;
        }
        return $data;
    }

    public static function randomStr(int $size, string $char): string
    {
        $str = null;
        for ($i = 0; $i < $size; $i++) {
            $str .= $char[rand(0, (strlen($char) - 1))];
        }
        return $str;
    }

    public static function rewriteNoVersion(string $no)
    {
        $arr = array_map(function ($value): int {
            return (int) $value;
        }, explode('.', $no));

        return implode('.', $arr);
    }

    public static function convertDateFormatThai(string $input, string $type): string
    {
        $text = null;
        switch ($type) {
            case 'INVITE': {
                    $text = date('?????????l????????? j F Y ???????????? G.i ???.', strtotime('+543 years', strtotime($input)));
                }
                break;
            case 'DATE': {
                    $text = date('?????????l????????? j F Y', strtotime('+543 years', strtotime($input)));
                }
                break;
            case 'TIME': {
                    $text = date('G.i ???.', strtotime($input));
                }
                break;
        }
        $text = self::convertDateLangThai('D', $text);
        $text = self::convertDateLangThai('M', $text);
        return $text;
    }

    public static function convertDateLangThai(string $mode, string $text): string
    {
        $list = [];
        switch ($mode) {
            case 'D': {
                    $list = [
                        'Monday' => '??????????????????',
                        'Tuesday' => '??????????????????',
                        'Wednesday' => '?????????',
                        'Thursday' => '????????????????????????',
                        'Friday' => '???????????????',
                        'Saturday' => '???????????????',
                        'Sunday' => '?????????????????????'
                    ];
                }
                break;
            case 'M': {
                    $list = [
                        'January' => '??????????????????',
                        'February' => '??????????????????????????????',
                        'March' => '??????????????????',
                        'April' => '??????????????????',
                        'May' => '?????????????????????',
                        'June' => '????????????????????????',
                        'July' => '?????????????????????',
                        'August' => '?????????????????????',
                        'September' => '?????????????????????',
                        'October' => '??????????????????',
                        'November' => '???????????????????????????',
                        'December' => '?????????????????????'
                    ];
                }
                break;
        }
        foreach ($list as $key => $value) {
            if (str_contains($text, $key)) {
                $text = str_replace($key, $value, $text);
                break;
            }
        }
        return $text;
    }
}