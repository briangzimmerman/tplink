<?php

class TpLink {
    private $ch;
    private $cookie;
    private $loggedIn = false;
    private $router_ip;
    private $session_id;
    private $session_regex = '/http\:\/\/[0-9A-Za-z.]+\/([A-Z]{16})\/userRpm\/Index\.htm/';

    function __construct($username, $password, $ip = '192.168.1.1') {
        $this->ch = curl_init();
        $this->router_ip = $ip;
        $this->cookie = 'Basic ' . base64_encode("$username:".md5($password));
        $this->request("http://{$this->router_ip}"); //For some reason this is necessary
        $this->login();
    }
//------------------------------------------------------------------------------

    private function getLoginURL() {
        return "http://{$this->router_ip}/userRpm/LoginRpm.htm?Save=Save";
    }
//------------------------------------------------------------------------------

    private function getRebootURL() {
        if(!$this->loggedIn) {
            throw new Exception('Not logged in');
        }

        return "http://{$this->router_ip}/{$this->session_id}/userRpm/SysRebootRpm.htm";
    }
//------------------------------------------------------------------------------

    private function getLogoutURL() {
        if(!$this->loggedIn) {
            throw new Exception('Not Logged In');
        }

        return "http://{$this->router_ip}/{$this->session_id}/userRpm/LogoutRpm.htm";
    }
//------------------------------------------------------------------------------

    private function getStatusURL() {
        if(!$this->loggedIn) {
            throw new Exception('Not Logged In');
        }

        return "http://{$this->router_ip}/{$this->session_id}/userRpm/StatusRpm.htm";
    }
//------------------------------------------------------------------------------

    private function request($url, $opts = []) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_COOKIE, "Authorization={$this->cookie};path=/");

        foreach($opts as $opt => $val) {
            curl_setopt($this->ch, $opt, $val);
        }

        return curl_exec($this->ch);
    }
//------------------------------------------------------------------------------

    public function login() {
        $output = $this->request($this->getLoginURL());
        preg_match($this->session_regex, $output, $matches);
        
        if(empty($matches)) {
            throw new Exception('Could not find session hash');
        }

        $this->session_id = $matches[1];
        $this->loggedIn = true;
    }
//------------------------------------------------------------------------------

    public function reboot() {
        if(!$this->loggedIn) {
            throw new Exception('Not Logged In');
        }

        $referer = $this->getRebootURL();

        $this->request($referer.'?Reboot=Reboot', [
            CURLOPT_REFERER => $referer
        ]);
    }
//------------------------------------------------------------------------------

    public function logout() {
        if(!$this->loggedIn) {
            throw new Exception('Not Logged In');
        }
        
        return $this->request($this->getLogoutURL(), [
           CURLOPT_REFERER => $this->getStatusURL()
        ]);
    }
}
