#!/bin/bash  
#设置项目目录
root=/www/wwwroot/xgh/qkz_chongwu
redis_pre=chongwu
php_pre=/www/server/php/70/bin/
cd $root;
chown www:www -R runtime

#连接redis,获取重启队列值
key_val=`/www/server/redis/src/redis-cli lpop ${redis_pre}_reset_list`
echo "已经提取场次${key_val}"

if [ $key_val > 0 ]
then
	#获取正在运行的pid
	pid="/www/server/redis/src/redis-cli get ${redis_pre}_process_game_${key_val}";
	is_pid=$(ps -ax | awk '{ print $1 }' | grep -e "^${pid}$");
	#echo $is_pig;
	if [ $is_pid > 0 ]
	then
		echo "正在清除进程${pid}"
		kill -9 $pid
	fi
	
	echo '清除锁定场次'
	del_suoding=`/www/server/redis/src/redis-cli del ${redis_pre}_suoding_${key_val}`
	echo '重启场次中:'${del_suoding}
	#重启中
    ${php_pre}php ${root}/think doflashbuy $key_val 0
else
	echo "木有";
fi;
exit;