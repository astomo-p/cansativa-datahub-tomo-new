<?php

namespace Modules\NewAnalytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Modules\NewAnalytics\Models\UserComments;
use Modules\NewAnalytics\Models\UserSavedPosts;
use Modules\NewContactData\Models\Contacts;
use Modules\NewContactData\Models\ContactTypes;

class NewAnalyticsController extends Controller
{

     /**
     * List of traits used in this controller.
     */
    use \App\Traits\ApiResponder;

    /**
     * AnalyticsController properties.
     */

    private $analytics_client;

     public function __construct()
    {
        $dir = dirname(__FILE__,6);
        $this->analytics_client = new BetaAnalyticsDataClient([
               'credentials' => $dir . env('ANALYTICS_CREDENTIAL_PATH')
            ]); 
    }

    /**
     * Return the monthly visitor data from Google Analytics.
     */
    public function analyticsMonthlyVisitor()
    {
            $year = date('Y',strtotime("-1 Year"));
            $now = date('Y-m-d');
            $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
            $date_range = [$ranges];
            $dimensions = [
                new Dimension(['name'=>'month']),
                new Dimension(['name'=>'year'])
            ];
            $metrics = [
                new Metric(['name'=>'totalUsers'])
            ];
            $request = new RunReportRequest([
                'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
                'date_ranges' => $date_range,
                'dimensions' => $dimensions,
                'metrics' => $metrics,
                'limit' => 100
            ]);
            $response = $this->analytics_client->runReport($request);
            $res = [];
            $total_00 = 0;
            $total_01 = 0;
            $total_02 = 0;
            $total_03 = 0;
            $total_04 = 0;
            $total_05 = 0;
            $total_06 = 0;
            $total_07 = 0;
            $total_08 = 0;
            $total_09 = 0;
            $total_10 = 0;
            $total_11 = 0;
            $total_12 = 0;
            foreach ($response->getRows() as $row) {
                $dimension_value = $row->getDimensionValues();
                $metrics_value = $row->getMetricValues();
                $month = $dimension_value[0]->getValue();
                $year = $dimension_value[1]->getValue();
                $timestamp = $year . '-' . $month;
               
                if($timestamp == date('Y-m',strtotime('-12 Month'))){
                  $total_00 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-11 Month'))){
                  $total_01 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-10 Month'))){
                  $total_02 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-9 Month'))){
                  $total_03 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-8 Month'))){
                  $total_04 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-7 Month'))){
                  $total_05 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-6 Month'))){
                  $total_06 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-5 Month'))){
                  $total_07 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-4 Month'))){
                  $total_08 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-3 Month'))){
                  $total_09 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-2 Month'))){
                  $total_10 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m',strtotime('-1 Month'))){
                  $total_11 += (int) $metrics_value[0]->getValue();
                }
                else if($timestamp == date('Y-m')){
                  $total_12 += (int) $metrics_value[0]->getValue();
                }
               
            }
           // array_push($res,env('APP_URL'));
           // return response(["status"=>"success","data"=>$res],200);
           array_push($res,[
            date('Y F',strtotime('-12 Month'))=>$total_00,
            date('Y F',strtotime('-11 Month'))=>$total_01,
            date('Y F',strtotime('-10 Month'))=>$total_02,
            date('Y F',strtotime('-9 Month'))=>$total_03,
            date('Y F',strtotime('-8 Month'))=>$total_04,
            date('Y F',strtotime('-7 Month'))=>$total_05,
            date('Y F',strtotime('-6 Month'))=>$total_06,
            date('Y F',strtotime('-5 Month'))=>$total_07,
            date('Y F',strtotime('-4 Month'))=>$total_08,
            date('Y F',strtotime('-3 Month'))=>$total_09,
            date('Y F',strtotime('-2 Month'))=>$total_10,
            date('Y F',strtotime('-1 Month'))=>$total_11,
            date('Y F')=>$total_12]);
           return $this->successResponse($res, 'Analytics monthly visitor retrieved successfully',200);
    }


