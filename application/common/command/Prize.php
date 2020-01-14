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
class Prize extends Command
{
    //重置游戏
    protected function configure()
    {
        $this->setName('prize')->setDescription('Prize');
    }

    protected function execute(Input $input, Output $output)
    {
        init_config();//初始配置表数据
        Recommend::run();
    }
}