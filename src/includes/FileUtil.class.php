<?php
/**
 * Zikula Application Framework
 *
 * @copyright Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: FileUtil.class.php 27746 2009-11-27 09:31:16Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Robert Gasch rgasch@gmail.com
 * @package Zikula_Core
 */

/**
 * FileUtil
 *
 * @package Zikula_Core
 * @subpackage FileUtil
 */
class FileUtil
{
    /**
     * Given a filename (complete with path) get the file basename
     *
     * @param  filename   The filename to process
     * @param  keepDot    whether or not to return the dot with the basename
     *
     * @return string     The file's filename
     */
    function getFilebase($filename, $keepDot = false)
    {
        if (!$filename) {
            return pn_exit(__f('%s: %s is empty', array('FileUtil::getFilename', 'filename')));
        }

        $base = basename($filename);
        $p = strrpos($base, '.');
        if ($p !== false) {
            if ($keepDot) {
                return substr($base, 0, $p + 1);
            } else {
                return substr($base, 0, $p);
            }
        }

        return $filename;
    }

    /**
     * Get the basename of a filename
     *
     * @param  filename     The filename to process
     *
     * @return string       The file's basename
     */
    function getBasename($filename)
    {
        if (!$filename) {
            return pn_exit(__f('%s: %s is empty', array('FileUtil::getBasename', 'filename')));
        }

        return basename($filename);
    }

    /**
     * Get the file's extension
     *
     * @param  filename    The filename to process
     * @param  keepDot     whether or not to return the '.' with the extension
     *
     * @return string      The file's extension
     */
    function getExtension($filename, $keepDot = false)
    {
        if (!$filename) {
            return pn_exit(__f('%s: %s is empty', array('FileUtil::getExtension', 'filename')));
        }

        $p = strrpos($filename, '.');
        if ($p !== false) {
            if ($keepDot) {
                return substr($filename, $p);
            } else {
                return substr($filename, $p + 1);
            }
        }

        return '';
    }

    /**
     * Strip the file's extension
     *
     * @param  filename   The filename to process
     * @param  keepDot    whether or not to return the '.' with the extension
     *
     * @return string     The filename without the extension
     */
    function stripExtension($filename, $keepDot = false)
    {
        if (!$filename) {
            return pn_exit(__f('%s: %s is empty', array('FileUtil::stripExtension', 'filename')));
        }

        $p = strrpos($filename, '.');
        if ($p !== false) {
            if ($keepDot) {
                return substr($filename, 0, $p + 1);
            } else {
                return substr($filename, 0, $p);
            }
        }

        return $filename;
    }

    /**
     * Generate a random filename
     *
     * @param min           Minimum number of characters
     * @param max           Maximum number of characters
     * @param useupper      whether to use uppercase characters
     * @param usenumbers    whether to use numeric characters
     * @param usespecial    whether to use special characters
     *
     * @return string       The generated filename extension
     */
    function generateRandomFilename($min, $max, $useupper = false, $usenumbers = true, $usespecial = false)
    {
        $rnd = '';
        $chars = 'abcdefghijklmnopqrstuvwxyz';

        if ($useupper) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        if ($usenumbers) {
            $chars .= '0123456789';
        }

        if ($usespecial) {
            $chars .= '~@#$%^*()_+-={}|][';
        }

        $charlen = strlen($chars) - 1;

        $len = mt_rand($min, $max);
        for ($i = 0; $i < $len; $i++) {
            $rnd .= $chars[mt_rand(0, $charlen)];
        }

        return $rnd;
    }