     /**
     * return the bounce rate data from Google Analytics.
     */
    public function analyticsBounceRate()   
    {
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month'])
        ];
        $metrics = [
            new Metric(['name'=>'bounceRate'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $bounce_total = 0;
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            $bounce_total += (float)$metrics_value[0]->getValue();
        }
        array_push($res,[
            "bounce_rate"=>$bounce_total
        ]);
       return $this->successResponse($res, 'Analytics bounce rate retrieved successfully',200);
    }

    /**
     * return the three month visitor data from Google Analytics.
     */
    public function analyticsThreeMonthVisitor()
    {
        $year = date('Y',strtotime("-1 Year"));
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month'])
        ];
        $metrics = [
            new Metric(['name'=>'totalUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $three_month = [
            date('m', strtotime('-2 month')),
            date('m', strtotime('-1 month')),
            date('m')
        ];
        $month_1 = 0;
        $month_2 = 0;
        $month_3 = 0;
    
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            $month = $dimension_value[0]->getValue();
            if($month == $three_month[0]){
                $month_1 += (int) $metrics_value[0]->getValue();
            } 
            else if($month == $three_month[1]){
                $month_2 += (int) $metrics_value[0]->getValue();
            }
            else if($month == $three_month[2]){
                $month_3 += (int) $metrics_value[0]->getValue();
            }
        }
       $month_name = [
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',    
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December'
       ];
       array_push($res,[
            $month_name[$three_month[0]]=>$month_1,
            $month_name[$three_month[1]]=>$month_2,
            $month_name[$three_month[2]]=>$month_3
        ]);
       return $this->successResponse($res, 'Analytics three month visitor retrieved successfully',200);
    }


    /**
     * return the thirty day visitor data from Google Analytics.
     */
    public function analyticsThirtyDayVisitor(Request $request)
    {
        $year = date('Y',strtotime("-1 Year"));
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'activeUsers'])
        ];
        $request_ga = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request_ga);
        $res = [];
        $thirty_day = [];
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            $month = is_null($request->prev_month) ? $dimension_value[0]->getValue() == date('m') : $dimension_value[0]->getValue() == date('m') || $dimension_value[0]->getValue() == date('m',strtotime('-1 month'));
            if($month){
                array_push($thirty_day,[
                "month"=>$dimension_value[0]->getValue(),
                "day"=>$dimension_value[1]->getValue(),
                "active_users"=>$metrics_value[0]->getValue()
            ]);
            }
        }
        $month_name = [
            "01"=>"January",
            "02"=>"February",
            "03"=>"March",
            "04"=>"April",
            "05"=>"May",
            "06"=>"June",
            "07"=>"July",
            "08"=>"August",
            "09"=>"September",
            "10"=>"October",
            "11"=>"November",
            "12"=>"December"
        ];
        for($day = 1; $day < 31; $day++){

            if(!is_null($request->prev_month)){
            foreach($thirty_day as $data){
                if($data['day'] == $day && $data['month'] == date('m',strtotime('-1 Month'))){
                   $active_users = (int) $data['active_users'];
                    $day_code = $day < 10 ? '0'.$day : "$day";
                    array_push($res,[
                        "day"=>$month_name[$data['month']] . ' ' .$day_code,
                        "active_users"=>$active_users
                    ]);
                } else if($data['day'] == $day && $data['month'] == date('m')){
                   $active_users = (int) $data['active_users'];
                    $day_code = $day < 10 ? '0'.$day : "$day";
                    array_push($res,[
                        "day"=>$month_name[$data['month']] . ' ' .$day_code,
                        "active_users"=>$active_users
                    ]);

                }
            }
           
            }

           else { $active_users = 0;
            foreach($thirty_day as $data){
                if($data['day'] == $day && $data['month'] == date('m')){
                   $active_users += (int) $data['active_users'];
                   $day_code = $day < 10 ? '0'.$day : "$day";
            array_push($res,[
                "day"=>$month_name[$data['month']] . ' ' .$day_code,
                "active_users"=>$active_users
            ]);
                }
            }
            /* $day_code = $day < 10 ? '0'.$day : "$day";
            array_push($res,[
                "day"=>$day_code,
                "active_users"=>$active_users
            ]); */
            }
            
        }
        return $this->successResponse($res, 'Analytics thirty day visitor retrieved successfully',200);
    }

    /**
     * return the twenty four hour visitor data from Google Analytics.
     */
    public function analyticsTwentyFourHourVisitor()
    {
        $year = date('Y',strtotime('-1 Year'));
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'year']),
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'hour']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'activeUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $twenty_four_hour = [];
        /* $prev_year = date('m') == '01' ? date('Y',strtotime('-1 Year')) : date('Y');
        $prev_month = date('d') == '01' ? date('m',strtotime('-1 Month')) : date('m');
        $prev_day = date('h') == '01' ? date('d',strtotime('-1 Day')) : date('d');
        $years = date('y');
         */
        $prev_year = date('m') == '01' ? date('Y',strtotime('-1 Year')) : date('Y');
        $prev_month = date('d') == '01' ? date('m',strtotime('-1 Month')) : date('m',strtotime('-1 Month'));
        $years = date('Y');
        $month = date('m');
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if(($dimension_value[0]->getValue() == $prev_year && $dimension_value[1]->getValue() == $prev_month) ||($dimension_value[0]->getValue() == $years && $dimension_value[1]->getValue() == $month)){
                array_push($twenty_four_hour,[
                "year"=>$dimension_value[0]->getValue(),
                "month"=>$dimension_value[1]->getValue(),
                "hour"=>$dimension_value[2]->getValue(),
                "day"=>$dimension_value[3]->getValue(),
                "active_users"=>$metrics_value[0]->getValue()
            ]);
            }
        }

            $year_check = date('Y',strtotime('-24 Hour'));
            $month_check = date('m',strtotime('-24 Hour'));
            $day_check = date('d',strtotime('-24 Hour'));
            $date_check = $day_check . " " . date('F',strtotime('-24 Hour')) . " " . $year_check;
       
        foreach($twenty_four_hour as $data){
           
                if($data['year'] == $year_check && $data['month'] == $month_check && $data['day'] == $day_check){
                   array_push($res,[
                    'date'=>$date_check,
                    'hour' => $data['hour'],
                    'users' => (int) $data['active_users']
                ]);
                }

                else if($data['year'] == date('Y') && $data['month'] == date('M') && $data['day'] == date('d')){
                   array_push($res,[
                    'date'=> date('d F Y'),
                    'hour' => $data['hour'],
                    'users' => (int) $data['active_users']
                ]);
                }
            }


       return $this->successResponse($res, 'Analytics twenty four hour visitor retrieved successfully',200);
    }

    /**
     * return the now on page data from Google Analytics.
     */
    public function analyticsNowOnPage()
    {
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'day']),
            new Dimension(['name'=>'pagePath'])
        ];
        $metrics = [
            new Metric(['name'=>'activeUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $day_now = date('d');
        $day_before = date('d',strtotime('-1 day')); 
        $month_now = date('m');
        $total_day_now = 0;
        $total_day_before = 0;
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == $month_now && $dimension_value[1]->getValue() == $day_now){
                $total_day_now += (int) $metrics_value[0]->getValue();
            }
            else if($dimension_value[0]->getValue() == $month_now && $dimension_value[1]->getValue() == $day_before){
                $total_day_before += (int) $metrics_value[0]->getValue();
            }
        }
        $delta = $total_day_now - $total_day_before;
        array_push($res,[
            "users_now"=>$total_day_now,
            "users_yesterday"=>$total_day_before,
            "delta" => ($delta > 0) ? "+$delta" : "$delta",
        ]);
       return $this->successResponse($res, 'Analytics now on page retrieved successfully',200);
    }

    /**
     * return the total user registered data from Google Analytics.
     */
    public function totalUserRegistered()
    {
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'totalUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $total_users_regist = 0;
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            $total_users_regist += (int) $metrics_value[0]->getValue();
        }
        array_push($res,[
            "total"=>$total_users_regist
        ]);
       return $this->successResponse($res, 'Analytics total user registered retrieved successfully',200);
    }

    /**
     * return the total seven day visitor data from Google Analytics.
     */
    public function analyticsTotalSevenDayVisitor()
    {
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'totalUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $total_users = 0;
        $seven_day = [
            date('d', strtotime('-6 day')),
            date('d', strtotime('-5 day')),
            date('d', strtotime('-4 day')),
            date('d', strtotime('-3 day')),
            date('d', strtotime('-2 day')),
            date('d', strtotime('-1 day')),
            date('d')
        ];
       
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == date('m')){
               switch($dimension_value[1]->getValue()){
                    case $seven_day[0]:
                        $total_users += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day[1]:
                        $total_users += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day[2]:
                        $total_users += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day[3]:
                        $total_users += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day[4]:
                        $total_users += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day[5]:
                        $total_users += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day[6]:
                        $total_users += (int) $metrics_value[0]->getValue();
                        break;
                }
               }
            }
            
        array_push($res,[
            "total"=>$total_users
        ]);
       return $this->successResponse($res, 'Analytics total seven day visitor retrieved successfully',200);
    }

    /**
     * return the total seven day new user data from Google Analytics.
     */
    public function analyticsTotalSevenDayNewUser(){
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'newUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $total_users_new = 0;
        $seven_day_new_user = [
            date('d', strtotime('-6 day')),
            date('d', strtotime('-5 day')),
            date('d', strtotime('-4 day')),
            date('d', strtotime('-3 day')),
            date('d', strtotime('-2 day')),
            date('d', strtotime('-1 day')),
            date('d')
        ];
       
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == date('m')){
               switch($dimension_value[1]->getValue()){
                    case $seven_day_new_user[0]:
                        $total_users_new += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day_new_user[1]:
                        $total_users_new += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day_new_user[2]:
                        $total_users_new += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day_new_user[3]:
                        $total_users_new += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day_new_user[4]:
                        $total_users_new += (int) $metrics_value[0]->getValue();
                        break;
                    case $seven_day_new_user[5]:
                        $total_users_new += (int) $metrics_value
                        ->getValue();
                        break;
                    case $seven_day_new_user[6]:    
                        $total_users_new += (int) $metrics_value[0]->getValue();
                        break;  
                    }
                }
            }
        array_push($res,[
            "total"=>$total_users_new
        ]); 
         return $this->successResponse($res, 'Analytics total seven day new user retrieved successfully',200);
    }

    /**
     * return the total thirty day visitor data from Google Analytics.
     */
    public function analyticsTotalThirtyDayVisitor()
    {
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'totalUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $total_users_thirty_day = 0;
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == date('m')){
                if($dimension_value[1]->getValue() > 0){
                    $total_users_thirty_day += (int) $metrics_value[0]->getValue();
                }
            }
        }
        array_push($res,[
            "total"=>$total_users_thirty_day
        ]);
       return $this->successResponse($res, 'Analytics total thirty day visitor retrieved successfully',200);
    }

    /**
     * return the total thirty day new user data from Google Analytics.
     */
    public function analyticsTotalThirtyDayNewUser()
    {
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'newUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $total_users_new_thirty_day = 0;
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == date('m')){
                if($dimension_value[1]->getValue() > 0){
                    $total_users_new_thirty_day += (int) $metrics_value[0]->getValue();
                }
            }
        }
        array_push($res,[
            "total"=>$total_users_new_thirty_day
        ]);
       return $this->successResponse($res, 'Analytics total thirty day new user retrieved successfully',200);
    }

    /**
     * return the total seven day likes data from database.
     */
    public function analyticsTotalSevenDayLikes(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $res = [];
        $total_likes = UserSavedPosts::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->where('is_like', 1)
        ->count();
        array_push($res,[
            "total"=>(int) $total_likes
        ]); 
         return $this->successResponse($res, 'Analytics total seven day like retrieved successfully',200);
    }

    /**
     * return the total seven day saves data from database.
     */
    public function analyticsTotalSevenDaySaves(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $res = [];
        $total_saves = UserSavedPosts::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->where('is_saved', 1)
        ->count();
        array_push($res,[
            "total"=>$total_saves
        ]); 
         return $this->successResponse($res, 'Analytics total seven day save retrieved successfully',200);
    }

    /**
     * return the total seven day comments data from database.
     */
    public function analyticsTotalSevenDayComments(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $res = [];
        $total_comments = UserComments::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->count();;
        array_push($res,[
            "total"=>$total_comments
        ]); 
         return $this->successResponse($res, 'Analytics total seven day comment retrieved successfully',200);
    }

    /**
     * return the total thirty day likes data from database.
     */
    
     public function analyticsTotalThirtyDayLikes(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $res = [];
        $total_likes = UserSavedPosts::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->where('is_like', 1)
        ->count();;
        array_push($res,[
            "total"=>$total_likes
        ]); 
         return $this->successResponse($res, 'Analytics total thirty day likes retrieved successfully',200);
    }

    /**
     * return the total thirty day saves data from database.
     */
    public function analyticsTotalThirtyDaySaves(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $res = [];
        $total_saves = UserSavedPosts::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->where('is_saved', 1)
        ->count();;
        array_push($res,[
            "total"=>$total_saves
        ]); 
         return $this->successResponse($res, 'Analytics total thirty day save retrieved successfully',200);
    }


    /**
     * return the total thirty day comments data from database.
     */

     public function analyticsTotalThirtyDayComments(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $res = [];
        $total_comments = UserComments::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->count();;
        array_push($res,[
            "total"=>$total_comments
        ]); 
         return $this->successResponse($res, 'Analytics total thirty day comment retrieved successfully',200);
    }

    /**
     * return the total twenty four hour likes data from database.
     */

     public function analyticsTotalTwentyFourHourLikes(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-1 days'));
        $res = [];
        $total_likes = UserSavedPosts::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->where('is_like', 1)
        ->count();;
        array_push($res,[
            "total"=>$total_likes
        ]); 
         return $this->successResponse($res, 'Analytics total twenty four hour likes retrieved successfully',200);
    }

    /**
     * return the total twenty four hour saves data from database.
     */

     public function analyticsTotalTwentyFourHourSaves(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-1 days'));
        $res = [];
        $total_saves = UserSavedPosts::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->where('is_saved', 1)
        ->count();;
        array_push($res,[
            "total"=>$total_saves
        ]); 
         return $this->successResponse($res, 'Analytics total twenty four hour save retrieved successfully',200);
     }

     /**
      * return the total twenty four hour comments data from database.
      */

      public function analyticsTotalTwentyFourHourComments(){
        $year = date('Y');
        $now = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-1 days'));
        $res = [];
        $total_comments = UserComments::where('created_date', '>=', $start_date)
        ->where('created_date', '<=', $now)
        ->count();;
        array_push($res,[
            "total"=>$total_comments
        ]); 
         return $this->successResponse($res, 'Analytics total twenty four hour comment retrieved successfully',200);
      }

      /**
       * return the total twenty four hour new user from Analytics
       */

       public function analyticsTotalTwentyFourHourNewUser(){
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'newUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $total_new_user = 0;
       
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == date('m') && $dimension_value[1]->getValue() == date('d')){
                $total_new_user += (int) $metrics_value[0]->getValue();
                }
            }
       
        array_push($res,[
            "total"=>$total_new_user
        ]); 
         return $this->successResponse($res, 'Analytics total twenty four hour new user retrieved successfully',200);
       }

       /**
        * return the total twenty four hour visitor from Analytics
        */

        public function analyticsTotalTwentyFourHourVisitor(){
             $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'hour']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'activeUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $twenty_four_hour = [];
        $month = date('m');
        $day = date('d');
        $total_visitor = 0;
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == $month && $dimension_value[1]->getValue() == $day){
                $total_visitor += (int) $metrics_value[0]->getValue();
            }
        }
        array_push($res,[
            "total"=>$total_visitor
        ]);
       
       
       return $this->successResponse($res, 'Analytics twenty four hour visitor retrieved successfully',200);
    
     }

    /**
     * Get Analytics average time onsite
     */

    public function analyticsAverageTimeOnsite()
    {
        $year = date('Y');
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'averageSessionDuration'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $average_onsite = 0;
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            $day = $dimension_value[0]->getValue();
            if($day == date('d')){
                $average_onsite += (int) $metrics_value[0]->getValue();
            } 
        }
         array_push($res,[
            "average_time_onsite"=>$average_onsite
        ]);
       
       
       return $this->successResponse($res, 'Analytics average time onsite retrieved successfully',200);
    
    }
    
     /**
     * return the twenty four hour yesterday visitor data from Google Analytics.
     */
    public function analyticsTwentyFourHourYesterdayVisitor()
    {
        $year = date('Y',strtotime('-1 Year'));
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'hour']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'activeUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $twenty_four_hour = [];
        $month = date('d') == '01' ? date('m',strtotime('-1 Month')) : date('m');
        $day = date('d',strtotime("-1 Day"));
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == $month && $dimension_value[1]->getValue() == $day){
                array_push($twenty_four_hour,[
                "month"=>$dimension_value[0]->getValue(),
                "hour"=>$dimension_value[1]->getValue(),
                "day"=>$dimension_value[2]->getValue(),
                "active_users"=>$metrics_value[0]->getValue()
            ]);
            }
        }
        for($hour = 0; $hour < 24; $hour++){
            $active_users = 0;
            foreach($twenty_four_hour as $data){
                if($data['hour'] == $hour){
                   $active_users += (int) $data['active_users'];
                }
            }
            $hour_code = $hour < 10 ? '0'.$hour : "$hour";
            array_push($res,[
                "hour"=>$hour_code,
                "active_users"=>$active_users
            ]);
            
        }
       return $this->successResponse($res, 'Analytics twenty four hour yesterday visitor retrieved successfully',200);
    }

     /**
     * return the twenty four hour yesterday new user data from Google Analytics.
     */
    public function analyticsTwentyFourHourYesterdayNewUser()
    {
        $year = date('Y',strtotime('-1 Year'));
        $now = date('Y-m-d');
        $ranges = new DateRange(['start_date' => "$year-01-01", 'end_date' => $now]);
        $date_range = [$ranges];
        $dimensions = [
            new Dimension(['name'=>'month']),
            new Dimension(['name'=>'hour']),
            new Dimension(['name'=>'day'])
        ];
        $metrics = [
            new Metric(['name'=>'newUsers'])
        ];
        $request = new RunReportRequest([
            'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
            'date_ranges' => $date_range,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'limit' => 100
        ]);
        $response = $this->analytics_client->runReport($request);
        $res = [];
        $twenty_four_hour = [];
        $month = date('d') == '01' ? date('m',strtotime('-1 Month')) : date('m');
        $day = date('d',strtotime("-1 Day"));
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == $month && $dimension_value[1]->getValue() == $day){
                array_push($twenty_four_hour,[
                "month"=>$dimension_value[0]->getValue(),
                "hour"=>$dimension_value[1]->getValue(),
                "day"=>$dimension_value[2]->getValue(),
                "active_users"=>$metrics_value[0]->getValue()
            ]);
            }
        }
        for($hour = 0; $hour < 24; $hour++){
            $active_users = 0;
            foreach($twenty_four_hour as $data){
                if($data['hour'] == $hour){
                   $active_users += (int) $data['active_users'];
                }
            }
            $hour_code = $hour < 10 ? '0'.$hour : "$hour";
            array_push($res,[
                "hour"=>$hour_code,
                "active_users"=>$active_users
            ]);
            
        }
       return $this->successResponse($res, 'Analytics twenty four hour yesterday new user retrieved successfully',200);
    }

    /**
     * Get Analytics select date range visitor
     */

