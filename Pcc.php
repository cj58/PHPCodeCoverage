<?php
/*
 * Record code coverage
 *
 * @link https://github.com/cj58/PHPCodeCoverage
 * @author cj
 * @copyright 2017 cj
 *
 */
class Pcc
{/*{{{*/
    public $project;
    public $startTime;
    public $endTime;
    public $consumeTime;
    public $dataDir;
    public $needFiles = array();
    public $needDirs = array();
    public $datas = array();

    public function __construct($project)
    {/*{{{*/
        $this->project = $project; 
        include_once(dirname(__FILE__).'/config.php');
        $this->dataDir = $configs['dataDir'];
    }/*}}}*/

    /**
     * start 
     * 
     * @access public
     * @return void
     */
    public function start()
    {/*{{{*/
        $this->startTime = $this->_microtimeFloat();
        xdebug_start_code_coverage();     
    }/*}}}*/

    /**
     * stop 
     * 
     * @access public
     * @return void
     */
    public function stop()
    {/*{{{*/
        $this->endTime = $this->_microtimeFloat();
        $this->consumeTime = $this->endTime - $this->startTime;
        $datas = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        $this->datas = $this->_choiceFile($datas);

        $this->_writeData();
    }/*}}}*/

    /**
     * write Data to file 
     * 
     * @param array $datas 
     * @access private
     * @return void
     */
    private function _writeData()
    {/*{{{*/

        if(false == is_dir($this->dataDir))
        {
            mkdir($this->dataDir,0777,true);     
        }

        $key = md5($this->project.$this->startTime.$this->endTime.$this->consumeTime);
        $pccFile = $this->project.'.'.$key.'.pcc'; 
        error_log(serialize($this),3,$this->dataDir.'/'.$pccFile);
    }/*}}}*/

    /**
     * choice File 
     * 
     * @param array $datas 
     * @access private
     * @return void
     */
    private function _choiceFile($datas)
    {/*{{{*/
        if(false == is_array($datas))
        {
            return array();
        }

        $ret = array();
        foreach($datas as $key => $items)
        {
            $fileName = basename($key);
            if(false == empty($this->needFiles) && is_array($this->needFiles))
            {
                if(in_array(strtolower($fileName),$this->needFiles))
                {
                    $ret[$key] = $items;
                    unset($datas[$key]);
                }
                continue;
            }

            $ret[$key] = $items;
            unset($datas[$key]);
        }
        return $ret; 
    }/*}}}*/

    /**
     * add your need files 
     * 
     * @param array $files 
     * @access public
     * @return void
     */
    public function addNeedFiles(array $files)
    {/*{{{*/
       $this->needFiles = array_map('strtolower',array_unique(array_merge($this->needFiles,$files))); 
    }/*}}}*/

    /**
     * add Need Dirs  
     * 
     * @param array $dirs 
     * @access public
     * @return void
     */
    public function addNeedDirs(array $dirs)
    {/*{{{*/
       $this->needDirs = array_map('strtolower',array_unique(array_merge($this->needDirs,$dirs))); 
    }/*}}}*/

    /**
     * microtime to Float 
     * 
     * @access private
     * @return void
     */
    private function _microtimeFloat()
    {/*{{{*/
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }/*}}}*/
    
}/*}}}*/
