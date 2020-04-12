<?php

/*
 * Copyright 2020 Mirco Soderi
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files (the "Software"), 
 * to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the 
 * Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 *
 */
 
function httpPost($method, $data)
{
    $curl = curl_init("https://api.telegram.org/yourbot/$method");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

httpPost("sendMessage",array("chat_id" => $argv[1], "text" => $argv[2], "reply_markup" => '{"inline_keyboard": [[{ "text": "Noo!! Delete it!", "callback_data": "undo tweet '.$argv[4].' '.$argv[5].'"}]]}'));

$userData = json_decode(file_get_contents($argv[3]),true);
$userData["chats"][$argv[1]]["wizard"]["status"] = "published";
$microtime = "".microtime(true);
$userData["chats"][$argv[1]]["log"][$microtime]["text"] = $argv[2];
$userData["chats"][$argv[1]]["log"][$microtime]["cost"] = intval($argv[6])/10;
file_put_contents($argv[3],json_encode($userData,JSON_PRETTY_PRINT));
?>