public function analyticsSelectDateVisitor(Request $request)
{
    
    $start_date = $request->start_date;
    $end_date = $request->end_date;

    $ranges = new DateRange(['start_date' => $start_date, 'end_date' => $end_date]);
    $date_range = [$ranges];
    $dimensions = [
        new Dimension(['name'=>'month']),
        new Dimension(['name'=>'day']),
        new Dimension(['name'=>'year'])
    ];
    $metrics = [
        new Metric(['name'=>'totalUsers'])
    ];
    $request = new RunReportRequest([
        'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
        'date_ranges' => $date_range,
        'dimensions' => $dimensions,
        'metrics' => $metrics,
        'limit' => 100
    ]);
    $response = $this->analytics_client->runReport($request);
    $res = [];
    $date_select = [];
    foreach ($response->getRows() as $row) {
        $dimension_value = $row->getDimensionValues();
        $metrics_value = $row->getMetricValues();
        array_push($date_select, [
            "year" => $dimension_value[2]->getValue(),
            "month" => $dimension_value[0]->getValue(),
            "day" => $dimension_value[1]->getValue(),
            "active_users" => (int) $metrics_value[0]->getValue()
        ]);
    }
    foreach ($date_select as $data) {
        $day = (int) $data['day'];
        $month = (int) $data['month'];
        $year = (int) $data['year'];
        array_push($res, [
            "date" => "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT),
            "vistor" => $data['active_users']
        ]);
    }
        
    
    return $this->successResponse($res, 'Analytics selected date visitor retrieved successfully', 200);
}

