<?php

namespace App;



class TagReport extends Report
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tag_report';
    protected $primaryKey = 'report_id';

    public function getTag(){

        return $this->hasOne('App\Tag','id', 'tag_id');
    }

    public function item(){

        return $this->getTag();
    }

    public static function search($reporterID, $reportedID){
        $query = TagReport::query('tag_report.id')
                        ->join('report', 'tag_report.report_id', '=', 'report.id')
                        ->where('report.reporter_id', $reporterID)
                        ->where('report.closed', false)
                        ->where('tag_report.tag_id', $reportedID);
        return $query->exists();
    }
}