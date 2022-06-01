<?php

namespace Uzsoftic\EcommerceTelegramBot;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TelegramBot
{

    public $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36',
        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
    ];

    public $commands;
    public $functions;
    public $render;
    public $botconfig = 'telegram';

    public function __construct($config){
        $this->botconfig = $config;
    }

    public function bot($method, $datas=[]){
        $url = config('telegram.call_api').config('telegram.bot.'.$this->botconfig.'.token')."/".$method;
        $client = new Client();
        try {
            $request = $client->requestAsync('POST', $url, ['form_params' => $datas]);
            $response = $request->wait();
            $statusCode = $response->getStatusCode();
        } catch (RequestException $e) {
            $statusCode = 0;
        }

        if ($statusCode == 200){
            $query = $response->getBody()->getContents();
            $result = json_decode($query);
        }else{
            $result = 'Error sending request, error_code: '.$statusCode;
        }

        return $result;
    }

    public function sendAction($chat_id, $action){
        $this->bot('sendChatAction',[
            'chat_id'=>$chat_id,
            'action'=>$action
        ]);
    }

    public function sendMessage($chat_id, $text, $parse_mod = 'html', $reply_markup = NULL){
        $this->bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'html',
            //'reply_markup' => $reply_markup
        ]);
    }

    public function sendMessageTest($chat_id, $text, $parse_mod = 'html', $reply_markup = NULL){
        $response = $this->bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'html',
            //'reply_markup' => $reply_markup
        ]);

        if(!(int)isset($response->result)){
            sendTelegram('me', json_encode($response)." \n\nChatID: ".$chat_id);
            return json_encode($response);
        }

        return 1;
    }

    public function sendPhoto($chat_id, $text, $img, $add = NULL){
        $send = array(
            'chat_id' => $chat_id,
            'caption' => $text,
            'photo' => $img,
            'parse_mode' => 'html',
        );

        if(isset($add))
            $send = array_merge($send, $add);

        $this->bot('sendPhoto', $send);
    }

    public function sendFullMessage($chat_id, $message_id, $text, $reply = 1, $keyboard = NULL, $add = NULL){
        $send = array(
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'html',

        );
        if(isset($reply) && $reply != 0)
            $send = array_merge($send, ['reply_to_message_id' => $message_id]);

        if(isset($keyboard))
            $send = array_merge($send, $keyboard);

        return $this->bot('sendMessage', $send);
    }

    public function deleteMessage($chat_id, $message_id){
        $this->bot('deleteMessage', [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ]);
    }

    public function editMessageText($chat_id, $message_id, $text, $parse_mod = 'markdown'){
        $this->bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text
            //'parse_mode' => $parse_mod,
            //'reply_markup' => $reply_markup
        ]);
    }

    public function editMessageCaption($chat_id, $message_id, $text, $parse_mod = 'markdown'){
        $this->bot('editMessageCaption', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'caption' => $text
            //'parse_mode' => $parse_mod,
            //'reply_markup' => $reply_markup
        ]);
    }

    public function editMessageMedia($chat_id, $message_id, $text, $media = NULL, $parse_mod = 'markdown'){
        $send = array(
            'chat_id' => $chat_id,
            'caption' => $text,
            'message_id' => $message_id,
            'photo' => $media
        );
        //if(isset($add)) $send = array_merge($send, $add);
        $this->bot('editMessageMedia', $send);
    }

    public function editMessageReplyMarkup($chat_id, $message_id, $keyboard, $add = NULL){
        $send = array(
            'chat_id' => $chat_id,
            //'reply_to_message_id' => $message_id,
            //'text' => $text,
            'message_id' => $message_id,
            'parse_mode' => 'markdown',

        );
        if(isset($keyboard)) $send = array_merge($send, $keyboard);

        $this->bot('editMessageReplyMarkup', $send);
    }

    public function sendAnswerInline($chat_id, $text, $add, $reply_markup = NULL){

        $send = array(
            'chat_id' => $chat_id,
            //'reply_to_message_id' => $message_id,
            'text' => $text,
            //'parse_mode' => 'markdown',

        );
        if(isset($add)) $send = array_merge($send, $add);
    }

    public function makeKeyboard($array){
        $make = json_encode([
            'keyboard' => $array,
            'one_time_keyboard' => true,
            'resize_keyboard' => true,
            'selective' => true
        ]);
        return array(
            'reply_markup' => $make,
        );
    }

    public function makeInline($array){
        $make = json_encode(['inline_keyboard' => $array]);
        return array(
            'reply_markup' => $make,
        );
    }

    public function removeKeyboard(){
        /*$keyboard = array(
            //'reply_markup' => json_encode(['hide_keyboard' => true]),
            'reply_markup' => json_encode([
                ['remove_keyboard' => true],
                ['hide_keyboard' => true],
            ]),
        );*/
        return array(
            'reply_markup' => json_encode([
                //'inline_keyboard' => [],
                //'hide_keyboard' => true,
                'remove_keyboard' => true,
            ])
        );
    }

    public function answerInlineQuery($inline, $array = []){
        $result = [];
        foreach($array as $product){
            $text = '<b>'.$product->name."</b>\n\n".home_discounted_base_price($product->id, 'bot')."\n\nID: #".$product->id."\n\n".route('product', $product->slug);
            $result[] = [
                'type'=> 'article',
                'thumb_url'=> 'https://openshop.uz/public/'.$product->thumbnail_img,
                'id' => $product->id,
                'title'=> $product->name,
                'description'=> home_discounted_base_price($product->id, 'bot'),
                'input_message_content'=> [
                    'disable_web_page_preview'=> false,
                    'parse_mode'=> 'html',
                    'message_text'=> $text,
                ],
                //'reply_markup' => makeInline([ [['text' => 'test', 'callback_data' => 'cb_homepage']] ]),
            ];
        }

        $send = array(
            'inline_query_id' => $inline->id,
            'results' => json_encode($result),
            'cache_time' => 100,
            'is_personal' => true
        );

        return $this->bot('answerInlineQuery', $send);
    }

    public function showAlert($chat_id, $text, $timeout = 0){
        $send = [
            'callback_query_id' => $chat_id,
            'text' => $text,
            'show_alert' => true,
            'disable_web_page_preview' => true,
            'cache_time' => $timeout,
        ];
        return $this->bot('answerCallbackQuery', $send);
    }


}
