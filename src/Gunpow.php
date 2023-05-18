<?php

namespace Hanoivip\Ddd2Ops;

use Hanoivip\GameContracts\Contracts\IGameOperator;
use Illuminate\Support\Facades\Log;
use Mervick\CurlHelper;
use Exception;

class Gunpow implements IGameOperator
{
    public function characters($user, $server)
    {
        $url = sprintf("http://%s/SearchPlayerServlet", $server->operate_uri);
        $params = [
            'accountId' => $user->getAuthIdentifier(),
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
        $code = $package->code;
        if (empty($params) || !isset($params['roleid']))
        {
            throw new Exception('GunPow Role/Character ID must specified.');
        }
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
        Log::debug($order);
        $url = sprintf("http://%s/callback", $server->operate_uri);
        $response = CurlHelper::factory($url)
        ->setPostParams($rechargeParams)
        ->exec();
        return $response['content'] == '200';
    }

    public function supportMultiChar()
    {
        return true;
    }

    public function online($server)
    {
        return 0;
    }

    public function rank($server)
    {
        return [];
    }

    public function enter($user, $server)
    {
        throw new Exception('Gunpow enter is not supported!');
    }

    public function sentItem($user, $server, $order, $itemId, $itemCount, $params = null)
    {
        throw new Exception('Gunpow sentItem is not supported!');
    }
    
    public function order($user, $server, $package, $params = null)
    {
        $channel = config('ipd.channel', 0);
        $channelDesc = config('ipd.channel_desc', '');
        if (empty($params) || !isset($params['roleid']))
            throw new Exception('GunPow Role/Character ID must specified.');
        $params = [
            'playerId' => $params['roleid'],
            'id' => $package->code,
            'channelid' => $channel,
            'paychannel' => $channelDesc,
        ];
        $url = sprintf("http://%s/OrderServlet", $server->operate_uri);
        $response = CurlHelper::factory($url)
        ->setPostParams($params)
        ->exec();
        if ($response['data'] === false)
        {
            Log::error("Gunpow order exception. Returned content: " . $response['content']);
            throw new Exception("Gunpow order error 1.");
        }
        return $response['data']['ordernum'];
    }
    
    public function useCode($user, $server, $code, $params)
    {
        throw new Exception('Gunpow useCode is not implemented!');
    }

}