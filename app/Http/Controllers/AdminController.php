<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Admin;
use App\Report;
use App\User;

class AdminController extends Controller {

    /**
     * Method that returns a view with admin center
     * /admins/{id}
     */
    public function show($id) {

        $admin = Admin::find($id);

        if (is_null($admin)){
            return abort(404, 'There is no admin with this id: ' . $id);
        }
        $this->authorize('view', $admin);

        $defaultPPP = 6;
        
        $adminListPaginator = Admin::paginate($defaultPPP);
        $adminListPaginator->withPath('/api/admins?ppp='.$defaultPPP);

        $banListPaginator = User::where('banned',true)->paginate($defaultPPP);
        $banListPaginator->withPath('/api/users/banned?ppp='.$defaultPPP);

        $openReportPaginator = Report::where('closed', false)->paginate($defaultPPP);
        $openReportPaginator->withPath('/api/reports?ppp='.$defaultPPP.'&type=open');
        
        $closedReportPaginator = Report::where('closed', true)->paginate($defaultPPP);
        $closedReportPaginator->withPath('/api/reports?ppp='.$defaultPPP.'&type=closed');
        
        // return post page
        return view('pages.admin_center', [

            'info' => $admin->get_info(),
            'stats' => $admin->get_stats(),
            'admin_list' => [
                'data' => Admin::list_info($adminListPaginator),
                'paginator' => $adminListPaginator,
                'users' => []
            ],
            'banned_list' => [
                'data' =>  User::getBannedUsersInfo($banListPaginator),
                'paginator' => $banListPaginator
            ],
            'report_inbox' => [

                'open' =>[
                    'data' => Report::get_info_from_list(Auth::user()->id,$openReportPaginator),
                    'paginator' => $openReportPaginator
                        ],
                'closed' => [
                    'data' => Report::get_info_from_list(Auth::user()->id,$closedReportPaginator),
                    'paginator' => $closedReportPaginator
                ]
            ]
        ]);
    }
  
    /**
     * Method that returns info about an admin
     * /api/admins/{id}
     */
    public function info($id) {
        
        $this->authorize('list', Admin::class);
        $admin = Admin::find($id);
        
        if (is_null($admin)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'There is no admin with this id: ' . $id
            ], 404);
        }

        return response()->json(["info"=>$admin->get_info(),"stats"=>$admin->get_stats()], 200);
    }

    public function list(Request $request){

        $this->authorize('list', Admin::class);
        $html = in_array('text/html', $request->getAcceptableContentTypes());
        $result = [];

        if (!(isset($_GET['ppp']) && isset($_GET['page']))) {
            $result = Admin::list_info(Admin::all());
            $paginator = null;
        } else {
            $currentPage = $request['page'];
            $postsPerPage = $_GET['ppp'];

            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0)))
            {
                if($html)
                    return abort(400, 'Invalid parameters');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['paginator' => 'Either page or ppp were not positive ints'],
                    ], 400);
            }

            do{
                $paginator = Admin::paginate($postsPerPage,['*'],'page', $currentPage);
                $paginator->withPath('/api/admins?ppp=' . $postsPerPage);
                $result = Admin::list_info($paginator);

                if( ($currentPage <= 1) || ($paginator->count() > 0))
                    break;  
                else
                    $currentPage--;
            } while(true);

        }

        if ($html)
            return response(View('partials.admin_list_table', ['data' => $result, 'emptyMessage' => 'There are no admins.', 'paginator' => $paginator ]),200);
        else
            return response()->json($result);
    }

    public function create(Request $request){

        $this->authorize('create', Admin::class);
        $newAdminID = $request->user_id;
        if($newAdminID == null)
            return response()->json(['status' => 'failure', 'status_code' => 400, 'message' => 'New admin ID not provided.'],400);
        
        $existentUser = User::find($newAdminID);
        if(is_null($existentUser) || $existentUser->banned)
            return response()->json(['status' => 'failure', 'status_code' => 403, 'message' => 'Banned users cannot be admins.'],403);

        if(Admin::find($newAdminID))
            return response()->json(['status' => 'failure', 'status_code' => 409, 'message' => 'Already exists.'],409);

        $newAdmin = new Admin();
        $newAdmin->user_id =$newAdminID;
        
        if(!$newAdmin->save())
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'message' => 'Error processing the request.',
            ], 500);

            return response()->json([
                'status' => 'success',
                'status_code' => 201,
                'message' => 'Admin added',
            ], 201);
    }

    public function delete($oldAdminID){

        $this->authorize('delete',[Admin::class,$oldAdminID]);
        
        $admin = Admin::find($oldAdminID);

        if(is_null($admin))
            return response()->json(['status' => 'failure', 'status_code' => 404,'message' => 'No admin with the given ID exists.'],404);

        $admin->delete();

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'Deleted',
        ], 200);
    }

    public function candidates(Request $request){

        $this->authorize('list', Admin::class);
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        if(($request->search == null) || ($request->search == "")){
            if($html){
                return response(View('partials.admin_add_new_modal_body',['users' => []]),200);
            }
            else
                return response()->json([], 200);
        }
 
        $query = User::searchQuery($request->search);
        $users = $query->get();
        $nonAdmins = [];

        foreach($users as $user){

            if(!$user->isAdmin() && !$user->banned)
                array_push($nonAdmins,$user);
        }

        $nonAdmins = collect($nonAdmins);
        if($html)
            return response(View('partials.admin_add_new_modal_body', ['users' => User::getUsersShortInfo($nonAdmins)]),200);
        else
            return response()->json(['users' => User::getUsersShortInfo($query->get())]);
    }
}