<?php

/**
 * dhPDO v1.3
 * @author daniel
 * @param array $config array with following elements dbtype,dbhost,dbport,dbuser,dbpass,dbname
 */
class dhpdo extends PDO
{
    public $debug = false;
    public $debugOpts = ['when' => 'post'];
    public $failed = false;

    public function __construct($config)
    {
        $dsn = $config['dbtype'].':host='.$config['dbhost'].';port='.$config['dbport'].';dbname='.$config['dbname'];
        $user = $config['dbuser'];
        $passwd = $config['dbpass'];
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,  // Connection timeout in seconds
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ];

        try {
            parent::__construct($dsn, $user, $passwd, $options);
            // Set MySQL session timeouts after connection
            $this->exec('SET SESSION wait_timeout = 30');
            $this->exec('SET SESSION interactive_timeout = 30');
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            // Log the connection error
            error_log('[ERROR] Database connection failed: ' . $e->getMessage());
            throw $e;  // Re-throw to allow proper error handling
        }
        if (isset($config['debug'])) {
            $this->setDebug($config['debug']);
        }
    }
    public function setDebug($debug, $opts = [])
    {
        $this->debug = $debug;
        if (count($opts) >= 1 && is_array($opts)) {
            foreach ($opts as $k => $v) {
                $this->setDebugOpts($k, $v);
            }
        }
    }
    public function setDebugOpts($item, $value)
    {
        echo "Set $item to $value\n";
        $this->debugOpts[$item] = $value;
    }
    public function bind(&$sth, $pos, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $sth->bindValue($pos, $value, $type);
    }
    public function run($sql, $arr = null, $arr2 = null)
    {
        $this->failed = false;
        if ($this->is_assoc($arr)) {
            $keys = '';
            $values = [];
            foreach ($arr as $k => $v) {
                $keys .= ",`$k`=?";
                $values[] = $v;
            }
            $keys = trim($keys, ',');
            $sql = str_replace('::replace::', $keys, $sql);
            $sth = $this->prepare($sql);
            foreach ($values as $k => $v) {
                $this->bind($sth, $k + 1, $v);
                $last = $k + 1;
            }
            if (is_array($arr2)) {
                foreach ($arr2 as $k => $v) {
                    $last++;
                    $this->bind($sth, $last, $v);
                }
            }
        } else {
            $sth = $this->prepare($sql);
            if (is_array($arr)) {
                foreach ($arr as $k => $v) {
                    $this->bind($sth, $k + 1, $v);
                }
            } elseif ($arr != null && $arr != '') {
                $this->bind($sth, 1, $arr);
            }
        }
        try {
            if ($this->debug && isset($this->debugOpts['when']) && $this->debugOpts['when'] == 'pre') {
                $this->printDebug($sql, $arr, 'pre', '');
            }
            $time_start = microtime(true);
            $sth->execute();
            $time_end = microtime(true);
            $exectime = '';
            if ($this->debug && isset($this->debugOpts['when']) && $this->debugOpts['when'] == 'post') {
                $time = $time_end - $time_start;
                $this->printDebug($sql, $arr, 'post', $time);
            }
        } catch (Exception $e) {
            $this->failed = true;
            $this->errors($e, $sql, $arr);
        }
        return $sth;
    }
    public function fetch($sth, $type = '')
    {
        if ($type == '') {
            $type = PDO::FETCH_ASSOC;
        } elseif ($type == 'array') {
            $type = PDO::FETCH_BOTH;
        }
        $row = $sth->fetch($type);
        return $row;
    }
    public function fetchAll($sth, $type = '')
    {
        if ($type == '') {
            $type = PDO::FETCH_ASSOC;
        } elseif ($type == 'array') {
            $type = PDO::FETCH_BOTH;
        }
        $result = $sth->fetchAll($type);
        return $result;
    }
    public function runFetchAll($sql, $arr = null)
    {
        $sth = $this->run($sql, $arr);
        $data = [];
        while ($row = $this->fetch($sth)) {
            $data[] = $row;
        }
        return $data;
    }
    public function getSingle($sql, $arr, $field = '')
    {
        $sth = $this->run($sql, $arr);
        $result = $this->fetch($sth);
        if ($field != '') {
            if (isset($result[$field])) {
                return $result[$field];
            }
        } else {
            return $result;
        }
        return false;
    }

    public function showColumns($table)
    {
        $columns = [];
        $sql = "show columns from `$table`";
        $sth = $this->run($sql);
        while ($row = $this->fetch($sth)) {
            $columns[$row['Field']] = $row;
        }
        if (!empty($columns)) {
            return $columns;
        }
        return false;
    }
    public function generateQuestions($array)
    {
        $string = '';
        foreach ($array as $v) {
            $string .= ',?';
        }
        return trim($string, ',');
    }

    public function printDebug($sql, $arr, $when = 'pre', $data = '')
    {
        $nl = '<br />';
        if (php_sapi_name() == 'cli') {
            $nl = "\n";
        } else {
            echo "<div class='debug'>";
        }
        if ($when == 'post') {
            $data = round($data, 5);
            echo "[DB] Executed in: $data seconds".$nl;
        }
        echo "[DB] Query: $sql".$nl;
        $string = '';
        $parms = true;
        if (is_array($arr) && count($arr) >= 1) {
            foreach ($arr as $k => $v) {
                $string .= " [$k]=>$v,";
            }
        } else {
            if (is_array($arr)) {
                $parms = false;
            } else {
                $string = $arr;
            }
        }
        $string = trim(trim($string), ',');
        if ($parms) {
            echo "[DB] Params: $string".$nl;
        }
        if (php_sapi_name() != 'cli') {
            echo '</div>';
        }
    }
    public function errors($e, $sql = '', $parms = '')
    {
        $arr['message'] = $e->getMessage();
        $arr['sql'] = $sql;
        $arr['parms'] = $parms;
        $arr['trace'] = $e->getTraceAsString();
        $return = print_r($arr, true);
        $nl = '<br />';
        if (php_sapi_name() == 'cli') {
            $nl = "\n";
            echo "[DB] ERROR:$nl";
            echo $return."$nl";
        } else {
            $return = nl2br($return);
            if ($this->debug) {
                echo "<div class='debug'>[DB] ERROR:$nl";
            }
            if ($this->debug) {
                echo $return.'</div>';
            }
        }
    }
    public function is_assoc($array)
    {
        if (!is_array($array)) {
            return false;
        }
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }
}
