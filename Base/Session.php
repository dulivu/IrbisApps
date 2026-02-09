<?php
namespace IrbisApps\Base;
use Irbis\Interfaces\ComponentInterface;
use Irbis\Interfaces\SessionInterface;
use Irbis\Traits\Component;
use Irbis\Orm\Record;


class Session implements ComponentInterface, SessionInterface {
    use Component;

    private $user;
    
    public function getUser() {
        if ($this->user)
            return $this->user;

        session_start();

        $user_id = (int) ($_SESSION['user'] ?? 0);

        $this->user = Record::find('users', $user_id);

        return $this->user;
    }
}