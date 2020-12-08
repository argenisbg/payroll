<?php

namespace App\Services\PayRoll;

use App\Config\Common;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class TimeSheetService
{
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Validate if JSON file has a correct format
     * @return bool
     * @author Argenis Barraza Guillen
     */
    public function validateJsonFormat()
    {
        $json = json_decode(
            file_get_contents($this->file), true
        );

        return $json === NULL ? false : true;
    }

    /**
     * Process information from json file
     * @return \Illuminate\Support\Collection
     * @author Argenis Barraza Guillen
     */
    public function process()
    {
        $file = $this->uploadFile($this->file);
        $collection = $this->createCollectionFromJsonFilePath(Storage::path($file));
        $collectionFiltered = $this->filterCollectionByUniqueClasses($collection);
        $collectionWithExtraTime = $this->addCollectionExtraTime($collectionFiltered);
        $collectionSortedAndGroup = $this->sortCollectionAndGroupByInstructor($collectionWithExtraTime);
        //$collectionRemoveOverlapping = $this->removeCollectionOverlapsPerInstructor($collectionSortedAndGroup);

        return $collectionSortedAndGroup;
    }

    /**
     * Create a collection from a JSON file
     * @param $filePath
     * @return \Illuminate\Support\Collection
     * @author Argenis Barraza Guillen
     */
    public static function createCollectionFromJsonFilePath($filePath)
    {
        return collect(
            json_decode(
                file_get_contents($filePath)
            )
        );
    }

    /**
     * Filter collection and remove duplicated start and end time classes
     * @param $collection
     * @return \Illuminate\Support\Collection
     * @author Argenis Barraza Guillen
     */
    public static function filterCollectionByUniqueClasses($collection)
    {
        return $collection->unique(function ($item){
            return $item->instructor_id.$item->start_datetime.$item->end_datetime.$item->duration;
        });
    }

    /**
     * For each class, add 15 minutes to start time and 10 minutes to end time
     * @param $collection
     * @return \Illuminate\Support\Collection
     * @author Argenis Barraza Guillen
     */
    public static function addCollectionExtraTime($collection)
    {
        return $collection->transform(function ($item){
            $item->start_datetime = Carbon::createFromDate($item->start_datetime)->addMinutes(-15)->toJSON();
            $item->end_datetime = Carbon::createFromDate($item->end_datetime)->addMinutes(10)->toJSON();
            return $item;
        });
    }

    public static function removeCollectionOverlapsPerInstructor($collection)
    {
        foreach ($collection as $instructor => $classes) {
            $periods[$instructor] = new PeriodCollection();
            foreach ($classes as $class) {
                $startDate = Carbon::createFromDate($class->start_datetime)->toDateTimeString();
                $endDate = Carbon::createFromDate($class->end_datetime)->toDateTimeString();
                $periods[$instructor] = $periods[$instructor]->add(Period::make($startDate, $endDate, Precision::MINUTE));
            }
            $overlaps[$instructor] = $periods[$instructor]->overlap();
        }
        return collect($overlaps);
    }

    /**
     * Order collection by start_datetime and group it by instructor id
     * @param \Illuminate\Support\Collection $collection
     * @return \Illuminate\Support\Collection
     * @author Argenis Barraza Guillen
     */
    public function sortCollectionAndGroupByInstructor($collection)
    {
        return $collection->sortBy(function ($item){
            return $item->start_datetime;
        })->groupBy('instructor_id');
    }

    /**
     * Upload the file
     * @param $file
     * @return mixed
     * @author Argenis Barraza Guillen
     */
    public function uploadFile($file)
    {
        $common = new Common();
        return $common->uploadFile($file);
    }

    public static function overlaps($startDate, $endDate, $collection)
    {

    }
}
