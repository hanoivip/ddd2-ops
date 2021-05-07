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
        $params = [
            'userId' => $user->getAuthIdentifier(),
            'svname' => $server->name
        ];
        $url = $server->operate_uri . '/role.php?' . http_build_query($params);
        Log::debug("Gunpow query url:" . $url);
        $response = CurlHelper::factory($url)->exec();
        if ($response['data'] === false)
        {
            Log::error("Gunpow recharge server exception. Returned content: " . $response['content']);
            throw new Exception("Gunpow query roles error 1.");
        }
        if ($response['data']['status'] != 0)
        {
            Log::error("Gunpow query roles error. Code=" . $response['data']['status']);
            throw new Exception("Gunpow query roles error 2.");
        }
        return $response['data']['chars'];
    }

    public function recharge($user, $server, $order, $package, $params = null)
    {
        $code = $package->code;
        if (empty($params) || !isset($params['roleid']))
            throw new Exception('GunPow Role/Character ID must specified.');
        $rechargeParams = [
            'roleid' => $params['roleid'],
            'svname' => $server->name,
            'package' => $code,
            'money' => $package->coin
        ];
        $rechargeUrl = $server->recharge_uri . '/pay.php?' . http_build_query($rechargeParams);
        Log::debug("Gunpow dump recharge url request:" . $rechargeUrl);
        $response = CurlHelper::factory($rechargeUrl)->exec();
        if ($response['data'] === false)
        {
            Log::error("Gunpow recharge server exception. Returned content: " . $response['content']);
            throw new Exception("Chuyển xu vào game không thành công. Vui lòng liên hệ GM.");
        }
        if ($response['data']['status'] != 0)
        {
            Log::error("Gunpow recharge server error. Code=" . $response['data']['status']);
            return false;
        }
        return true;
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
        throw new Exception('Gunpow web is not supported!');
    }

    public function sentItem($user, $server, $order, $itemId, $itemCount, $params = null)
    {
        if (empty($params) || !isset($params['roleid']))
            throw new Exception('GunPow Role/Character ID must specified.');
        $uid = $user->getAuthIdentifier();
        $sendParams = [
            'userId' => $uid,
            'roleId' => $params['roleid'],
            'svname' => $server->name,
            'itemId' => $itemId,
            'itemCount' => $itemCount,
            'order' => $order,
            'sign' => md5($uid . $params['roleid'] . $server->name . $itemId . $itemCount . $order . config('game.recharegkey')),
        ];
        $sendUrl = $server->recharge_uri . '/senditem.php?' . http_build_query($sendParams);
        Log::debug("Gunpow dump send url:" . $sendUrl);
        $response = CurlHelper::factory($sendUrl)->exec();
        if ($response['data'] === false ||
            $response['status'] != 200)
        {
            Log::error("Gunpow send item exception. Returned content: " . $response['content']);
            throw new Exception("Chuyển đồ vào game không thành công. Vui lòng liên hệ GM.");
        }
        if ($response['data']['status'] != 0)
        {
            Log::error("Gunpow send item error. Code=" . $response['data']['status']);
            return false;
        }
        return true;
    }
    
    public function order($user, $server, $package, $params = null)
    {
        if (empty($params) || !isset($params['roleid']))
            throw new Exception('GunPow Role/Character ID must specified.');
        $params = [
            'role' => $params['roleid'],
            'svid' => $server->name,
            'product' => $package,
        ];
        $url = $server->operate_uri . '/order.php?' . http_build_query($params);
        Log::debug("Gunpow query url:" . $url);
        $response = CurlHelper::factory($url)->exec();
        if ($response['data'] === false)
        {
            Log::error("Gunpow order exception. Returned content: " . $response['content']);
            throw new Exception("Gunpow order error 1.");
        }
        if ($response['data']['status'] != 0)
        {
            Log::error("Gunpow order error. Code=" . $response['data']['status']);
            throw new Exception("Gunpow order error 2.");
        }
        return $response['data']['order'];
    }
    public function useCode($user, $server, $code, $params)
    {}



    
}