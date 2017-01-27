<?php

namespace ZanySoft\Cpanel;

use Config;

Class Cpanel extends xmlapi {
    protected $config;

    protected $username = '';

    protected $password = '';

    protected $domain = '';

    protected $subdomain_dir = '';

    public function __construct() {

        $config = Config::get('cpanel');

        if ($config) {
            $this->username      = $config['username'];
            $this->password      = $config['password'];
            $this->domain        = $config['domain'];
            $this->subdomain_dir = $config['subdomain_dir'];

            $this->set_host($config['ip']);
            $this->password_auth($config['username'], $config['password']);
            $this->set_port($config['port']);
            $this->set_debug($config['debug']);

            $this->config = $config;
        }

        parent::__construct($config['ip']);
    }

    public function setHost($host) {
        $this->set_host($host);

        return $this;
    }

    public function setAuth($username, $password) {

        $this->username = $username;
        $this->password = $password;

        $this->password_auth($username, $password);

        return $this;
    }

    public function api1_query($user, $module, $function, $args = array()) {

        $result = $this->api1($user, $module, $function, $args = array());

        return $this->returnResult($result);
    }

    public function api2($user, $module, $function, $args = array()) {

        $result = $this->api2_query($user, $module, $function, $args);

        return $this->returnResult($result);
    }

    public function createSubdomain($subdomain, $username = '', $subdomain_dir = '', $main_domain = '') {

        $subdomain_dir = $subdomain_dir ? $subdomain_dir : $this->subdomain_dir;
        $username      = $username ? $username : $this->username;
        $domain        = $main_domain ? $main_domain : $this->domain;

        $parse = parse_url($domain);

        if (isset($parse['host'])) {
            $domain = $parse['host'];
        } else if (mb_strpos($domain, '/', 2) !== false) {
            $domain = strstr($domain, '/', true);
        }

        $domain = str_replace('www.', '', $domain);

        if (!$domain || mb_strpos($domain, '.') === false) {
            return (object)array('reason' => 'Please sent main domain first', 'result' => 0);
        }

        $result = $this->api2_query($username, 'SubDomain', 'addsubdomain', array(
                'domain'      => $subdomain,
                'rootdomain'  => $domain,
                'dir'         => '/public_html/' . $subdomain_dir,
                'disallowdot' => 1
            )
        );

        return $this->returnResult($result);
    }

    public function removeSubdomain($subdomain, $main_domain = '') {

        $username = $this->username;
        $domain   = $main_domain ? $main_domain : $this->domain;

        $parse = parse_url($domain);

        if (isset($parse['host'])) {
            $domain = $parse['host'];
        } else if (mb_strpos($domain, '/', 2) !== false) {
            $domain = strstr($domain, '/', true);
        }

        $domain = str_replace('www.', '', $domain);

        if (!$domain || mb_strpos($domain, '.') === false) {
            return (object)array('reason' => 'Please sent main domain first', 'result' => 0);
        }

        $result = $this->api2_query($username, 'SubDomain', 'delsubdomain', array(
                'domain' => $subdomain . '.' . $domain,
            )
        );

        return $this->returnResult($result);
    }

    public function createdb($db_name) {

        $name_length = 54 - strlen($this->username);

        $db_name       = str_replace($this->username . '_', '', $this->slug($db_name, '_'));
        $database_name = $this->username . "_" . $db_name;

        if (strlen($db_name) > $name_length || strlen($db_name) < 4) {
            return (object)array('reason' => 'Database name should be greater than 4 and less than ' . $name_length . ' characters.', 'result' => 0);
        }

        $result = $this->api2_query($this->username, "MysqlFE", "createdb", array('db' => $database_name));

        return $this->returnResult($result);
    }

    public function checkdbuser($db_user) {

        $dbuser = $this->username . '_' . ($db_user ? str_replace($this->username . '_', '', $db_user) : "myadmin");

        $user = $this->api2_query($this->username, "MysqlFE", "dbuserexists", array('dbuser' => $dbuser));

        return $this->returnResult($user);
    }

    public function createdbuser($db_user, $db_pass) {

        if (!$db_user || !$db_pass) {
            return (object)array('reason' => 'Please sent database username and password.', 'result' => '0');
        }

        $user_length = 16 - strlen($this->username);
        $db_user     = str_replace($this->username . '_', '', $this->slug($db_user, '_'));
        $dbuser      = $this->username . "_" . $db_user;

        if (strlen($db_user) > $user_length || strlen($db_user) < 4) {
            return (object)array('reason' => 'Database username should be greater than 4 and less than ' . $user_length . ' characters.', 'result' => 0);
        }

        $validate = $this->checkPassword($db_pass);

        if ($validate != '') {
            return (object)array('reason' => $validate, 'result' => '0');
        }

        $user = $this->checkdbuser($dbuser);

        if ($user->result == 1) {
            return (object)array('reason' => 'Database user ' . $dbuser . ' already exist.', 'result' => '0');
        } else {
            $user = $this->api2_query(
                $this->username,
                "MysqlFE",
                "createdbuser",
                array('dbuser' => $dbuser, 'password' => $db_pass)
            );

            return $this->returnResult($user);
        }
    }

    protected function setdbuser($db_name, $db_user, $privileges = '') {

        $dbname = $this->username . "_" . str_replace($this->username . '_', '', $db_name);
        $dbuser = $this->username . '_' . ($db_user ? str_replace($this->username . '_', '', $db_user) : "myadmin"); //be careful this can only have a maximum of 7 characters

        if (is_array($privileges)) {
            $privileges = implode(',', $privileges);
        }

        $privileges = $privileges ? $privileges : 'ALL PRIVILEGES';

        $added = $this->api2_query($this->username, "MysqlFE", "setdbuserprivileges", array('privileges' => $privileges, 'dbuser' => $dbuser, 'db' => $dbname));

        return $this->returnResult($added);
    }

    public function accountsList($search_type = '', $search = '') {

        return $this->listaccts($search_type, $search);

    }

    public function accountDetials($username = '') {
        $username = $username ? $username : $this->username;

        return $this->accountsummary($username);
    }

    protected function returnResult($result) {

        $json   = json_encode($result);
        $result = json_decode($json, TRUE);

        if (isset($result['data'])) {
            $data = $result['data'];
            if (is_array($data)) {
                $reason = (string)$data['reason'];
                $status = (string)$data['result'];

                if (mb_strpos($reason, ')') !== false) {
                    $reason = ltrim(strstr($reason, ')'), ') ');
                }

                if (mb_strpos($reason, ' at ') !== false) {
                    $reason = trim(strstr($reason, ' at ', true));
                }

                return (object)array('reason' => $reason, 'result' => (int)$status);
            } else {
                if (isset($result['func'])) {
                    $function = $result['func'];
                    $status   = $data;

                    switch ($function) {
                        case 'createdb':
                            $reason = 'Database' . ($status ? ' ' : ' not ') . 'created successfully';
                        break;
                        case 'createdbuser':
                            $reason = 'Database user' . ($status ? ' ' : ' not ') . 'created successfully';
                        break;
                        case 'dbuserexists':
                            $reason = 'Database user' . ($status ? ' ' : ' not ') . 'exist';
                        break;
                        case 'addsubdomain':
                            $reason = 'Subdomain ' . ($status ? ' created successfully.' : ' not created.');
                        break;
                        case 'delsubdomain':
                            $reason = 'Subdomain ' . ($status ? ' removed successfully.' : ' not removed.');
                        break;
                        case 'setdbuserprivileges':
                            $reason = 'Database user privileges ' . ($status ? ' set successfully.' : ' not set.');
                        break;
                        default:
                            $reason = '';
                    }
                }

                return (object)array('reason' => $reason, 'result' => (int)$data);
            }
        } else {
            return $result;
        }
    }

    public function slug($title, $separator = '-') {
        // Convert all dashes/underscores into separator
        $flip = $separator == '-' ? '_' : '-';

        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', mb_strtolower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

        return trim($title, $separator);
    }

    protected function checkPassword($pwd) {
        if (strlen($pwd) < 8) {
            return "Password too short!";
        }

        if (!preg_match("#[0-9]+#", $pwd)) {
            return "Password must include at least one number!";
        }

        if (!preg_match("#[a-zA-Z]+#", $pwd)) {
            return "Password must include at least one letter!";
        }

        return '';
    }

    /*protected function toArray($obj) {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }
        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $key       = preg_replace('/[^a-zA-Z0-9 _-]/', "", $key);
                $new[$key] = $this->toArray($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }*/

}