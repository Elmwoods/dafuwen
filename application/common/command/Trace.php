<?php
namespace app\common\command;
use app\common\controller\Recommend;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * Created by PhpStorm.
 * User: Xgh
 * Date: 2018/12/29
 * Time: 17:19
 */
class Trace extends Command
{
    //强制交易
    protected function configure()
    {
        $this->setName('trace')->setDescription('trace');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("trace");
        Recommend::run_pig_order();
    }
}