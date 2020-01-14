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
class OpenGame extends Command
{
    //重置游戏
    protected function configure()
    {
        $this->setName('OpenGame')->setDescription('OpenGame');
    }

    protected function execute(Input $input, Output $output)
    {
        $gamer = new Game();
        $gamer->openGame();
        
    }
}