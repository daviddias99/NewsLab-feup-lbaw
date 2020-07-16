<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Report;

class ReportController extends Controller {

   
    public function list(Request $request){

        $this->authorize('list', Report::class);

        $html = in_array('text/html', $request->getAcceptableContentTypes());

        // If no type is provided return all the reports in a json devided into open/closed
        if($request->type){

            $type = $request->type;

            if($type != 'open' && $type != 'closed')
                return response()->json([ 'status' => 'failure', 'status_code' => 400, 'message' => 'Invalid type argument'], 400);

            if(!(isset($_GET['ppp']) && isset($_GET['page'])))
                return response()->json([ 'status' => 'failure', 'status_code' => 400, 'message' => 'Pagination info missing or incomplete'], 400);

            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0)))
            {
                if($html)
                    return abort(400, 'Invalid parameters');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Invalid pagination info.',
                    ], 400);
            }

            $closed = ($type == 'closed') ? true : false;
            $reportPaginator = Report::where('closed', $closed)->paginate($request->ppp);
            $reportPaginator->withPath('/api/reports?ppp='.$request->ppp.'&type='. ($closed ? 'closed' : 'open'));
            $reports = Report::get_info_from_list(Auth::user()->id,$reportPaginator);
            
            if($html)
                if(!$closed){
                    return response(View('partials.admin_report_inbox_open', ['data' => $reports, 'paginator' => $reportPaginator ]),200);
                }
                else {
                    return response(View('partials.admin_report_inbox_closed', ['data' => $reports, 'paginator' => $reportPaginator ]),200);
                }
            
            return  response()->json(['type' => $type, 'reports' => $reports],200);
        }
        else{

            $reports = ["open" => Report::get_info_from_list(Auth::user()->id,Report::all()->where('closed', false)), 
                        "closed" => Report::get_info_from_list(Auth::user()->id,Report::all()->where('closed', true))];
            return  response()->json($reports,200);
        }
    }

    public function close($reportID){
        $report = Report::find($reportID);
        if($report == null){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Report not found.',
            ], 404);
        }

        $this->authorize('close', [Report::class,$report]);

        $report->closed = true;
        $report->solver_id = Auth::user()->id;


        if(!$report->save())
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'message' => 'Error processing the request.',
            ], 500);

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'Success',
        ], 200);
    }

}

