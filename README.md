PermsHiker
===============
2016-06-16


PermsHiker helps migrating permissions from a server to another.

PermsHiker can be installed as a [planet](https://github.com/lingtalfi/Observer/blob/master/article/article.planetReference.eng.md).



What's the goal?
-------------------

The goal is to be a useful tool for when you migrate your application from server A to server B.
Migrating from a server sometimes happen.

When it does, you have to recreate the environment from server A to server B.
This means you will have to copy the files, recreate the database if any, and re-apply special permissions if any.

PermsHiker helps you with the permissions. 
Basically, you can port permissions and ownerships from server A to server B.




How to use?
--------------

```php
<?php


use PermsHiker\Applier\PermsHikerApplier;
use PermsHiker\Parser\PermsHikerParser;

require_once "bigbang.php"; // start the local universe (https://github.com/lingtalfi/Observer/blob/master/article/article.planetReference.eng.md)


//------------------------------------------------------------------------------/
// DO NOT EXECUTE THIS SCRIPT AS IS
//------------------------------------------------------------------------------/
/**
 * The demo code below illustrates the whole migration workflow with PermsHiker.
 * In production, you will want to split the code and use only part of it at a time.
 *
 */


$dir = '/path/to/appdir'; // this is the application which permissions we want to port
$file = $dir . '/_permsmap.txt'; // this is where the PermsHiker will create/read from the perms map


//------------------------------------------------------------------------------/
// STEP 1: CREATE THE PERMS MAP
//------------------------------------------------------------------------------/
/**
 * The line below tells PermsHiker to create the perms map and to return
 * it as an array.
 *
 * We use the commonPerms to indicate that the PermsHiker should ignore a file if:
 *
 *  - it is a directory owned by myuser:staff with mode 0755
 *  - or it is a file owned by myuser:staff with mode 0644
 *
 */
a(PermsHikerParser::create()
    ->addCommonPerm('myuser', 'staff', 'd', 0755)
    ->addCommonPerm('myuser', 'staff', 'f', 0644)
    ->toArray($dir));


//------------------------------------------------------------------------------/
// STEP 1b: CREATE THE PERMS MAP
//------------------------------------------------------------------------------/
/**
 * This is a variation of the section above (in prod, you would use either one section
 * or the other, but not both at the same time).
 *
 * The line below tells PermsHiker to create the perms map and to put it into the file $file.
 *
 * We use the commonPerms to indicate that the PermsHiker should ignore a file if:
 *
 *  - it is a directory owned by 501:20 with mode 0755
 *  - or it is a file owned by 501:20 with mode 0644
 *
 */
a(PermsHikerParser::create()
    ->addCommonPerm(501, 20, 'd', 0755)
    ->addCommonPerm(501, 20, 'f', 0644)
    ->toFile($dir, $file));


//------------------------------------------------------------------------------/
// STEP 2: APPLY THE PERMISSIONS ON SERVER B
//------------------------------------------------------------------------------/
/**
 * So now we assume that we are on server B, and our permission map is $file.
 * The target application is $dir.
 *
 * In the example below, I illustrate how to use the owner and ownerGroup adapters.
 * The PermsHiker uses the adapters to convert any owner (and/or ownergroup) found in the
 * permission map into an owner (or ownergroup) of your choice.
 *
 */
a(PermsHikerApplier::create()
    ->setStrictMode(true)
    ->setOwnerAdapter([
        '_www' => 'www-data',
    ])
    ->setOwnerGroupAdapter([
//        '_www' => 'www-data',
        // sometimes, you'll prefer to work with id rather than names, this is just for demonstration purpose
        '70' => 'www-data',
    ])
    ->fromFile($file, $dir));
```




How does it work?
-------------------

![permshiker workflow](https://s19.postimg.org/upg6b3xwj/Perms_Hiker_idea.jpg)

The main idea is to use a medium file (created by PermsHiker automatically) that contains the permission information.
This special file is called a perms map.

The workflow is basically:

- you have a source directory and a target directory
- you parse the source directory and PermsHiker create the perms map for you
- you do your migration things (moving files to server B, recreating databases, ...)
- now on server B, you tell PermsHiker to read the perms map (now copied on server B), and it automagically re-applies the permissions for you
- that's all

Important: you need to execute the PermsHike as root, because only root can change
permissions and ownerships.



Perms map
-------------
A **perms map** is a simple text file that contains information about 
the owner and permissions of potentially every file for a given directory.
It has a human friendly format, so not only computer can use it, but human
can also take some info out of it if they wanted to do so.

Here is what a perms map looks like:

```
./app:www-data:www-data:0755
./app/file1:www-data:www-data:0644
./app/file2:www-data:www-data:0644
./app/dir2:joe:joe:0755
```

Links (symlinks) are always ignored by the PermsHiker.

The **perms list** is the list of permissions, as given in the previous example.
Every entry is written on its own line.
Each entry is composed of 4 components separated by the colon (:) symbol:

- the path
- the owner
- the owner group
- the permission

All paths are relative, and hence start with the dot slash (./) prefix.
 


Implementation 
-------------------------

![permshiker implementation](https://s19.postimg.org/6mzcg8h9f/PermsHiker-implementation.jpg)


commonPerms
---------------

To create the perms map, PermsHiker scans every entry of your application recursively.
Depending on your application size, that might be a lot of entries to parse.

The idea behind commonPerms is to to ignore files that we don't intend to apply permissions on.
Typically, we don't need to apply permissions on the directories and files owned by the owner of the application.
 
By doing so, we substantially reduces the number of lines of the perms map, and it becomes more human readable.

Here is how it works.
Imagine we have an user named ling that owns the source directory (our application).
To set a commonPerm, we want to define the following:

- owner 
- owner group
- type (d=directory, f=file)
- permission (chmod)
                
When those 4 match an entry, then PermsHiker will ignore that entry.

 
### Applier


- adapter
    
    the adapter allows you to change the owner and/or ownerGroup to apply
    to the target directory.
    
    This feature was originally developed for tests purposes,
    but can be useful as long as server A and server B have different user and/or groups.
    
    The workflow with adapter is to set the adapter BEFORE you actually call the fromFile 
    or fromArray methods.
    
    There are two separate adapters: one for the owner, and the other for the ownerGroup.
   
    
    
    



Dependencies
------------------

- [lingtalfi/Bat 1.31](https://github.com/lingtalfi/Bat)
- [lingtalfi/DirScanner 1.3.0](https://github.com/lingtalfi/DirScanner)




History Log
------------------
    
- 1.0.0 -- 2016-06-16

    - initial commit
    
    

