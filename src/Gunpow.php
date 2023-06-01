<?php

namespace Hanoivip\Ddd2Ops;

use Hanoivip\GameContracts\Contracts\IGameOperator;
use Illuminate\Support\Facades\Log;
use Mervick\CurlHelper;
use Exception;

class Gunpow implements IGameOperator
{
    use Ddd2Helper;

    public function supportMultiChar()
    {
        return true;
    }

    public function online($server)
    {
        return 0;
    }

    public function enter($user, $server)
    {
        throw new Exception('Gunpow enter is not supported!');
    }

    public function sentItem($user, $server, $order, $itemId, $itemCount, $params = null)
    {
        throw new Exception('Gunpow sentItem is not supported!');
    }
    
    public function useCode($user, $server, $code, $params)
    {
        throw new Exception('Gunpow useCode is not implemented!');
    }
    
    protected function getAccountId($user)
    {
        return $user->getAuthIdentifier();
    }

}