    /**
     * Generate a file/directory listing (can be recusive)
     *
     * @param rootPath      The root-path we wish to start at
     * @param recurse       whether or not to recurse directories (optional) (default=true)
     * @param relativePath  whether or not to list relative (vs abolute) paths (optional) (default=true)
     * @param extension     The file extension or array of extensions to scan for (optional) (default=null)
     * @param type          The type of object (file or directory or both) to return (optional) (default=null)
     * @param nestedData    Whether or not to return a nested data set (optional) (default=false)
     *
     * @return array        the array of files in the given path
     */
    function getFiles($rootPath, $recurse=true, $relativePath=true, $extensions=null, $type=null, $nestedData=false)
    {
        $files = array();
        $type  = strtolower ($type);

        if ($type && $type != 'd' && $type != 'f') {
            pn_exit(__f('Error! Invalid %s received.', "type [$type]"));
        } 

        if (!file_exists($rootPath) || !is_dir($rootPath) || !is_readable($rootPath)) {
            return $files;
        }

        $skiplist = array('.', '..', 'CVS', '.svn', '_svn', 'index.html', '.htaccess', '.DS_Store', '-vti-cnf');

        $el = (is_string($extensions) ? strlen($extensions) : 0);
        $dh = opendir($rootPath);
        while (($file = readdir($dh)) !== false)
        {
            $relativepath = $relativePath;
            if (!in_array($file, $skiplist)) {
                $path = "$rootPath/$file";

                if ($type == 'f' && !$recurse && is_dir($path)) {
                    continue;
                }

                if ($type == 'd' && !is_dir($path)) {
                    continue;
                }

                $filenameToStore = $path;
                if ($relativePath) {
                    $filenameToStore = $file;
                    if (is_string($relativepath)) {
                        if (!$nestedData) {
                            $filenameToStore = "$relativepath/$file";
                        }
                        $relativepath = "$relativepath/$file";
                    } else {
                        $relativepath = $file;
                    }
                }

                if ($recurse && is_dir($path)) {
                    if ($nestedData) { 
                        $files[$filenameToStore] = (array)FileUtil::getFiles($path, $recurse, $relativepath, $extensions, $type, $nestedData);
                    } else {
                        $files = array_merge ((array)$files,
                                              (array)FileUtil::getFiles($path, $recurse, $relativepath, $extensions, $type, $nestedData));
                    }

                } elseif (!$extensions) {
                    $files[] = $filenameToStore;

                } elseif (is_array($extensions)) {
                    foreach ($extensions as $extension) {
                        if (substr($file, -strlen($extension)) == $extension) {
                            $files[] = $filenameToStore;
                            break;
                        }
                    }

                } elseif (substr($file, -$el) == $extensions) {
                    $files[] = $filenameToStore;
                }
            }
        }

        closedir($dh);
        return $files;
    }

    /**
     * Recursiveley create a directory path
     *
     * @param  path       The path we wish to generate
     * @param  mode       The (UNIX) mode we wish to create the files with
     * @param  absolute   Allow absolute paths (default=false) (optional)
     *
     * @return boolean    TRUE on success, FALSE on failure
     */
    function mkdirs($path, $mode = null, $absolute = false)
    {
        if (is_dir($path)) {
            return true;
        }

        $pPath = DataUtil::formatForOS(dirname($path), $absolute);
        if (FileUtil::mkdirs($pPath, $mode) === false) {
            return false;
        }

        if ($mode) {
            return mkdir($path, $mode);
        } else {
            return mkdir($path);
        }
    }