/**
 * Get analytics select date range new user
 */

public function analyticsSelectDateNewUser(Request $request)
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    $ranges = new DateRange(['start_date' => $start_date, 'end_date' => $end_date]);
    $date_range = [$ranges];
    $dimensions = [
        new Dimension(['name'=>'month']),
        new Dimension(['name'=>'day']),
        new Dimension(['name'=>'year'])
    ];
    $metrics = [
        new Metric(['name'=>'newUsers'])
    ];
    $request = new RunReportRequest([
        'property' => 'properties/' . env('ANALYTICS_PROPERTY'),
        'date_ranges' => $date_range,
        'dimensions' => $dimensions,
        'metrics' => $metrics,
        'limit' => 100
    ]);
    $response = $this->analytics_client->runReport($request);
    $res = [];
    $date_select = [];      
    foreach ($response->getRows() as $row) {
        $dimension_value = $row->getDimensionValues();
        $metrics_value = $row->getMetricValues();
        array_push($date_select, [
            "year" => $dimension_value[2]->getValue(),
            "month" => $dimension_value[0]->getValue(),
            "day" => $dimension_value[1]->getValue(),
            "active_users" => (int) $metrics_value[0]->getValue()
        ]);
    }
    foreach ($date_select as $data) {
        $day = (int) $data['day'];
        $month = (int) $data['month'];
        $year = (int) $data['year'];
        array_push($res, [
            "date" => "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT),
            "new_user" => $data['active_users']
        ]);
    }
    return $this->successResponse($res, 'Analytics selected date new user retrieved successfully', 200);
}

