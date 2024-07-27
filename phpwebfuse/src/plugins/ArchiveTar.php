<?php

/**
 * MODIFIED FROM ORIGINAL 'Archive_Tar'
 * @link      http://pear.php.net/package/Archive_Tar
 */
if (!class_exists("\ArchiveTar")) {
    define('ARCHIVE_TAR_ATT_SEPARATOR', 90001);
    define('ARCHIVE_TAR_END_BLOCK', pack("a512", ''));

    if (!function_exists('gzopen') && function_exists('gzopen64')) {

        function gzopen($filename, $mode, $use_include_path = 0)
        {
            return gzopen64($filename, $mode, $use_include_path);
        }

    }

    if (!function_exists('gztell') && function_exists('gztell64')) {

        function gztell($zp)
        {
            return gztell64($zp);
        }

    }

    if (!function_exists('gzseek') && function_exists('gzseek64')) {

        function gzseek($zp, $offset, $whence = SEEK_SET)
        {
            return gzseek64($zp, $offset, $whence);
        }

    }

    /**
     * Creates a (compressed) Tar archive
     *
     * @package ArchiveTar
     */
    class ArchiveTar
    {
        /**
         * @var string Name of the Tar
         */
        public $_tarname = '';

        /**
         * @var boolean if true, the Tar file will be gzipped
         */
        public $_compress = false;

        /**
         * @var string Type of compression : 'none', 'gz', 'bz2' or 'lzma2'
         */
        public $_compress_type = 'none';

        /**
         * @var string Explode separator
         */
        public $_separator = ' ';

        /**
         * @var file descriptor
         */
        public $_file = 0;

        /**
         * @var string Local Tar name of a remote Tar (http:// or ftp://)
         */
        public $_temp_tarname = '';

        /**
         * @var string regular expression for ignoring files or directories
         */
        public $_ignore_regexp = '';

        /**
         * Format for data extraction
         *
         * @var string
         */
        public $_fmt = '';

        /**
         * @var int Length of the read buffer in bytes
         */
        protected $buffer_length;

        /**
         * Archive_Tar Class constructor. This flavour of the constructor only
         * declare a new Archive_Tar object, identifying it by the name of the
         * tar file.
         * If the compress argument is set the tar will be read or created as a
         * gzip or bz2 compressed TAR file.
         *
         * @param string $tarname The name of the tar archive to create
         * @param string $compress can be null, 'gz', 'bz2' or 'lzma2'. This
         *               parameter indicates if gzip, bz2 or lzma2 compression
         *               is required.  For compatibility reason the
         *               boolean value 'true' means 'gz'.
         * @param int $buffer_length Length of the read buffer in bytes
         *
         * @return bool
         */
        public function __construct($tarname, $compress = null, $buffer_length = 512)
        {
            $this->_compress = false;
            $this->_compress_type = 'none';
            if (($compress === null) || ($compress == '')) {
                if (@file_exists($tarname)) {
                    if ($fp = @fopen($tarname, "rb")) {
                        // look for gzip magic cookie
                        $data = fread($fp, 2);
                        fclose($fp);
                        if ($data == "\37\213") {
                            $this->_compress = true;
                            $this->_compress_type = 'gz';
                            // No sure it's enought for a magic code ....
                        } elseif ($data == "BZ") {
                            $this->_compress = true;
                            $this->_compress_type = 'bz2';
                        } elseif (file_get_contents($tarname, false, null, 1, 4) == '7zXZ') {
                            $this->_compress = true;
                            $this->_compress_type = 'lzma2';
                        }
                    }
                } else {
                    // probably a remote file or some file accessible
                    // through a stream interface
                    if (substr($tarname, -2) == 'gz') {
                        $this->_compress = true;
                        $this->_compress_type = 'gz';
                    } elseif ((substr($tarname, -3) == 'bz2') ||
                            (substr($tarname, -2) == 'bz')
                    ) {
                        $this->_compress = true;
                        $this->_compress_type = 'bz2';
                    } else {
                        if (substr($tarname, -2) == 'xz') {
                            $this->_compress = true;
                            $this->_compress_type = 'lzma2';
                        }
                    }
                }
            } else {
                if (($compress === true) || ($compress == 'gz')) {
                    $this->_compress = true;
                    $this->_compress_type = 'gz';
                } else {
                    if ($compress == 'bz2') {
                        $this->_compress = true;
                        $this->_compress_type = 'bz2';
                    } else {
                        if ($compress == 'lzma2') {
                            $this->_compress = true;
                            $this->_compress_type = 'lzma2';
                        } else {
                            $this->_error(
                                "Unsupported compression type '$compress'\n" .
                                    "Supported types are 'gz', 'bz2' and 'lzma2'.\n"
                            );
                            return false;
                        }
                    }
                }
            }
            $this->_tarname = $tarname;
            if ($this->_compress) { // assert zlib or bz2 or xz extension support
                if ($this->_compress_type == 'gz') {
                    $extname = 'zlib';
                } else {
                    if ($this->_compress_type == 'bz2') {
                        $extname = 'bz2';
                    } else {
                        if ($this->_compress_type == 'lzma2') {
                            $extname = 'xz';
                        }
                    }
                }

                if (!extension_loaded($extname)) {
                    PEAR::loadExtension($extname);
                }
                if (!extension_loaded($extname)) {
                    $this->_error(
                        "The extension '$extname' couldn't be found.\n" .
                            "Please make sure your version of PHP was built " .
                            "with '$extname' support.\n"
                    );
                    return false;
                }
            }


            if (version_compare(PHP_VERSION, "5.5.0-dev") < 0) {
                $this->_fmt = "a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/" .
                        "a8checksum/a1typeflag/a100link/a6magic/a2version/" .
                        "a32uname/a32gname/a8devmajor/a8devminor/a131prefix";
            } else {
                $this->_fmt = "Z100filename/Z8mode/Z8uid/Z8gid/Z12size/Z12mtime/" .
                        "Z8checksum/Z1typeflag/Z100link/Z6magic/Z2version/" .
                        "Z32uname/Z32gname/Z8devmajor/Z8devminor/Z131prefix";
            }


            $this->buffer_length = $buffer_length;
        }

        public function __destruct()
        {
            $this->_close();
            // ----- Look for a local copy to delete
            if ($this->_temp_tarname != '' && (bool) preg_match('/^tar[[:alnum:]]*\.tmp$/', $this->_temp_tarname)) {
                @unlink($this->_temp_tarname);
            }
        }

        /**
         * This method creates the archive file and add the files / directories
         * that are listed in $filelist.
         * If a file with the same name exist and is writable, it is replaced
         * by the new tar.
         * The method return false and a PEAR error text.
         * The $filelist parameter can be an array of string, each string
         * representing a filename or a directory name with their path if
         * needed. It can also be a single string with names separated by a
         * single blank.
         * For each directory added in the archive, the files and
         * sub-directories are also added.
         * See also createModify() method for more details.
         *
         * @param array $filelist An array of filenames and directory names, or a
         *              single string with names separated by a single
         *              blank space.
         *
         * @return bool true on success, false on error.
         * @see    createModify()
         */
        public function create($filelist)
        {
            return $this->createModify($filelist, '', '');
        }

        /**
         * This method add the files / directories that are listed in $filelist in
         * the archive. If the archive does not exist it is created.
         * The method return false and a PEAR error text.
         * The files and directories listed are only added at the end of the archive,
         * even if a file with the same name is already archived.
         * See also createModify() method for more details.
         *
         * @param array $filelist An array of filenames and directory names, or a
         *              single string with names separated by a single
         *              blank space.
         *
         * @return bool true on success, false on error.
         * @see    createModify()
         * @access public
         */
        public function add($filelist)
        {
            return $this->addModify($filelist, '', '');
        }

        /**
         * @param string $path
         * @param bool $preserve
         * @param bool $symlinks
         * @return bool
         */
        public function extract($path = '', $preserve = false, $symlinks = true)
        {
            return $this->extractModify($path, '', $preserve, $symlinks);
        }

        /**
         * @return array|int
         */
        public function listContent()
        {
            $list_detail = array();

            if ($this->_openRead()) {
                if (!$this->_extractList('', $list_detail, "list", '', '')) {
                    unset($list_detail);
                    $list_detail = 0;
                }
                $this->_close();
            }

            return $list_detail;
        }

        /**
         * This method creates the archive file and add the files / directories
         * that are listed in $filelist.
         * If the file already exists and is writable, it is replaced by the
         * new tar. It is a create and not an add. If the file exists and is
         * read-only or is a directory it is not replaced. The method return
         * false and a PEAR error text.
         * The $filelist parameter can be an array of string, each string
         * representing a filename or a directory name with their path if
         * needed. It can also be a single string with names separated by a
         * single blank.
         * The path indicated in $remove_dir will be removed from the
         * memorized path of each file / directory listed when this path
         * exists. By default nothing is removed (empty path '')
         * The path indicated in $add_dir will be added at the beginning of
         * the memorized path of each file / directory listed. However it can
         * be set to empty ''. The adding of a path is done after the removing
         * of path.
         * The path add/remove ability enables the user to prepare an archive
         * for extraction in a different path than the origin files are.
         * See also addModify() method for file adding properties.
         *
         * @param array $filelist An array of filenames and directory names,
         *                             or a single string with names separated by
         *                             a single blank space.
         * @param string $add_dir A string which contains a path to be added
         *                             to the memorized path of each element in
         *                             the list.
         * @param string $remove_dir A string which contains a path to be
         *                             removed from the memorized path of each
         *                             element in the list, when relevant.
         *
         * @return boolean true on success, false on error.
         * @see addModify()
         */
        public function createModify($filelist, $add_dir, $remove_dir = '')
        {
            $result = true;

            if (!$this->_openWrite()) {
                return false;
            }

            if ($filelist != '') {
                if (is_array($filelist)) {
                    $list = $filelist;
                } elseif (is_string($filelist)) {
                    $list = explode($this->_separator, $filelist);
                } else {
                    $this->_cleanFile();
                    $this->_error('Invalid file list');
                    return false;
                }

                $result = $this->_addList($list, $add_dir, $remove_dir);
            }

            if ($result) {
                $this->_writeFooter();
                $this->_close();
            } else {
                $this->_cleanFile();
            }

            return $result;
        }

        /**
         * This method add the files / directories listed in $filelist at the
         * end of the existing archive. If the archive does not yet exists it
         * is created.
         * The $filelist parameter can be an array of string, each string
         * representing a filename or a directory name with their path if
         * needed. It can also be a single string with names separated by a
         * single blank.
         * The path indicated in $remove_dir will be removed from the
         * memorized path of each file / directory listed when this path
         * exists. By default nothing is removed (empty path '')
         * The path indicated in $add_dir will be added at the beginning of
         * the memorized path of each file / directory listed. However it can
         * be set to empty ''. The adding of a path is done after the removing
         * of path.
         * The path add/remove ability enables the user to prepare an archive
         * for extraction in a different path than the origin files are.
         * If a file/dir is already in the archive it will only be added at the
         * end of the archive. There is no update of the existing archived
         * file/dir. However while extracting the archive, the last file will
         * replace the first one. This results in a none optimization of the
         * archive size.
         * If a file/dir does not exist the file/dir is ignored. However an
         * error text is send to PEAR error.
         * If a file/dir is not readable the file/dir is ignored. However an
         * error text is send to PEAR error.
         *
         * @param array $filelist An array of filenames and directory
         *                             names, or a single string with names
         *                             separated by a single blank space.
         * @param string $add_dir A string which contains a path to be
         *                             added to the memorized path of each
         *                             element in the list.
         * @param string $remove_dir A string which contains a path to be
         *                             removed from the memorized path of
         *                             each element in the list, when
         *                             relevant.
         *
         * @return bool true on success, false on error.
         */
        public function addModify($filelist, $add_dir, $remove_dir = '')
        {
            $result = true;

            if (!$this->_isArchive()) {
                $result = $this->createModify(
                    $filelist,
                    $add_dir,
                    $remove_dir
                );
            } else {
                if (is_array($filelist)) {
                    $list = $filelist;
                } elseif (is_string($filelist)) {
                    $list = explode($this->_separator, $filelist);
                } else {
                    $this->_error('Invalid file list');
                    return false;
                }

                $result = $this->_append($list, $add_dir, $remove_dir);
            }

            return $result;
        }

        /**
         * This method add a single string as a file at the
         * end of the existing archive. If the archive does not yet exists it
         * is created.
         *
         * @param string $filename A string which contains the full
         *                           filename path that will be associated
         *                           with the string.
         * @param string $string The content of the file added in
         *                           the archive.
         * @param bool|int $datetime A custom date/time (unix timestamp)
         *                           for the file (optional).
         * @param array $params An array of optional params:
         *                               stamp => the datetime (replaces
         *                                   datetime above if it exists)
         *                               mode => the permissions on the
         *                                   file (600 by default)
         *                               type => is this a link?  See the
         *                                   tar specification for details.
         *                                   (default = regular file)
         *                               uid => the user ID of the file
         *                                   (default = 0 = root)
         *                               gid => the group ID of the file
         *                                   (default = 0 = root)
         *
         * @return bool true on success, false on error.
         */
        public function addString($filename, $string, $datetime = false, $params = array())
        {
            $stamp = @$params["stamp"] ? $params["stamp"] : ($datetime ? $datetime : time());
            $mode = @$params["mode"] ? $params["mode"] : 0600;
            $type = @$params["type"] ? $params["type"] : "";
            $uid = @$params["uid"] ? $params["uid"] : "";
            $gid = @$params["gid"] ? $params["gid"] : "";
            $result = true;

            if (!$this->_isArchive()) {
                if (!$this->_openWrite()) {
                    return false;
                }
                $this->_close();
            }

            if (!$this->_openAppend()) {
                return false;
            }

            // Need to check the get back to the temporary file ? ....
            $result = $this->_addString($filename, $string, $datetime, $params);

            $this->_writeFooter();

            $this->_close();

            return $result;
        }

        /**
         * This method extract all the content of the archive in the directory
         * indicated by $path. When relevant the memorized path of the
         * files/dir can be modified by removing the $remove_path path at the
         * beginning of the file/dir path.
         * While extracting a file, if the directory path does not exists it is
         * created.
         * While extracting a file, if the file already exists it is replaced
         * without looking for last modification date.
         * While extracting a file, if the file already exists and is write
         * protected, the extraction is aborted.
         * While extracting a file, if a directory with the same name already
         * exists, the extraction is aborted.
         * While extracting a directory, if a file with the same name already
         * exists, the extraction is aborted.
         * While extracting a file/directory if the destination directory exist
         * and is write protected, or does not exist but can not be created,
         * the extraction is aborted.
         * If after extraction an extracted file does not show the correct
         * stored file size, the extraction is aborted.
         * When the extraction is aborted, a PEAR error text is set and false
         * is returned. However the result can be a partial extraction that may
         * need to be manually cleaned.
         *
         * @param string $path The path of the directory where the
         *                               files/dir need to by extracted.
         * @param string $remove_path Part of the memorized path that can be
         *                               removed if present at the beginning of
         *                               the file/dir path.
         * @param boolean $preserve Preserve user/group ownership of files
         * @param boolean $symlinks Allow symlinks.
         *
         * @return boolean true on success, false on error.
         * @see    extractList()
         */
        public function extractModify($path, $remove_path, $preserve = false, $symlinks = true)
        {
            $result = true;
            $list_detail = array();

            if ($result = $this->_openRead()) {
                $result = $this->_extractList(
                    $path,
                    $list_detail,
                    "complete",
                    0,
                    $remove_path,
                    $preserve,
                    $symlinks
                );
                $this->_close();
            }

            return $result;
        }

        /**
         * This method extract from the archive one file identified by $filename.
         * The return value is a string with the file content, or NULL on error.
         *
         * @param string $filename The path of the file to extract in a string.
         *
         * @return a string with the file content or NULL.
         */
        public function extractInString($filename)
        {
            if ($this->_openRead()) {
                $result = $this->_extractInString($filename);
                $this->_close();
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * This method extract from the archive only the files indicated in the
         * $filelist. These files are extracted in the current directory or
         * in the directory indicated by the optional $path parameter.
         * If indicated the $remove_path can be used in the same way as it is
         * used in extractModify() method.
         *
         * @param array $filelist An array of filenames and directory names,
         *                               or a single string with names separated
         *                               by a single blank space.
         * @param string $path The path of the directory where the
         *                               files/dir need to by extracted.
         * @param string $remove_path Part of the memorized path that can be
         *                               removed if present at the beginning of
         *                               the file/dir path.
         * @param boolean $preserve Preserve user/group ownership of files
         * @param boolean $symlinks Allow symlinks.
         *
         * @return bool true on success, false on error.
         * @see    extractModify()
         */
        public function extractList($filelist, $path = '', $remove_path = '', $preserve = false, $symlinks = true)
        {
            $result = true;
            $list_detail = array();

            if (is_array($filelist)) {
                $list = $filelist;
            } elseif (is_string($filelist)) {
                $list = explode($this->_separator, $filelist);
            } else {
                $this->_error('Invalid string list');
                return false;
            }

            if ($result = $this->_openRead()) {
                $result = $this->_extractList(
                    $path,
                    $list_detail,
                    "partial",
                    $list,
                    $remove_path,
                    $preserve,
                    $symlinks
                );
                $this->_close();
            }

            return $result;
        }

        /**
         * This method set specific attributes of the archive. It uses a variable
         * list of parameters, in the format attribute code + attribute values :
         * $arch->setAttribute(ARCHIVE_TAR_ATT_SEPARATOR, ',');
         *
         * @return bool true on success, false on error.
         */
        public function setAttribute()
        {
            $result = true;

            // ----- Get the number of variable list of arguments
            if (($size = func_num_args()) == 0) {
                return true;
            }

            // ----- Get the arguments
            $att_list = func_get_args();

            // ----- Read the attributes
            $i = 0;
            while ($i < $size) {

                // ----- Look for next option
                switch ($att_list[$i]) {
                    // ----- Look for options that request a string value
                    case ARCHIVE_TAR_ATT_SEPARATOR:
                        // ----- Check the number of parameters
                        if (($i + 1) >= $size) {
                            $this->_error(
                                'Invalid number of parameters for '
                                    . 'attribute ARCHIVE_TAR_ATT_SEPARATOR'
                            );
                            return false;
                        }

                        // ----- Get the value
                        $this->_separator = $att_list[$i + 1];
                        $i++;
                        break;

                    default:
                        $this->_error('Unknown attribute code ' . $att_list[$i] . '');
                        return false;
                }

                // ----- Next attribute
                $i++;
            }

            return $result;
        }

        /**
         * This method sets the regular expression for ignoring files and directories
         * at import, for example:
         * $arch->setIgnoreRegexp("#CVS|\.svn#");
         *
         * @param string $regexp regular expression defining which files or directories to ignore
         */
        public function setIgnoreRegexp($regexp)
        {
            $this->_ignore_regexp = $regexp;
        }

        /**
         * This method sets the regular expression for ignoring all files and directories
         * matching the filenames in the array list at import, for example:
         * $arch->setIgnoreList(array('CVS', '.svn', 'bin/tool'));
         *
         * @param array $list a list of file or directory names to ignore
         *
         * @access public
         */
        public function setIgnoreList($list)
        {
            $list = str_replace(array('#', '.', '^', '$'), array('\#', '\.', '\^', '\$'), $list);
            $regexp = '#/' . join('$|/', $list) . '#';
            $this->setIgnoreRegexp($regexp);
        }

        /**
         * @param string $message
         */
        public function _error($message)
        {
            throw new Exception($message);
        }

        /**
         * @param string $message
         */
        public function _warning($message)
        {
            throw new Exception($message);
        }

        /**
         * @param string $filename
         * @return bool
         */
        public function _isArchive($filename = null)
        {
            if ($filename == null) {
                $filename = $this->_tarname;
            }
            clearstatcache();
            return @is_file($filename) && !@is_link($filename);
        }

        /**
         * @return bool
         */
        public function _openWrite()
        {
            if ($this->_compress_type == 'gz' && function_exists('gzopen')) {
                $this->_file = @gzopen($this->_tarname, "wb9");
            } else {
                if ($this->_compress_type == 'bz2' && function_exists('bzopen')) {
                    $this->_file = @bzopen($this->_tarname, "w");
                } else {
                    if ($this->_compress_type == 'lzma2' && function_exists('xzopen')) {
                        $this->_file = @xzopen($this->_tarname, 'w');
                    } else {
                        if ($this->_compress_type == 'none') {
                            $this->_file = @fopen($this->_tarname, "wb");
                        } else {
                            $this->_error(
                                'Unknown or missing compression type ('
                                    . $this->_compress_type . ')'
                            );
                            return false;
                        }
                    }
                }
            }

            if ($this->_file == 0) {
                $this->_error(
                    'Unable to open in write mode \''
                        . $this->_tarname . '\''
                );
                return false;
            }

            return true;
        }

        /**
         * @return bool
         */
        public function _openRead()
        {
            if (strtolower(substr($this->_tarname, 0, 7)) == 'http://') {

                // ----- Look if a local copy need to be done
                if ($this->_temp_tarname == '') {
                    $this->_temp_tarname = uniqid('tar') . '.tmp';
                    if (!$file_from = @fopen($this->_tarname, 'rb')) {
                        $this->_error(
                            'Unable to open in read mode \''
                                . $this->_tarname . '\''
                        );
                        $this->_temp_tarname = '';
                        return false;
                    }
                    if (!$file_to = @fopen($this->_temp_tarname, 'wb')) {
                        $this->_error(
                            'Unable to open in write mode \''
                                . $this->_temp_tarname . '\''
                        );
                        $this->_temp_tarname = '';
                        return false;
                    }
                    while ($data = @fread($file_from, 1024)) {
                        @fwrite($file_to, $data);
                    }
                    @fclose($file_from);
                    @fclose($file_to);
                }

                // ----- File to open if the local copy
                $filename = $this->_temp_tarname;
            } else {
                // ----- File to open if the normal Tar file

                $filename = $this->_tarname;
            }

            if ($this->_compress_type == 'gz' && function_exists('gzopen')) {
                $this->_file = @gzopen($filename, "rb");
            } else {
                if ($this->_compress_type == 'bz2' && function_exists('bzopen')) {
                    $this->_file = @bzopen($filename, "r");
                } else {
                    if ($this->_compress_type == 'lzma2' && function_exists('xzopen')) {
                        $this->_file = @xzopen($filename, "r");
                    } else {
                        if ($this->_compress_type == 'none') {
                            $this->_file = @fopen($filename, "rb");
                        } else {
                            $this->_error(
                                'Unknown or missing compression type ('
                                    . $this->_compress_type . ')'
                            );
                            return false;
                        }
                    }
                }
            }

            if ($this->_file == 0) {
                $this->_error('Unable to open in read mode \'' . $filename . '\'');
                return false;
            }

            return true;
        }

        /**
         * @return bool
         */
        public function _openReadWrite()
        {
            if ($this->_compress_type == 'gz') {
                $this->_file = @gzopen($this->_tarname, "r+b");
            } else {
                if ($this->_compress_type == 'bz2') {
                    $this->_error(
                        'Unable to open bz2 in read/write mode \''
                            . $this->_tarname . '\' (limitation of bz2 extension)'
                    );
                    return false;
                } else {
                    if ($this->_compress_type == 'lzma2') {
                        $this->_error(
                            'Unable to open lzma2 in read/write mode \''
                                . $this->_tarname . '\' (limitation of lzma2 extension)'
                        );
                        return false;
                    } else {
                        if ($this->_compress_type == 'none') {
                            $this->_file = @fopen($this->_tarname, "r+b");
                        } else {
                            $this->_error(
                                'Unknown or missing compression type ('
                                    . $this->_compress_type . ')'
                            );
                            return false;
                        }
                    }
                }
            }

            if ($this->_file == 0) {
                $this->_error(
                    'Unable to open in read/write mode \''
                        . $this->_tarname . '\''
                );
                return false;
            }

            return true;
        }

        /**
         * @return bool
         */
        public function _close()
        {
            //if (isset($this->_file)) {
            if (is_resource($this->_file)) {
                if ($this->_compress_type == 'gz') {
                    @gzclose($this->_file);
                } else {
                    if ($this->_compress_type == 'bz2') {
                        @bzclose($this->_file);
                    } else {
                        if ($this->_compress_type == 'lzma2') {
                            @xzclose($this->_file);
                        } else {
                            if ($this->_compress_type == 'none') {
                                @fclose($this->_file);
                            } else {
                                $this->_error(
                                    'Unknown or missing compression type ('
                                        . $this->_compress_type . ')'
                                );
                            }
                        }
                    }
                }

                $this->_file = 0;
            }

            // ----- Look if a local copy need to be erase
            // Note that it might be interesting to keep the url for a time : ToDo
            if ($this->_temp_tarname != '') {
                @unlink($this->_temp_tarname);
                $this->_temp_tarname = '';
            }

            return true;
        }

        /**
         * @return bool
         */
        public function _cleanFile()
        {
            $this->_close();

            // ----- Look for a local copy
            if ($this->_temp_tarname != '') {
                // ----- Remove the local copy but not the remote tarname
                @unlink($this->_temp_tarname);
                $this->_temp_tarname = '';
            } else {
                // ----- Remove the local tarname file
                @unlink($this->_tarname);
            }
            $this->_tarname = '';

            return true;
        }

        /**
         * @param mixed $binary_data
         * @param integer $len
         * @return bool
         */
        public function _writeBlock($binary_data, $len = null)
        {
            if (is_resource($this->_file)) {
                if ($len === null) {
                    if ($this->_compress_type == 'gz') {
                        @gzputs($this->_file, $binary_data);
                    } else {
                        if ($this->_compress_type == 'bz2') {
                            @bzwrite($this->_file, $binary_data);
                        } else {
                            if ($this->_compress_type == 'lzma2') {
                                @xzwrite($this->_file, $binary_data);
                            } else {
                                if ($this->_compress_type == 'none') {
                                    @fputs($this->_file, $binary_data);
                                } else {
                                    $this->_error(
                                        'Unknown or missing compression type ('
                                            . $this->_compress_type . ')'
                                    );
                                }
                            }
                        }
                    }
                } else {
                    if ($this->_compress_type == 'gz') {
                        @gzputs($this->_file, $binary_data, $len);
                    } else {
                        if ($this->_compress_type == 'bz2') {
                            @bzwrite($this->_file, $binary_data, $len);
                        } else {
                            if ($this->_compress_type == 'lzma2') {
                                @xzwrite($this->_file, $binary_data, $len);
                            } else {
                                if ($this->_compress_type == 'none') {
                                    @fputs($this->_file, $binary_data, $len);
                                } else {
                                    $this->_error(
                                        'Unknown or missing compression type ('
                                            . $this->_compress_type . ')'
                                    );
                                }
                            }
                        }
                    }
                }
            }
            return true;
        }

        /**
         * @return null|string
         */
        public function _readBlock()
        {
            $block = null;
            if (is_resource($this->_file)) {
                if ($this->_compress_type == 'gz') {
                    $block = @gzread($this->_file, 512);
                } else {
                    if ($this->_compress_type == 'bz2') {
                        $block = @bzread($this->_file, 512);
                    } else {
                        if ($this->_compress_type == 'lzma2') {
                            $block = @xzread($this->_file, 512);
                        } else {
                            if ($this->_compress_type == 'none') {
                                $block = @fread($this->_file, 512);
                            } else {
                                $this->_error(
                                    'Unknown or missing compression type ('
                                        . $this->_compress_type . ')'
                                );
                            }
                        }
                    }
                }
            }
            return $block;
        }

        /**
         * @param null $len
         * @return bool
         */
        public function _jumpBlock($len = null)
        {
            if (is_resource($this->_file)) {
                if ($len === null) {
                    $len = 1;
                }

                if ($this->_compress_type == 'gz') {
                    @gzseek($this->_file, gztell($this->_file) + ($len * 512));
                } else {
                    if ($this->_compress_type == 'bz2') {
                        // ----- Replace missing bztell() and bzseek()
                        for ($i = 0; $i < $len; $i++) {
                            $this->_readBlock();
                        }
                    } else {
                        if ($this->_compress_type == 'lzma2') {
                            // ----- Replace missing xztell() and xzseek()
                            for ($i = 0; $i < $len; $i++) {
                                $this->_readBlock();
                            }
                        } else {
                            if ($this->_compress_type == 'none') {
                                @fseek($this->_file, $len * 512, SEEK_CUR);
                            } else {
                                $this->_error(
                                    'Unknown or missing compression type ('
                                        . $this->_compress_type . ')'
                                );
                            }
                        }
                    }
                }
            }
            return true;
        }

        /**
         * @return bool
         */
        public function _writeFooter()
        {
            if (is_resource($this->_file)) {
                // ----- Write the last 0 filled block for end of archive
                $binary_data = pack('a1024', '');
                $this->_writeBlock($binary_data);
            }
            return true;
        }

        /**
         * @param array $list
         * @param string $add_dir
         * @param string $remove_dir
         * @return bool
         */
        public function _addList($list, $add_dir, $remove_dir)
        {
            $result = true;
            $header = array();

            // ----- Remove potential windows directory separator
            $add_dir = $this->_translateWinPath($add_dir);
            $remove_dir = $this->_translateWinPath($remove_dir, false);

            if (!$this->_file) {
                $this->_error('Invalid file descriptor');
                return false;
            }

            if (sizeof($list) == 0) {
                return true;
            }

            foreach ($list as $filename) {
                if (!$result) {
                    break;
                }

                // ----- Skip the current tar name
                if ($filename == $this->_tarname) {
                    continue;
                }

                if ($filename == '') {
                    continue;
                }

                // ----- ignore files and directories matching the ignore regular expression
                if ($this->_ignore_regexp && preg_match($this->_ignore_regexp, '/' . $filename)) {
                    $this->_warning("File '$filename' ignored");
                    continue;
                }

                if (!file_exists($filename) && !is_link($filename)) {
                    $this->_warning("File '$filename' does not exist");
                    continue;
                }

                // ----- Add the file or directory header
                if (!$this->_addFile($filename, $header, $add_dir, $remove_dir)) {
                    return false;
                }

                if (@is_dir($filename) && !@is_link($filename)) {
                    if (!($hdir = opendir($filename))) {
                        $this->_warning("Directory '$filename' can not be read");
                        continue;
                    }
                    while (false !== ($hitem = readdir($hdir))) {
                        if (($hitem != '.') && ($hitem != '..')) {
                            if ($filename != ".") {
                                $temp_list[0] = $filename . '/' . $hitem;
                            } else {
                                $temp_list[0] = $hitem;
                            }

                            $result = $this->_addList(
                                $temp_list,
                                $add_dir,
                                $remove_dir
                            );
                        }
                    }

                    unset($temp_list);
                    unset($hdir);
                    unset($hitem);
                }
            }

            return $result;
        }

        /**
         * @param string $filename
         * @param mixed $header
         * @param string $add_dir
         * @param string $remove_dir
         * @param null $stored_filename
         * @return bool
         */
        public function _addFile($filename, &$header, $add_dir, $remove_dir, $stored_filename = null)
        {
            if (!$this->_file) {
                $this->_error('Invalid file descriptor');
                return false;
            }

            if ($filename == '') {
                $this->_error('Invalid file name');
                return false;
            }

            if (is_null($stored_filename)) {
                // ----- Calculate the stored filename
                $filename = $this->_translateWinPath($filename, false);
                $stored_filename = $filename;

                if (strcmp($filename, $remove_dir) == 0) {
                    return true;
                }

                if ($remove_dir != '') {
                    if (substr($remove_dir, -1) != '/') {
                        $remove_dir .= '/';
                    }

                    if (substr($filename, 0, strlen($remove_dir)) == $remove_dir) {
                        $stored_filename = substr($filename, strlen($remove_dir));
                    }
                }

                $stored_filename = $this->_translateWinPath($stored_filename);
                if ($add_dir != '') {
                    if (substr($add_dir, -1) == '/') {
                        $stored_filename = $add_dir . $stored_filename;
                    } else {
                        $stored_filename = $add_dir . '/' . $stored_filename;
                    }
                }

                $stored_filename = $this->_pathReduction($stored_filename);
            }

            if ($this->_isArchive($filename)) {
                if (($file = @fopen($filename, "rb")) == 0) {
                    $this->_warning(
                        "Unable to open file '" . $filename
                            . "' in binary read mode"
                    );
                    return true;
                }

                if (!$this->_writeHeader($filename, $stored_filename)) {
                    return false;
                }

                while (($buffer = fread($file, $this->buffer_length)) != '') {
                    $buffer_length = strlen("$buffer");
                    if ($buffer_length != $this->buffer_length) {
                        $pack_size = ((int) ($buffer_length / 512) + ($buffer_length % 512 !== 0 ? 1 : 0)) * 512;
                        $pack_format = sprintf('a%d', $pack_size);
                    } else {
                        $pack_format = sprintf('a%d', $this->buffer_length);
                    }
                    $binary_data = pack($pack_format, "$buffer");
                    $this->_writeBlock($binary_data);
                }

                fclose($file);
            } else {
                // ----- Only header for dir
                if (!$this->_writeHeader($filename, $stored_filename)) {
                    return false;
                }
            }

            return true;
        }

        /**
         * @param string $filename
         * @param string $string
         * @param bool $datetime
         * @param array $params
         * @return bool
         */
        public function _addString($filename, $string, $datetime = false, $params = array())
        {
            $stamp = @$params["stamp"] ? $params["stamp"] : ($datetime ? $datetime : time());
            $mode = @$params["mode"] ? $params["mode"] : 0600;
            $type = @$params["type"] ? $params["type"] : "";
            $uid = @$params["uid"] ? $params["uid"] : 0;
            $gid = @$params["gid"] ? $params["gid"] : 0;
            if (!$this->_file) {
                $this->_error('Invalid file descriptor');
                return false;
            }

            if ($filename == '') {
                $this->_error('Invalid file name');
                return false;
            }

            // ----- Calculate the stored filename
            $filename = $this->_translateWinPath($filename, false);

            // ----- If datetime is not specified, set current time
            if ($datetime === false) {
                $datetime = time();
            }

            if (!$this->_writeHeaderBlock(
                $filename,
                strlen($string),
                $stamp,
                $mode,
                $type,
                $uid,
                $gid
            )
            ) {
                return false;
            }

            $i = 0;
            while (($buffer = substr($string, (($i++) * 512), 512)) != '') {
                $binary_data = pack("a512", $buffer);
                $this->_writeBlock($binary_data);
            }

            return true;
        }

        /**
         * @param string $filename
         * @param string $stored_filename
         * @return bool
         */
        public function _writeHeader($filename, $stored_filename)
        {
            if ($stored_filename == '') {
                $stored_filename = $filename;
            }

            $reduced_filename = $this->_pathReduction($stored_filename);

            if (strlen($reduced_filename) > 99) {
                if (!$this->_writeLongHeader($reduced_filename, false)) {
                    return false;
                }
            }

            $linkname = '';
            if (@is_link($filename)) {
                $linkname = readlink($filename);
            }

            if (strlen($linkname) > 99) {
                if (!$this->_writeLongHeader($linkname, true)) {
                    return false;
                }
            }

            $info = lstat($filename);
            $uid = sprintf("%07s", DecOct($info[4]));
            $gid = sprintf("%07s", DecOct($info[5]));
            $perms = sprintf("%07s", DecOct($info['mode'] & 000777));
            $mtime = sprintf("%011s", DecOct($info['mtime']));

            if (@is_link($filename)) {
                $typeflag = '2';
                $size = sprintf("%011s", DecOct(0));
            } elseif (@is_dir($filename)) {
                $typeflag = "5";
                $size = sprintf("%011s", DecOct(0));
            } else {
                $typeflag = '0';
                clearstatcache();
                $size = sprintf("%011s", DecOct($info['size']));
            }

            $magic = 'ustar ';
            $version = ' ';
            $uname = '';
            $gname = '';

            if (function_exists('posix_getpwuid')) {
                $userinfo = posix_getpwuid($info[4]);
                $groupinfo = posix_getgrgid($info[5]);

                if (isset($userinfo['name'])) {
                    $uname = $userinfo['name'];
                }

                if (isset($groupinfo['name'])) {
                    $gname = $groupinfo['name'];
                }
            }

            $devmajor = '';
            $devminor = '';
            $prefix = '';

            $binary_data_first = pack(
                "a100a8a8a8a12a12",
                $reduced_filename,
                $perms,
                $uid,
                $gid,
                $size,
                $mtime
            );
            $binary_data_last = pack(
                "a1a100a6a2a32a32a8a8a155a12",
                $typeflag,
                $linkname,
                $magic,
                $version,
                $uname,
                $gname,
                $devmajor,
                $devminor,
                $prefix,
                ''
            );

            // ----- Calculate the checksum
            $checksum = 0;
            // ..... First part of the header
            for ($i = 0; $i < 148; $i++) {
                $checksum += ord(substr($binary_data_first, $i, 1));
            }
            // ..... Ignore the checksum value and replace it by ' ' (space)
            for ($i = 148; $i < 156; $i++) {
                $checksum += ord(' ');
            }
            // ..... Last part of the header
            for ($i = 156, $j = 0; $i < 512; $i++, $j++) {
                $checksum += ord(substr($binary_data_last, $j, 1));
            }

            // ----- Write the first 148 bytes of the header in the archive
            $this->_writeBlock($binary_data_first, 148);

            // ----- Write the calculated checksum
            $checksum = sprintf("%06s\0 ", DecOct($checksum));
            $binary_data = pack("a8", $checksum);
            $this->_writeBlock($binary_data, 8);

            // ----- Write the last 356 bytes of the header in the archive
            $this->_writeBlock($binary_data_last, 356);

            return true;
        }

        /**
         * @param string $filename
         * @param int $size
         * @param int $mtime
         * @param int $perms
         * @param string $type
         * @param int $uid
         * @param int $gid
         * @return bool
         */
        public function _writeHeaderBlock($filename, $size, $mtime = 0, $perms = 0, $type = '', $uid = 0, $gid = 0)
        {
            $filename = $this->_pathReduction($filename);

            if (strlen($filename) > 99) {
                if (!$this->_writeLongHeader($filename, false)) {
                    return false;
                }
            }

            if ($type == "5") {
                $size = sprintf("%011s", DecOct(0));
            } else {
                $size = sprintf("%011s", DecOct($size));
            }

            $uid = sprintf("%07s", DecOct($uid));
            $gid = sprintf("%07s", DecOct($gid));
            $perms = sprintf("%07s", DecOct($perms & 000777));

            $mtime = sprintf("%11s", DecOct($mtime));

            $linkname = '';

            $magic = 'ustar ';

            $version = ' ';

            if (function_exists('posix_getpwuid')) {
                $userinfo = posix_getpwuid($uid);
                $groupinfo = posix_getgrgid($gid);

                if ($userinfo === false || $groupinfo === false) {
                    $uname = '';
                    $gname = '';
                } else {
                    $uname = $userinfo['name'];
                    $gname = $groupinfo['name'];
                }
            } else {
                $uname = '';
                $gname = '';
            }

            $devmajor = '';

            $devminor = '';

            $prefix = '';

            $binary_data_first = pack(
                "a100a8a8a8a12A12",
                $filename,
                $perms,
                $uid,
                $gid,
                $size,
                $mtime
            );
            $binary_data_last = pack(
                "a1a100a6a2a32a32a8a8a155a12",
                $type,
                $linkname,
                $magic,
                $version,
                $uname,
                $gname,
                $devmajor,
                $devminor,
                $prefix,
                ''
            );

            // ----- Calculate the checksum
            $checksum = 0;
            // ..... First part of the header
            for ($i = 0; $i < 148; $i++) {
                $checksum += ord(substr($binary_data_first, $i, 1));
            }
            // ..... Ignore the checksum value and replace it by ' ' (space)
            for ($i = 148; $i < 156; $i++) {
                $checksum += ord(' ');
            }
            // ..... Last part of the header
            for ($i = 156, $j = 0; $i < 512; $i++, $j++) {
                $checksum += ord(substr($binary_data_last, $j, 1));
            }

            // ----- Write the first 148 bytes of the header in the archive
            $this->_writeBlock($binary_data_first, 148);

            // ----- Write the calculated checksum
            $checksum = sprintf("%06s ", DecOct($checksum));
            $binary_data = pack("a8", $checksum);
            $this->_writeBlock($binary_data, 8);

            // ----- Write the last 356 bytes of the header in the archive
            $this->_writeBlock($binary_data_last, 356);

            return true;
        }

        /**
         * @param string $filename
         * @return bool
         */
        public function _writeLongHeader($filename, $is_link = false)
        {
            $uid = sprintf("%07s", 0);
            $gid = sprintf("%07s", 0);
            $perms = sprintf("%07s", 0);
            $size = sprintf("%'011s", DecOct(strlen($filename)));
            $mtime = sprintf("%011s", 0);
            $typeflag = ($is_link ? 'K' : 'L');
            $linkname = '';
            $magic = 'ustar ';
            $version = ' ';
            $uname = '';
            $gname = '';
            $devmajor = '';
            $devminor = '';
            $prefix = '';

            $binary_data_first = pack(
                "a100a8a8a8a12a12",
                '././@LongLink',
                $perms,
                $uid,
                $gid,
                $size,
                $mtime
            );
            $binary_data_last = pack(
                "a1a100a6a2a32a32a8a8a155a12",
                $typeflag,
                $linkname,
                $magic,
                $version,
                $uname,
                $gname,
                $devmajor,
                $devminor,
                $prefix,
                ''
            );

            // ----- Calculate the checksum
            $checksum = 0;
            // ..... First part of the header
            for ($i = 0; $i < 148; $i++) {
                $checksum += ord(substr($binary_data_first, $i, 1));
            }
            // ..... Ignore the checksum value and replace it by ' ' (space)
            for ($i = 148; $i < 156; $i++) {
                $checksum += ord(' ');
            }
            // ..... Last part of the header
            for ($i = 156, $j = 0; $i < 512; $i++, $j++) {
                $checksum += ord(substr($binary_data_last, $j, 1));
            }

            // ----- Write the first 148 bytes of the header in the archive
            $this->_writeBlock($binary_data_first, 148);

            // ----- Write the calculated checksum
            $checksum = sprintf("%06s\0 ", DecOct($checksum));
            $binary_data = pack("a8", $checksum);
            $this->_writeBlock($binary_data, 8);

            // ----- Write the last 356 bytes of the header in the archive
            $this->_writeBlock($binary_data_last, 356);

            // ----- Write the filename as content of the block
            $i = 0;
            while (($buffer = substr($filename, (($i++) * 512), 512)) != '') {
                $binary_data = pack("a512", "$buffer");
                $this->_writeBlock($binary_data);
            }

            return true;
        }

        /**
         * @param mixed $binary_data
         * @param mixed $header
         * @return bool
         */
        public function _readHeader($binary_data, &$header)
        {
            if (strlen($binary_data) == 0) {
                $header['filename'] = '';
                return true;
            }

            if (strlen($binary_data) != 512) {
                $header['filename'] = '';
                $this->_error('Invalid block size : ' . strlen($binary_data));
                return false;
            }

            if (!is_array($header)) {
                $header = array();
            }
            // ----- Calculate the checksum
            $checksum = 0;
            // ..... First part of the header
            $binary_split = str_split($binary_data);
            $checksum += array_sum(array_map('ord', array_slice($binary_split, 0, 148)));
            $checksum += array_sum(array_map('ord', array(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',)));
            $checksum += array_sum(array_map('ord', array_slice($binary_split, 156, 512)));

            $data = unpack($this->_fmt, $binary_data);

            if (strlen($data["prefix"]) > 0) {
                $data["filename"] = "$data[prefix]/$data[filename]";
            }

            // ----- Extract the checksum
            $data_checksum = trim($data['checksum']);
            if (!preg_match('/^[0-7]*$/', $data_checksum)) {
                $this->_error(
                    'Invalid checksum for file "' . $data['filename']
                        . '" : ' . $data_checksum . ' extracted'
                );
                return false;
            }

            $header['checksum'] = OctDec($data_checksum);
            if ($header['checksum'] != $checksum) {
                $header['filename'] = '';

                // ----- Look for last block (empty block)
                if (($checksum == 256) && ($header['checksum'] == 0)) {
                    return true;
                }

                $this->_error(
                    'Invalid checksum for file "' . $data['filename']
                        . '" : ' . $checksum . ' calculated, '
                        . $header['checksum'] . ' expected'
                );
                return false;
            }

            // ----- Extract the properties
            $header['filename'] = rtrim($data['filename'], "\0");
            if ($this->_isMaliciousFilename($header['filename'])) {
                $this->_error(
                    'Malicious .tar detected, file "' . $header['filename'] .
                        '" will not install in desired directory tree'
                );
                return false;
            }
            $header['mode'] = OctDec(trim($data['mode']));
            $header['uid'] = OctDec(trim($data['uid']));
            $header['gid'] = OctDec(trim($data['gid']));
            $header['size'] = $this->_tarRecToSize($data['size']);
            $header['mtime'] = OctDec(trim($data['mtime']));
            if (($header['typeflag'] = $data['typeflag']) == "5") {
                $header['size'] = 0;
            }
            $header['link'] = trim($data['link']);
            /* ----- All these fields are removed form the header because
              they do not carry interesting info
              $header[magic] = trim($data[magic]);
              $header[version] = trim($data[version]);
              $header[uname] = trim($data[uname]);
              $header[gname] = trim($data[gname]);
              $header[devmajor] = trim($data[devmajor]);
              $header[devminor] = trim($data[devminor]);
             */

            return true;
        }

        /**
         * Convert Tar record size to actual size
         *
         * @param string $tar_size
         * @return size of tar record in bytes
         */
        private function _tarRecToSize($tar_size)
        {
            /*
             * First byte of size has a special meaning if bit 7 is set.
             *
             * Bit 7 indicates base-256 encoding if set.
             * Bit 6 is the sign bit.
             * Bits 5:0 are most significant value bits.
             */
            $ch = ord($tar_size[0]);
            if ($ch & 0x80) {
                // Full 12-bytes record is required.
                $rec_str = $tar_size . "\x00";

                $size = ($ch & 0x40) ? -1 : 0;
                $size = ($size << 6) | ($ch & 0x3f);

                for ($num_ch = 1; $num_ch < 12; ++$num_ch) {
                    $size = ($size * 256) + ord($rec_str[$num_ch]);
                }

                return $size;
            } else {
                return OctDec(trim($tar_size));
            }
        }

        /**
         * Detect and report a malicious file name
         *
         * @param string $file
         *
         * @return bool
         */
        private function _isMaliciousFilename($file)
        {
            if (strpos($file, '://') !== false) {
                return true;
            }
            if (strpos($file, '../') !== false || strpos($file, '..\\') !== false) {
                return true;
            }
            return false;
        }

        /**
         * @param $header
         * @return bool
         */
        public function _readLongHeader(&$header)
        {
            $filename = '';
            $filesize = $header['size'];
            $n = floor($header['size'] / 512);
            for ($i = 0; $i < $n; $i++) {
                $content = $this->_readBlock();
                $filename .= $content;
            }
            if (($header['size'] % 512) != 0) {
                $content = $this->_readBlock();
                $filename .= $content;
            }

            // ----- Read the next header
            $binary_data = $this->_readBlock();

            if (!$this->_readHeader($binary_data, $header)) {
                return false;
            }

            $filename = rtrim(substr($filename, 0, $filesize), "\0");
            $header['filename'] = $filename;
            if ($this->_isMaliciousFilename($filename)) {
                $this->_error(
                    'Malicious .tar detected, file "' . $filename .
                        '" will not install in desired directory tree'
                );
                return false;
            }

            return true;
        }

        /**
         * This method extract from the archive one file identified by $filename.
         * The return value is a string with the file content, or null on error.
         *
         * @param string $filename The path of the file to extract in a string.
         *
         * @return a string with the file content or null.
         */
        private function _extractInString($filename)
        {
            $result_str = "";

            while (strlen($binary_data = $this->_readBlock()) != 0) {
                if (!$this->_readHeader($binary_data, $header)) {
                    return null;
                }

                if ($header['filename'] == '') {
                    continue;
                }

                switch ($header['typeflag']) {
                    case 'L': {
                        if (!$this->_readLongHeader($header)) {
                            return null;
                        }
                    }
                        break;

                    case 'K': {
                        $link_header = $header;
                        if (!$this->_readLongHeader($link_header)) {
                            return null;
                        }
                        $header['link'] = $link_header['filename'];
                    }
                        break;
                }

                if ($header['filename'] == $filename) {
                    if ($header['typeflag'] == "5") {
                        $this->_error(
                            'Unable to extract in string a directory '
                                . 'entry {' . $header['filename'] . '}'
                        );
                        return null;
                    } else {
                        $n = floor($header['size'] / 512);
                        for ($i = 0; $i < $n; $i++) {
                            $result_str .= $this->_readBlock();
                        }
                        if (($header['size'] % 512) != 0) {
                            $content = $this->_readBlock();
                            $result_str .= substr(
                                $content,
                                0,
                                ($header['size'] % 512)
                            );
                        }
                        return $result_str;
                    }
                } else {
                    $this->_jumpBlock(ceil(($header['size'] / 512)));
                }
            }

            return null;
        }

        /**
         * @param string $path
         * @param string $list_detail
         * @param string $mode
         * @param string $file_list
         * @param string $remove_path
         * @param bool $preserve
         * @param bool $symlinks
         * @return bool
         */
        public function _extractList($path, &$list_detail, $mode, $file_list, $remove_path, $preserve = false, $symlinks = true)
        {
            $result = true;
            $nb = 0;
            $extract_all = true;
            $listing = false;

            $path = $this->_translateWinPath($path, false);
            if ($path == '' || (substr($path, 0, 1) != '/' && substr($path, 0, 3) != "../" && !strpos($path, ':'))
            ) {
                $path = "./" . $path;
            }
            $remove_path = $this->_translateWinPath($remove_path);

            // ----- Look for path to remove format (should end by /)
            if (($remove_path != '') && (substr($remove_path, -1) != '/')) {
                $remove_path .= '/';
            }
            $remove_path_size = strlen($remove_path);

            switch ($mode) {
                case "complete":
                    $extract_all = true;
                    $listing = false;
                    break;
                case "partial":
                    $extract_all = false;
                    $listing = false;
                    break;
                case "list":
                    $extract_all = false;
                    $listing = true;
                    break;
                default:
                    $this->_error('Invalid extract mode (' . $mode . ')');
                    return false;
            }

            clearstatcache();

            while (strlen($binary_data = $this->_readBlock()) != 0) {
                $extract_file = false;
                $extraction_stopped = 0;

                if (!$this->_readHeader($binary_data, $header)) {
                    return false;
                }

                if ($header['filename'] == '') {
                    continue;
                }

                switch ($header['typeflag']) {
                    case 'L': {
                        if (!$this->_readLongHeader($header)) {
                            return null;
                        }
                    }
                        break;

                    case 'K': {
                        $link_header = $header;
                        if (!$this->_readLongHeader($link_header)) {
                            return null;
                        }
                        $header['link'] = $link_header['filename'];
                    }
                        break;
                }

                // ignore extended / pax headers
                if ($header['typeflag'] == 'x' || $header['typeflag'] == 'g') {
                    $this->_jumpBlock(ceil(($header['size'] / 512)));
                    continue;
                }

                if ((!$extract_all) && (is_array($file_list))) {
                    // ----- By default no unzip if the file is not found
                    $extract_file = false;

                    for ($i = 0; $i < sizeof($file_list); $i++) {
                        // ----- Look if it is a directory
                        if (substr($file_list[$i], -1) == '/') {
                            // ----- Look if the directory is in the filename path
                            if ((strlen($header['filename']) > strlen($file_list[$i])) && (substr($header['filename'], 0, strlen($file_list[$i])) == $file_list[$i])
                            ) {
                                $extract_file = true;
                                break;
                            }
                        } // ----- It is a file, so compare the file names
                        elseif ($file_list[$i] == $header['filename']) {
                            $extract_file = true;
                            break;
                        }
                    }
                } else {
                    $extract_file = true;
                }

                // ----- Look if this file need to be extracted
                if (($extract_file) && (!$listing)) {
                    if (($remove_path != '') && (substr($header['filename'] . '/', 0, $remove_path_size) == $remove_path)
                    ) {
                        $header['filename'] = substr(
                            $header['filename'],
                            $remove_path_size
                        );
                        if ($header['filename'] == '') {
                            continue;
                        }
                    }
                    if (($path != './') && ($path != '/')) {
                        while (substr($path, -1) == '/') {
                            $path = substr($path, 0, strlen($path) - 1);
                        }

                        if (substr($header['filename'], 0, 1) == '/') {
                            $header['filename'] = $path . $header['filename'];
                        } else {
                            $header['filename'] = $path . '/' . $header['filename'];
                        }
                    }
                    if (file_exists($header['filename'])) {
                        if ((@is_dir($header['filename'])) && ($header['typeflag'] == '')
                        ) {
                            $this->_error(
                                'File ' . $header['filename']
                                    . ' already exists as a directory'
                            );
                            return false;
                        }
                        if (($this->_isArchive($header['filename'])) && ($header['typeflag'] == "5")
                        ) {
                            $this->_error(
                                'Directory ' . $header['filename']
                                    . ' already exists as a file'
                            );
                            return false;
                        }
                        if (!is_writeable($header['filename'])) {
                            $this->_error(
                                'File ' . $header['filename']
                                    . ' already exists and is write protected'
                            );
                            return false;
                        }
                        if (filemtime($header['filename']) > $header['mtime']) {
                            // To be completed : An error or silent no replace ?
                        }
                    } // ----- Check the directory availability and create it if necessary
                    elseif (($result = $this->_dirCheck(
                        ($header['typeflag'] == "5" ? $header['filename'] : dirname($header['filename']))
                    )) != 1
                    ) {
                        $this->_error('Unable to create path for ' . $header['filename']);
                        return false;
                    }

                    if ($extract_file) {
                        if ($header['typeflag'] == "5") {
                            if (!@file_exists($header['filename'])) {
                                if (!@mkdir($header['filename'], 0775)) {
                                    $this->_error(
                                        'Unable to create directory {'
                                            . $header['filename'] . '}'
                                    );
                                    return false;
                                }
                            }
                        } elseif ($header['typeflag'] == "2") {
                            if (!$symlinks) {
                                $this->_warning(
                                    'Symbolic links are not allowed. '
                                        . 'Unable to extract {'
                                        . $header['filename'] . '}'
                                );
                                return false;
                            }
                            $absolute_link = false;
                            $link_depth = 0;
                            if (strpos($header['link'], "/") === 0 || strpos($header['link'], ':') !== false) {
                                $absolute_link = true;
                            } else {
                                $s_filename = preg_replace('@^' . preg_quote($path) . '@', "", $header['filename']);
                                $s_linkname = str_replace('\\', '/', $header['link']);
                                foreach (explode("/", $s_filename) as $dir) {
                                    if ($dir === "..") {
                                        $link_depth--;
                                    } elseif ($dir !== "" && $dir !== ".") {
                                        $link_depth++;
                                    }
                                }
                                foreach (explode("/", $s_linkname) as $dir) {
                                    if ($link_depth <= 0) {
                                        break;
                                    }
                                    if ($dir === "..") {
                                        $link_depth--;
                                    } elseif ($dir !== "" && $dir !== ".") {
                                        $link_depth++;
                                    }
                                }
                            }
                            if ($absolute_link || $link_depth <= 0) {
                                $this->_error(
                                    'Out-of-path file extraction {'
                                        . $header['filename'] . ' --> ' .
                                        $header['link'] . '}'
                                );
                                return false;
                            }
                            if (@file_exists($header['filename'])) {
                                @unlink($header['filename']);
                            }
                            if (!@symlink($header['link'], $header['filename'])) {
                                $this->_error(
                                    'Unable to extract symbolic link {'
                                        . $header['filename'] . '}'
                                );
                                return false;
                            }
                        } else {
                            if (($dest_file = @fopen($header['filename'], "wb")) == 0) {
                                $this->_error(
                                    'Error while opening {' . $header['filename']
                                        . '} in write binary mode'
                                );
                                return false;
                            } else {
                                $n = floor($header['size'] / 512);
                                for ($i = 0; $i < $n; $i++) {
                                    $content = $this->_readBlock();
                                    fwrite($dest_file, $content, 512);
                                }
                                if (($header['size'] % 512) != 0) {
                                    $content = $this->_readBlock();
                                    fwrite($dest_file, $content, ($header['size'] % 512));
                                }

                                @fclose($dest_file);

                                if ($preserve) {
                                    @chown($header['filename'], $header['uid']);
                                    @chgrp($header['filename'], $header['gid']);
                                }

                                // ----- Change the file mode, mtime
                                @touch($header['filename'], $header['mtime']);
                                if ($header['mode'] & 0111) {
                                    // make file executable, obey umask
                                    $mode = fileperms($header['filename']) | (~umask() & 0111);
                                    @chmod($header['filename'], $mode);
                                }
                            }

                            // ----- Check the file size
                            clearstatcache();
                            if (!is_file($header['filename'])) {
                                $this->_error(
                                    'Extracted file ' . $header['filename']
                                        . 'does not exist. Archive may be corrupted.'
                                );
                                return false;
                            }

                            $filesize = filesize($header['filename']);
                            if ($filesize != $header['size']) {
                                $this->_error(
                                    'Extracted file ' . $header['filename']
                                        . ' does not have the correct file size \''
                                        . $filesize
                                        . '\' (' . $header['size']
                                        . ' expected). Archive may be corrupted.'
                                );
                                return false;
                            }
                        }
                    } else {
                        $this->_jumpBlock(ceil(($header['size'] / 512)));
                    }
                } else {
                    $this->_jumpBlock(ceil(($header['size'] / 512)));
                }

                /* TBC : Seems to be unused ...
                  if ($this->_compress)
                  $end_of_file = @gzeof($this->_file);
                  else
                  $end_of_file = @feof($this->_file);
                 */

                if ($listing || $extract_file || $extraction_stopped) {
                    // ----- Log extracted files
                    if (($file_dir = dirname($header['filename'])) == $header['filename']
                    ) {
                        $file_dir = '';
                    }
                    if ((substr($header['filename'], 0, 1) == '/') && ($file_dir == '')) {
                        $file_dir = '/';
                    }

                    $list_detail[$nb++] = $header;
                    if (is_array($file_list) && (count($list_detail) == count($file_list))) {
                        return true;
                    }
                }
            }

            return true;
        }

        /**
         * @return bool
         */
        public function _openAppend()
        {
            if (filesize($this->_tarname) == 0) {
                return $this->_openWrite();
            }

            if ($this->_compress) {
                $this->_close();

                if (!@rename($this->_tarname, $this->_tarname . ".tmp")) {
                    $this->_error(
                        'Error while renaming \'' . $this->_tarname
                            . '\' to temporary file \'' . $this->_tarname
                            . '.tmp\''
                    );
                    return false;
                }

                if ($this->_compress_type == 'gz') {
                    $temp_tar = @gzopen($this->_tarname . ".tmp", "rb");
                } elseif ($this->_compress_type == 'bz2') {
                    $temp_tar = @bzopen($this->_tarname . ".tmp", "r");
                } elseif ($this->_compress_type == 'lzma2') {
                    $temp_tar = @xzopen($this->_tarname . ".tmp", "r");
                }


                if ($temp_tar == 0) {
                    $this->_error(
                        'Unable to open file \'' . $this->_tarname
                            . '.tmp\' in binary read mode'
                    );
                    @rename($this->_tarname . ".tmp", $this->_tarname);
                    return false;
                }

                if (!$this->_openWrite()) {
                    @rename($this->_tarname . ".tmp", $this->_tarname);
                    return false;
                }

                if ($this->_compress_type == 'gz') {
                    $end_blocks = 0;

                    while (!@gzeof($temp_tar)) {
                        $buffer = @gzread($temp_tar, 512);
                        if ($buffer == ARCHIVE_TAR_END_BLOCK || strlen($buffer) == 0) {
                            $end_blocks++;
                            // do not copy end blocks, we will re-make them
                            // after appending
                            continue;
                        } elseif ($end_blocks > 0) {
                            for ($i = 0; $i < $end_blocks; $i++) {
                                $this->_writeBlock(ARCHIVE_TAR_END_BLOCK);
                            }
                            $end_blocks = 0;
                        }
                        $binary_data = pack("a512", $buffer);
                        $this->_writeBlock($binary_data);
                    }

                    @gzclose($temp_tar);
                } elseif ($this->_compress_type == 'bz2') {
                    $end_blocks = 0;

                    while (strlen($buffer = @bzread($temp_tar, 512)) > 0) {
                        if ($buffer == ARCHIVE_TAR_END_BLOCK || strlen($buffer) == 0) {
                            $end_blocks++;
                            // do not copy end blocks, we will re-make them
                            // after appending
                            continue;
                        } elseif ($end_blocks > 0) {
                            for ($i = 0; $i < $end_blocks; $i++) {
                                $this->_writeBlock(ARCHIVE_TAR_END_BLOCK);
                            }
                            $end_blocks = 0;
                        }
                        $binary_data = pack("a512", $buffer);
                        $this->_writeBlock($binary_data);
                    }

                    @bzclose($temp_tar);
                } elseif ($this->_compress_type == 'lzma2') {
                    $end_blocks = 0;

                    while (strlen($buffer = @xzread($temp_tar, 512)) > 0) {
                        if ($buffer == ARCHIVE_TAR_END_BLOCK || strlen($buffer) == 0) {
                            $end_blocks++;
                            // do not copy end blocks, we will re-make them
                            // after appending
                            continue;
                        } elseif ($end_blocks > 0) {
                            for ($i = 0; $i < $end_blocks; $i++) {
                                $this->_writeBlock(ARCHIVE_TAR_END_BLOCK);
                            }
                            $end_blocks = 0;
                        }
                        $binary_data = pack("a512", $buffer);
                        $this->_writeBlock($binary_data);
                    }

                    @xzclose($temp_tar);
                }

                if (!@unlink($this->_tarname . ".tmp")) {
                    $this->_error(
                        'Error while deleting temporary file \''
                            . $this->_tarname . '.tmp\''
                    );
                }
            } else {
                // ----- For not compressed tar, just add files before the last
                //       one or two 512 bytes block
                if (!$this->_openReadWrite()) {
                    return false;
                }

                clearstatcache();
                $size = filesize($this->_tarname);

                // We might have zero, one or two end blocks.
                // The standard is two, but we should try to handle
                // other cases.
                fseek($this->_file, $size - 1024);
                if (fread($this->_file, 512) == ARCHIVE_TAR_END_BLOCK) {
                    fseek($this->_file, $size - 1024);
                } elseif (fread($this->_file, 512) == ARCHIVE_TAR_END_BLOCK) {
                    fseek($this->_file, $size - 512);
                }
            }

            return true;
        }

        /**
         * @param $filelist
         * @param string $add_dir
         * @param string $remove_dir
         * @return bool
         */
        public function _append($filelist, $add_dir = '', $remove_dir = '')
        {
            if (!$this->_openAppend()) {
                return false;
            }

            if ($this->_addList($filelist, $add_dir, $remove_dir)) {
                $this->_writeFooter();
            }

            $this->_close();

            return true;
        }

        /**
         * Check if a directory exists and create it (including parent
         * dirs) if not.
         *
         * @param string $dir directory to check
         *
         * @return bool true if the directory exists or was created
         */
        public function _dirCheck($dir)
        {
            clearstatcache();
            if ((@is_dir($dir)) || ($dir == '')) {
                return true;
            }

            $parent_dir = dirname($dir);

            if (($parent_dir != $dir) &&
                    ($parent_dir != '') &&
                    (!$this->_dirCheck($parent_dir))
            ) {
                return false;
            }

            if (!@mkdir($dir, 0775)) {
                $this->_error("Unable to create directory '$dir'");
                return false;
            }

            return true;
        }

        /**
         * Compress path by changing for example "/dir/foo/../bar" to "/dir/bar",
         * rand emove double slashes.
         *
         * @param string $dir path to reduce
         *
         * @return string reduced path
         */
        private function _pathReduction($dir)
        {
            $result = '';

            // ----- Look for not empty path
            if ($dir != '') {
                // ----- Explode path by directory names
                $list = explode('/', $dir);

                // ----- Study directories from last to first
                for ($i = sizeof($list) - 1; $i >= 0; $i--) {
                    // ----- Look for current path
                    if ($list[$i] == ".") {
                        // ----- Ignore this directory
                        // Should be the first $i=0, but no check is done
                    } else {
                        if ($list[$i] == "..") {
                            // ----- Ignore it and ignore the $i-1
                            $i--;
                        } else {
                            if (($list[$i] == '') && ($i != (sizeof($list) - 1)) && ($i != 0)
                            ) {
                                // ----- Ignore only the double '//' in path,
                                // but not the first and last /
                            } else {
                                $result = $list[$i] . ($i != (sizeof($list) - 1) ? '/'
                                        . $result : '');
                            }
                        }
                    }
                }
            }

            if (defined('OS_WINDOWS') && OS_WINDOWS) {
                $result = strtr($result, '\\', '/');
            }

            return $result;
        }

        /**
         * @param $path
         * @param bool $remove_disk_letter
         * @return string
         */
        public function _translateWinPath($path, $remove_disk_letter = true)
        {
            if (defined('OS_WINDOWS') && OS_WINDOWS) {
                // ----- Look for potential disk letter
                if (($remove_disk_letter) && (($position = strpos($path, ':')) != false)
                ) {
                    $path = substr($path, $position + 1);
                }
                // ----- Change potential windows directory separator
                if ((strpos($path, '\\') > 0) || (substr($path, 0, 1) == '\\')) {
                    $path = strtr($path, '\\', '/');
                }
            }
            return $path;
        }
    }

}
