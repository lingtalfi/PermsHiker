<?php

namespace PermsHiker\Applier;

use Bat\PermTool;
use PermsHiker\Exception\PermsHikerException;


/**
 * PermsHikerApplier
 * @author Lingtalfi
 * 2016-06-16
 *
 */
class PermsHikerApplier
{

    private $errors;

    /**
     * If this mode is true (default is false),
     * errors are turned into exception (not caught by the applier).
     */
    private $strictMode;

    /**
     * adapters are implemented as simple arrays.
     */
    private $ownerAdapter;
    private $ownerGroupAdapter;

    public function __construct()
    {
        $this->errors = [];
        $this->ownerAdapter = [];
        $this->ownerGroupAdapter = [];
        $this->strictMode = false;
    }


    public static function create()
    {
        return new static();
    }

    public function fromFile($file, $targetDir = null)
    {
        if ($this->isRoot()) {


            if (false !== ($lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))) {
                if (null === $targetDir) {
                    $targetDir = dirname($file);
                }
                $targetDir = rtrim($targetDir, '/');

                foreach ($lines as $line) {
                    $line = trim($line);

                    /**
                     * parsing the separator from the end,
                     * so to avoid potential conflict of a path
                     * that contains the separator.
                     */
                    $enil = strrev($line);
                    $p = explode(':', $enil, 4);
                    if (4 === count($p)) {
                        $mode = strrev($p[0]);
                        $ownerGroup = $this->getRealOwnerGroup(strrev($p[1]));
                        $owner = $this->getRealOwner(strrev($p[2]));
                        $path = strrev($p[3]);


                        $realFile = $targetDir . '/' . $path;

                        if (false === PermTool::chperms($realFile, $mode, $owner, $ownerGroup)) {
                            $this->error("could not change the permissions of $realFile");
                        }                        
                    }
                    else {
                        $this->error("invalid perms list entry notation, please check the doc: $line");
                    }
                }
            }
            else {
                $this->error("Can not read from $file");
            }
        }
        else {
            $this->error("You must be root to execute this method");
        }

        return (false === $this->hasErrors());
    }


    //------------------------------------------------------------------------------/
    // 
    //------------------------------------------------------------------------------/
    public function setStrictMode($strictMode)
    {
        $this->strictMode = $strictMode;
        return $this;
    }

    public function hasErrors()
    {
        return (0 !== count($this->errors));
    }

    public function getErrors()
    {
        return $this->errors;
    }


    public function setOwnerAdapter(array $adapter)
    {
        $this->ownerAdapter = $adapter;
        return $this;
    }

    public function setOwnerGroupAdapter(array $adapter)
    {
        $this->ownerGroupAdapter = $adapter;
        return $this;
    }


    //------------------------------------------------------------------------------/
    // 
    //------------------------------------------------------------------------------/
    private function error($m)
    {
        if (true === $this->strictMode) {
            throw new PermsHikerException($m);
        }
        $this->errors[] = $m;
    }

    private function isRoot()
    {
        if ('root' === exec("whoami")) {
            return true;
        }
        return false;
    }

    private function getRealOwner($owner)
    {
        if (array_key_exists($owner, $this->ownerAdapter)) {
            return $this->ownerAdapter[$owner];
        }
        return $owner;
    }

    private function getRealOwnerGroup($ownerGroup)
    {
        if (array_key_exists($ownerGroup, $this->ownerGroupAdapter)) {
            return $this->ownerGroupAdapter[$ownerGroup];
        }
        return $ownerGroup;
    }

}
