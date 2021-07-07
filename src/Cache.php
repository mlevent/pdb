<?php

namespace Mlevent;

class Cache
{
    protected $cacheTime;
    protected $cachePath;
    protected $cacheHash;

    public function __construct($path, $time){
        $this->cacheTime = $time;
        $this->cachePath = $path;
        if(!file_exists($this->cachePath))
            mkdir($this->cachePath, 0755);
    }

    public function get(){
        if(file_exists($this->cacheHash)){
            $cachedResults = unserialize(file_get_contents($this->cacheHash));
            if(time() > $cachedResults['time'])
                unlink($this->cacheHash);
            return $cachedResults;
        }
		return;
	}

    public function set($data){
        if(!file_exists($this->cacheHash)){
            $saveData = serialize([
                'data' => $data, 
                'rows' => sizeof($data), 
                'time' => time() + $this->cacheTime
            ]);
            file_put_contents($this->cacheHash, $saveData);
        }
	}

    public function hash(){
		$this->cacheHash = $this->cachePath . '/' . md5(implode(func_get_args()));
	}
}