# 1 功能介绍
PHPCodeCoverage是一个基于xdebug检测php代码覆盖的工具，它能够应用于功能测试，接口测试，单元测试等任何php代码环境。它能够通过Web页面和Cli终端两种途经展示代码覆盖的结果。
# 2 安装
## 2.1 安装xdebug
### 2.1.1 检测出php模块是否包含xdebug
由于PHPCodeCoverage是基于xdebug，所以必须安装xdebug。如果如下命令，检测出php模块包含Xdebug则不用安装。

```Bash
$ php -m | grep Xdebug
```

### 2.1.2 安装xdebug

```Bash
# wget http://www.xdebug.org/files/xdebug-2.2.5.tgz
# tar zxvf xdebug-2.2.5.tgz 
# cd xdebug-2.2.5
# phpize
# ./configure --with-php-config=/usr/local/php5/bin/php-config
# make
# make install
```

### 2.1.2 php中配置xdebug
vim /etc/php.ini

    [xdebug]
    zend_extension="/usr/local/php5/lib/php/extensions/no-debug-non-zts-20121212/xdebug.so"
    xdebur.cli_color=1
    xdebug.force_display_errors = 1
    xdebug.profiler_enable = on
    xdebug.default_enable = on
    xdebug.trace_output_dir="/tmp/xdebug"
    xdebug.trace_output_name = trace.%c.%p
    xdebug.profiler_output_dir="/tmp/xdebug"
    xdebug.profiler_output_name="cachegrind.out.%s"
    xdebug.show_exception_trace = Off
    xdebug.collect_vars         = On
    xdebug.collect_return       = On
    xdebug.max_nesting_level = 10000
    xdebug.dump_globals= on
    xdebug.show_local_vars=on
    xdebug.collect_params=2


### 2.1.3 创建xdebug输出目录

```Bash
# mkdir /tmp/xdebug
# chmod -R 777 /tmp/xdebug
```
## 2.2 安装PHPCodeCoverage
```
#创建PHPCodeCoverage项目目录
# mkdir -p /home/dev/svn/avatar/PHPCodeCoverage

#下载源码 
# wget https://github.com/cj58/PHPCodeCoverage/archive/master.zip

#解压
# unzip master.zip

#将代码拷贝到项目目录
# cp PHPCodeCoverage-master/* /home/dev/svn/avatar/PHPCodeCoverage -r
```
# 3 配置
配置PHPCodeCoverage的数据目录，默认是./data目录。也可以建立/tmp/pcc/data。主要是为了确保后面web用户能够对data目录有操作权限。
```
# mkdir -p /home/dev/svn/avatar/PHPCodeCoverage/data
# chmod -R 777 /home/dev/svn/avatar/PHPCodeCoverage/data
```
修改配置目录
vim config.php
```php
<?php
/*
 * this file is config for PHPCodeCoverage
 *
 * @link https://github.com/cj58/PHPCodeCoverage
 * @author cj
 * @copyright 2017 cj
 *
 */

$configs = array();

//data dir for pcc
//$configs['dataDir'] = '/tmp/pcc/data';
$configs['dataDir'] = dirname(__FILE__).'/data';
```
# 4 使用
vim test.php
```php
<?php
include_once("/home/dev/svn/avatar/PHPCodeCoverage/Pcc.php");
$p = new Pcc('testProject');
$p->start();

//..... you want Coverage Code,start
$a = 1;
if($a)
{
    $out = 'a = 1';
}
else
{
    $out = 'a <> 1';
}
//....you want Coverage Code,end
//....

$p->stop();

?>
```
`$ php test.php`
![data_list](https://github.com/cj58/img/blob/master/PHPCodeCoverage/data_list.png)
