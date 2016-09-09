<?php
namespace Www\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        echo url('index/index');

        echo PHP_EOL;

        print_r(config('DEFAULT_FILTER'));

        echo PHP_EOL;

        print_r(config('single'));

        echo PHP_EOL;

        $Test = new \Www\Library\Test();
    }

    public function testAction()
    {
        throw new \Exception('exception');
    }
}