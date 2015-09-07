<?php

namespace Timekit;

class User
{
    private $email;

    private $token;

    private $isAuthenticated;

    public function __construct($email, $token)
    {
        $this->email = $email;
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getIsAuthenticated()
    {
        return $this->isAuthenticated;
    }

    public function __toString()
    {
        return sprintf('User [email: %s, token: %s]', $this->getEmail(), $this->getToken());
    }
}
