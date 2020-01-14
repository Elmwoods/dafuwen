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
class FlashBuyDo extends Command
{
    //重置游戏
    protected function configure()
    {
        $this->setName('doflashbuy')->addArgument('id')->addArgument('ii')->setDescription('doflashbuy');
    }

    protected function execute(Input $input, Output $output)
    {
        $myparme = $input->getArguments();
        $id = $myparme['id'];
        $ii = $myparme['ii'];
        $Game = new Game();
        $Game->flashBuyDo($id,$ii);
    }
}