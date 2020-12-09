<?php

namespace App\Services\PayRoll;

use App\Config\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TimeSheetService
{
    protected $file;
    const ADMIN_TIME_PER_CLASS = 125;

    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Process information from json file
     * @return array
     * @author Argenis Barraza Guillen
     */
    public function process()
    {
        $this->validateJsonFormat();
        $file = $this->uploadFile($this->file);
        $collection = $this->createCollectionFromJsonFilePath(Storage::path($file));
        $this->validateJsonData($collection);
        $collectionFiltered = $this->filterCollectionByUniqueClasses($collection);
        $collectionWithExtraTime = $this->addCollectionExtraTime($collectionFiltered);
        $collectionSortedAndGrouped = $this->sortCollectionAndGroupByInstructor($collectionWithExtraTime);
        $this->removeCollectionOverlaps($collectionSortedAndGrouped);
        $this->removeCollectionGaps($collectionSortedAndGrouped);

        return $this->calculateFinalAdminTime($collectionSortedAndGrouped);
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

    /**
     * Create a collection from a JSON file
     * @param $filePath
     * @return Collection
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
     * @return Collection
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

    /**
     * Order collection by start_datetime and group it by instructor id
     * @param Collection $collection
     * @return Collection
     * @author Argenis Barraza Guillen
     */
    public function sortCollectionAndGroupByInstructor($collection)
    {
        return $collection->sortBy(function ($item){
            return $item->start_datetime;
        })->groupBy('instructor_id');
    }

    /**
     * Remove overlaps through all collection classes
     * @param Collection $instructors
     * @return void
     * @author Argenis Barraza Guillen
     */
    public static function removeCollectionOverlaps($instructors)
    {
        foreach ($instructors as $instructor => $classes) {
            foreach ($classes as $key => $class) {
                if($key == 0) {
                    continue;
                }
                $previousClass = $classes[$key - 1];
                $currentClass = $classes[$key];
                $startTime = Carbon::parse($previousClass->end_datetime);
                $endTime = Carbon::parse($currentClass->start_datetime);
                $difference = $endTime->lessThan($startTime);

                if ($difference) {
                    $currentClass->start_datetime = $previousClass->end_datetime;
                }
            }
        }

    }

    /**
     * Remove gaps through all collection classes
     * @param Collection $instructors
     * @return void
     * @author Argenis Barraza Guillen
     */
    public static function removeCollectionGaps($instructors)
    {
        foreach ($instructors as $instructor => $classes) {
            foreach ($classes as $key => $class) {
                if($key == 0) {
                    continue;
                }
                $previousClass = $classes[$key - 1];
                $currentClass = $classes[$key];
                $startTime = Carbon::parse($previousClass->end_datetime);
                $endTime = Carbon::parse($currentClass->start_datetime);
                $difference = $endTime->diffInMinutes($startTime);

                if ($difference <= 60) {
                    $currentClass->start_datetime = $previousClass->end_datetime;
                }
            }
        }
    }

    /**
     * Calculate final admin time and returns the array with all data
     * @param Collection $instructors
     * @return array
     * @author Argenis Barraza Guillen
     */
    public function calculateFinalAdminTime($instructors)
    {
        $finalData = [];
        foreach ($instructors as $instructor => $classes) {
            $allocatedAdminTime = $classes->count() * self::ADMIN_TIME_PER_CLASS;
            $totalOfMinutesWorked = self::getTotalMinutesWorkedPerDay($classes);
            $finalAdminTime = $allocatedAdminTime - $totalOfMinutesWorked;
            $finalData[] = [
                "instructor_id" => $instructor,
                "classes" => $classes->map(function($item){
                  return [
                      "start_time" => $item->start_datetime,
                      "end_time" => $item->end_datetime,
                  ];
                }),
                "admin_time" => $finalAdminTime
            ];
        }

        return $finalData;
    }

    /**
     * Get the total number of minutes worked
     * @param Collection $classes
     * @return int
     * @author Argenis Barraza Guillen
     */
    public static function getTotalMinutesWorkedPerDay($classes)
    {
        $totalMinutes = 0;
        foreach ($classes as $class) {
            $startDate = Carbon::parse($class->start_datetime);
            $endDate = Carbon::parse($class->end_datetime);
            $totalMinutes += $startDate->diffInMinutes($endDate);
        }

        return $totalMinutes;
    }

    /**
     * Validate if JSON file has a correct format
     * @return void
     * @throws \Exception
     * @author Argenis Barraza Guillen
     */
    public function validateJsonFormat()
    {
        $json = json_decode(
            file_get_contents($this->file), true
        );

        if ($json === NULL) {
            throw new \Exception("The file must be a file of type: json.");
        }
    }

    /**
     * Validate if JSON file has a correct data
     * @param $collection
     * @return void
     * @throws \Exception
     * @author Argenis Barraza Guillen
     */
    public function validateJsonData($collection)
    {
        $validator = $collection->contains(function($item){
            return isset($item->start_datetime).isset($item->end_datetime);
        });

        if (!$validator) {
            throw new \Exception("Json file does not have the correct data");
        }
    }
}
