<?php

namespace Modules\Analytics\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Modules\Analytics\App\Models\UserComments;
use Modules\Analytics\App\Models\VisitorLikes;
use Modules\Analytics\App\Models\UserSavedPosts;

class AnalyticsController extends Controller
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
            $year = date('Y');
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
               
                if($month == '01'){
                  $total_01 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '02'){
                  $total_02 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '03'){
                  $total_03 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '04'){
                  $total_04 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '05'){
                  $total_05 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '06'){
                  $total_06 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '07'){
                  $total_07 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '08'){
                  $total_08 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '09'){
                  $total_09 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '10'){
                  $total_10 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '11'){
                  $total_11 += (int) $metrics_value[0]->getValue();
                }
                else if($month == '12'){
                  $total_12 += (int) $metrics_value[0]->getValue();
                }
               
            }
           // array_push($res,env('APP_URL'));
           // return response(["status"=>"success","data"=>$res],200);
           array_push($res,[
            "January"=>$total_01,
            "February"=>$total_02,
            "March"=>$total_03,
            "April"=>$total_04,
            "May"=>$total_05,
            "June"=>$total_06,
            "July"=>$total_07,
            "August"=>$total_08,
            "September"=>$total_09,
            "October"=>$total_10,
            "November"=>$total_11,
            "December"=>$total_12]);
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
        $year = date('Y');
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
    public function analyticsThirtyDayVisitor()
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
        $thirty_day = [];
        $month = date('m');
        foreach ($response->getRows() as $row) {
            $dimension_value = $row->getDimensionValues();
            $metrics_value = $row->getMetricValues();
            if($dimension_value[0]->getValue() == $month){
                array_push($thirty_day,[
                "month"=>$dimension_value[0]->getValue(),
                "day"=>$dimension_value[1]->getValue(),
                "active_users"=>$metrics_value[0]->getValue()
            ]);
            }
        }
        for($day = 1; $day < 31; $day++){
            $active_users = 0;
            foreach($thirty_day as $data){
                if($data['day'] == $day){
                   $active_users += (int) $data['active_users'];
                }
            }
            $day_code = $day < 10 ? '0'.$day : "$day";
            array_push($res,[
                "day"=>$day_code,
                "active_users"=>$active_users
            ]);
            
        }
        return $this->successResponse($res, 'Analytics thirty day visitor retrieved successfully',200);
    }

    /**
     * return the twenty four hour visitor data from Google Analytics.
     */
    public function analyticsTwentyFourHourVisitor()
    {
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
            "total_users_registered"=>$total_users_regist
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
            "total_seven_day_visitor"=>$total_users
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
            "total_seven_day_new_user"=>$total_users_new
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
            "total_thirty_day_visitor"=>$total_users_thirty_day
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
            "total_thirty_day_new_user"=>$total_users_new_thirty_day
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
            "total_likes"=>(int) $total_likes
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
            "total_saves"=>$total_saves
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
            "total_comments"=>$total_comments
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
            "total_likes"=>$total_likes
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
            "total_saves"=>$total_saves
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
            "total_comments"=>$total_comments
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
            "total_likes"=>$total_likes
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
            "total_saves"=>$total_saves
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
            "total_comments"=>$total_comments
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
            "total_new_user"=>$total_new_user
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
            "total_visitor"=>$total_visitor
        ]);
       
       
       return $this->successResponse($res, 'Analytics twenty four hour visitor retrieved successfully',200);
    
        }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('analytics::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('analytics::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('analytics::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('analytics::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
