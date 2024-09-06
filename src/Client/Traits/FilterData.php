<?php

namespace Kazio73\SynologyApiClient\Client\Traits;

use InvalidArgumentException;

trait FilterData
{
    /**
     * This method find directory ID in TeamFolders directory
     * @param string $directory
     * @param array $filterData
     * @return string
     */
    public function findId(string $directory, array $filterData) :string
    {
        foreach ($filterData['items'] as $filter) {
           if($filter['name'] === $directory) {
               return $filter['file_id'];
           }
        }
        return false;
    }

    public function accessType(string $type, string $path) :string
    {
        if ($type === 'private') {
            $path = self::MY.$path;
        } elseif ($type === 'team') {
            $path = self::TEAM.$path;
        } else {
            throw new InvalidArgumentException("Invalid type provided: $type");
        }
        return $path;
    }
}