/**
 * Generate signature
 */

 public function generateSignature(Request $request)
 {
    $apiToken = 'dummy-auth';

    $payload = [];
    $expired = base64_encode(date("Y-m-d H:i:s",strtotime("+1 Day")));

    $signature = $expired . "." . hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $apiToken);
        
    return $this->successResponse(['signature' => $signature], 'Signature generated successfully', 200);
 }


 /**
  * Get contact community likes amount
  */

  public function communityLikesAmount(Request $request)
  {

      $community_id = ContactTypes::where('contact_type_name', 'COMMUNITY')->first()->id;

      $community_data = ContactTypes::find($community_id)
      ->contacts()
      ->where('user_id', $request->user_id)
      ->first();

      if (!$community_data) {
          return $this->errorResponse('Community contact not found', 404);
      }

        $likes_amount = UserSavedPosts::where('user_id', $request->user_id)
        ->where('is_like', 1)
        ->count();

        $res = [
            'likes_amount_max' => $likes_amount,
            'likes_amount_min' => 0
        ];

      return $this->successResponse($res, 'Community contact retrieved successfully', 200);
  }

  /**
   * Get contact community comments amount
   */

    public function communityCommentsAmount(Request $request)
    {
        $community_id = ContactTypes::where('contact_type_name', 'COMMUNITY')->first()->id; 
        $community_data = ContactTypes::find($community_id)
        ->contacts()
        ->where('user_id', $request->user_id)
        ->first();

        if (!$community_data) {
            return $this->errorResponse('Community contact not found', 404);
        }

        $comments_amount = UserComments::where('user_id', $request->user_id)->count();

        $res = [
            'comments_amount_max' => $comments_amount,
            'comments_amount_min' => 0
        ];  
        return $this->successResponse($res, 'Community contact retrieved successfully', 200);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('newanalytics::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('newanalytics::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('newanalytics::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('newanalytics::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
