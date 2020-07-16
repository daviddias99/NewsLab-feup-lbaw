<div class="table-responsive">
    <table id="banned_list_table" class="table">


        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">User</th>
                <th scope="col">Banned by</th>
                <th scope="col">Date</th>
                <th scope="col">Days left</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody id="banned_list_table_body">
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
                        <td><a href="/users/{{$data[$i]['banned_user_id']}}" class="text-decoration-none text-dark">{{$data[$i]['banned_user_name']}}</a></td>
                        
                        @if ($data[$i]['admin_user_id'] == null)
                        <td>-</td>
                        @else
                        <td><a href="/users/{{$data[$i]['admin_user_id']}}" class="text-decoration-none text-dark">{{$data[$i]['admin_user_name']}}</a></td>
                        @endif

                        <td>{{$data[$i]['start_date']}}</td>
                        @if ($data[$i]['end_date'] != null)
                            <?php 
                                $date =  \Carbon\Carbon::parse($data[$i]['end_date']);
                                $now =  \Carbon\Carbon::now();

                                $diff = ceil($date->floatDiffInDays($now));
                            ?>
                            <td>{{$diff}} </td>
                        @else
                            <td>Permanent</td>
                        @endif


                        <td><button data-user_id="{{$data[$i]['banned_user_id']}}" type="button" class="close o-100 text-secondary unban_user" aria-label="Close">
                                <small aria-hidden="true">Unban</small>
                            </button></td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>
</div>

<nav class="my-4 banned_list_nav" aria-label="Posts navigation .banned_list">
    @if (isset($paginator))
        {{$paginator->links('pagination.links')}}
    @endif
</nav>