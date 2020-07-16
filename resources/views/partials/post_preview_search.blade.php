<div>
@include('partials.post_preview_list', [
    'posts' => $news['data'],
    'editable' => false,
    'showAuthor' => true,
    'showDate' => false,
    'emptyMessage' => 'No News found',
    'paginator' => $news['paginator'],
])
</div>
<div>
@include('partials.post_preview_list', [
    'posts' => $opinion['data'],
    'editable' => false,
    'showAuthor' => true,
    'showDate' => false,
    'emptyMessage' => 'No Opinions found',
    'paginator' =>  $opinion['paginator'],
])
</div>