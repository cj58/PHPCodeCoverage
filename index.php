<?php
/*
 * show for Web or Cli
 *
 * @link https://github.com/cj58/PHPCodeCoverage
 * @author cj
 * @copyright 2017 cj
 *
 */
class IndexController
{/*{{{*/
    const RESPONSE_OK = 'OK';
    public $dataDir;

    public function __construct()
    {/*{{{*/
        include_once(dirname(__FILE__).'/config.php');
        $this->dataDir = $configs['dataDir'];
    }/*}}}*/

    public function run() 
    {/*{{{*/
        $reqeust = $this->getReqeust();
        $action = $reqeust['action'];
        $action = $action ? $action : 'index';
        $modelMethod = $action.'Model';
        if(false == method_exists($this,$modelMethod))
        {
            echo "ERROR,$modelMethod not found!\n";
            $action = 'help';     
            $modelMethod = $action.'Model';
        }
        $response = $this->$modelMethod($reqeust);
        if(self::RESPONSE_OK != $response['errorMsg'])
        {
            echo "ERROR,".$response['errorMsg'];
            return;
        }
        $env = $reqeust['env'];
        $viewMethod = $action.$env.'View';
        $this->$viewMethod($response);

    }/*}}}*/

    /**
     * Reqeust param 
     * 
     * @access public
     * @return void
     */
    public function getReqeust()
    {/*{{{*/
        $reqeust = array();
        $reqeust['env'] = 'cli' == php_sapi_name() ? 'Cli' : 'Web';
        $reqeust['action'] = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $reqeust['pccFile'] = isset($_REQUEST['pccFile']) ? $_REQUEST['pccFile'] : '';
        $reqeust['phpFile'] = isset($_REQUEST['phpFile']) ? $_REQUEST['phpFile'] : '';

        $options = getopt('a:c:p:', array("action:", "pccFile:","phpFile:"));

        $reqeust['action'] = empty($reqeust['action']) && isset($options['a']) 
            ? $options['a']: $reqeust['action'];
        $reqeust['action'] = empty($reqeust['action']) && isset($options['action']) 
            ? $options['action']: $reqeust['action'];

        $reqeust['pccFile'] = empty($reqeust['pccFile']) && isset($options['c']) 
            ? $options['c']: $reqeust['pccFile'];
        $reqeust['pccFile'] = empty($reqeust['pccFile']) && isset($options['pccFile']) 
            ? $options['pccFile']: $reqeust['pccFile'];

        $reqeust['phpFile'] = empty($reqeust['phpFile']) && isset($options['p']) 
            ? $options['p']: $reqeust['phpFile'];
        $reqeust['phpFile'] = empty($reqeust['phpFile']) && isset($options['phpFile']) 
            ? $options['phpFile']: $reqeust['phpFile'];
        return $reqeust;
    }/*}}}*/

    /**
     * index Model 
     * 
     * @param mixed $reqeust 
     * @access public
     * @return void
     */
    public function indexModel($reqeust)
    {/*{{{*/
        $dir = $this->dataDir;
        $res = array();
        $res['errorMsg'] = self::RESPONSE_OK;
        $res['data'] = array();
        if(empty($dir))
        {
            $res['errorMsg'] = 'dataDir is empty string';
            return $res;    
        }

        if(false == is_dir($dir))
        {
            $res['errorMsg'] = "${dir} is not dir";
            return $res;    
        }

        $fileList = array();
        $sortKey = array();
        if ($dh = opendir($dir))
        {
            while (($file = readdir($dh)) !== false)
            {
                $filePath = $dir .'/'. $file;
                if('file' != filetype($filePath))
                {
                    continue;
                }
                $fileCtime = date('Y-m-d H:i:s',filectime($filePath));
                $sortKey[] =  $fileCtime;
                $fileList[] = array('file'=> $file,'fileCtime' => $fileCtime);
            }
            closedir($dh);
        }
        array_multisort($sortKey,SORT_STRING,SORT_DESC,$fileList);
        $res['data'] = $fileList;
        return $res;
    }/*}}}*/

    /**
     * index CliView
     * 
     * @param mixed $response 
     * @access public
     * @return void
     */
    public function indexCliView($response)
    {/*{{{*/
        if(self::RESPONSE_OK != $response['errorMsg'])
        {
            return;
        }
        $data = $response['data'];
        if(false == is_array($data))
        {
            return;    
        }
        foreach($data as $row)
        {
            echo $row['file'].' '.$row['fileCtime']."\n";        
        }
    }/*}}}*/

