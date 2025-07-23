<?php

namespace Modules\NewContactData\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Automattic\WooCommerce\Client as WooClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class WoocommerceDataController extends Controller
{

    /**
     * List of traits used by the controller.
     *
     * @return void
     */
    use \App\Traits\ApiResponder;

    /**
     * Export Woocommerce data.
     */

    public function exportWoocommerceData()
    {
        // Logic to export Woocommerce data
        // This could involve fetching data from the database, formatting it, and returning it as a response


        $woocommerce = new WooClient(
                env('WOOCOMMERCE_API_URL'), 
                env('WOOCOMMERCE_CLIENT_KEY'),
                env('WOOCOMMERCE_CLIENT_SECRET'),
                [
                        'version' => 'wc/v3'
                ]
                );        
      $woo_response = $woocommerce->get('customers?page=1&per_page=50');
       $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->createSheet();
                $sheet->setTitle('Woocommerce Data');
                $sheet->setCellValue('A1', 'Pharmacy Name');
                $sheet->setCellValue('B1', 'Pharmacy Number');
                $sheet->setCellValue('C1', 'Address');
                $sheet->setCellValue('D1', 'Postcode');
                $sheet->setCellValue('E1', 'Country');
                $sheet->setCellValue('F1', 'City');
                $sheet->setCellValue('G1', 'State');
                $sheet->setCellValue('H1', 'Contact Person');
                $sheet->setCellValue('I1', 'Email'); 
                $sheet->setCellValue('J1', 'Phone Number');
                $sheet->setCellValue('K1', 'Amount of Purchase');
                $sheet->setCellValue('L1', 'Average of Purchase');
                $sheet->setCellValue('M1', 'Total Purchase');
                $sheet->setCellValue('N1', 'Last Purchase Date');
                $sheet->setCellValue('O1', 'Created At');
                $rows = 2;
               


            foreach($woo_response as $key){
                $sheet->setCellValue('A' . $rows, $key->billing->company);
                $sheet->setCellValue('B' . $rows, "");
                $sheet->setCellValue('C' . $rows, $key->billing->address_1 . " " . $key->billing->address_2);
                $sheet->setCellValue('D' . $rows, $key->billing->postcode);
                $sheet->setCellValue('E' . $rows, $key->billing->country);
                $sheet->setCellValue('F' . $rows, $key->billing->city);
                $sheet->setCellValue('G' . $rows, $key->billing->state);
                $sheet->setCellValue('H' . $rows, $key->first_name . " " . $key->last_name);
                $sheet->setCellValue('I' . $rows, $key->email);
                $sheet->setCellValue('J' . $rows, $key->billing->phone);
                $sheet->setCellValue('K' . $rows, "0.00");
                $sheet->setCellValue('L' . $rows, "0.00");
                $sheet->setCellValue('M' . $rows, "0.00");
                $sheet->setCellValue('N' . $rows, date('d F Y', strtotime($key->date_created)));
                $sheet->setCellValue('O' . $rows, date('d F Y', strtotime($key->date_modified)));

                 $rows++;

       }

            $filename = date('YmdHis') . "-woocommerce-sample-data.xlsx";
            $path = public_path($filename);
            $writer = new Xlsx($spreadsheet); 
            $writer->save($path);

        return $this->successResponse(["filename"=>url('public/' . $filename)],'Woocommerce data exported successfully',200);
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('newcontactdata::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('newcontactdata::create');
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
        return view('newcontactdata::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('newcontactdata::edit');
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
