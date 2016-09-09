<?php
namespace Mobile\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        echo 'Test Module';

        echo PHP_EOL;

        echo url('test/index/index');
    }

    public function testAction()
    {
        echo 'Test Module';

        echo PHP_EOL;

        echo url('test/index/test');
    }
}