    /**
     * index WebView
     * 
     * @param mixed $response 
     * @access public
     * @return void
     */
    public function indexWebView($response)
    {/*{{{*/
        if(self::RESPONSE_OK != $response['errorMsg'])
        {
            return;
        }
        $data = $response['data'];
        if(false == is_array($data))
        {
            return;    
        }
        echo "<html><head><title>phpCodeCoverage</title></head><body>";
        foreach($data as $row)
        {
            $html = '<a href=index.php?action=dataInfo&pccFile='.$row['file'].'>';
            $html .= $row['file'];
            $html .= '</a> ';
            $html .= $row['fileCtime'];
            $html .= '<br/>';
            echo $html;
        }
        echo '</body></html>';
    }/*}}}*/

    /**
     * dataInfo Model
     * 
     * @param mixed $reqeust 
     * @access public
     * @return void
     */
    public function dataInfoModel($reqeust)
    {/*{{{*/
        $res = array();
        $res['errorMsg'] = self::RESPONSE_OK;
        $res['data'] = array();
        $pccFile = $this->dataDir.'/'.$reqeust['pccFile'];
        $ret = $this->_getPccObjecByFile($pccFile); 
        if(self::RESPONSE_OK != $ret['errorMsg'])
        {
            $res['errorMsg'] = $ret['errorMsg'];
            return $res;
        }
        $res['data']['pcc'] = $ret['data']['pcc'];
        $res['data']['pccFile'] = $reqeust['pccFile'];
        return $res;
    }/*}}}*/

    /**
     * get a pcc object from a file 
     * 
     * @param mixed $pccFile 
     * @access private
     * @return void
     */
    private function _getPccObjecByFile($pccFile)
    {/*{{{*/
        $res['errorMsg'] = self::RESPONSE_OK;
        $res['data'] = array();
        if(false == file_exists($pccFile))
        { 
            $res['errorMsg'] = "{$pccFile} is not exists";
            return $res;
        }
        $fp = fopen($pccFile,"r"); 
        if(!$fp)
        {
            $res['errorMsg'] = "open {$pccFile} error";
            return $res;
        }
        $str = fread($fp,filesize($pccFile));
        fclose($fp);
        include_once(dirname(__FILE__)."/Pcc.php");
        $pcc =  unserialize($str);
        if(false == $pcc instanceof Pcc)
        {
            $res['errorMsg'] = "{$pccFile} content is not class of Pcc";
            return $res;
        }
        $data = array();
        $data['pcc'] = $pcc;
        $res['data'] = $data;
        return $res;
    }/*}}}*/

    /**
     * dataInfo WebView 
     * 
     * @param mixed $response 
     * @access public
     * @return void
     */
    public function dataInfoWebView($response)
    {/*{{{*/
        if(self::RESPONSE_OK != $response['errorMsg'])
        {
            return;
        }
        $data = $response['data'];
        if(false == is_array($data))
        {
            return;    
        }
        $pcc = $data['pcc'];
        $pccFile = $data['pccFile'];
        $rows = $pcc->datas;

        $title = $pccFile;
        echo "<html><head><title>{$title}</title></head><body>";

        echo "project:{$pcc->project}<br/>";
        $startTime = $this->_microtimeFormat($pcc->startTime);
        echo "startTime:{$startTime}<br/>";
        $endTime = $this->_microtimeFormat($pcc->endTime);
        echo "endTime:{$endTime}<br/>";
        $consumeTime = number_format($pcc->consumeTime, 6, '.', '');
        echo "consumeTime:{$consumeTime}<br/>";
        echo "<br/>";

        foreach($rows as $key => $row)
        {
            $count = count($row);
            $html = '<a href=index.php?action=fileInfo&pccFile='.$pccFile.'&phpFile=';
            $html .= $key.'>'.$key.'</a>';
            $html .= " ${count}<br/>";
            echo $html;
        }
        echo '</body></html>';
    }/*}}}*/

    /**
     * dataInfo CliView 
     * 
     * @param mixed $response 
     * @access public
     * @return void
     */
    public function dataInfoCliView($response)
    {/*{{{*/
        if(self::RESPONSE_OK != $response['errorMsg'])
        {
            return;
        }
        $data = $response['data'];
        if(false == is_array($data))
        {
            return;    
        }
        $pcc = $data['pcc'];
        $pccFile = $data['pccFile'];
        $rows = $pcc->datas;

        $title = $pccFile;

        echo "project:{$pcc->project}\n";
        $startTime = $this->_microtimeFormat($pcc->startTime);
        echo "startTime:{$startTime}\n";
        $endTime = $this->_microtimeFormat($pcc->endTime);
        echo "endTime:{$endTime}\n";
        $consumeTime = number_format($pcc->consumeTime, 6, '.', '');
        echo "consumeTime:{$consumeTime}\n";
        echo "\n";

        foreach($rows as $key => $row)
        {

            $count = count($row);
            echo $key.' '.$count."\n";
        }
    }/*}}}*/

