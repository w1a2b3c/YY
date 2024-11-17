<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
namespace app\jk\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
  
class SendMessage extends Command
{
    protected function configure(){
        $this->setName('SendMessage')->setDescription("计划任务 SendMessage");
    }
  
    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output){
        /*** 这里写计划任务列表集 START ***/
        
        $echo = $this->cehsald();//监控支付回调
        $output->writeln($echo);
        
        /*** 这里写计划任务列表集 END ***/
        $output->writeln('['.date("Y-m-d H:i:s").'] Successful');
        $output->writeln('------------------------------------------------');
    }
    
    public function cehsald()//监控支付回调
    {
       $this->Heart('cehsald');//监控回调心跳检测
       $rows = Db::table("pay_order")->where("state", 1)->select();
       if(!$rows){
            return '未检测到正在付款的订单';
       }
       foreach ($rows as $row){
            $cache = tohc('Heart');
            if($cache->get('pcyjk')>=date('Y-m-d H:i:s',time())){
                if($row['mid_dm']!='mq_gzfbyjk' and $row['mid_dm']!='mq_gqqyjk' and $row['mid_dm']!='mq_gvxyjk'){//云监控(支付宝)||云监控(Q Q)||云监控(微信)
                }else{
                  continue;
                }
                if($row['mid_dm']!='mq_gzfbyd' and $row['mid_dm']!='mq_gqqyd' and $row['mid_dm']!='mq_gvxyd'){//云端(支付宝)||云端(Q Q)||云端(微信)
                }else{
                  continue;
                }
                if($row['mid_dm']!='mq_gzfbjkd' and $row['mid_dm']!='mq_gqqjkd' and $row['mid_dm']!='mq_gvxjkd'){//监控端(支付宝)||监控端(Q Q)||监控端(微信)
                }else{
                  continue;
                }
            }
            $ree = Db::table("pay_jie")->where("pid", $row['pid'])->where("id",$row['mid_id'])->find();
            $cls = common($ree['game_dm']);//支付通道路径
            $run = $cls->cehsald($row);//支付通道数据
            if($run['code']==1){
                $eer = Db::table("pay_gfg")->where("pid", $row['pid'])->where("mid",$row['mid'])->find();
                $data = Db::table("pay_user")->where("id", $row['pid'])->find();
                url_notify($eer,$row,$data);
            }
            echo  '通道：'.$run['json']['mid_type'].' | 订单号：'.$run['json']['order_id'].' | 状态：'.$run['data']."\r\n";
       }
       return '检测订单监听中...';
    }
    
    public function Heart($type)//监控回调心跳检测
    {
        
        if($type=='cehsald'){//宝塔链接监控回调
            
            $atad = Db::table("pay_sz")->find(1);
            $cache = tohc('Heart');
            if($atad['ds_todaorul']){//云端网关
                $cache->set('ds_todaorul',$atad['ds_todaorul'],60*3);
                $cache->set('cehsald',date('Y-m-d H:i:s',time()+60*3),60*3);
            }
            if($atad['ds_yjkrul']){//云监控网关
                $cache->set('ds_yjkrul',$atad['ds_yjkrul'],60*3);
                $cache->set('pcyjk',date('Y-m-d H:i:s',time()+60*3),60*3);
            }
            closeEndOrder();//检测过期订单
            $rows = Db::table("pay_gfg")->cursor();
            foreach ($rows as $row){
                $ree = Db::table("pay_jie")->where("pid", $row['pid'])->where("id",$row['typec_id'])->cache(true)->find();
                if($ree['game_dm']!='mq_gzfbjkd'and$ree['game_dm']!='mq_gvxjkd'and$ree['game_dm']!='mq_gqqjkd'and$ree['game_dm']!='gm_gjkd'){//除了监控端(微信)||监控端(支付宝)||监控端(Q Q)||监控端(固码)
                    Db::name("pay_gfg")->where("pid", $row['pid'])->where("typec_id",$ree['id'])->update(array("lastheart"=>time(),"jkstate"=>1));
                }else{
                    continue;
                }
            }
        }
    }
}