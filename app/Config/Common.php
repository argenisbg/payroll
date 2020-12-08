<?php

namespace App\Config;

use Carbon\Carbon;

class Common
{
    private $date;
    private $uploadFileName;

    public function __construct()
    {
        $this->date = Carbon::now()->timestamp;
        $this->uploadFileName = 'extractedData_' . $this->date . '.json';
    }

    /**
     * upload file to a specific disk or the default
     * @param $file
     * @param string $disk
     * @return mixed
     */
    public function uploadFile($file, $disk = '')
    {
        return $file->storeAs('jsons', $this->uploadFileName, $disk);
    }
}
