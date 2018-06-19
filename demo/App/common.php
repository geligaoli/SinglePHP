<?php
if (!function_exists("Now")) {
    function Now()
    {
        return Date('Y-m-d H:i:s');
    }
}
if (!function_exists("simplexml")) {
    function simplexml($topchild, $value = FALSE)
    {
        $value = ($value === FALSE ? "" : htmlspecialchars($value, ENT_XML1));
        return simplexml_load_string("<" . "?xml version=\"1.0\" encoding=\"UTF-8\"?" . ">\n<$topchild>$value</$topchild>");
    }
}
if (!function_exists("fastcgi_finish_request")) {
    function fastcgi_finish_request()
    {
    }
}
if (!function_exists("base64url_encode")) {
    function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
if (!function_exists("base64url_decode")) {
    function base64url_decode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
if (!function_exists("tojson")) {
    function tojson($array) {
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
