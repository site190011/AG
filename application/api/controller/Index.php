<?php

namespace app\api\controller;

use app\admin\model\AgApi;
use app\admin\model\Kefu;
use app\common\controller\Api;
use app\common\library\RedisManager;
use app\common\model\User;
use think\App;
use think\Db;
use think\Log;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $agapi = new \app\admin\model\AgApi();

        $this->success('请求成功', $agapi->getGames('ag'));
    }

    public function test()
    {
        $agapi = new \app\admin\model\AgApi();

        $list = $agapi->getPlatTypeList();

        foreach ($list as $key => $name) {
            $res = Db::name('game_plat')->where('key', $key)->find();

            if (!$res) {
                Db::name('game_plat')->insert([
                    'key' => $key,
                    'name' => $name,
                    'api_scope' => 'fetch_api_game_list'
                ]);
            }
        }

        $list2 = [
            'tf' => '电竞牛',
            'db5' => '多宝电竞',
            'xj' => '小金',
            'wl' => '瓦力',
            'ug' => 'UG',
            'ss' => '三昇',
            'sbo' => 'SBO',
            'saba' => '沙巴',
            'panda' => '熊猫体育',
            'newbb' => 'NewBB',
            'im' => 'IM',
            'fb' => 'FB',
            'crown' => '皇冠crown',
            'cmd' => 'CMD',
            'ap' => '平博',
            'ww' => '双赢',
            'vr' => 'VR',
            'tcg' => '天成',
            'sgwin' => '双赢',
            'ig' => 'IG',
            'gw' => 'GW',
            'db3' => '多宝彩票',
            'esb' => '电竞牛',
            'cr' => '皇冠体育',
            'bbin' => 'BB彩票'
        ];

        foreach ($list2 as $key => $name) {
            $res = Db::name('game_plat')->where('key', $key)->find();

            if (!$res) {
                Db::name('game_plat')->insert([
                    'key' => $key,
                    'name' => $name,
                    'api_scope' => 'none'
                ]);
            }
        }

    }

    public function getConfig()
    {
        $this->success('请求成功', [
            'permanent_url' => config('site.permanent_url'),
            'kefu_url' => config('site.kefu_url'),
            'name' => config('site.name'),
            'version' => config('site.version'),
            'timezone' => config('site.timezone'),
        ]);
    }

    public function slideList()
    {
        $slideMode = new \app\admin\model\Slide();

        $this->success('请求成功', $slideMode->getSlideList());
    }

    public function notices()
    {

        $getArticleMode = function () {
            $fields = 'id,title,type,views,status,weigh,publishtime,coverimage,description,tags,jumplink,is_top';
            $articleMode = new \app\admin\model\Article();
            $articleMode = $articleMode->field($fields)->where('status', 'published')->order('publishtime desc');

            return $articleMode;
        };

        $articleSetByType = [
            'notice' => $getArticleMode()->where('type', 'notice')->paginate(100),
            'noticeRoll' => $getArticleMode()->where('type', 'noticeRoll')->paginate(100),
            'events' => $getArticleMode()->where('type', 'events')->paginate(100)
        ];

        if ($this->auth->id) {
            $articleReadRecord = Db::table('fa_user_behavior')->where('uid', $this->auth->id)->where('type', 'articleRead')->find();
            $articleReadRecordData = json_decode($articleReadRecord['data'] ?? '[]', true);

            foreach ($articleSetByType as $key => $item) {
                foreach ($item as $key2 => $item2) {
                    if (in_array($item2['id'], $articleReadRecordData)) {
                        $articleSetByType[$key][$key2]['is_read'] = true;
                    } else {
                        $articleSetByType[$key][$key2]['is_read'] = false;
                    }
                }
            }
        }

        $this->success('请求成功', $articleSetByType);
    }

    public function article_list()
    {
        $articleMode = new \app\admin\model\Article();
        $type = $this->request->get('type') ?: $this->request->get('articleType');
        $type2 = $this->request->get('type2');
        $articleList = [];
        $where = [
            'status' => 'published'
        ];

        if ($type == 'allNotice') {
            $where['type'] = ['in', ['notice', 'noticeRoll']];
        } else {
            $where['type'] = $type;
        }

        if ($type2) {
            $where['type2'] = $type2;
        }

        $articleList = $articleMode->where($where)->order('publishtime desc')->paginate(100);

        if ($this->auth->id) {
            $articleReadRecord = Db::table('fa_user_behavior')->where('uid', $this->auth->id)->where('type', 'articleRead')->find();
            $articleReadRecordData = json_decode($articleReadRecord['data'] ?? '[]', true);

            foreach ($articleList as $key => $item) {
                if (in_array($item['id'], $articleReadRecordData)) {
                    $articleList[$key]['is_read'] = true;
                } else {
                    $articleList[$key]['is_read'] = false;
                }
            }
        }

        $this->success('请求成功', $articleList);
    }

    public function article_detail()
    {
        $articleMode = new \app\admin\model\Article();
        $id = $this->request->get('id');

        $article = $articleMode->where('status', 'published')->where('id', $id)->find();

        if ($this->auth->id) {
            $articleReadRecord = Db::table('fa_user_behavior')->where('uid', $this->auth->id)->where('type', 'articleRead')->find();
            $articleReadRecordData = json_decode($articleReadRecord['data'] ?? '[]', true);

            if (in_array($article['id'], $articleReadRecordData)) {
                $article['is_read'] = true;
            } else {
                $article['is_read'] = false;
            }
        }

        $this->success('请求成功', $article);
    }

    public function get_article_unread_count()
    {
        $uid = $this->auth->id;

        if (!$uid) {
            $this->error('请先登录');
        }

        $articleReadRecord = Db::table('fa_user_behavior')->where('uid', $uid)->where('type', 'articleRead')->find();
        $articleReadRecordData = json_decode($articleReadRecord['data'] ?? '[]', true);

        $articleMode = new \app\admin\model\Article();

        $articleList = $articleMode->where('status', 'published')->field('type, group_concat(id) as ids')->group('type')->select();
        $unreadCounts = [];

        foreach ($articleList as $item) {
            $articleIds = explode(',', $item['ids']);
            $unreadCount = count(array_diff($articleIds, $articleReadRecordData));
            $unreadCounts[$item['type']] = $unreadCount;
        }

        $this->success('请求成功', [
            'unreadCounts' => $unreadCounts,
            'total' => array_sum($unreadCounts)
        ]);
    }

    /**
     * 上报行为
     */
    public function report_behavior()
    {
        $type = $this->request->post('type');
        $id = $this->request->post('id');
        $uid = $this->auth->id;

        if (!$uid) {
            $this->error('请先登录');
        }

        $record = Db::table('fa_user_behavior')->where('uid', $uid)->where('type', $type)->find();

        if (!$record) {
            Db::table('fa_user_behavior')->insert([
                'uid' => $uid,
                'type' => $type,
                'data' => json_encode([$id])
            ]);
        } else {
            $data = json_decode($record['data'], true) ?: [];

            if (!in_array($id, $data)) {
                $data[] = $id;
                Db::table('fa_user_behavior')->where('id', $record['id'])->update([
                    'data' => json_encode($data)
                ]);
            }
        }

        $this->success('上报成功');
    }

    public function get_vip_list()
    {
        $vipList = Db::table('fa_vip_config')->select();

        $this->success('请求成功', $vipList);
    }

    public function get_bank_list()
    {
        $bankList = Db::table('fa_bank')->select();

        $this->success('请求成功', $bankList);
    }

    public function get_bank_virtual_list()
    {
        $bankList = (array)Db::table('fa_bank')->where('type', 'virtual')->field('type,name,bank_code')->select();
        $bankList = array_column($bankList, null, 'bank_code');

        $this->success('请求成功', $bankList);
    }

    public function get_game_plat_type_list()
    {
        $agapi = new AgApi();
        $gamePlatTypeList = $agapi->getPlatTypeList();

        $this->success('请求成功', $gamePlatTypeList);
    }

    public function get_kefu_list()
    {
         $platKefuList = Kefu::where('status', 1)->select();
         $promotionKefuUrl = '';

         $user = $this->auth->getUser();

         if ($user){
            $pid_path = array_values(array_filter(explode(',',$user->pid_path)));
            if (count($pid_path) > 0){
                $promotionKefuUrl = Db::name('promotion_user_config')->where('user_id',$pid_path[0])->value('kefuUrl');
            }
         }

         Log::test('ttttttttttttttttttttttttttt');

         $this->success('请求成功', [
             'platKefuList' => $platKefuList,
             'promotionKefuUrl' => $promotionKefuUrl
         ]);
    }

}
