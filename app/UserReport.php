<?php

namespace App;



class UserReport extends Report
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_report';
    protected $primaryKey = 'report_id';


    public function getUser(){

        return $this->hasOne('App\User','id', 'user_id');
    }

    public function item(){

        return $this->getUser();
    }

    public static function search($reporterID, $reportedID){
        $query = UserReport::query('user_report.id')
                        ->join('report', 'user_report.report_id', '=', 'report.id')
                        ->where('report.reporter_id', $reporterID)
                        ->where('report.closed', false)
                        ->where('user_report.user_id', $reportedID);
        return $query->exists();
    }

}