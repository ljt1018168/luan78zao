<?php

/*
 * @Author:常三虎
 * @CreateTime:2017-1-4 10:33:31
 * @Description:
 */

namespace App\Http\Business;

use Illuminate\Support\Facades\DB;

class UserManageBusiness {

    private static $_instance = null;

    /**
     * 构造函数 
     */
    private function __construct() {
        //构造函数
    }

    /**
     * 禁止克隆
     *
     */
    private function __clone() {
        //覆盖__clone()方法，禁止克隆
    }

    /*
     * 对外只有一个实例（单列模式）
     * @return
     */

    public static function getInstance() {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    //获取用户列表
    public function getUserList($param) {

        $userList = DB::connection('TM_MiniTrademark')->table('USER_ACCOUNT')
                        ->leftjoin('ACCOUNT_BALANCE', 'USER_ACCOUNT.UserId', '=', 'ACCOUNT_BALANCE.UserId')
                        ->leftjoin('ACCOUNT_AGENTORG', 'USER_ACCOUNT.UserId', '=', 'ACCOUNT_AGENTORG.UserId')
                        ->leftjoin('ACCOUNT_COMPANY', 'USER_ACCOUNT.UserId', '=', 'ACCOUNT_COMPANY.UserId')
                        ->leftjoin('ACCOUNT_ACTIVITY_GIFT', 'USER_ACCOUNT.UserId', '=', 'ACCOUNT_ACTIVITY_GIFT.UserId')
                        ->select('USER_ACCOUNT.*', 'ACCOUNT_BALANCE.UsableAmount', 'ACCOUNT_AGENTORG.AgentOrgName', 'ACCOUNT_COMPANY.CompanyName', 'ACCOUNT_ACTIVITY_GIFT.ClassId', 'ACCOUNT_ACTIVITY_GIFT.GiftValue')
                        ->Where(function ($query) {
                            $query->orwhere('ACCOUNT_ACTIVITY_GIFT.ClassId', '1')
                            ->orwhereNull('ACCOUNT_ACTIVITY_GIFT.ClassId');
                        })
                        ->orderBy('UsableAmount', 'desc')->orderBy('GiftValue', 'desc');
        //搜索功能
        if (!empty($param['companyAgency'])) {
            $companyAgency = trim($param['companyAgency']);
            $userList = $userList->where('AgentOrgName', 'like', '%' . $companyAgency . '%')->orwhere('CompanyName', 'like', '%' . $companyAgency . '%');
        }
        if (!empty($param['phone'])) {
            $MobileNo = trim($param['phone']);
            $userList = $userList->where('USER_ACCOUNT.MobileNo', 'like', '%' . $MobileNo . '%');
        }
        if (!empty($param['email'])) {
            $EMail = trim($param['email']);
            $userList = $userList->where('USER_ACCOUNT.EMail', 'like', '%' . $EMail . '%');
        }
        if (!empty($param['addMan'])) {
            $addMan = trim($param['addMan']);
            $userList = $userList->where('USER_ACCOUNT.AddMan', 'like', '%' . $addMan . '%');
        }
        if (!empty($param['userType'])) {//用户类型0、免费用户（试用用户）1、付费用户（普通用户） 2、vip用户 3、内部用户
            if ($param['userType'] == 'free') {
                $userList = $userList->where('USER_ACCOUNT.isPublishAccount', '0');
            }
            if ($param['userType'] == 'vip') {
                $userList = $userList->where('USER_ACCOUNT.isPublishAccount', '2');
            }
            if ($param['userType'] == 'inner') {
                $userList = $userList->where('USER_ACCOUNT.isPublishAccount', '3');
            }
            if ($param['userType'] == 'common') {
                $userList = $userList->where('USER_ACCOUNT.isPublishAccount', '1');
            }
        }
        if (!empty($param['regType'])) {//1代表手机类型 2代表邮箱用户
            if ($param['regType'] == 'phone') {
                $userList = $userList->where('USER_ACCOUNT.RegisterType', '1');
            }
            if ($param['regType'] == 'email') {
                $userList = $userList->where('USER_ACCOUNT.RegisterType', '2');
            }
        }
        //判断注册时间
        if (!empty($param['startTime']) && empty($param['endTime'])) {
            $startTime = strtotime($param['startTime']);
            $userList = $userList->where('USER_ACCOUNT.RegisterTime', '>', $startTime);
        }
        if (!empty($param['endTime']) && empty($param['startTime'])) {
            $endTime = strtotime($param['endTime']) + 86400;
            $userList = $userList->where('USER_ACCOUNT.RegisterTime', '<', $endTime);
        }
        if (!empty($param['startTime']) && !empty($param['endTime']) && ($param['startTime'] < $param['endTime'])) {
            $startTime = strtotime($param['startTime']);
            $endTime = strtotime($param['endTime']) + 86400;
            $userList = $userList->whereBetween('USER_ACCOUNT.RegisterTime', [$startTime, $endTime]);
        }
        //判断输入的剩余抵用券
        if (!empty($param['surplusMon']) && $param['surplusMon'] == 'giftAmount') {
            $monLeft = trim($param['monLeft']);
            $monRight = trim($param['monRight']);
            if (!empty($monLeft) && empty($monRight)) {
                $userList = $userList->where('GiftValue', '>', $monLeft);
            }
            if (!empty($monRight) && empty($monLeft)) {
                $userList = $userList->whereBetween('GiftValue', [0, $monRight]);
            }
            if (!empty($monLeft) && !empty($monRight) && $monLeft < $monRight) {
                $userList = $userList->whereBetween('GiftValue', [$monLeft, $monRight]);
            }
        }

        //判断输入的剩余余额
        if (!empty($param['surplusMon']) && $param['surplusMon'] == 'realAmount') {
            $monLeft = trim($param['monLeft']);
            $monRight = trim($param['monRight']);
            if (!empty($monLeft) && empty($monRight)) {
                $userList = $userList->where('ACCOUNT_BALANCE.RealAmount', '>', $monLeft);
            }
            if (!empty($monRight) && empty($monLeft)) {
                $userList = $userList->whereBetween('ACCOUNT_BALANCE.RealAmount', [0, $monRight]);
            }
            if (!empty($monLeft) && !empty($monRight) && $monLeft < $monRight) {
                $userList = $userList->whereBetween('ACCOUNT_BALANCE.RealAmount', [$monLeft, $monRight]);
            }
        }

        $userArr = $userList->get();
        $userList = $userList->paginate(10);
        $addMan = DB::connection('TM_MiniTrademark')->table('USER_ACCOUNT')->select('AddMan')->distinct()->get();

        return $data = [
            'userList' => $userList, //带分页的数据，用于页面显示使用
            'userArr' => $userArr, //不带分页的数据,用于导出Excel表格使用
            'addMan' => $addMan, //添加人列表
        ];
    }

    //获取用户详细信息

    public function getUserInfor($id) {
        if (!empty($id)) {
            $userInfor = DB::connection('TM_MiniTrademark')->table('USER_ACCOUNT')
                            ->leftjoin('ACCOUNT_BALANCE', 'USER_ACCOUNT.UserId', '=', 'ACCOUNT_BALANCE.UserId')
                            ->leftjoin('ACCOUNT_AGENTORG', 'USER_ACCOUNT.UserId', '=', 'ACCOUNT_AGENTORG.UserId')
                            ->leftjoin('ACCOUNT_COMPANY', 'USER_ACCOUNT.UserId', '=', 'ACCOUNT_COMPANY.UserId')
                            ->select('USER_ACCOUNT.*', 'UsableAmount', 'AgentOrgName', 'CompanyName')
                            ->where('USER_ACCOUNT.UserId', $id)->get();
            $userGift = DB::connection('TM_MiniTrademark')->table('ACCOUNT_ACTIVITY_GIFT')->where('UserId', $id)->get();
            return $data = [
                'userInfor' => $userInfor,
                'userGift' => $userGift,
            ];
        }
    }

    /*
     * 获取用户订单列表
     */

    public function getOrderList($id) {
        if (!empty($id)) {
            $orderList = DB::connection('TM_MiniTrademark')->table('ORDER_SUMMARY')
                    ->leftJoin('ORDER_TYPE', 'ORDER_SUMMARY.BusiType', '=', 'ORDER_TYPE.TypeId')
                    ->leftJoin('ORDER_PAYMENT','ORDER_PAYMENT.PaymentId','=','ORDER_SUMMARY.PaymentMode')
                    ->select('ORDER_SUMMARY.*', 'ORDER_TYPE.TypeName','ORDER_PAYMENT.PaymentName')
                    ->orderBy('ORDER_SUMMARY.OrderPayTime','desc')
                    ->where('UserId', $id)->paginate(4);
        }
        return $orderList;
    }

}
