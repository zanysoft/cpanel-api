<?php

namespace ZanySoft\Cpanel;

use Config, Exception;

Class Cpanel extends xmlapi {
    protected $config;

    protected $username = '';

    protected $password = '';

    protected $domain = '';

    protected $subdomain_dir = '';

    public function __construct() {

        $config = Config::get('cpanel');

        if (!$config) {
            $config = include(__DIR__ . '/../config/cpanel.php');
        }

        if ($config['ip']) {
            parent::__construct($config['ip']);
        } else {
            throw new Exception('Host IP not defined.');
        }

        $this->username      = $config['username'];
        $this->password      = $config['password'];
        $this->domain        = $config['domain'];
        $this->subdomain_dir = $config['subdomain_dir'];

        if ($config['username'] && $config['password']) {
            $this->password_auth($config['username'], $config['password']);
        }

        $this->set_host($config['ip']);
        $this->set_debug($config['debug']);
        $this->set_port($config['port']);
    }

    public function setHost($host) {
        $this->set_host($host);

        return $this;
    }

    public function setPort($port) {
        $this->set_port($port);

        return $this;
    }

    public function setAuth($username, $password) {

        $this->username = $username;
        $this->password = $password;

        $this->password_auth($username, $password);

        return $this;
    }

    public function api1($user, $module, $function, $args = array()) {

        if (!isset($user) || !isset($module) || !isset($function)) {
            $msg = "api1 requires that a username, module and function are passed to it";

            return array('reason' => $msg, 'result' => 0);
        }
        if (!is_array($args)) {
            $msg = "api2_query requires that an array is passed to it as the 4th parameter";

            return array('reason' => $msg, 'result' => 0);
        }

        $result = $this->api1_query($user, $module, $function, $args = array());

        return $this->returnResult($result);
    }

    public function api2($user, $module, $function, $args = array()) {

        if (!isset($user) || !isset($module) || !isset($function)) {
            $msg = "api2 requires that a username, module and function are passed to it";

            return array('reason' => $msg, 'result' => 0);
        }
        if (!is_array($args)) {
            $msg = "api2_query requires that an array is passed to it as the 4th parameter";

            return array('reason' => $msg, 'result' => 0);
        }

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
            return array('reason' => 'Please sent main domain first', 'result' => 0);
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
            return array('reason' => 'Please sent main domain first', 'result' => 0);
        }

        $result = $this->api2_query($username, 'SubDomain', 'delsubdomain', array(
                'domain' => $subdomain . '.' . $domain,
            )
        );

        return $this->returnResult($result);
    }

    public function createdb($db_name) {

        if (!isset($db_name) || empty($db_name)) {
            $msg = "database name is  required.";

            return array('reason' => $msg, 'result' => 0);
        }

        $name_length = 54 - strlen($this->username);

        $db_name       = str_replace($this->username . '_', '', $this->slug($db_name, '_'));
        $database_name = $this->username . "_" . $db_name;

        if (strlen($db_name) > $name_length || strlen($db_name) < 4) {
            return array('reason' => 'Database name should be greater than 4 and less than ' . $name_length . ' characters.', 'result' => 0);
        }

        $result = $this->api2_query($this->username, "MysqlFE", "createdb", array('db' => $database_name));

        return $this->returnResult($result);
    }

    public function checkdbuser($db_user) {

        if (!isset($db_user) || empty($db_user)) {
            $msg = "Database username is  required.";

            return array('reason' => $msg, 'result' => 0);
        }

        $dbuser = $this->username . '_' . ($db_user ? str_replace($this->username . '_', '', $db_user) : "myadmin");

        $user = $this->api2_query($this->username, "MysqlFE", "dbuserexists", array('dbuser' => $dbuser));

        return $this->returnResult($user);
    }

    public function createdbuser($db_user, $db_pass) {

        if (!isset($db_user) || !isset($db_pass)) {
            $msg = "Database username and password is required.";

            return array('reason' => $msg, 'result' => 0);
        }

        if (!$db_user || !$db_pass) {
            return array('reason' => 'Please sent database username and password.', 'result' => '0');
        }

        $user_length = 16 - strlen($this->username);
        $db_user     = str_replace($this->username . '_', '', $this->slug($db_user, '_'));
        $dbuser      = $this->username . "_" . $db_user;

        if (strlen($db_user) > $user_length || strlen($db_user) < 4) {
            return array('reason' => 'Database username should be greater than 4 and less than ' . $user_length . ' characters.', 'result' => 0);
        }

        $validate = $this->checkPassword($db_pass);

        if ($validate != '') {
            return array('reason' => $validate, 'result' => '0');
        }

        $user = $this->checkdbuser($dbuser);

        if ($user['result'] == 1) {
            return array('reason' => 'Database user ' . $dbuser . ' already exist.', 'result' => '0');
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

        if (!isset($db_name) || !isset($db_user)) {
            $msg = "Database name and username is required.";

            return array('reason' => $msg, 'result' => 0);
        }

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

        return $this->returnResult($this->listaccts($search_type, $search));

    }

    public function accountDetials($username = '') {

        $username = $username ? $username : $this->username;

        return $this->accountsummary($username);
    }

    protected function returnResult($result) {

        if ($this->get_output() == 'xml') {
            $response = simplexml_load_string($result, null, LIBXML_NOERROR | LIBXML_NOWARNING);

            if ($response) {
                $json   = json_encode($response);
                $result = json_decode($json, TRUE);
            }
        } else if ($this->get_output() == 'json') {
            $result = json_decode($result, TRUE);
        } else {
            $json   = json_encode($result);
            $result = json_decode($json, TRUE);
        }

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

                return array('reason' => $reason, 'result' => (int)$status);
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

                return array('reason' => $reason, 'result' => (int)$data);
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
}
