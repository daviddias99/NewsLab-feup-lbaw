<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'report';

    protected $primaryKey = 'id';


    public function reporter()
    {
        return $this->hasOne('App\User', 'id', 'reporter_id');
    }

    public function solver()
    {
        return $this->hasOne('App\User', 'id', 'solved_id');
    }

    public function reasons()
    {

        return $this->belongsToMany('App\Reason', "report_reason");
    }

    public function type()
    {


        $contentReport = ContentReport::find($this->id);

        if ($contentReport)
            return $contentReport->getType();

        $userReport = UserReport::find($this->id);

        if ($userReport)
            return 'user';

        $tagReport = TagReport::find($this->id);

        if ($tagReport)
            return 'tag';
    }

    public function item()
    {

        $contentReport = ContentReport::find($this->id);

        if ($contentReport)
            return $contentReport->item();

        $userReport = UserReport::find($this->id);

        if ($userReport)
            return $userReport->item;

        $tagReport = TagReport::find($this->id);

        if ($tagReport)
            return $tagReport->item;
    }

    public static function get_info_from_list($admin_id, $reports)
    {

        $result = ['user' => [], 'content' => [], 'tag' => []];

        foreach ($reports as $report) {

            $newReport = $report->get_info();

            if($newReport['reporter']['user_id'] == $admin_id){
                continue;
            }

            if (in_array($newReport['type'], ['post', 'comment', 'reply']))
                array_push($result['content'], $newReport);
            else if ($newReport['type'] == 'user'){

                if($newReport['item']->id == $admin_id){
                    continue;
                }

                array_push($result['user'], $newReport);
                
            }
            else if ($newReport['type'] == 'tag')
                array_push($result['tag'], $newReport);
        }


        return $result;
    }

    public function get_info()
    {

        // Build report reasons
        $reas = [];

        foreach ($this->reasons as $reason) {
            array_push($reas, $reason->name);
        }

        // Build reporter info
        $reporter = $this->reporter;
        $reporter_info = [];

        if (!is_null($reporter)) {

            $reporter_info['user_id'] = $reporter->id;
            $reporter_info['name'] = $reporter->name;
        }

        $newReport = [
            'report_id' => $this->id,
            'reasons' => $reas,
            'explanation' => $this->explanation,
            'reporter' => $reporter_info,
            'type' => $this->type(),
            'item' => $this->item()
        ];

        return $newReport;
    }
}
