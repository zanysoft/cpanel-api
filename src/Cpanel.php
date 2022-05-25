<?php

namespace ZanySoft\Cpanel;

use Config;
use Exception;

class Cpanel extends xmlapi
{
    protected $config;

    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * All parameters to this function are optional and can be set via the accessor functions or constants
     *
     * @param string $host The host to perform queries on
     * @param string $username The username to authenticate as
     * @param string $password The password to authenticate with
     * @throws Exception
     */
    public function __construct($host = null, $username = null, $password = null)
    {
        $config = Config::get('cpanel');

        $host = $host ?: $config['ip'];
        $username = $username ?: $config['username'];
        $password = $password ?: $config['password'];

        if (!$host) {
            throw new Exception('Host IP not defined.');
        }

        $this->setAuth($username, $password);
        $this->setHost($host);
        $this->set_debug($config['debug']);
        $this->setPort($config['port']);

        parent::__construct($host, $username, $password);
    }

    /**
     * @return $this
     */
    public function make($host = null, $username = null, $password = null): Cpanel
    {
        if ($host) {
            $this->set_host($host);
        }
        if ($username) {
            $this->set_user($username);
        }
        if ($password) {
            $this->set_password($password);
        }
        return $this;
    }

    /**
     * @param $host
     * @return $this
     */
    public function setHost($host): Cpanel
    {
        $this->set_host($host);

        return $this;
    }

    /**
     * @param $port
     * @return $this
     * @throws Exception
     */
    public function setPort($port): Cpanel
    {
        $this->set_port($port);

        return $this;
    }

    /**
     * @param $username
     * @param $password
     * @return $this
     */
    public function setAuth($username, $password): Cpanel
    {
        $this->username = $username;
        $this->password = $password;

        $this->password_auth($username, $password);

        return $this;
    }

    /**
     * @param $user
     * @param $module
     * @param $function
     * @param $args
     * @return array|mixed
     */
    public function api1($user, $module, $function, $args = array())
    {
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

    /**
     * @param $user
     * @param $module
     * @param $function
     * @param $args
     * @return array|mixed
     */
    public function api2($user, $module, $function, $args = array())
    {

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

    /**
     * @param $subdomain
     * @param $username
     * @param $subdomain_dir
     * @param $main_domain
     * @return array|mixed
     */
    public function createSubdomain($subdomain, $username = '', $subdomain_dir = '', $main_domain = '')
    {

        $subdomain_dir = $subdomain_dir ? $subdomain_dir : config('cpanel.subdomain_dir');
        $username = $username ? $username : $this->username;
        $domain = $main_domain ? $main_domain : config('cpanel.domain');

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
                'domain' => $subdomain,
                'rootdomain' => $domain,
                'dir' => '/public_html/' . $subdomain_dir,
                'disallowdot' => 1
            )
        );

        return $this->returnResult($result);
    }

    /**
     * @param string $subdomain
     * @param string $main_domain
     * @return array|mixed
     */
    public function removeSubdomain(string $subdomain, string $main_domain = '')
    {

        $username = $this->username;
        $domain = $main_domain ? $main_domain : config('cpanel.domain');

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

    /**
     * @param string $db_name
     * @return array|mixed
     */
    public function createdb(string $db_name)
    {

        if (!isset($db_name) || empty($db_name)) {
            $msg = "database name is  required.";

            return array('reason' => $msg, 'result' => 0);
        }

        $name_length = 54 - strlen($this->username);

        $db_name = str_replace($this->username . '_', '', $this->slug($db_name, '_'));
        $database_name = $this->username . "_" . $db_name;

        if (strlen($db_name) > $name_length || strlen($db_name) < 4) {
            return array('reason' => 'Database name should be greater than 4 and less than ' . $name_length . ' characters.', 'result' => 0);
        }

        $result = $this->api2_query($this->username, "MysqlFE", "createdb", array('db' => $database_name));

        return $this->returnResult($result);
    }

    /**
     * @param string $db_user
     * @return array|mixed
     */
    public function checkdbuser(string $db_user)
    {
        if (!isset($db_user) || empty($db_user)) {
            $msg = "Database username is  required.";

            return array('reason' => $msg, 'result' => 0);
        }

        $dbuser = $this->username . '_' . ($db_user ? str_replace($this->username . '_', '', $db_user) : "myadmin");

        $user = $this->api2_query($this->username, "MysqlFE", "dbuserexists", array('dbuser' => $dbuser));

        return $this->returnResult($user);
    }

    /**
     * @param $db_user
     * @param $db_pass
     * @return array|mixed|string[]
     */
    public function createdbuser($db_user, $db_pass)
    {

        if (!isset($db_user) || !isset($db_pass)) {
            $msg = "Database username and password is required.";

            return array('reason' => $msg, 'result' => 0);
        }

        if (!$db_user || !$db_pass) {
            return array('reason' => 'Please sent database username and password.', 'result' => '0');
        }

        $user_length = 16 - strlen($this->username);
        $db_user = str_replace($this->username . '_', '', $this->slug($db_user, '_'));
        $dbuser = $this->username . "_" . $db_user;

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

    /**
     * @param string $db_name
     * @param string $db_user
     * @param string $privileges
     * @return array|mixed
     */
    protected function setdbuser(string $db_name, string $db_user, string $privileges = '')
    {

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

    /**
     * @param string $search_type
     * @param string $search
     * @return array|mixed
     */
    public function accountsList(string $search_type = '', string $search = '')
    {
        return $this->returnResult($this->listaccts($search_type, $search));
    }

    /**
     * @param string $username
     * @return array|bool|mixed|SimpleXMLElement|null
     */
    public function accountDetials(string $username = '')
    {
        $username = $username ? $username : $this->username;

        return $this->accountsummary($username);
    }

    /**
     * @param $result
     * @return array|mixed
     */
    protected function returnResult($result)
    {

        if ($this->get_output() == 'xml') {
            $response = simplexml_load_string($result, null, LIBXML_NOERROR | LIBXML_NOWARNING);

            if ($response) {
                $json = json_encode($response);
                $result = json_decode($json, TRUE);
            }
        } else if ($this->get_output() == 'json') {
            $result = json_decode($result, TRUE);
        } else {
            $json = json_encode($result);
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
                    $status = $data;

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

    /**
     * @param $title
     * @param string $separator
     * @return string
     */
    protected function slug($title, $separator = '-')
    {
        // Convert all dashes/underscores into separator
        $flip = $separator == '-' ? '_' : '-';

        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', mb_strtolower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

        return trim($title, $separator);
    }

    /**
     * @param $pwd
     * @return string
     */
    protected function checkPassword($pwd)
    {
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
