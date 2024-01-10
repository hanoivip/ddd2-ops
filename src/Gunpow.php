<?php

namespace Hanoivip\Ddd2Ops;

use Hanoivip\GameContracts\Contracts\IGameOperator;
use Exception;

class Gunpow implements IGameOperator
{
    use Ddd2Helper;

    public function online($server)
    {
        throw new Exception('Gunpow online is not supported!');
    }

    public function sentItem($user, $server, $order, $itemId, $itemCount, $role)
    {
        throw new Exception('Gunpow sentItem is not supported!');
    }
    
    public function useCode($user, $server, $code, $role)
    {
        throw new Exception('Gunpow useCode is not implemented!');
    }
    
    protected function getAccountId($user)
    {
        return $user->getAuthIdentifier();
    }

    
}