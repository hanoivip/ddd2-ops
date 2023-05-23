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
    
    public function recharge($user, $server, $order, $package, $params = null)
    {
        if (empty($params) || !isset($params['roleid']))
        {
            throw new Exception('GunPow Role/Character ID must specified.');
        }
        //TODO: check $order is already game order?
        //Log::debug(print_r($package, true));
        $gameOrder = $this->order($user, $server, $package, $params);
        if (empty($gameOrder))
        {
            Log::error("Gunpow order failure.");
            throw new Exception("Gunpow order failure.");
        }
        $channel = config('ipd.channel', 0);
        $channelDesc = config('ipd.channel_desc', '');
        $rechargeKey = config('ipd.recharge_key', '');
        $rechargeParams = [
            'playerId' => $params['roleid'],
            'orderNum' => $gameOrder,
            'realAmt' => $package->coin,
            'channel' => $channel,
            'cardMedium' => 'web',
            'message' => $order,
            'agent' => $channelDesc,
            'verify' => md5($params['roleid'] . $gameOrder . $rechargeKey),
        ];
        $url = sprintf("http://%s/callback", $server->operate_uri);
        $response = CurlHelper::factory($url)
        ->setPostParams($rechargeParams)
        ->exec();
        return $response['content'] == '200';
    }
    
    public function order($user, $server, $package, $params = null)
    {
        $channel = config('ipd.channel', 0);
        $channelDesc = config('ipd.channel_desc', '');
        if (gettype($package) == 'string')
        {
            $code = $package;
        }
        else
        {
            $code = $package->code;
        }
        Log::debug(print_r($code, true));
        if (empty($params) || !isset($params['roleid']))
            throw new Exception('GunPow Role/Character ID must specified.');
            $params = [
                'playerId' => $params['roleid'],
                'id' => $code,
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
            return $response['data']['ordernum'];
    }
    
}