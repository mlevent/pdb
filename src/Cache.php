<?php

namespace Mlevent;

class Cache
{
    protected $cacheTime;
    protected $cachePath;
    protected $cacheFile;

    public function __construct($path, $time){
        $this->cacheTime = $time;
        $this->cachePath = $path;
        if(!file_exists($this->cachePath))
            mkdir($this->cachePath, 0755);
    }

    public function get(){
        if(file_exists($this->cacheFile)){
            $cachedResults = unserialize(file_get_contents($this->cacheFile));
            if(time() > $cachedResults['time'])
                unlink($this->cacheFile);
            return $cachedResults;
        }
		return;
	}

    public function set($data){
        if(!file_exists($this->cacheFile)){
            $saveData = serialize([
                'data' => $data, 
                'rows' => sizeof((array)$data), 
                'time' => time() + $this->cacheTime
            ]);
            file_put_contents($this->cacheFile, $saveData);
        }
	}

    public function setFile(){
		$this->cacheFile = $this->cachePath . '/' . md5(implode(func_get_args()));
	}
}