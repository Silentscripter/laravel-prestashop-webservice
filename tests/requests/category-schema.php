<?php

return [
    'HTTP/1.1 200 OK
Server: nginx
Date: Fri, 26 Jan 2018 20:38:53 GMT
Content-Type: text/xml;charset=utf-8
Content-Length: 825
Connection: keep-alive
Vary: Accept-Encoding
Access-Time: 1516999133
X-Powered-By: PrestaShop Webservice
PSWS-Version: 1.7.2.4
Execution-Time: 0.006
Content-Sha1: 1234
Set-Cookie: PrestaShop-1234; expires=Thu, 15-Feb-2018 20:38:53 GMT; Max-Age=1728000; path=/; domain=example.com; HttpOnly
Vary: Accept-Encoding

<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
<category>
    <id></id>
    <id_parent></id_parent>
    <active></active>
    <id_shop_default></id_shop_default>
    <is_root_category></is_root_category>
    <position></position>
    <date_add></date_add>
    <date_upd></date_upd>
    <name><language id="1"></language></name>
    <link_rewrite><language id="1"></language></link_rewrite>
    <description><language id="1"></language></description>
    <meta_title><language id="1"></language></meta_title>
    <meta_description><language id="1"></language></meta_description>
    <meta_keywords><language id="1"></language></meta_keywords>
<associations>
<categories>
    <category>
    <id></id>
    </category>
</categories>
<products>
    <product>
    <id></id>
    </product>
</products>
</associations>
</category>
</prestashop>',

    json_decode('{"url":"http:\/\/example.com\/api\/categories?schema=blank","content_type":"text\/xml;charset=utf-8","http_code":200,"header_size":436,"request_size":155,"filetime":-1,"ssl_verify_result":0,"redirect_count":0,"total_time":0.149629,"namelookup_time":0.004162,"connect_time":0.025428,"pretransfer_time":0.025453,"size_upload":0,"size_download":825,"speed_download":5513,"speed_upload":0,"download_content_length":825,"upload_content_length":-1,"starttransfer_time":0.149337,"redirect_time":0,"redirect_url":"","primary_ip":"1.2.3.4","certinfo":[],"primary_port":80,"local_ip":"192.168.1.1","local_port":56568,"request_header":"GET \/api\/categories?schema=blank HTTP\/1.1\r\nHost: example.com\r\nAuthorization: Basic XXX\r\nAccept: *\/*\r\n\r\n"}', true),
    
    false
];
