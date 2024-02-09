<?php

namespace craft\feedme\helpers;

class HttpHelper
{
    public static function validate_headers($raw_headers)
    {
        return !!preg_match("/^([a-zA-Z0-9-]+:\s*.+\r?\n)*([a-zA-Z0-9-]+:\s*.+)$/", $raw_headers);
    }

    public static function parse_headers($raw_headers)
    {
        $headers = [];
        $lines = explode("\n", $raw_headers);

        $last_key = null;
        foreach($lines as $header) {
            [$key, $value] = explode(':', $header, 2);

            if (!empty($value)) {
                if (!isset($headers[$key])) {
                    $headers[$key] = trim($value);
                } elseif (is_array($headers[$key])) {
                    $headers[$key] = [...$headers[$key], trim($value)];
                } else {
                    $headers[$key] = [$headers[$key], trim($value)];
                }

                $last_key = $key;
            } else {
                if (str_starts_with($key, "\t")) {
                    $headers[$last_key] .= "\r\n\t".trim($key);
                }
            }
        }

        return $headers;
    }
}