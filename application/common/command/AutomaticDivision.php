<?php
namespace app\common\command;
use app\common\controller\Recommend;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\logic\Game;


/**
 * Created by PhpStorm.
 * User: Xgh
 * Date: 2018/12/29
 * Time: 17:19
 */
class AutomaticDivision extends Command
{
    // 自动分裂
    protected function configure()
    {
        $this->setName('division')->setDescription('division');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("division");
        Recommend::division();
    }
}