    /**
     * Format microtime
     * 
     * @param mixed $time 
     * @access private
     * @return void
     */
    private function _microtimeFormat($time)
    {/*{{{*/
        list($sec,$usec) = explode(".", $time);  
        $usec =  (float)('0.'.$usec);
        $usec = number_format($usec, 3, '.', '');
        list($zero,$usec) = explode(".", $usec);  
        return date('Y-m-d~H:i:s',$sec).'.'.$usec;
    }/*}}}*/

    /**
     * fileInfo Model 
     * 
     * @param mixed $reqeust 
     * @access public
     * @return void
     */
    public function fileInfoModel($reqeust)
    {/*{{{*/
        $res = array();
        $res['errorMsg'] = self::RESPONSE_OK;
        $res['data'] = array();
        $pccFile = $this->dataDir.'/'.$reqeust['pccFile'];
        $ret = $this->_getPccObjecByFile($pccFile); 
        if(self::RESPONSE_OK != $ret['errorMsg'])
        {
            $res['errorMsg'] = $ret['errorMsg'];
            return $res;
        }
        $pcc = $ret['data']['pcc'];
        $phpFile = $reqeust['phpFile'];
        $coverageHash = isset($pcc->datas[$phpFile]) ? $pcc->datas[$phpFile] : array();
        $fp = fopen($phpFile,"r");
        if(!$fp)
        {
            $res['errorMsg'] = "open {$phpFile} error";
            return $res;
        }

        $rowNum = 1;
        $lines = array();
        while (!feof($fp))
        {
            $line = fgets($fp);
            $isCoverage = isset($coverageHash[$rowNum]) ? 1 : 0 ;
            $lines[] = array('rowNum' => $rowNum,'isCoverage' => $isCoverage,'line' => $line);
            $rowNum++;
        }
        fclose($fp);
        $res['data']['lines'] = $lines;
        $res['data']['phpFile'] = $phpFile;
        return $res;
    }/*}}}*/

    /**
     * fileInfo web View 
     * 
     * @param mixed $response 
     * @access public
     * @return void
     */
    public function fileInfoWebView($response)
    {/*{{{*/
        if(self::RESPONSE_OK != $response['errorMsg'])
        {
            return;
        }
        $lines = $response['data']['lines'];
        if(false == is_array($lines))
        {
            return;    
        }

        $title = basename($response['data']['phpFile']);
        echo "<html><head><title>{$title}</title></head><body>";

        $rowNumLen = strlen(count($lines));
        foreach($lines as $line)
        {
            $str = $line['line'];

            $html = sprintf("%{$rowNumLen}d%s%s",$line['rowNum'],' ',$str);
            $html = htmlspecialchars($html);
            $html = str_replace(' ','&nbsp',$html);
            $html = str_replace("\t",'&nbsp&nbsp&nbsp&nbsp',$html);
            if($line['isCoverage'])
            {
                $html = '<font style="background:green" bgcolor="green">'.$html."</font>";
            }

            echo $html.'<br/>';
        }
        echo '</body></html>';
    }/*}}}*/

    /**
     * fileInfo CliView 
     * 
     * @param mixed $response 
     * @access public
     * @return void
     */
    public function fileInfoCliView($response)
    {/*{{{*/
        if(self::RESPONSE_OK != $response['errorMsg'])
        {
            return;
        }
        $lines = $response['data']['lines'];
        if(false == is_array($lines))
        {
            return;    
        }


        $rowNumLen = strlen(count($lines));
        foreach($lines as $line)
        {
            $str = $line['line'];
            $isCoverageStr = $line['isCoverage'] ? '+' : ' ';
            echo sprintf("%{$rowNumLen}d%s%s",$line['rowNum'],$isCoverageStr,$str);
        }
    }/*}}}*/

    /**
     * help Model
     * 
     * @param mixed $reqeust 
     * @access public
     * @return void
     */
    public function helpModel($reqeust)
    {/*{{{*/
        $res = array();
        $res['errorMsg'] = self::RESPONSE_OK;
        $res['data'] = array();
        return $res;
    }/*}}}*/

    /**
     * help web View
     * 
     * @param mixed $reqeust 
     * @access public
     * @return void
     */
    public function helpWebView($reqeust)
    {/*{{{*/
        echo "https://github.com/cj58/PHPCodeCoverage";
    }/*}}}*/

    /**
     * help cli View 
     * 
     * @param mixed $reqeust 
     * @access public
     * @return void
     */
    public function helpCliView($reqeust)
    {/*{{{*/
        echo "Usage: php index.php [options...] \n";
        echo "@link https://github.com/cj58/PHPCodeCoverage\n";
        echo "-a/--action    do action.index/dataInfo/fileInfo/help\n";
        echo "-c/--pccFile   pccFile path\n";
        echo "-p/--phpFile   phpFile\n";
    }/*}}}*/

}/*}}}*/

$index = new IndexController();
$index->run();
