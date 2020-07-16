
    <div class="table-responsive">
        <table id="admin_list_table" class="table">

        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col"><span data-toggle="tooltip" data-placement="top" title="Admin name"> Name </span> </th>
                <th scope="col"><span data-toggle="tooltip" data-placement="top" title="Admin email"> Email </span> </th>
                <th scope="col"><span data-toggle="tooltip" data-placement="top" title="Reports solved"> RS </span> </th>
                <th scope="col"><span data-toggle="tooltip" data-placement="top" title="Users banned"> UB </span> </th>
                <th scope="col"><span data-toggle="tooltip" data-placement="top" title="Posts deleted"> PD </span> </th>
                <th scope="col"><span data-toggle="tooltip" data-placement="top" title="Comments deleted"> CD </span> </th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody id="admin_list_table_body">

            @if (count($data) == 0)
            <td colspan="6"><p class="text-muted font-italic text-center">{{$emptyMessage}}</p> </td>
            @else
                @for ($i = 0; $i < count($data); $i++)
                    <tr>
                        <th scope="row">
                            @if ($paginator != null)
                                {{($paginator->currentPage() -1)* $paginator->perPage() + $i + 1}}
                            @else
                                {{$i + 1}}
                            @endif
                        </th>
                        <td><a href='{{"/users/" . $data[$i]['info']['id']}}'  class="text-decoration-none text-dark">{{$data[$i]['info']['name']}}</a></td>
                        <td>{{$data[$i]['info']['email']}}</td>
                        <td>{{$data[$i]['stats']['reports_solved']}}</td>
                        <td>{{$data[$i]['stats']['users_banned']}}</td>
                        <td>{{$data[$i]['stats']['posts_deleted']}}</td>
                        <td>{{$data[$i]['stats']['comments_deleted']}}</td>
                        <td>
                            @if($data[$i]['info']['id'] != Auth::user()->id)
                            <button data-admin_id="{{$data[$i]['info']['id']}}" type="button" data-toggle="tooltip" data-placement="top" title="Remove admin" class="delete-admin close o-100 text-secondary" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            @endif
                        </td>
                    </tr>
                @endfor
            @endif

        </tbody>
        </table>
    </div>

    <div class="row d-flex justify-content-between align-items-center">
        <small class="text-muted"> <strong>RS</strong>- Reports Solved | <strong>UB</strong>- Users Banned | <strong>PD</strong>- Posts Deleted | <strong>CD</strong>- Comments Deleted</small>
        <button type="button" class=" btn btn-primary mt-1" data-toggle="modal" data-target="#searchAdmin" data-whatever="@fat">Add new admin</button>
    </div>

    <nav class="my-4 admin_list_nav" aria-label="Posts navigation .admin_list">
        @if (isset($paginator))
            {{$paginator->links('pagination.links')}}
        @endif
    </nav>