    /**
     * Recursiveley delete given directory path
     *
     * @param  path       The path/folder we wish to delete
     * @param  absolute   Allow absolute paths (default=false) (optional)
     *
     * @return boolean    TRUE on success, FALSE on failure
     */
    function deldir($path, $absolute = false)
    {
        $path = DataUtil::formatForOS($path, $absolute);

        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                if (is_dir("$path/$file") && ($file != '.' && $file != '..')) {
                    FileUtil::deldir("$path/$file");
                } else if ($file != '.' && $file != '..') {
                    unlink("$path/$file");
                }
            }
            closedir($dh);
        }

        return rmdir($path);
    }

    /**
     * Read a file's contents and return them as a string. This method also
     * opens and closes the file.
     *
     * @param  filename   The file to read
     * @param  absolute   Allow absolute paths (default=false) (optional)
     *
     * @return mixed      The file's contents or FALSE on failure
     */
    function readFile($filename, $absolute = false)
    {
        if (!strlen($filename)) {
            return pn_exit(__f('%s: %s is empty', array('FileUtil::readFile', 'filename')));
        }

        return file_get_contents(DataUtil::formatForOS($filename, $absolute));
    }

    /**
     * Read a file's contents and return them as an array of lines.
     * This method also opens and closes the file.
     *
     * @param filename    The file to read
     * @param absolute    Allow absolute paths (default=false) (optional)
     *
     * @return mixed      The file's contents as array or FALSE on failure
     */
    function readFileLines($filename, $absolute = false)
    {
        $lines = false;
        if ($data = FileUtil::readFile($filename, $absolute)) {
            $lines = explode("\n", $data);
        }
        return $lines;
    }

    /**
     * Read a serialized's file's contents and return them as a string
     * This method also opens and closes the file.
     *
     * @param  filename    The file to read
     * @param  absolute    Allow absolute paths (default=false) (optional)
     *
     * @return mixed       The file's contents or FALSE on failure
     */
    function readSerializedFile($filename, $absolute = false)
    {
        return unserialize(FileUtil::readFile($filename, $absolute));
    }

    /**
     * Take an existing filename and 'randomize' it
     *
     * @param  filename   The filename to randomize
     * @param  dir        The directory the file should be in
     *
     * @return string     The 'randomized' filename
     */
    function randomizeFilename($filename, $dir)
    {
        $ext = '';
        $time = time();

        if (!$filename) {
            $filename = FileUtil::generateRandomFilename(10, 15, true, true);
        } else if (strrchr($filename, '.') !== false) { // do we have an extension?
            $ext = FileUtil::getExtension($filename);
            $filename = FileUtil::stripExtension($filename);
        }

        if ($dir) {
            $dir .= '/';
        }

        if ($ext) {
            $rnd = $dir . $filename . '_' . $time . '.' . $ext;
        } else {
            $rnd = $dir . $filename . '_' . $time;
        }

        return $rnd;
    }

    /**
     * Write a string to a file
     * This method also opens and closes the file.
     * On versions >= PHP5 this method will use the file_put_contents API
     *
     * @param  filename   The file to write
     * @param  data       The data to write to the file
     * @param  absolute   Allow absolute paths (default=false) (optional)
     *
     * @return bool       TRUE on success, FALSE on failure
     */
    function writeFile($filename, $data = '', $absolute = false)
    {
        if (!$filename) {
            return pn_exit(__f('%s: %s is empty', array('FileUtil::writeFile', 'filename')));
        }

        if (version_compare(phpversion(), '5.0.0', '>=')) {
            return (bool)file_put_contents(DataUtil::formatForOS($filename, $absolute), $data);
        }

        // for PHP4 versions which don't have file_put_contents
        if ($fd = fopen(DataUtil::formatForOS($filename, $absolute), 'w')) {
            fwrite($fd, $data);
            return fclose($fd);
        }

        return false;
    }

    /**
     * Write a serialized string to a file
     * This method also opens and closes the file.
     *
     * @param filename   The file to write
     * @param data       The data to write to the file
     * @param absolute   Allow absolute paths (default=false) (optional)
     *
     * @return bool      TRUE on success, FALSE on failure
     */
    function writeSerializedFile($filename, $data, $absolute = false)
    {
        return FileUtil::writeFile($filename, serialize($data), $absolute);
    }

    /**
     * Upload a file
     *
     * @param key           The filename key to use in accessing the file data
     * @param destination   The destination where the file should end up
     * @param newName       The new name to give the file (optional) (default='')
     * @param absolute      Allow absolute paths (default=false) (optional)
     *
     * @return mixed        TRUE if success, a string with the error message on failure
     */
    function uploadFile($key, $destination, $newName = '', $absolute = false)
    {
        if (!$key) {
            return pn_exit(__f('%s: called with invalid %s.', array('FileUtil::uploadFile', 'key')));
        }

        if (!$destination) {
            return pn_exit(__f('%s: called with invalid %s.', array('FileUtil::uploadFile', 'destination')));
        }

        $msg = '';
        if (!is_dir($destination) || !is_writable($destination)) {
            if (SecurityUtil::checkPermission('::', '::', ACCESS_ADMIN)) {
                $msg = __f('The destination path [%s] does not exist or is not writable', $destination);
            } else {
                $msg = __('The destination path does not exist or is not writable');
            }
        } elseif (isset($_FILES[$key]['name'])) {
            $uploadfile = $_FILES[$key]['tmp_name'];
            $origfile   = $_FILES[$key]['name'];

            if ($newName) {
                $uploaddest = DataUtil::formatForOS("$destination/$newName", $absolute);
            } else {
                $uploaddest = DataUtil::formatForOS("$destination/$origfile", $absolute);
            }

            $rc = move_uploaded_file($uploadfile, $uploaddest);

            if ($rc) {
                return true;
            } else {
                switch ($_FILES[$key]['error'])
                {
                    case 1:
                        $msg = __('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
                        break;
                    case 2:
                        $msg = __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.');
                        break;
                    case 3:
                        $msg = __('The uploaded file was only partially uploaded.');
                        break;
                    case 4:
                        $msg = __('No file was uploaded.');
                        break;
                    case 5:
                        $msg = __('Uploaded file size 0 bytes.');
                        break;
                }
            }
        }

        return $msg;
    }
}
