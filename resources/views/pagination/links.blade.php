<?php
$max_links = 5; // looks better if it's an odd number :)

$num_pages = $paginator->lastPage();

$links = $num_pages < $max_links ? $num_pages : $max_links;
$each_side = floor($links / 2);

$from = $paginator->currentPage() - $each_side;
$to = $paginator->currentPage() + $each_side;

if ($paginator->currentPage() <= $each_side) {
    $from = 1;
    $to = $links;
}
if ($paginator->currentPage() > $paginator->lastPage() - $each_side) {
    $from = $paginator->lastPage() - $links + 1;
    $to = $paginator->lastPage();
}

?>
    <nav aria-label="Posts navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item {{ ($paginator->currentPage() == 1) ? ' disabled' : '' }}">
                <a class="page-link mx-2 rounded-circle" href="{{ $paginator->previousPageUrl() }}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                    <span class="sr-only">Previous</span>
                </a>
            </li>

            @for ($i = $from; $i <= $to; $i++)
                <li class="page-item {{ ($paginator->currentPage() == $i) ? ' active' : '' }}">
                    <a class="page-link mx-2 rounded-circle" href="{{ $paginator->url($i) }}">{{ $i }}</a>
                </li>
            @endfor

            <li class="page-item {{ ($paginator->currentPage() == $paginator->lastPage()) ? ' disabled' : '' }}">
                <a class="page-link mx-2 rounded-circle" href="{{ $paginator->nextPageUrl() }}">
                    <span aria-hidden="true">&raquo;</span>
                    <span class="sr-only">Next</span>
                </a>
            </li>
        </ul>
    </nav>


