<?php

namespace Modules\AuditLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AuditLog\Models\AuditLogs;

class AuditLogController extends Controller
{
    use \App\Traits\ApiResponder;
    /**
     * Display a listing of the resource.
     */
    public function getAllAuditLogs(Request $request)
    {
        // default pagination setup
        $sort_column = $request->get('sort') == '' ? 'audit_logs.id' :  'audit_logs.' . explode('-',$request->get('sort'))[0];
        $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');
        
        $baseQuery = AuditLogs::when($request->get('module'), function($query, $row){
                $query->where('module', 'ilike', strtolower($row));
            })
            ->when($request->get('activity'), function($query, $row){
                $query->where('activity', 'ilike', strtolower($row)); 
            });
        
        $records_total = $baseQuery->count();

        $records_filtered = $records_total;
        if($search){
            $search = trim($search);
            $results = $baseQuery
            ->where(function($query) use ($search) {
                $query->where('audit_logs.full_name', 'ilike', '%'.$search.'%')
                      ->orWhere('audit_logs.email', 'ilike', '%'.$search.'%');
            })
            ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();
            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        } else {
            $results = $baseQuery
            ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();
            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        }

        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $results,
        ];

       return $this->successResponse($res,'All audit logs data',200);
    }

    public function getAuditFilter()
    {
        $filter = AuditLogs::select('module', 'activity')->groupBy('module','activity')->get();

        $result = collect($filter)
        ->groupBy('module')
        ->map(function ($items, $module) {
            return [
                'module' => $module,
                'activity' => $items->pluck('activity')->values()
            ];
        })
        ->values()
        ->toArray();
        
       return $this->successResponse($result,'Get audit filter',200);
    }

}
