<?php

namespace Kazio73\SynologyApiClient\Client;

use InvalidArgumentException;
use Kazio73\SynologyApiClient\Client\Traits\FilterData;

/**
 * Class Client.
 */
class DriveStationClient extends Client
{

    use FilterData;


    public const API_SERVICE_NAME = 'SynologyDrive';

    public const API_NAMESPACE = 'SYNO';

    /**
     * Info API setup
     *
     * @param string $address
     * @param int $port
     * @param string $protocol
     * @param int $version
     * @param boolean $verifySSL
     */
    public function __construct(
        $address,
        $port = null,
        $protocol = null,
        $version = 1,
        $verifySSL = false
    )
    {
        parent::__construct(
            self::API_SERVICE_NAME,
            self::API_NAMESPACE,
            $address,
            $port,
            $protocol,
            $version,
            $verifySSL
        );
    }

    /**
     * Get Info about the directory or file
     * @param string $type The type of directory, can be either 'private' or 'team'.
     * @param string|null $path The path of the directory (optional). Defaults based on the $type.
     * @return array
     * @throws SynologyException
     */
    public function getInfo(
        string $type = 'private',
        ?string $path = null,
    ): array
    {

        $path = $this->accessType($type, $path);
        return $this->request(
            self::API_SERVICE_NAME,
            'Files',
            'entry.cgi',
            'get',
            [
                'path' => json_encode($path),
            ],
            2
        );
    }

    /**
     * Get Info TeamFolders
     * @param $offset
     * @param $limit
     * @param $filter
     * @param $sort_by
     * @param $sort_direction
     * @return array
     * @throws SynologyException
     */
    public function getTeamFolders(
        $offset = 0,
        $limit = 100,
        $filter = ['include_transient' => true],
        $sort_by = 'name',
        $sort_direction = 'asc',

    ): array
    {
        return $this->request(
            self::API_SERVICE_NAME,
            'TeamFolders',
            'entry.cgi',
            'list',
            [
                'offset' => $offset,
                'limit' => $limit,
                'filter' => json_encode($filter),
                'sort_by' => $sort_by,
                'sort_direction' => $sort_direction,
            ],
            1
        );
    }

    /**
     * Get Info MyDrive
     * @param $offset
     * @param $limit
     * @param $filter
     * @param $sort_by
     * @param $sort_direction
     * @return array
     * @throws SynologyException
     */
    public function getMyDrive(
        $offset = 0,
        $limit = 100,
        $filter = ['include_transient' => true],
        $sort_by = 'name',
        $sort_direction = 'asc',

    ): array
    {
        $path = self::MY;
        return $this->request(
            self::API_SERVICE_NAME,
            'Files',
            'entry.cgi',
            'list',
            [
                'path' => json_encode($path),
                'offset' => $offset,
                'limit' => $limit,
                'filter' => json_encode($filter),
                'sort_by' => $sort_by,
                'sort_direction' => $sort_direction,
            ],
            2
        );
    }

    /**
     * This method get all object in directory
     * @param string $type The type of directory, can be either 'private' or 'team'.
     * @param string|null $path The path of the directory (optional). Defaults based on the $type.
     * @param $offset
     * @param $limit
     * @param $filter
     * @param $sort_by
     * @param $sort_direction
     * @return array
     * @throws SynologyException
     */
    public function getDir(
        string $type = 'private',
        ?string $path = null,
        $offset = 0,
        $limit = 100,
        $filter = ['include_transient' => true],
        $sort_by = 'name',
        $sort_direction = 'asc',
    ): array
    {
        $path = $this->accessType($type, $path);

        return $this->request(
            self::API_SERVICE_NAME,
            'Files',
            'entry.cgi',
            'list',
            [
                'path' => json_encode($path),
                'offset' => $offset,
                'limit' => $limit,
                'filter' => json_encode($filter),
                'sort_by' => $sort_by,
                'sort_direction' => $sort_direction,
            ],
            1
        );
    }

    /**
     * Create folder in directory
     * @param string $type The type of directory, can be either 'private' or 'team'.
     * @param string|null $path The path of the directory (optional). Defaults based on the $type.
     * @param string $name The name of the new folder.
     * @param string $kind Default 'folder'.
     * @param string $conflict Default 'autorename'
     * @return array|bool|mixed
     * @throws SynologyException
     */
    public function createFolder(
        string $type = 'private',
        ?string $path = null,
        string $name,
        $kind = 'folder',
        $conflict = 'autorename'
    )
    {
        $path = $this->accessType($type, $path);

        if ($name != null ) {
            $path = $path.'/'.$name;
        } else {
            throw new InvalidArgumentException("Empty type provided: $name");
        }

        return $this->request(
            self::API_SERVICE_NAME,
            'Files',
            'entry.cgi',
            'create',
            [
                'path' => json_encode($path),
                'type' => $kind,
                'conflict_action' => $conflict,
            ],
            2
        );
    }

