<?php
namespace Appdemo\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        echo url('index/index');

        echo PHP_EOL;

        print_r(config('DEFAULT_FILTER'));

        echo PHP_EOL;

        print_r(config('simple'));
    }

    public function testAction()
    {
        echo url('index/test');

        echo PHP_EOL;

        $Test = new \Appdemo\Library\Test();

        console(url('index/test'));
    }
}