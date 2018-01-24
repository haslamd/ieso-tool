<?php
/*
 * This file is part of ieso-tool - the ieso query and download tool.
 *
 * (c) LivITy Consultinbg Ltd, Enbridge Inc., Kevin Edwards
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace LivITy\IESO;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\TransferException;

Class Crawler
{
    /** @var array|null available class props */
    protected $props = [];

    protected $log;

    /**
     * Create class, seeds config
     *
     * @param $config configuration options
     * @param $logger logger instance
     * @return Crawler
     */
    public function __construct(Config $config, Logger $logger)
    {
        $this->log = $logger;
        $this->props['client'] = new Client([
            'base_uri' => \Env::get('IESO_BASE_URI')
        ]);
        return $this;
    }

    /**
     * Magic set method
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $method = 'set'. ucfirst($key);
        if (is_callable([$this, $method])) {
            $this->$method($value);
        } else {
            $this->props[$key] = $value;
        }
    }

    /**
     * Magic get method
     *
     * @param $key
     * @return  $this->props[$key]
     */
    public function __get($key)
    {
        $method = 'get' .  ucfirst($key);
        if (is_callable([$this, $method])) {
            return $this->$method();
        } else if (array_key_exists($key, $this->props)) {
            return $this->props[$key];
        }
    }

    public function recurse(String $path)
    {
        $paths = explode('/', $path);
        $result = $this->request($path);
        $this->props['stats'] = [];
        $this->log->get_logger()->info("Scanning Path $path");

        foreach ($result['files'] as $k => $v) {
            $storage = preg_replace('#^TIDAL/#', '\\' . \Env::get('IESO_ENBRIDGE_PATH'), $path);
            if (!file_exists($storage)) {
                mkdir($storage, 0777, true);
            }

            if ($v['isDirectory']) {
                $dir = $path . $v['fileName'] . '/';
                $this->log->get_logger()->info("Scanning Directory $dir");
                $this->recurse($dir);
            } else {
                if (!preg_match('/^(.*)_v[0-9]{1,2}\.[a-z]*$/', $v['fileName'])) {
                    $filePath = $storage .  $v['fileName'];

                    if (file_exists($filePath)) {
                        $file_time_disk = filemtime($filePath);
                        if (substr($v['lastModifiedTime'], 0, 10) != $file_time_disk) {
                            $this->log->get_logger()->info('updating file ' . $v['fileName']);
                            $this->client->request('GET', $path . $v['fileName'], [
                                'auth' => [\Env::get('IESO_AUTH_USER'), \Env::get('IESO_AUTH_PASSWORD')],
                                'sink' => $filePath
                            ]);
                            touch($filePath, substr($v['lastModifiedTime'], 0, 10), substr($v['lastModifiedTime'], 0, 10));
                            $this->props['stats'][$paths[1]]['updated']['files'][] = [ 'name' => $v['fileName'] ];
                            @$this->props['stats'][$paths[1]]['updated']['count'] += 1;
                        }
                    } else {
                        $this->props['stats'][$paths[1]]['created']['files'][] = [ 'name' => $v['fileName'] ];
                        @$this->props['stats'][$paths[1]]['created']['count'] += 1;
                        $this->log->get_logger()->info('Retrieving file  ' . $v['fileName']);
                        $this->client->request('GET', $path . $v['fileName'], [
                            'auth' => [\Env::get('IESO_AUTH_USER'), \Env::get('IESO_AUTH_PASSWORD')],
                            'sink' => $filePath
                        ]);
                        touch($filePath, substr($v['lastModifiedTime'], 0, 10), substr($v['lastModifiedTime'], 0, 10));
                    }
                }
            }
        }
        return $this->props['stats'];
    }

    public function getData(Array $paths)
    {
        foreach ($paths as $path) {
            $this->props['stats'] = $this->recurse($path);
        }
        return true;
    }

    protected function getFileData(Array $data, String $path, Int $count, Bool $dl = false)
    {
        $folder = explode('/', $path);
        $cnt = 1;

        foreach ($data['files'] as $k => $v) {
            if ($v['isRegularFile']) {
                if (!preg_match('/^(.*)_v[0-9]{1,2}\.[a-z]*$/', $v['fileName'])) {
                    $modified = substr($v['lastModifiedTime'], 0, 10);
                    $v['rfc_date'] = date('r', $modified);

                    $v['folder'] = $folder[1];
                    $v['storage'] = realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/' . $folder[1];

                    $last_update = strtotime('-1 day');
                    if ($modified > $last_update) {
                        $v['update_required'] = true;
                    } else {
                        $v['update_required'] = false;
                    }

                    $v['filePath'] = $path . '/' . $v['fileName'];
                    $v['file'] = preg_replace('#^TIDAL/#', '', $v['filePath']);

                    $this->props['data']['dir'][$v['folder']][$v['fileName']] = $v;

                    if ($count !== 0 && $cnt >= $count) {
                        break 1;
                    }
                    $cnt += 1;
                }
            }
        }

        foreach ($this->props['data']['dir'][$folder[1]] as $k => $v) {
            if ($dl) {
                if (!file_exists($v['storage']) && !is_dir($v['storage']) ) {
                    mkdir($v['storage']);
                }

                if (file_exists(realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/'. $v['file'])) {
                    $file_time_disk = filemtime(realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/'. $v['file']);
                    if (substr($v['lastModifiedTime'], 0, 10) == $file_time_disk) {
                    } else {
                        $this->client->request('GET', $v['filePath'], [
                            'auth' => [\Env::get('IESO_AUTH_USER'), \Env::get('IESO_AUTH_PASSWORD')],
                            'sink' => realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/'. $v['file']
                        ]);
                        touch(realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/'. $v['file'], substr($v['lastModifiedTime'], 0, 10), substr($v['lastModifiedTime'], 0, 10));
                    }
                } else {
                    $this->client->request('GET', $v['filePath'], [
                        'auth' => [\Env::get('IESO_AUTH_USER'), \Env::get('IESO_AUTH_PASSWORD')],
                        'sink' => realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/'. $v['file']
                    ]);
                    touch(realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/'. $v['file'], substr($v['lastModifiedTime'], 0, 10), substr($v['lastModifiedTime'], 0, 10));
                }
            }
        }

        return $this->props['data']['dir'][$folder[1]];
    }

    protected function getDirData(Array $data)
    {
        foreach ($data['files'] as $k => $v) {
            if ($v['isDirectory']) {
                $modified = substr($v['lastModifiedTime'], 0, 10);
                $v['rfc_date'] = date('r', $modified);

                $last_update = strtotime('-1 day');
                if ($modified > $last_update) {
                    $v['update_required'] = true;
                } else {
                    $v['update_required'] = false;
                }

                $this->props['data']['dir'][$v['fileName']] = $v;
            }
        }
        return $this->props['data']['dir'];
    }

    /**
     *
     */
    public function request(String $path, Int $extract = 2, Int $count = 0, Bool $dl = false)
    {
        try {
            $res = $this->client->request('GET', $path, [
                'auth' => [\Env::get('IESO_AUTH_USER'), \Env::get('IESO_AUTH_PASSWORD')]
            ]);
        } catch(TransferException $e) {
            throw new \Exception($e->getMessage());
        }

        $data = json_decode($res->getBody()->getContents(), true);

        switch ($extract) {
            case 0:
                return $this->getFileData($data, $path, $count, $dl);
                break;
            case 1:
                return $this->getDirData($data);
                break;
            case 2:
                return $data;
                break;
        }

    }

    public function getClients()
    {
        return $this->props['client'];
    }
}
