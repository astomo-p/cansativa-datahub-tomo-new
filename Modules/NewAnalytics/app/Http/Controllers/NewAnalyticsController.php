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
