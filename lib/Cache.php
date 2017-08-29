<?php

namespace Kss;

/**
 * Class Cache
 *
 * @package Kss
 */
class Cache
{
    /**
     * @var string
     */
    protected $path = __DIR__ . '/../cache';

    /**
     * Cache constructor.
     */
    public function __construct()
    {
        $this->prepare();
    }

    /**
     * Prepare cache folder
     */
    public function prepare()
    {
        if (!file_exists($this->path)) {
            umask(0000);
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Get file path with cache key
     *
     * @param $key
     * @return string
     */
    public function getFilePath($key)
    {
        return $this->path . '/' . $key;
    }

    /**
     * Set cache data
     *
     * @param $key
     * @param $data
     */
    public function set($key, $data) {
        $file = $this->getFilePath($key);

        if ($handle = fopen($file, 'w')) {
            fwrite($handle, $data);
            fclose($handle);
        }
    }

    /**
     * Get cache data
     *
     * @param $key
     * @return bool|string
     */
    public function get($key)
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            $data = file_get_contents($file);

            if (!empty($data)) {
                return $data;
            }
        }

        return false;
    }

    /**
     * Remove one cache file
     *
     * @param $key
     * @return bool
     */
    public function remove($key)
    {
        return unlink($this->getFilePath($key));
    }

    /**
     * Clean all cache
     */
    public function clean()
    {
        $directory = scandir($this->path);

        if (!empty($directory)) {
            foreach ($directory as $item) {
                if ($item !== '.' && $item !== '..') {
                    $this->remove($item);
                }
            }
        }
    }
}