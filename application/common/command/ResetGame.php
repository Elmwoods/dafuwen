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
class ResetGame extends Command
{
    //重置游戏
    protected function configure()
    {
        $this->setName('resetgame')->setDescription('reset');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("resetgoods");

        Recommend::run_goods();
    }
}