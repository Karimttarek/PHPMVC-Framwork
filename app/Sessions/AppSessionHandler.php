<?php

namespace PHPMVC\App\Sessions;

define('DS' , DIRECTORY_SEPARATOR);
define('SESSION_SAVE_PATH' , dirname(realpath(__FILE__)));

class AppSessionHandler extends \SessionHandler
{
    private $sessionName = 'APPSESS';
    private $sessionMaxLifeTime = 0;
    private $sessionSSL = false;
    private $sessionHTTPOnly = true;
    private $sessionPath = '/';
    private $sessionDomain = '.localhost';
    private $sessionSavePath = SESSION_SAVE_PATH;

    private $sessionCipherAlgo = MCRYPT_BLOWFISH;
    private $sessionCipherMode = MCRYPT_MODE_ECB;
    private $sessionCipherKey = '@PPCRYPTOK3y@291299';

    /**
     * @var int Time To Live
     */
    private $ttl = 30;

    public function __construct()
    {
        ini_set('session.use_cookies' ,1); // Prevent access by js
        ini_set('session.use_only_cookies' ,1);
        ini_set('session.use_trans_sid' ,0); // prevent access session id in URL
        ini_set('session.save_handler' , 'files'); // session handler save in files

        session_name($this->sessionName);
        session_save_path($this->sessionSavePath);

        session_set_cookie_params(
            $this->sessionMaxLifeTime ,
            $this->sessionPath,
            $this->sessionDomain,
            $this->sessionSSL,
            $this->sessionHTTPOnly
        );
        session_set_save_handler($this ,true);
    }

    public function __get($key){
        return false !== $_SESSION[$key] ? $_SESSION[$key] : false;
    }

    public function __set($key , $value)
    {
        $_SESSION[$key] = $value;
    }

     public function __isset($key)
     {
          return isset($_SESSION[$key]) ? true : false;
     }

    public function read($id)
    {
        // TODO: Change the autogenerated stub
        return mcrypt_decrypt($this->sessionCipherAlgo , $this->sessionCipherKey , parent::read($id) , $this->sessionCipherMode);
    }

    public function write($id, $data)
    {
        // TODO: Change the autogenerated stub
        return parent::write($id,mcrypt_encrypt($this->sessionCipherAlgo , $this->sessionCipherKey , $data , $this->sessionCipherMode));
    }


    private function setTime()
    {
        if (!isset($this->sessionStartTime)) {
            $this->sessionStartTime = time();
        }
        return true;
    }

    private function checkTimeValidity()
    {
        // if session time greater than validate time
        if ((time() - $this->sessionStartTime) > ($this->ttl  * 60)){
            // renew the session id and time
            $this->renewSession();
            $this->renewDetector();
        }
        return true;
    }

    private function renewSession()
    {
        $this->sessionStartTime = time();
        return session_regenerate_id(true);
    }


    public function start()
    {
        if ('' === session_id()){
            if (session_start()){
                $this->setTime();
                $this->checkTimeValidity();
            }
        }
    }

    // End Session
    public function kill()
    {
        session_unset();
        setcookie(
            $this->sessionName,
            '' ,
            time() - 1000 , 
            $this->sessionPath ,
            $this->sessionDomain,
            $this->sessionSSL,
            $this->sessionHTTPOnly
        );

        session_destroy();
    }

    private function renewDetector()
    {
        $agentId = $_SERVER['HTTP_USER_AGENT'];
            $this->cipherKey = mcrypt_create_iv(32);
            $this->detector = md5($agentId . $this->cipherKey . session_id());
    }

    public function userDetector()
    {
        if(!isset($this->detector)){
            $this->renewDetector();
        }
        $detector = md5($_SERVER['HTTP_USER_AGENT'] . $this->cipherKey . session_id());

        if($detector === $this->detector){
            return true;
        }else{
            $session->kill();
        }
    }
    
}

$session = new AppSessionHandler();
$session->start();
// $session->kill();
// $session->userDetector();
// echo $session->detector;

// var_dump($session);