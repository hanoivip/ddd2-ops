<?php

namespace Hanoivip\Ddd2Ops;

use Illuminate\Support\Facades\Log;
use Mervick\CurlHelper;
use Exception;

trait Ddd2Helper 
{
    public function getGameAccountId($username)
    {
        $ipd = config('ipd.uri', '');
        $url = sprintf("http://%s/getAccountId", $ipd);
        $response = CurlHelper::factory($url)
        ->setPostFields(['name' => $username])
        ->exec();
        if (empty($response['data']))
        {
            Log::error("Gunpow search account id by username exception. Returned content: " . $response['content']);
            throw new Exception("Gunpow search account id error 1.");
        }
        if (isset($response['data']['accountID']))
        {
            return $response['data']['accountID'];
        }
        throw new Exception("Gunpow search account id error 2.");
    }
    
    public function characters($user, $server)
    {
        $url = sprintf("http://%s/SearchPlayerServlet", $server->operate_uri);
        $params = [
            'accountId' => $this->getAccountId($user)
        ];
        $response = CurlHelper::factory($url)
        ->setPostParams($params)
        ->exec();
        if ($response['data'] === false)
        {
            Log::error("Gunpow search player exception. Returned content: " . $response['content']);
            throw new Exception("Gunpow query roles error 1.");
        }
        $chars = [];
        foreach ($response['data'] as $char)
        {
            $chars[$char['RoleId']] = json_decode('"'.$char['PlayerName'].'"');
        }
        return $chars;
    }
    
    public function buyPackage($user, $server, $order, $package, $role)
    {
        if (empty($role))
        {
            throw new Exception('GunPow Role/Character ID must specified.');
        }
        $gameOrder = $this->order($user, $server, $package, $role);
        if (empty($gameOrder))
        {
            Log::error("Gunpow order failure.");
            throw new Exception("Gunpow order failure.");
        }
        $channel = config('ipd.channel', 0);
        $channelDesc = config('ipd.channel_desc', '');
        $rechargeKey = config('ipd.recharge_key', '');
        $rechargeParams = [
            'playerId' => $role,
            'orderNum' => $gameOrder,
            'realAmt' => 0,
            'channel' => $channel,
            'cardMedium' => 'web',
            'message' => $order,
            'agent' => $channelDesc,
            'verify' => md5($role . $gameOrder . $rechargeKey),
        ];
        $url = sprintf("http://%s/callback", $server->operate_uri);
        $response = CurlHelper::factory($url)
        ->setPostParams($rechargeParams)
        ->exec();
        return $response['content'] == '200';
    }
    
    public function buyByMoney($user, $server, $order, $money, $role)
    {
        if (empty($role))
        {
            throw new Exception('GunPow Role/Character ID must specified.');
        }
        $payCode = config('ddd2.defPkgCode', '');// for getting order?
        $gameOrder = $this->order($user, $server, $payCode, $role);
        if (empty($gameOrder))
        {
            Log::error("Gunpow order failure.");
            throw new Exception("Gunpow order failure.");
        }
        $channel = 0;
        $channelDesc = config('ipd.channel_desc', '');
        $rechargeKey = config('ipd.recharge_key', '');
        $rechargeParams = [
            'playerId' => $role,
            'orderNum' => $gameOrder,
            'realAmt' => $money,
            'channel' => $channel,
            'cardMedium' => 'web',
            'message' => $order,
            'agent' => $channelDesc,
            'verify' => md5($role . $gameOrder . $rechargeKey),
        ];
        $url = sprintf("http://%s/callback", $server->operate_uri);
        $response = CurlHelper::factory($url)
        ->setPostParams($rechargeParams)
        ->exec();
        return $response['content'] == '200';
    }
    
    public function order($user, $server, $package, $role)
    {
        $channel = config('ipd.channel', 0);
        $channelDesc = config('ipd.channel_desc', '');
        if (empty($role))
            throw new Exception('GunPow Role/Character ID must specified.');
        $params = [
            'playerId' => $role,
            'id' => $package,
            'channelid' => $channel,
            'paychannel' => $channelDesc,
        ];
        $url = sprintf("http://%s/OrderServlet", $server->operate_uri);
        $response = CurlHelper::factory($url)
        ->setPostParams($params)
        ->exec();
        if (empty($response['data']))
        {
            Log::error("Gunpow order exception. Returned content: " . $response['content']);
            throw new Exception("Gunpow order error 1.");
        }
        if (empty($response['data']['status']))
        {
            Log::error("Gunpow order exception. Status fail");
            throw new Exception("Gunpow order error 2.");
        }
        return $response['data']['ordernum'];
    }
    
    public function rank($server, $type)
    {
        $url = sprintf("http://%s/RankServlet?type=%s", $server->operate_uri, $type);
        //Log::debug($url);
        $response = CurlHelper::factory($url)->exec();
        if (empty($response['data']))
        {
            Log::error("Gunpow order exception. Returned content: " . $response['content']);
            throw new Exception("Gunpow rank list error.");
        }
        $list = $response['data']['list'];
        return $list;
    }
    
    public function broadcast($server, $message)
    {
        $url = sprintf("http://%s/MessageServlet", $server->operate_uri);
        $key = config('ipd.messaging_key', '');
        $params = [
            'message' => $message,
            'sign' => md5($key . $message),
        ];
        $response = CurlHelper::factory($url)
        ->setPostParams($params)
        ->exec();
        if (empty($response['data']))
        {
            Log::error("Gunpow broadcast exception. Returned content: " . $response['content']);
            throw new Exception("Gunpow broadcast error.");
        }
        return $response['data']['status'] == true;
    }
    
    public function transfer($oldId, $newId)
    {
        $ipd = config('ipd.uri', '');
        $secret = config('ipd.secret', '');
        $url = sprintf("http://%s/transferAccount/TransferAccountServlet", $ipd);
        $params = [
            'oldId' => $oldId,
            'newId' => $newId,
            'sign' => md5($secret . $oldId. $newId . $secret),
        ];
        $response = CurlHelper::factory($url)
        ->setPostParams($params)
        ->exec();
        if ($response['status'] != 200)
        {
            Log::error("Gunpow transfer error. Returned status: " . $response['status']);
            throw new Exception("Gunpow transfer error. You should check httpwhitelist.txt");
        }
        Log::error("Gunpow transfer " . $response['content']);
        return $response['content'] == 200;
    }
    
    public function orderNotify($user, $order)
    {
        
    }
}