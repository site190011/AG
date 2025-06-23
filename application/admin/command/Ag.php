<?php

namespace app\admin\command;

use app\admin\command\Api\library\Builder;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;

class Ag extends Command
{
    protected $typeList = [
        'syncGamesToDB' => '同步所有游戏列表',
        'syncRealTimeRecord' => '同步投注实时记录',
        'syncHistoryRecord' => '同步投注历史记录',
        'handleRebate' => '处理返水',
        'handleBonus' => '处理红利'
    ];
    protected function configure()
    {

        //sudo -u www php ./think Ag --type=syncGamesToDB
        $this
            ->setName('Ag')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '执行方法:' . implode('|', array_keys($this->typeList)), '')
            ->addOption('pageNo', '', Option::VALUE_OPTIONAL, '页码', '1')
            ->addOption('pageSize', '', Option::VALUE_OPTIONAL, '每页数量', '2000')
            ->addOption('startTime', '', Option::VALUE_OPTIONAL, '开始时间', date('Y-m-d H:i:s', time() - 3600 * 3))
            ->addOption('endTime', '', Option::VALUE_OPTIONAL, '结束时间', date('Y-m-d H:i:s'))
            ->setDescription('Ag 相关命令: php ./think Ag --type=' . implode('|', array_keys($this->typeList)));
    }

    protected function execute(Input $input, Output $output)
    {
        $type = $input->getOption('type');

        if (!$type) {
            $output->info("请输入 --type=* 参数");
            return;
        }

        $this->tryStopLastJob($type, $output);

        $output->info('正在执行:' . $this->typeList[$type] ?? $type);

        switch ($type) {
            case 'syncGamesToDB':
                (new \app\admin\model\AgApi())->syncGamesToDB();
            break;
            case 'syncRealTimeRecord':
                $pageNo = $input->getOption('pageNo');
                $pageSize = $input->getOption('pageSize');
                (new \app\admin\model\Games())->syncRealTimeRecord($pageNo, $pageSize);
            break;
            case 'syncHistoryRecord':
                //sudo -u www php ./think Ag --type=syncHistoryRecord --startTime="2025-04-28 22:00:00" --endTime="2025-04-29 02:00:00"
                $startTime = $input->getOption('startTime');
                $endTime = $input->getOption('endTime');
                $pageNo = $input->getOption('pageNo');
                $pageSize = $input->getOption('pageSize');
                (new \app\admin\model\Games())->syncHistoryRecord($startTime, $endTime, $pageNo, $pageSize);
            break;
            case 'handleRebate':
                //sudo -u www php ./think Ag --type=handleRebate
                (new \app\admin\model\Games())->handleRebate();
            break;
            case 'handleBonus':
                //sudo -u www php ./think Ag --type=handleBonus
                $output->info('-每月红包');
                (new \app\admin\model\vip\Info())->handleBonus('monthlyRedPacket', Date('Y-m'), '每月红包');
                // $output->info('-每周红包');
            break;
            default:
                $output->info("命令不存在!");
        }

        $output->info('执行完毕');
    }

    protected function tryStopLastJob ($type, $output)
    {
        // 指定锁文件的路径
        $lockFile = RUNTIME_PATH . "/lockfile/{$type}.lockfile.pid";

        if (!is_dir(dirname($lockFile))) {
            mkdir(dirname($lockFile), 0755, true);
        }

        // 检查是否有上一次运行的任务
        if (file_exists($lockFile)) {
            $pid = (int)file_get_contents($lockFile);
            // 检查进程是否存在
            if (posix_kill($pid, 0)) {
                $output->writeln('<error>上一个任务仍在运行，强行停止该任务...</error>');
                // 发送终止信号
                posix_kill($pid, SIGTERM);
                // 等待一段时间以确保进程被终止
                sleep(1);
                if (posix_kill($pid, 0)) {
                    $output->writeln('<error>无法停止上一个任务，请手动处理进程 PID: ' . $pid . '</error>');
                    die;
                } else {
                    $output->writeln('<info>上一个任务已成功停止。</info>');
                }
            }
        }

        // 创建锁文件  
        file_put_contents($lockFile, getmypid());
    }

}
