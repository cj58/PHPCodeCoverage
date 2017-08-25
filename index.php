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
        $reqeust['findPath'] = isset($_REQUEST['findPath']) ? $_REQUEST['findPath'] : '';
        $reqeust['isExpand'] = isset($_REQUEST['isExpand']) ? $_REQUEST['isExpand'] : '';

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
        $res['data']['title'] = $reqeust['pccFile'];
        $res['data']['findPath'] = $reqeust['findPath'];
        $res['data']['isExpand'] = $reqeust['isExpand'];
        $res['data']['action'] = $reqeust['action'];
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
     * infoWebViewComm 
     * 
     * @param mixed $response 
     * @access private
     * @return void
     */
    private function _infoWebViewComm($response)
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
        $pccFile = $data['pccFile'];
        $pcc = $data['pcc'];

        $title = $data['title'];

        echo '<html>
                <head>
                  <title>'.$title.'</title>
                   <meta http-equiv=Content-Type content="text/html;charset=gb2312">
                   <meta name="renderer" content="webkit">
                </head>
                <body>';
        
        echo "<table align='center'>";
        echo "<tr><td align='right'>Project : </td><td>{$pcc->project}</td>";
        $startTime = $this->_microtimeFormat($pcc->startTime);
        echo "<tr><td align='right'>StartTime : </td><td>{$startTime}</td>";
        $endTime = $this->_microtimeFormat($pcc->endTime);
        echo "<tr><td align='right'>EndTime : </td><td>{$endTime}</td>";
        $consumeTime = number_format($pcc->consumeTime, 6, '.', '');
        echo "<tr><td align='right'>ConsumeTime : </td><td>{$consumeTime}</td>";
        echo "</table>";
        echo "<hr>";
      
        $findPath = $data['findPath'];
        echo '<font>'; 
        if('dataInfo' == $data['action'])
        {
            $newIsExpand = $data['isExpand'] ? 0 : 1;
            $btn = $newIsExpand ? '+Expand' : '-Folded';
            echo '[<a href=index.php?action=dataInfo&pccFile='.$pccFile.'&findPath='.$findPath
                 .'&isExpand='.$newIsExpand.'>'.$btn.'</a>]&nbsp&nbsp';
        }
        if(false == empty($findPath))
        {
           $tmpPos = 0;
           $k = 0;
           while(1)
           {
               $pos = strpos($findPath,DIRECTORY_SEPARATOR,$tmpPos);
               if(false === $pos)
               {
                   $tmpFindPath = $findPath;
                   $display = substr($findPath,$tmpPos);
                   if($findPath == $tmpFindPath)
                   {
                        echo $display;
                   }
                   else
                   {
                       $html = '<a href=index.php?action=fileInfo&pccFile='.$pccFile.'&findPath=';
                       $html .= $pccFile.'&phpFile=';
                       $html .= $tmpFindPath.'>'.$display.'</a>';
                       echo $html;
                   }
                   break;
               }
               else
               {

                   $display = substr($findPath,$tmpPos,$pos - $tmpPos + strlen(DIRECTORY_SEPARATOR));
                   $tmpPos = $pos + strlen(DIRECTORY_SEPARATOR);
                   $tmpFindPath = substr($findPath,0,$tmpPos);

                   if($findPath == $tmpFindPath)
                   {
                        echo $display;
                   }
                   else
                   {
                       $html = '<a href=index.php?action=dataInfo&pccFile='.$pccFile.'&findPath=';
                       $html .= $tmpFindPath.'>'.$display.'</a>&nbsp';
                       echo $html;
                   }
               }
           }
        }
        echo "</font>";
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
        $this->_infoWebViewComm($response);

        $pccFile = $data['pccFile'];
        $pcc = $data['pcc'];
        $findPath = $data['findPath'];
        $rows = $pcc->datas;

        echo "<ul>";
        if($data['isExpand'])
        {
            foreach($rows as $file => $item)
            {
                if(false == empty($findPath) && false === strpos($file,$findPath))
                {
                    continue;
                }
                $html = '<a href=index.php?action=fileInfo&pccFile='.$pccFile.'&findPath=';
                $html .= $file.'&phpFile=';
                $html .= $file.'>'.substr($file,strlen($findPath)).'</a>&nbsp&nbsp'.count($item);
                echo "<li>".$html."</li>";
            }
        }
        else
        {
            $out = $this->_scanDirList(array_keys($rows),$findPath); 
            foreach($out as $key => $type)
            {
                if('file' == $type)
                {
                    $tmpPHPFile = $findPath.$key;
                    $count = isset($rows[$tmpPHPFile]) ? count($rows[$tmpPHPFile]) : 0;
                    $html = '<a href=index.php?action=fileInfo&pccFile='.$pccFile.'&findPath=';
                    $html .= $findPath.$key.'&phpFile=';
                    $html .= $tmpPHPFile.'>'.$key.'</a>&nbsp&nbsp'.$count;
                }
                else
                {
                    $html = '<a href=index.php?action=dataInfo&pccFile='.$pccFile.'&findPath=';
                    $html .= $findPath.$key.'>'.$key.'</a>';
                }
                echo "<li>".$html."</li>";
            }
        }
        echo "</ul>";
        echo '</body></html>';
    }/*}}}*/

    private function _scanDirList(array $files,$findPath = '')
    {/*{{{*/
       $out = array();
       foreach($files as $file)
       {
           if(empty($findPath))
           {
               $pos = strpos($file,DIRECTORY_SEPARATOR);     
               if(0 === $pos)
               {
                   $out[DIRECTORY_SEPARATOR] = 'dir';    
               }
               else
               {
                   $key = substr($file,0,$pos);     
                   $out[$key.DIRECTORY_SEPARATOR] = 'dir';
               }
           }
           else
           {
               $pos = strpos($file,$findPath);     
               if(0 !== $pos)
               {
                   continue;
               }
               $file = substr($file,strlen($findPath));                  
               $pos = strpos($file,DIRECTORY_SEPARATOR);     
               if(false !== $pos)
               {
                   $key = substr($file,0,$pos);
                   $out[$key.DIRECTORY_SEPARATOR] = 'dir';
               }
               else
               {
                   $out[$file] = 'file';
               }
           }
       }
       return $out;
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

        echo "    Project: {$pcc->project}\n";
        $startTime = $this->_microtimeFormat($pcc->startTime);
        echo "  StartTime: {$startTime}\n";
        $endTime = $this->_microtimeFormat($pcc->endTime);
        echo "    EndTime: {$endTime}\n";
        $consumeTime = number_format($pcc->consumeTime, 6, '.', '');
        echo "ConsumeTime: {$consumeTime}\n";
        echo "-----------------------------------------------------";
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
        $res['data']['pcc'] = $pcc;
        $res['data']['pccFile'] = $reqeust['pccFile'];
        $res['data']['phpFile'] = $phpFile;
        $res['data']['title'] = basename($phpFile);
        $res['data']['findPath'] = $reqeust['findPath'];
        $res['data']['isExpand'] = $reqeust['isExpand'];
        $res['data']['action'] = $reqeust['action'];
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

        $this->_infoWebViewComm($response);

        //$title = basename($response['data']['phpFile']);

        echo   '<link href="http://cdn.bootcss.com/highlight.js/8.0/styles/monokai_sublime.min.css" rel="stylesheet">  
                <script src="http://cdn.bootcss.com/highlight.js/8.0/highlight.min.js"></script>  
                <script >hljs.initHighlightingOnLoad();</script>';
                
        echo ' <pre>
                        <code class="php">';
        $rowNumLen = strlen(count($lines));
        foreach($lines as $line)
        {
            $str = $line['line'];

            $html = sprintf("%{$rowNumLen}d%s%s",$line['rowNum'],' ',$str);
            $html = mb_convert_encoding($html, "GBK", "auto");
            $html = htmlspecialchars($html,ENT_COMPAT,'GB2312');
            if($line['isCoverage'])
            {
                $html = '<font style="background:green" bgcolor="green">'.$html."</font>";
            }

            echo $html;
        }
        echo ' </code>
                    </pre>';
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
            if($line['isCoverage'])
            {
                echo sprintf("\033[1;32;40m%{$rowNumLen}d%s%s\033[0m",$line['rowNum'],$isCoverageStr,$str);
            }
            else
            {
                echo sprintf("%{$rowNumLen}d%s%s",$line['rowNum'],$isCoverageStr,$str);
            }

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