    public function deleteFolder(
        string $type = 'private',
        ?string $path = null,
        string $name,
        $permanent = 'false'
    )
    {
        $path = $this->accessType($type, $path);

        if ($name != null ) {
            $list = [$path.'/'.$name];
        }
        else {
            throw new InvalidArgumentException("Empty type provided: $name");
        }

        print_r($list).PHP_EOL;
        return $this->request(
            self::API_SERVICE_NAME,
            'Files',
            'entry.cgi',
            'delete',
            [
                'files' => json_encode($list),
                'permanent' => $permanent,
                'revision' => 1
            ],
            2
        );
    }

    /**
     * CreateShare a file
     *
     * @param string $path (comma separated)
     * @param string $name
     * @return array
     * @throws SynologyException
     */
    public function createShare(
        string $type = 'private',
        ?string $path = null,
        string $name,
        ): array
    {

        $path = $this->accessType($type, $path);

        if ($name != null ) {
            $list = [$path.'/'.$name];
        }
        else {
            throw new InvalidArgumentException("Empty type provided: $name");
        }

        return $this->request(
            self::API_SERVICE_NAME,
            'Sharing',
            'entry.cgi',
            'create_link',
            [
                'path' => json_encode($path),
                'password' => 'KazioK',
                'date_expired' => '',
                'date_available' => '',
            ],
            1
        );
    }

    /**
     * Get Available Shares
     *
     * @param bool $onlywritable
     * @param int $limit
     * @param int $offset
     * @param string $sortby
     * @param string $sortdirection
     * @param bool $additional
     * @return array
     * @throws SynologyException
     */
    public function getShares(
        $onlywritable = false,
        $limit = 25,
        $offset = 0,
        $sortby = 'name',
        $sortdirection = 'asc',
        $additional = false
    ): array
    {
        return $this->request(
            self::API_SERVICE_NAME,
            'List',
            'entry.cgi',
            'list_share',
            [
                'onlywritable' => $onlywritable,
                'limit' => $limit,
                'offset' => $offset,
                'sort_by' => $sortby,
                'sort_direction' => $sortdirection,
                'additional' => $additional ? 'real_path,owner,time,perm,volume_status' : '',
            ],
        );
    }

    /**
     * Upload file to given path
     *
     * @param $file
     * @param $filename
     * @return mixed
     * @throws SynologyException
     */
    public function uploadFile($file, $filename, $path = '/home')
    {
        return $this->request(
            self::API_SERVICE_NAME,
            'Upload',
            'entry.cgi',
            'upload',
            [
                'path' => $path,
                'overwrite' => 'false',
                'create_parents' => 'true',
                'filename' => $filename,
            ],
            2,
            'post',
            $file
        );
    }

    /**
     * Search for files/directories in a given path
     *
     * @param string $pattern
     * @param string $path like '/home'
     * @param int $limit
     * @param int $offset
     * @param string $sortby (name|size|user|group|mtime|atime|ctime|crtime|posix|type)
     * @param string $sortdirection (asc|desc)
     * @param string $filetype (all|file|dir)
     * @param bool $additional
     * @return array
     * @throws SynologyException
     */
    public function search(
        $pattern,
        $path = '/home',
        $limit = 25,
        $offset = 0,
        $sortby = 'name',
        $sortdirection = 'asc',
        $filetype = 'all',
        $additional = false
    ): array
    {
        return $this->request(
            self::API_SERVICE_NAME,
            'List',
            'entry.cgi',
            'list',
            [
                'folder_path' => $path,
                'limit' => $limit,
                'offset' => $offset,
                'sort_by' => $sortby,
                'sort_direction' => $sortdirection,
                'pattern' => $pattern,
                'filetype' => $filetype,
                'additional' => $additional ? 'real_path,size,owner,time,perm' : '',
            ]
        );
    }

    /**
     * Download a file
     *
     * @param string $path (comma separated)
     * @param string $mode
     * @return array
     * @throws SynologyException
     */
    public function download($path, $mode = 'open'): array
    {
        return $this->request(
            self::API_SERVICE_NAME,
            'Download',
            'entry.cgi',
            'download',
            [
                'path' => $path,
                'mode' => $mode
            ]
        );
    }

    /**
     * Download a file
     *
     * @param string $path (comma separated)
     * @param string $name
     * @return array
     * @throws SynologyException
     */
    public function rename(string $path, string $name): array
    {
        return $this->request(
            self::API_SERVICE_NAME,
            'List',
            'entry.cgi',
            'rename',
            [
                'path' => $path,
                'name' => $name,
            ]
        );
    }

    /**
     * ListShare a file
     *
     * @return array
     * @throws SynologyException
     */
    public function shareList(): array
    {
        return $this->request(
            self::API_SERVICE_NAME,
            'Sharing',
            'entry.cgi',
            'list',
            [
                'offset' => 0,
                'limit' => 10,
            ],
            3
        );
    }
    /**
     * Delete file from a given path
     *
     * @param string $path like '/home'
     * @return mixed
     * @throws SynologyException
     */
    public function delete($path)
    {
        return $this->request(
            self::API_SERVICE_NAME,
            'Delete',
            'entry.cgi',
            'delete',
            ['path' => $path],
            1
        );
    }
}
