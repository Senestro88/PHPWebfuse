<?php

/**
 * MODIFIED FROM ORIGINAL 'Archive_Zip'
 * @link      http://pear.php.net/package/Archive_Zip
 */
if (!class_exists("\ArchiveZip")) {
    // Constants
    define('ARCHIVE_ZIP_READ_BLOCK_SIZE', 2048);

    // File list separator
    define('ARCHIVE_ZIP_SEPARATOR', ',');

    define('ARCHIVE_ZIP_TEMPORARY_DIR', sys_get_temp_dir());

    // Error codes
    define('ARCHIVE_ZIP_ERR_NO_ERROR', 0);
    define('ARCHIVE_ZIP_ERR_WRITE_OPEN_FAIL', -1);
    define('ARCHIVE_ZIP_ERR_READ_OPEN_FAIL', -2);
    define('ARCHIVE_ZIP_ERR_INVALID_PARAMETER', -3);
    define('ARCHIVE_ZIP_ERR_MISSING_FILE', -4);
    define('ARCHIVE_ZIP_ERR_FILENAME_TOO_LONG', -5);
    define('ARCHIVE_ZIP_ERR_INVALID_ZIP', -6);
    define('ARCHIVE_ZIP_ERR_BAD_EXTRACTED_FILE', -7);
    define('ARCHIVE_ZIP_ERR_DIR_CREATE_FAIL', -8);
    define('ARCHIVE_ZIP_ERR_BAD_EXTENSION', -9);
    define('ARCHIVE_ZIP_ERR_BAD_FORMAT', -10);
    define('ARCHIVE_ZIP_ERR_DELETE_FILE_FAIL', -11);
    define('ARCHIVE_ZIP_ERR_RENAME_FILE_FAIL', -12);
    define('ARCHIVE_ZIP_ERR_BAD_CHECKSUM', -13);
    define('ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP', -14);
    define('ARCHIVE_ZIP_ERR_MISSING_OPTION_VALUE', -15);
    define('ARCHIVE_ZIP_ERR_INVALID_PARAM_VALUE', -16);

    // Warning codes
    define('ARCHIVE_ZIP_WARN_NO_WARNING', 0);
    define('ARCHIVE_ZIP_WARN_FILE_EXIST', 1);

    // Methods parameters
    define('ARCHIVE_ZIP_PARAM_PATH', 'path');
    define('ARCHIVE_ZIP_PARAM_ADD_PATH', 'add_path');
    define('ARCHIVE_ZIP_PARAM_REMOVE_PATH', 'remove_path');
    define('ARCHIVE_ZIP_PARAM_REMOVE_ALL_PATH', 'remove_all_path');
    define('ARCHIVE_ZIP_PARAM_SET_CHMOD', 'set_chmod');
    define('ARCHIVE_ZIP_PARAM_EXTRACT_AS_STRING', 'extract_as_string');
    define('ARCHIVE_ZIP_PARAM_NO_COMPRESSION', 'no_compression');
    define('ARCHIVE_ZIP_PARAM_BY_NAME', 'by_name');
    define('ARCHIVE_ZIP_PARAM_BY_INDEX', 'by_index');
    define('ARCHIVE_ZIP_PARAM_BY_PREG', 'by_preg');

    define('ARCHIVE_ZIP_PARAM_PRE_EXTRACT', 'callback_pre_extract');
    define('ARCHIVE_ZIP_PARAM_POST_EXTRACT', 'callback_post_extract');
    define('ARCHIVE_ZIP_PARAM_PRE_ADD', 'callback_pre_add');
    define('ARCHIVE_ZIP_PARAM_POST_ADD', 'callback_post_add');

    class ArchiveZip
    {
        public $zipname = '';
        public $zip_fd = 0;
        public $error_code = 1;
        public $error_string = '';

        public function __construct($zipname)
        {
            return $this->ArchiveZip($zipname);
        }

        public function ArchiveZip($zipname)
        {
            if (!extension_loaded('zlib')) {
                throw new Exception("The extension 'zlib' couldn't be found.\n" . "Please make sure your version of PHP was built " . "with 'zlib' support.\n");
                return false;
            }
            // Set the attributes
            $this->zipname = $zipname;
            $this->zip_fd = 0;
            return;
        }

        // }}}
        // {{{ create()

        /**
         * This method creates a Zip Archive with the filename set with
         * the constructor.
         * The files and directories indicated in $filelist
         * are added in the archive.
         * When a directory is in the list, the directory and its content is added
         * in the archive.
         * The methods takes a variable list of parameters in $params.
         * The supported parameters for this method are :
         *   'add_path' : Add a path to the archived files.
         *   'remove_path' : Remove the specified 'root' path of the archived files.
         *   'remove_all_path' : Remove all the path of the archived files.
         *   'no_compression' : The archived files will not be compressed.
         *
         *
         * @param mixed $filelist The list of the files or folders to add.
         *                             It can be a string with filenames separated
         *                             by a comma, or an array of filenames.
         * @param mixed $params   An array of variable parameters and values.
         *
         * @return mixed An array of file description on success,
         *               an error code on error
         */
        public function create($filelist, $params = 0)
        {
            $this->_errorReset();

            // Set default values
            if ($params === 0) {
                $params = array();
            }
            if ($this->_check_parameters(
                $params,
                array('no_compression' => false,
                                'add_path' => "",
                                'remove_path' => "",
                                'remove_all_path' => false)
            ) != 1) {
                return 0;
            }

            // Look if the $filelist is really an array
            $result_list = array();
            if (is_array($filelist)) {
                $v_result = $this->_create($filelist, $result_list, $params);
            } elseif (is_string($filelist)) {
                // Create a list with the elements from the string
                $v_list = explode(ARCHIVE_ZIP_SEPARATOR, $filelist);

                $v_result = $this->_create($v_list, $result_list, $params);
            } else {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
                    'Invalid variable type p_filelist'
                );
                $v_result = ARCHIVE_ZIP_ERR_INVALID_PARAMETER;
            }

            if ($v_result != 1) {
                return 0;
            }

            return $result_list;
        }

        // }}}
        // {{{ add()

        /**
         * This method add files or directory in an existing Zip Archive.
         * If the Zip Archive does not exist it is created.
         * The files and directories to add are indicated in $filelist.
         * When a directory is in the list, the directory and its content is added
         * in the archive.
         * The methods takes a variable list of parameters in $params.
         * The supported parameters for this method are :
         *   'add_path' : Add a path to the archived files.
         *   'remove_path' : Remove the specified 'root' path of the archived files.
         *   'remove_all_path' : Remove all the path of the archived files.
         *   'no_compression' : The archived files will not be compressed.
         *   'callback_pre_add' : A callback function that will be called before
         *                        each entry archiving.
         *   'callback_post_add' : A callback function that will be called after
         *                         each entry archiving.
         *
         * @param mixed $filelist The list of the files or folders to add.
         *                               It can be a string with filenames separated
         *                               by a comma, or an array of filenames.
         * @param mixed $params   An array of variable parameters and values.
         *
         * @return mixed An array of file description on success,
         *               0 on an unrecoverable failure, an error code is logged.
         * @access public
         */
        public function add($filelist, $params = 0)
        {
            $this->_errorReset();

            // Set default values
            if ($params === 0) {
                $params = array();
            }
            if ($this->_check_parameters(
                $params,
                array('no_compression' => false,
                                'add_path' => '',
                                'remove_path' => '',
                                'remove_all_path' => false,
                                'callback_pre_add' => '',
                                'callback_post_add' => '')
            ) != 1) {
                return 0;
            }

            // Look if the $filelist is really an array
            $result_list = array();
            if (is_array($filelist)) {
                // Call the create fct
                $v_result = $this->_add($filelist, $result_list, $params);
            } elseif (is_string($filelist)) {
                // Create a list with the elements from the string
                $v_list = explode(ARCHIVE_ZIP_SEPARATOR, $filelist);

                // Call the create fct
                $v_result = $this->_add($v_list, $result_list, $params);
            } else {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
                    "add() : Invalid variable type p_filelist"
                );
                $v_result = ARCHIVE_ZIP_ERR_INVALID_PARAMETER;
            }

            if ($v_result != 1) {
                return 0;
            }

            return $result_list;
        }

        // }}}
        // {{{ listContent()

        /**
         * This method gives the names and properties of the files and directories
         * which are present in the zip archive.
         * The properties of each entries in the list are :
         *   filename : Name of the file.
         *              For create() or add() it's the filename given by the user.
         *              For an extract() it's the filename of the extracted file.
         *   stored_filename : Name of the file / directory stored in the archive.
         *   size : Size of the stored file.
         *   compressed_size : Size of the file's data compressed in the archive
         *                     (without the zip headers overhead)
         *   mtime : Last known modification date of the file (UNIX timestamp)
         *   comment : Comment associated with the file
         *   folder : true | false (indicates if the entry is a folder)
         *   index : index of the file in the archive (-1 when not available)
         *   status : status of the action on the entry (depending of the action) :
         *            Values are :
         *              ok : OK !
         *              filtered : the file/dir was not extracted (filtered by user)
         *              already_a_directory : the file can't be extracted because a
         *                                    directory with the same name already
         *                                    exists
         *              write_protected : the file can't be extracted because a file
         *                                with the same name already exists and is
         *                                write protected
         *              newer_exist : the file was not extracted because a newer
         *                            file already exists
         *              path_creation_fail : the file is not extracted because the
         *                                   folder does not exists and can't be
         *                                   created
         *              write_error : the file was not extracted because there was a
         *                            error while writing the file
         *              read_error : the file was not extracted because there was a
         *                           error while reading the file
         *              invalid_header : the file was not extracted because of an
         *                               archive format error (bad file header)
         * Note that each time a method can continue operating when there
         * is an error on a single file, the error is only logged in the file status.
         *
         * @access public
         * @return mixed An array of file description on success,
         *               0 on an unrecoverable failure, an error code is logged.
         */
        public function listContent()
        {
            $this->_errorReset();

            // Check archive
            if (!$this->_checkFormat()) {
                return (0);
            }

            $v_list = array();
            if ($this->_list($v_list) != 1) {
                unset($v_list);
                return (0);
            }

            return $v_list;
        }

        // }}}
        // {{{ extract()

        /**
         * This method extract the files and folders which are in the zip archive.
         * It can extract all the archive or a part of the archive by using filter
         * feature (extract by name, by index, by preg). The extraction
         * can occur in the current path or an other path.
         * All the advanced features are activated by the use of variable
         * parameters.
         * The return value is an array of entry descriptions which gives
         * information on extracted files (See listContent()).
         * The method may return a success value (an array) even if some files
         * are not correctly extracted (see the file status in listContent()).
         * The supported variable parameters for this method are :
         *   'add_path' : Path where the files and directories are to be extracted
         *   'remove_path' : First part ('root' part) of the memorized path
         *                   (if similar) to remove while extracting.
         *   'remove_all_path' : Remove all the memorized path while extracting.
         *   'extract_as_string' :
         *   'set_chmod' : After the extraction of the file the indicated mode
         *                 will be set.
         *   'by_name' : It can be a string with file/dir names separated by ',',
         *               or an array of file/dir names to extract from the archive.
         *   'by_index' : A string with range of indexes separated by ',',
         *                (sample "1,3-5,12").
         *   'by_preg' : A regular expression (preg) that must match the extracted
         *               filename.
         *   'callback_pre_extract' : A callback function that will be called before
         *                            each entry extraction.
         *   'callback_post_extract' : A callback function that will be called after
         *                            each entry extraction.
         *
         * @param mixed $params An array of variable parameters and values.
         *
         * @return mixed An array of file description on success,
         *               0 on an unrecoverable failure, an error code is logged.
         */
        public function extract($params = 0)
        {

            $this->_errorReset();

            // Check archive
            if (!$this->_checkFormat()) {
                return (0);
            }

            // Set default values
            if ($params === 0) {
                $params = array();
            }

            if ($this->_check_parameters(
                $params,
                array('extract_as_string' => false,
                                'add_path' => '',
                                'remove_path' => '',
                                'remove_all_path' => false,
                                'callback_pre_extract' => '',
                                'callback_post_extract' => '',
                                'set_chmod' => 0,
                                'by_name' => '',
                                'by_index' => '',
                                'by_preg' => '')
            ) != 1) {
                return 0;
            }

            // Call the extracting fct
            $v_list = array();
            if ($this->_extractByRule($v_list, $params) != 1) {
                unset($v_list);
                return (0);
            }

            return $v_list;
        }

        // }}}
        // {{{ delete()

        /**
         * This methods delete archive entries in the zip archive.
         * Notice that at least one filtering rule (set by the variable parameter
         * list) must be set.
         * Also notice that if you delete a folder entry, only the folder entry
         * is deleted, not all the files bellonging to this folder.
         * The supported variable parameters for this method are :
         *   'by_name' : It can be a string with file/dir names separated by ',',
         *               or an array of file/dir names to delete from the archive.
         *   'by_index' : A string with range of indexes separated by ',',
         *                (sample "1,3-5,12").
         *   'by_preg' : A regular expression (preg) that must match the extracted
         *               filename.
         *
         * @param mixed $params An array of variable parameters and values.
         *
         * @return mixed An array of file description on success,
         *               0 on an unrecoverable failure, an error code is logged.
         */
        public function delete($params)
        {
            $this->_errorReset();

            // Check archive
            if (!$this->_checkFormat()) {
                return (0);
            }

            // Set default values
            if ($this->_check_parameters(
                $params,
                array('by_name' => '',
                                'by_index' => '',
                                'by_preg' => '')
            ) != 1) {
                return 0;
            }

            // Check that at least one rule is set
            if (($params['by_name'] == '') && ($params['by_index'] == '') && ($params['by_preg'] == '')) {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
                    'At least one filtering rule must'
                        . ' be set as parameter'
                );
                return 0;
            }

            // Call the delete fct
            $v_list = array();
            if ($this->_deleteByRule($v_list, $params) != 1) {
                unset($v_list);
                return (0);
            }

            return $v_list;
        }

        // }}}
        // {{{ properties()

        /**
         * This method gives the global properties of the archive.
         *  The properties are :
         *    nb : Number of files in the archive
         *    comment : Comment associated with the archive file
         *    status : not_exist, ok
         *
         * @return mixed An array with the global properties or 0 on error.
         */
        public function properties()
        {
            $this->_errorReset();

            // Check archive
            if (!$this->_checkFormat()) {
                return (0);
            }

            // Default properties
            $v_prop = array();
            $v_prop['comment'] = '';
            $v_prop['nb'] = 0;
            $v_prop['status'] = 'not_exist';

            // Look if file exists
            if (@is_file($this->zipname)) {
                // Open the zip file
                if (($this->zip_fd = @fopen($this->zipname, 'rb')) == 0) {
                    $this->_errorLog(
                        ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
                        'Unable to open archive \'' . $this->zipname
                            . '\' in binary read mode'
                    );
                    return 0;
                }

                // Read the central directory informations
                $v_central_dir = array();
                if (($v_result = $this->_readEndCentralDir($v_central_dir)) != 1) {
                    return 0;
                }

                $this->_closeFd();

                // Set the user attributes
                $v_prop['comment'] = $v_central_dir['comment'];
                $v_prop['nb'] = $v_central_dir['entries'];
                $v_prop['status'] = 'ok';
            }

            return $v_prop;
        }

        // }}}
        // {{{ duplicate()

        /**
         * This method creates an archive by copying the content of an other one.
         * If the archive already exist, it is replaced by the new one without
         * any warning.
         *
         *
         * @param mixed $archive It can be a valid ArchiveZip object or
         *                            the filename of a valid zip archive.
         *
         * @return integer 1 on success, 0 on failure.
         */
        public function duplicate($archive)
        {
            $this->_errorReset();

            // Look if the $archive is a ArchiveZip object
            if ((is_object($archive)) && (strtolower(get_class($archive)) == 'archive_zip')) {
                $v_result = $this->_duplicate($archive->_zipname);
            } elseif (is_string($archive)) {
                // Check that $archive is a valid zip file
                // TBC : Should also check the archive format
                if (!is_file($archive)) {
                    $this->_errorLog(
                        ARCHIVE_ZIP_ERR_MISSING_FILE,
                        "No file with filename '" . $archive . "'"
                    );
                    $v_result = ARCHIVE_ZIP_ERR_MISSING_FILE;
                } else {
                    $v_result = $this->_duplicate($archive);
                }
            } else {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
                    "Invalid variable type p_archive_to_add"
                );
                $v_result = ARCHIVE_ZIP_ERR_INVALID_PARAMETER;
            }

            return $v_result;
        }

        // }}}
        // {{{ merge()

        /**
         *  This method merge a valid zip archive at the end of the
         *  archive identified by the ArchiveZip object.
         *  If the archive ($this) does not exist, the merge becomes a duplicate.
         *  If the archive to add does not exist, the merge is a success.
         *
         * @param mixed $archive_to_add It can be a valid ArchiveZip object or
         *                                 the filename of a valid zip archive.
         *
         * @return integer 1 on success, 0 on failure.
         */
        public function merge($archive_to_add)
        {
            $v_result = 1;
            $this->_errorReset();

            // Check archive
            if (!$this->_checkFormat()) {
                return (0);
            }

            // Look if the $archive_to_add is a ArchiveZip object
            if ((is_object($archive_to_add)) && (strtolower(get_class($archive_to_add)) == 'archive_zip')) {
                $v_result = $this->_merge($archive_to_add);
            } elseif (is_string($archive_to_add)) {
                // Create a temporary archive
                $v_object_archive = new ArchiveZip($archive_to_add);

                // Merge the archive
                $v_result = $this->_merge($v_object_archive);
            } else {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
                    "Invalid variable type p_archive_to_add"
                );
                $v_result = ARCHIVE_ZIP_ERR_INVALID_PARAMETER;
            }

            return $v_result;
        }

        // }}}
        // {{{ errorCode()

        /**
         * Method that gives the lastest error code.
         *
         * @access public
         * @return integer The error code value.
         */
        public function errorCode()
        {
            return ($this->error_code);
        }

        // }}}
        // {{{ errorName()

        /**
         * This method gives the latest error code name.
         *
         * @param boolean $with_code If true, gives the name and the int value.
         *
         * @access public
         * @return string The error name.
         */
        public function errorName($with_code = false)
        {
            $v_const_list = get_defined_constants();

            // Extract error constants from all const.
            foreach ($v_const_list as $v_key => $v_value) {
                if (substr($v_key, 0, strlen('ARCHIVE_ZIP_ERR_')) == 'ARCHIVE_ZIP_ERR_') {
                    $v_error_list[$v_key] = $v_value;
                }
            }

            // Search the name form the code value
            $v_key = array_search($this->error_code, $v_error_list, true);
            if ($v_key != false) {
                $v_value = $v_key;
            } else {
                $v_value = 'NoName';
            }

            if ($with_code) {
                return ($v_value . ' (' . $this->error_code . ')');
            } else {
                return ($v_value);
            }
        }

        // }}}
        // {{{ errorInfo()

        /**
         * This method returns the description associated with the latest error.
         *
         * @param boolean $full If set to true gives the description with the
         *                         error code, the name and the description.
         *                         If set to false gives only the description
         *                         and the error code.
         *
         * @access public
         * @return string The error description.
         */
        public function errorInfo($full = false)
        {
            if ($full) {
                return ($this->errorName(true) . " : " . $this->error_string);
            } else {
                return ($this->error_string . " [code " . $this->error_code . "]");
            }
        }

        // }}}
        // -----------------------------------------------------------------------------
        // ***** UNDER THIS LINE ARE DEFINED PRIVATE INTERNAL FUNCTIONS *****
        // *****                                                        *****
        // *****       THESES FUNCTIONS MUST NOT BE USED DIRECTLY       *****
        // -----------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _checkFormat()
        // Description :
        //   This method check that the archive exists and is a valid zip archive.
        //   Several level of check exists. (futur)
        // Parameters :
        //   $level : Level of check. Default 0.
        //              0 : Check the first bytes (magic codes) (default value))
        //              1 : 0 + Check the central directory (futur)
        //              2 : 1 + Check each file header (futur)
        // Return Values :
        //   true on success,
        //   false on error, the error code is set.
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_checkFormat()
         *
         * { Description }
         *
         * @param integer $level
         *
         * @return bool
         */
        public function _checkFormat($level = 0)
        {
            $v_result = true;

            // Reset the error handler
            $this->_errorReset();

            // Look if the file exits
            if (!is_file($this->zipname)) {
                // Error log
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_MISSING_FILE,
                    "Missing archive file '" . $this->zipname . "'"
                );
                return (false);
            }

            // Check that the file is readeable
            if (!is_readable($this->zipname)) {
                // Error log
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
                    "Unable to read archive '" . $this->zipname . "'"
                );
                return (false);
            }

            // Check the magic code
            // TBC
            // Check the central header
            // TBC
            // Check each file header
            // TBC

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _create()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_create()
         *
         * { Description }
         *
         * @return int
         */
        public function _create($list, &$result_list, &$params)
        {
            $v_result = 1;

            $v_list_detail = array();

            $add_dir = $params['add_path'];
            $remove_dir = $params['remove_path'];
            $remove_all_dir = $params['remove_all_path'];

            // Open the file in write mode
            if (($v_result = $this->_openFd('wb')) != 1) {

                return $v_result;
            }

            // Add the list of files
            $v_result = $this->_addList($list, $result_list, $add_dir, $remove_dir, $remove_all_dir, $params);

            // Close
            $this->_closeFd();

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _add()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_add()
         *
         * { Description }
         *
         * @return int
         */
        public function _add($list, &$result_list, &$params)
        {
            $v_result = 1;

            $v_list_detail = array();

            $add_dir = $params['add_path'];
            $remove_dir = $params['remove_path'];
            $remove_all_dir = $params['remove_all_path'];

            // Look if the archive exists or is empty and need to be created
            if ((!is_file($this->zipname)) || (filesize($this->zipname) == 0)) {
                $v_result = $this->_create($list, $result_list, $params);
                return $v_result;
            }

            // Open the zip file
            if (($v_result = $this->_openFd('rb')) != 1) {
                return $v_result;
            }

            // Read the central directory informations
            $v_central_dir = array();
            if (($v_result = $this->_readEndCentralDir($v_central_dir)) != 1) {
                $this->_closeFd();
                return $v_result;
            }

            // Go to beginning of File
            @rewind($this->zip_fd);

            // Creates a temporay file
            $v_zip_temp_name = ARCHIVE_ZIP_TEMPORARY_DIR . uniqid('archive_zip-') . '.tmp';

            // Open the temporary file in write mode
            if (($v_zip_temp_fd = @fopen($v_zip_temp_name, 'wb')) == 0) {
                $this->_closeFd();

                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
                    'Unable to open temporary file \''
                        . $v_zip_temp_name . '\' in binary write mode'
                );
                return ArchiveZip::errorCode();
            }

            // Copy the files from the archive to the temporary file
            // TBC : Here I should better append the file and go back to erase the
            // central dir
            $v_size = $v_central_dir['offset'];
            while ($v_size != 0) {
                $v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                $v_buffer = fread($this->zip_fd, $v_read_size);

                @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
                $v_size -= $v_read_size;
            }

            // Swap the file descriptor
            // Here is a trick : I swap the temporary fd with the zip fd, in order to
            // use the following methods on the temporary fil and not the real archive
            $v_swap = $this->zip_fd;

            $this->zip_fd = $v_zip_temp_fd;
            $v_zip_temp_fd = $v_swap;

            // Add the files
            $v_header_list = array();
            if (($v_result = $this->_addFileList(
                $list,
                $v_header_list,
                $add_dir,
                $remove_dir,
                $remove_all_dir,
                $params
            )) != 1) {
                fclose($v_zip_temp_fd);
                $this->_closeFd();
                @unlink($v_zip_temp_name);

                return $v_result;
            }

            // Store the offset of the central dir
            $v_offset = @ftell($this->zip_fd);

            // Copy the block of file headers from the old archive
            $v_size = $v_central_dir['size'];
            while ($v_size != 0) {
                $v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                $v_buffer = @fread($v_zip_temp_fd, $v_read_size);

                @fwrite($this->zip_fd, $v_buffer, $v_read_size);
                $v_size -= $v_read_size;
            }

            // Create the Central Dir files header
            for ($i = 0, $v_count = 0; $i < sizeof($v_header_list); $i++) {
                // Create the file header
                if ($v_header_list[$i]['status'] == 'ok') {
                    if (($v_result = $this->_writeCentralFileHeader($v_header_list[$i])) != 1) {
                        fclose($v_zip_temp_fd);
                        $this->_closeFd();
                        @unlink($v_zip_temp_name);

                        return $v_result;
                    }
                    $v_count++;
                }

                // Transform the header to a 'usable' info
                $this->_convertHeader2FileInfo($v_header_list[$i], $result_list[$i]);
            }

            // Zip file comment
            $v_comment = '';

            // Calculate the size of the central header
            $v_size = @ftell($this->zip_fd) - $v_offset;

            // Create the central dir footer
            if (($v_result = $this->_writeCentralHeader(
                $v_count + $v_central_dir['entries'],
                $v_size,
                $v_offset,
                $v_comment
            )) != 1) {
                // Reset the file list
                unset($v_header_list);

                return $v_result;
            }

            // Swap back the file descriptor
            $v_swap = $this->zip_fd;

            $this->zip_fd = $v_zip_temp_fd;
            $v_zip_temp_fd = $v_swap;

            // Close
            $this->_closeFd();

            // Close the temporary file
            @fclose($v_zip_temp_fd);

            // Delete the zip file
            // TBC : I should test the result ...
            @unlink($this->zipname);

            // Rename the temporary file
            // TBC : I should test the result ...
            //@rename($v_zip_temp_name, $this->zipname);
            $this->_tool_Rename($v_zip_temp_name, $this->zipname);

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _openFd()
        // Description :
        // Parameters :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_openFd()
         *
         * { Description }
         *
         * @return int
         */
        public function _openFd($mode)
        {
            $v_result = 1;

            // Look if already open
            if ($this->zip_fd != 0) {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
                    'Zip file \'' . $this->zipname . '\' already open'
                );
                return ArchiveZip::errorCode();
            }

            // Open the zip file
            if (($this->zip_fd = @fopen($this->zipname, $mode)) == 0) {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
                    'Unable to open archive \'' . $this->zipname
                        . '\' in ' . $mode . ' mode'
                );
                return ArchiveZip::errorCode();
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _closeFd()
        // Description :
        // Parameters :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_closeFd()
         *
         * { Description }
         *
         * @return int
         */
        public function _closeFd()
        {
            $v_result = 1;

            if ($this->zip_fd != 0) {
                @fclose($this->zip_fd);
            }
            $this->zip_fd = 0;

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _addList()
        // Description :
        //   $add_dir and $remove_dir will give the ability to memorize a path which is
        //   different from the real path of the file. This is usefull if you want to have PclTar
        //   running in any directory, and memorize relative path from an other directory.
        // Parameters :
        //   $list : An array containing the file or directory names to add in the tar
        //   $result_list : list of added files with their properties (specially the status field)
        //   $add_dir : Path to add in the filename path archived
        //   $remove_dir : Path to remove in the filename path archived
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_addList()
         *
         * { Description }
         *
         * @return int
         */
        public function _addList(
            $list,
            &$result_list,
            $add_dir,
            $remove_dir,
            $remove_all_dir,
            &$params
        ) {
            $v_result = 1;

            // Add the files
            $v_header_list = array();
            if (($v_result = $this->_addFileList(
                $list,
                $v_header_list,
                $add_dir,
                $remove_dir,
                $remove_all_dir,
                $params
            )) != 1) {
                return $v_result;
            }

            // Store the offset of the central dir
            $v_offset = @ftell($this->zip_fd);

            // Create the Central Dir files header
            for ($i = 0, $v_count = 0; $i < sizeof($v_header_list); $i++) {
                // Create the file header
                if ($v_header_list[$i]['status'] == 'ok') {
                    if (($v_result = $this->_writeCentralFileHeader($v_header_list[$i])) != 1) {
                        return $v_result;
                    }
                    $v_count++;
                }

                // Transform the header to a 'usable' info
                $this->_convertHeader2FileInfo($v_header_list[$i], $result_list[$i]);
            }

            // Zip file comment
            $v_comment = '';

            // Calculate the size of the central header
            $v_size = @ftell($this->zip_fd) - $v_offset;

            // Create the central dir footer
            if (($v_result = $this->_writeCentralHeader(
                $v_count,
                $v_size,
                $v_offset,
                $v_comment
            )) != 1) {
                // Reset the file list
                unset($v_header_list);

                return $v_result;
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _addFileList()
        // Description :
        //   $add_dir and $remove_dir will give the ability to memorize a path which is
        //   different from the real path of the file. This is usefull if you want to
        //   run the lib in any directory, and memorize relative path from an other directory.
        // Parameters :
        //   $list : An array containing the file or directory names to add in the tar
        //   $result_list : list of added files with their properties (specially the status field)
        //   $add_dir : Path to add in the filename path archived
        //   $remove_dir : Path to remove in the filename path archived
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_addFileList()
         *
         * { Description }
         *
         * @return int
         */
        public function _addFileList(
            $list,
            &$result_list,
            $add_dir,
            $remove_dir,
            $remove_all_dir,
            &$params
        ) {
            $v_result = 1;
            $v_header = array();

            // Recuperate the current number of elt in list
            $v_nb = sizeof($result_list);

            // Loop on the files
            for ($j = 0; ($j < count($list)) && ($v_result == 1); $j++) {
                // Recuperate the filename
                $filename = $this->_tool_TranslateWinPath($list[$j], false);

                // Skip empty file names
                if ($filename == "") {
                    continue;
                }

                // Check the filename
                if (!file_exists($filename)) {
                    $this->_errorLog(
                        ARCHIVE_ZIP_ERR_MISSING_FILE,
                        "File '$filename' does not exists"
                    );
                    return ArchiveZip::errorCode();
                }

                // Look if it is a file or a dir with no all pathnre move
                if ((is_file($filename)) || ((is_dir($filename)) && !$remove_all_dir)) {
                    // Add the file
                    if (($v_result = $this->_addFile($filename, $v_header, $add_dir, $remove_dir, $remove_all_dir, $params)) != 1) {
                        return $v_result;
                    }

                    // Store the file infos
                    $result_list[$v_nb++] = $v_header;
                }

                // Look for directory
                if (is_dir($filename)) {

                    // Look for path
                    if ($filename != ".") {
                        $v_path = $filename . "/";
                    } else {
                        $v_path = "";
                    }

                    // Read the directory for files and sub-directories
                    $hdir = opendir($filename);
                    $hitem = readdir($hdir); // '.' directory
                    $hitem = readdir($hdir); // '..' directory

                    while ($hitem = readdir($hdir)) {

                        // Look for a file
                        if (is_file($v_path . $hitem)) {

                            // Add the file
                            if (($v_result = $this->_addFile($v_path . $hitem, $v_header, $add_dir, $remove_dir, $remove_all_dir, $params)) != 1) {
                                return $v_result;
                            }

                            // Store the file infos
                            $result_list[$v_nb++] = $v_header;
                        } else {
                            // Recursive call to _addFileList()
                            // Need an array as parameter
                            $temp_list[0] = $v_path . $hitem;

                            $v_result = $this->_addFileList($temp_list, $result_list, $add_dir, $remove_dir, $remove_all_dir, $params);

                            // Update the number of elements of the list
                            $v_nb = sizeof($result_list);
                        }
                    }

                    // Free memory for the recursive loop
                    unset($temp_list);
                    unset($hdir);
                    unset($hitem);
                }
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _addFile()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_addFile()
         *
         * { Description }
         *
         * @return int
         */
        public function _addFile($filename, &$header, $add_dir, $remove_dir, $remove_all_dir, &$params)
        {
            $v_result = 1;

            if ($filename == "") {
                // Error log
                $this->_errorLog(ARCHIVE_ZIP_ERR_INVALID_PARAMETER, "Invalid file list parameter (invalid or empty list)");

                return ArchiveZip::errorCode();
            }

            // Calculate the stored filename
            $v_stored_filename = $filename;

            // Look for all path to remove
            if ($remove_all_dir) {
                $v_stored_filename = basename($filename);
            } elseif ($remove_dir != "") {
                if (substr($remove_dir, -1) != '/') {
                    $remove_dir .= "/";
                }

                if ((substr($filename, 0, 2) == "./") || (substr($remove_dir, 0, 2) == "./")) {
                    if ((substr($filename, 0, 2) == "./") && (substr($remove_dir, 0, 2) != "./")) {
                        $remove_dir = "./" . $remove_dir;
                    }
                    if ((substr($filename, 0, 2) != "./") && (substr($remove_dir, 0, 2) == "./")) {
                        $remove_dir = substr($remove_dir, 2);
                    }
                }

                $v_compare = $this->_tool_PathInclusion($remove_dir, $filename);
                if ($v_compare > 0) {
                    if ($v_compare == 2) {
                        $v_stored_filename = "";
                    } else {
                        $v_stored_filename = substr($filename, strlen($remove_dir));
                    }
                }
            }
            // Look for path to add
            if ($add_dir != "") {
                if (substr($add_dir, -1) == "/") {
                    $v_stored_filename = $add_dir . $v_stored_filename;
                } else {
                    $v_stored_filename = $add_dir . "/" . $v_stored_filename;
                }
            }

            // Filename (reduce the path of stored name)
            $v_stored_filename = $this->_tool_PathReduction($v_stored_filename);

            /* filename length moved after call-back in release 1.3
              // Check the path length
              if (strlen($v_stored_filename) > 0xFF) {
              // Error log
              $this->_errorLog(-5, "Stored file name is too long (max. 255) : '$v_stored_filename'");

              return ArchiveZip::errorCode();
              }
             */

            // Set the file properties
            clearstatcache();
            $header['comment'] = '';
            $header['comment_len'] = 0;
            $header['compressed_size'] = 0;
            $header['compression'] = 0;
            $header['crc'] = 0;
            $header['disk'] = 0;
            $header['external'] = (is_file($filename) ? 0xFE49FFE0 : 0x41FF0010);
            $header['extra'] = '';
            $header['extra_len'] = 0;
            $header['filename'] = $filename;
            $header['filename_len'] = strlen($filename);
            $header['flag'] = 0;
            $header['index'] = -1;
            $header['internal'] = 0;
            $header['mtime'] = filemtime($filename);
            $header['offset'] = 0;
            $header['size'] = filesize($filename);
            $header['status'] = 'ok';
            $header['stored_filename'] = $v_stored_filename;
            $header['version'] = 20;
            $header['version_extracted'] = 10;

            // Look for pre-add callback
            if ((isset($params[ARCHIVE_ZIP_PARAM_PRE_ADD])) && ($params[ARCHIVE_ZIP_PARAM_PRE_ADD] != '')) {

                // Generate a local information
                $v_local_header = array();
                $this->_convertHeader2FileInfo($header, $v_local_header);

                // Call the callback
                // Here I do not use call_user_func() because I need to send a reference to the
                // header.
                eval('$v_result = ' . $params[ARCHIVE_ZIP_PARAM_PRE_ADD] . '(ARCHIVE_ZIP_PARAM_PRE_ADD, $v_local_header);');
                if ($v_result == 0) {
                    // Change the file status
                    $header['status'] = "skipped";

                    $v_result = 1;
                }

                // Update the informations
                // Only some fields can be modified
                if ($header['stored_filename'] != $v_local_header['stored_filename']) {
                    $header['stored_filename'] = $this->_tool_PathReduction($v_local_header['stored_filename']);
                }
            }

            // Look for empty stored filename
            if ($header['stored_filename'] == "") {
                $header['status'] = "filtered";
            }

            // Check the path length
            if (strlen($header['stored_filename']) > 0xFF) {
                $header['status'] = 'filename_too_long';
            }

            // Look if no error, or file not skipped
            if ($header['status'] == 'ok') {

                // Look for a file
                if (is_file($filename)) {
                    // Open the source file
                    if (($v_file = @fopen($filename, "rb")) == 0) {
                        $this->_errorLog(ARCHIVE_ZIP_ERR_READ_OPEN_FAIL, "Unable to open file '$filename' in binary read mode");
                        return ArchiveZip::errorCode();
                    }

                    if ($params['no_compression']) {
                        // Read the file content
                        $v_content_compressed = @fread($v_file, $header['size']);

                        // Calculate the CRC
                        $header['crc'] = crc32($v_content_compressed);
                    } else {
                        // Read the file content
                        $v_content = @fread($v_file, $header['size']);

                        // Calculate the CRC
                        $header['crc'] = crc32($v_content);

                        // Compress the file
                        $v_content_compressed = gzdeflate($v_content);
                    }

                    // Set header parameters
                    $header['compressed_size'] = strlen($v_content_compressed);
                    $header['compression'] = 8;

                    // Call the header generation
                    if (($v_result = $this->_writeFileHeader($header)) != 1) {
                        @fclose($v_file);
                        return $v_result;
                    }

                    // Write the compressed content
                    $v_binary_data = pack('a' . $header['compressed_size'], $v_content_compressed);
                    @fwrite($this->zip_fd, $v_binary_data, $header['compressed_size']);

                    // Close the file
                    @fclose($v_file);
                } else {
                    // Look for a directory
                    // Set the file properties
                    $header['filename'] .= '/';
                    $header['filename_len']++;

                    $header['size'] = 0;
                    $header['external'] = 0x41FF0010; // Value for a folder : to be checked
                    // Call the header generation
                    if (($v_result = $this->_writeFileHeader($header)) != 1) {
                        return $v_result;
                    }
                }
            }

            // Look for pre-add callback
            if ((isset($params[ARCHIVE_ZIP_PARAM_POST_ADD])) && ($params[ARCHIVE_ZIP_PARAM_POST_ADD] != '')) {

                // Generate a local information
                $v_local_header = array();
                $this->_convertHeader2FileInfo($header, $v_local_header);

                // Call the callback
                // Here I do not use call_user_func() because I need to send a reference to the
                // header.
                eval('$v_result = ' . $params[ARCHIVE_ZIP_PARAM_POST_ADD] . '(ARCHIVE_ZIP_PARAM_POST_ADD, $v_local_header);');

                if ($v_result == 0) {
                    // Ignored
                    $v_result = 1;
                }

                // Update the informations
                // Nothing can be modified
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _writeFileHeader()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_writeFileHeader()
         *
         * { Description }
         *
         * @return int
         */
        public function _writeFileHeader(&$header)
        {
            $v_result = 1;

            // TBC
            //for(reset($header); $key = key($header); next($header)) {
            //}
            // Store the offset position of the file
            $header['offset'] = ftell($this->zip_fd);

            // Transform UNIX mtime to DOS format mdate/mtime
            $v_date = getdate($header['mtime']);
            $v_mtime = ($v_date['hours'] << 11) + ($v_date['minutes'] << 5) + $v_date['seconds'] / 2;
            $v_mdate = (($v_date['year'] - 1980) << 9) + ($v_date['mon'] << 5) + $v_date['mday'];

            // Packed data
            $v_binary_data = pack(
                "VvvvvvVVVvv",
                0x04034b50,
                $header['version'],
                $header['flag'],
                $header['compression'],
                $v_mtime,
                $v_mdate,
                $header['crc'],
                $header['compressed_size'],
                $header['size'],
                strlen($header['stored_filename']),
                $header['extra_len']
            );

            // Write the first 148 bytes of the header in the archive
            fputs($this->zip_fd, $v_binary_data, 30);

            // Write the variable fields
            if (strlen($header['stored_filename']) != 0) {
                fputs($this->zip_fd, $header['stored_filename'], strlen($header['stored_filename']));
            }
            if ($header['extra_len'] != 0) {
                fputs($this->zip_fd, $header['extra'], $header['extra_len']);
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _writeCentralFileHeader()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_writeCentralFileHeader()
         *
         * { Description }
         *
         * @return int
         */
        public function _writeCentralFileHeader(&$header)
        {
            $v_result = 1;

            // TBC
            //for(reset($header); $key = key($header); next($header)) {
            //}
            // Transform UNIX mtime to DOS format mdate/mtime
            $v_date = getdate($header['mtime']);
            $v_mtime = ($v_date['hours'] << 11) + ($v_date['minutes'] << 5) + $v_date['seconds'] / 2;
            $v_mdate = (($v_date['year'] - 1980) << 9) + ($v_date['mon'] << 5) + $v_date['mday'];

            // Packed data
            $v_binary_data = pack(
                "VvvvvvvVVVvvvvvVV",
                0x02014b50,
                $header['version'],
                $header['version_extracted'],
                $header['flag'],
                $header['compression'],
                $v_mtime,
                $v_mdate,
                $header['crc'],
                $header['compressed_size'],
                $header['size'],
                strlen($header['stored_filename']),
                $header['extra_len'],
                $header['comment_len'],
                $header['disk'],
                $header['internal'],
                $header['external'],
                $header['offset']
            );

            // Write the 42 bytes of the header in the zip file
            fputs($this->zip_fd, $v_binary_data, 46);

            // Write the variable fields
            if (strlen($header['stored_filename']) != 0) {
                fputs($this->zip_fd, $header['stored_filename'], strlen($header['stored_filename']));
            }
            if ($header['extra_len'] != 0) {
                fputs($this->zip_fd, $header['extra'], $header['extra_len']);
            }
            if ($header['comment_len'] != 0) {
                fputs($this->zip_fd, $header['comment'], $header['comment_len']);
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _writeCentralHeader()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_writeCentralHeader()
         *
         * { Description }
         *
         * @return int
         */
        public function _writeCentralHeader($nb_entries, $size, $offset, $comment)
        {
            $v_result = 1;

            // Packed data
            $v_binary_data = pack("VvvvvVVv", 0x06054b50, 0, 0, $nb_entries, $nb_entries, $size, $offset, strlen($comment));

            // Write the 22 bytes of the header in the zip file
            fputs($this->zip_fd, $v_binary_data, 22);

            // Write the variable fields
            if (strlen($comment) != 0) {
                fputs($this->zip_fd, $comment, strlen($comment));
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _list()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_list()
         *
         * { Description }
         *
         * @return int
         */
        public function _list(&$list)
        {
            $v_result = 1;

            // Open the zip file
            if (($this->zip_fd = @fopen($this->zipname, 'rb')) == 0) {
                // Error log
                $this->_errorLog(ARCHIVE_ZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \'' . $this->zipname . '\' in binary read mode');

                return ArchiveZip::errorCode();
            }

            // Read the central directory informations
            $v_central_dir = array();
            if (($v_result = $this->_readEndCentralDir($v_central_dir)) != 1) {
                return $v_result;
            }

            // Go to beginning of Central Dir
            @rewind($this->zip_fd);
            if (@fseek($this->zip_fd, $v_central_dir['offset'])) {
                // Error log
                $this->_errorLog(ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

                return ArchiveZip::errorCode();
            }

            // Read each entry
            for ($i = 0; $i < $v_central_dir['entries']; $i++) {
                // Read the file header
                if (($v_result = $this->_readCentralFileHeader($v_header)) != 1) {
                    return $v_result;
                }
                $v_header['index'] = $i;

                // Get the only interesting attributes
                $this->_convertHeader2FileInfo($v_header, $list[$i]);
                unset($v_header);
            }

            // Close the zip file
            $this->_closeFd();

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _convertHeader2FileInfo()
        // Description :
        //   This function takes the file informations from the central directory
        //   entries and extract the interesting parameters that will be given back.
        //   The resulting file infos are set in the array $info
        //     $info['filename'] : Filename with full path. Given by user (add),
        //                           extracted in the filesystem (extract).
        //     $info['stored_filename'] : Stored filename in the archive.
        //     $info['size'] = Size of the file.
        //     $info['compressed_size'] = Compressed size of the file.
        //     $info['mtime'] = Last modification date of the file.
        //     $info['comment'] = Comment associated with the file.
        //     $info['folder'] = true/false : indicates if the entry is a folder or not.
        //     $info['status'] = status of the action on the file.
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_convertHeader2FileInfo()
         *
         * { Description }
         *
         * @return int
         */
        public function _convertHeader2FileInfo($header, &$info)
        {
            $v_result = 1;

            // Get the interesting attributes
            $info['filename'] = $header['filename'];
            $info['stored_filename'] = $header['stored_filename'];
            $info['size'] = $header['size'];
            $info['compressed_size'] = $header['compressed_size'];
            $info['mtime'] = $header['mtime'];
            $info['comment'] = $header['comment'];
            $info['folder'] = (($header['external'] & 0x00000010) == 0x00000010);
            $info['index'] = $header['index'];
            $info['status'] = $header['status'];

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _extractByRule()
        // Description :
        //   Extract a file or directory depending of rules (by index, by name, ...)
        // Parameters :
        //   $file_list : An array where will be placed the properties of each
        //                  extracted file
        //   $path : Path to add while writing the extracted files
        //   $remove_path : Path to remove (from the file memorized path) while writing the
        //                    extracted files. If the path does not match the file path,
        //                    the file is extracted with its memorized path.
        //                    $remove_path does not apply to 'list' mode.
        //                    $path and $remove_path are commulative.
        // Return Values :
        //   1 on success,0 or less on error (see error code list)
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_extractByRule()
         *
         * { Description }
         *
         * @return int
         */
        public function _extractByRule(&$file_list, &$params)
        {
            $v_result = 1;

            $path = $params['add_path'];
            $remove_path = $params['remove_path'];
            $remove_all_path = $params['remove_all_path'];

            // Check the path
            if (($path == "") || ((substr($path, 0, 1) != "/") && (substr($path, 0, 3) != "../") && (substr($path, 1, 2) != ":/"))) {
                $path = "./" . $path;
            }

            // Reduce the path last (and duplicated) '/'
            if (($path != "./") && ($path != "/")) {
                // Look for the path end '/'
                while (substr($path, -1) == "/") {
                    $path = substr($path, 0, strlen($path) - 1);
                }
            }

            // Look for path to remove format (should end by /)
            if (($remove_path != "") && (substr($remove_path, -1) != '/')) {
                $remove_path .= '/';
            }
            $remove_path_size = strlen($remove_path);

            // Open the zip file
            if (($v_result = $this->_openFd('rb')) != 1) {
                return $v_result;
            }

            // Read the central directory informations
            $v_central_dir = array();
            if (($v_result = $this->_readEndCentralDir($v_central_dir)) != 1) {
                // Close the zip file
                $this->_closeFd();

                return $v_result;
            }

            // Start at beginning of Central Dir
            $v_pos_entry = $v_central_dir['offset'];

            // Read each entry
            $j_start = 0;

            for ($i = 0, $v_nb_extracted = 0; $i < $v_central_dir['entries']; $i++) {
                // Read next Central dir entry
                @rewind($this->zip_fd);
                if (@fseek($this->zip_fd, $v_pos_entry)) {
                    $this->_closeFd();

                    $this->_errorLog(
                        ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP,
                        'Invalid archive size'
                    );

                    return ArchiveZip::errorCode();
                }

                // Read the file header
                $v_header = array();
                if (($v_result = $this->_readCentralFileHeader($v_header)) != 1) {
                    $this->_closeFd();

                    return $v_result;
                }

                // Store the index
                $v_header['index'] = $i;

                // Store the file position
                $v_pos_entry = ftell($this->zip_fd);

                // Look for the specific extract rules
                $v_extract = false;

                // Look for extract by name rule
                if ((isset($params[ARCHIVE_ZIP_PARAM_BY_NAME])) && ($params[ARCHIVE_ZIP_PARAM_BY_NAME] != 0)) {

                    // Look if the filename is in the list
                    for ($j = 0; ($j < sizeof($params[ARCHIVE_ZIP_PARAM_BY_NAME])) && (!$v_extract); $j++) {
                        // Look for a directory
                        if (substr($params[ARCHIVE_ZIP_PARAM_BY_NAME][$j], -1) == "/") {

                            // Look if the directory is in the filename path
                            if ((strlen($v_header['stored_filename']) > strlen($params[ARCHIVE_ZIP_PARAM_BY_NAME][$j])) && (substr($v_header['stored_filename'], 0, strlen($params[ARCHIVE_ZIP_PARAM_BY_NAME][$j])) == $params[ARCHIVE_ZIP_PARAM_BY_NAME][$j])) {
                                $v_extract = true;
                            }
                        } elseif ($v_header['stored_filename'] == $params[ARCHIVE_ZIP_PARAM_BY_NAME][$j]) {
                            $v_extract = true;
                        }
                    }
                } elseif ((isset($params[ARCHIVE_ZIP_PARAM_BY_PREG])) && ($params[ARCHIVE_ZIP_PARAM_BY_PREG] != "")) {
                    // Look for extract by preg rule
                    if (preg_match($params[ARCHIVE_ZIP_PARAM_BY_PREG], $v_header['stored_filename'])) {
                        $v_extract = true;
                    }
                } elseif ((isset($params[ARCHIVE_ZIP_PARAM_BY_INDEX])) && ($params[ARCHIVE_ZIP_PARAM_BY_INDEX] != 0)) {

                    // Look for extract by index rule
                    // Look if the index is in the list
                    for ($j = $j_start; ($j < sizeof($params[ARCHIVE_ZIP_PARAM_BY_INDEX])) && (!$v_extract); $j++) {

                        if (($i >= $params[ARCHIVE_ZIP_PARAM_BY_INDEX][$j]['start']) && ($i <= $params[ARCHIVE_ZIP_PARAM_BY_INDEX][$j]['end'])) {
                            $v_extract = true;
                        }

                        if ($i >= $params[ARCHIVE_ZIP_PARAM_BY_INDEX][$j]['end']) {
                            $j_start = $j + 1;
                        }

                        if ($params[ARCHIVE_ZIP_PARAM_BY_INDEX][$j]['start'] > $i) {
                            break;
                        }
                    }
                } else {
                    // Look for no rule, which means extract all the archive
                    $v_extract = true;
                }

                // Look for real extraction
                if ($v_extract) {

                    // Go to the file position
                    @rewind($this->zip_fd);
                    if (@fseek($this->zip_fd, $v_header['offset'])) {
                        // Close the zip file
                        $this->_closeFd();

                        // Error log
                        $this->_errorLog(ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

                        return ArchiveZip::errorCode();
                    }

                    // Look for extraction as string
                    if ($params[ARCHIVE_ZIP_PARAM_EXTRACT_AS_STRING]) {

                        // Extracting the file
                        if (($v_result = $this->_extractFileAsString($v_header, $v_string)) != 1) {
                            // Close the zip file
                            $this->_closeFd();

                            return $v_result;
                        }

                        // Get the only interesting attributes
                        if (($v_result = $this->_convertHeader2FileInfo($v_header, $file_list[$v_nb_extracted])) != 1) {
                            // Close the zip file
                            $this->_closeFd();

                            return $v_result;
                        }

                        // Set the file content
                        $file_list[$v_nb_extracted]['content'] = $v_string;

                        // Next extracted file
                        $v_nb_extracted++;
                    } else {
                        // Extracting the file
                        if (($v_result = $this->_extractFile($v_header, $path, $remove_path, $remove_all_path, $params)) != 1) {
                            // Close the zip file
                            $this->_closeFd();

                            return $v_result;
                        }

                        // Get the only interesting attributes
                        if (($v_result = $this->_convertHeader2FileInfo($v_header, $file_list[$v_nb_extracted++])) != 1) {
                            // Close the zip file
                            $this->_closeFd();

                            return $v_result;
                        }
                    }
                }
            }

            // Close the zip file
            $this->_closeFd();

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _extractFile()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_extractFile()
         *
         * { Description }
         *
         * @return int
         */
        public function _extractFile(&$entry, $path, $remove_path, $remove_all_path, &$params)
        {
            $v_result = 1;

            // Read the file header
            if (($v_result = $this->_readFileHeader($v_header)) != 1) {
                return $v_result;
            }

            // Check that the file header is coherent with $entry info
            // TBC
            // Look for all path to remove
            if ($remove_all_path == true) {
                // Get the basename of the path
                $entry['filename'] = basename($entry['filename']);
            } elseif ($remove_path != "") {
                if ($this->_tool_PathInclusion($remove_path, $entry['filename']) == 2) {
                    // Change the file status
                    $entry['status'] = "filtered";

                    return $v_result;
                }

                $remove_path_size = strlen($remove_path);
                if (substr($entry['filename'], 0, $remove_path_size) == $remove_path) {

                    // Remove the path
                    $entry['filename'] = substr($entry['filename'], $remove_path_size);
                }
            }

            // Add the path
            if ($path != '') {
                $entry['filename'] = $path . "/" . $entry['filename'];
            }

            // Look for pre-extract callback
            if ((isset($params[ARCHIVE_ZIP_PARAM_PRE_EXTRACT])) && ($params[ARCHIVE_ZIP_PARAM_PRE_EXTRACT] != '')) {

                // Generate a local information
                $v_local_header = array();
                $this->_convertHeader2FileInfo($entry, $v_local_header);

                // Call the callback
                // Here I do not use call_user_func() because I need to send a reference to the
                // header.
                eval('$v_result = ' . $params[ARCHIVE_ZIP_PARAM_PRE_EXTRACT] . '(ARCHIVE_ZIP_PARAM_PRE_EXTRACT, $v_local_header);');

                if ($v_result == 0) {
                    // Change the file status
                    $entry['status'] = "skipped";

                    $v_result = 1;
                }

                // Update the informations
                // Only some fields can be modified
                $entry['filename'] = $v_local_header['filename'];
            }

            // Trace
            // Look if extraction should be done
            if ($entry['status'] == 'ok') {

                // Look for specific actions while the file exist
                if (file_exists($entry['filename'])) {
                    // Look if file is a directory
                    if (is_dir($entry['filename'])) {
                        // Change the file status
                        $entry['status'] = "already_a_directory";

                        //return $v_result;
                    } elseif (!is_writeable($entry['filename'])) {
                        // Look if file is write protected
                        // Change the file status
                        $entry['status'] = "write_protected";

                        //return $v_result;
                    } elseif (filemtime($entry['filename']) > $entry['mtime']) {
                        // Look if the extracted file is older
                        // Change the file status
                        $entry['status'] = "newer_exist";

                        //return $v_result;
                    }
                } else {

                    // Check the directory availability and create it if necessary
                    if ((($entry['external'] & 0x00000010) == 0x00000010) || (substr($entry['filename'], -1) == '/')) {
                        $v_dir_to_check = $entry['filename'];
                    } elseif (!strstr($entry['filename'], "/")) {
                        $v_dir_to_check = "";
                    } else {
                        $v_dir_to_check = dirname($entry['filename']);
                    }

                    if (($v_result = $this->_dirCheck($v_dir_to_check, (($entry['external'] & 0x00000010) == 0x00000010))) != 1) {
                        // Change the file status
                        $entry['status'] = "path_creation_fail";

                        //return $v_result;
                        $v_result = 1;
                    }
                }
            }

            // Look if extraction should be done
            if ($entry['status'] == 'ok') {

                // Do the extraction (if not a folder)
                if (!(($entry['external'] & 0x00000010) == 0x00000010)) {

                    // Look for not compressed file
                    if ($entry['compressed_size'] == $entry['size']) {

                        // Opening destination file
                        if (($v_dest_file = @fopen($entry['filename'], 'wb')) == 0) {

                            // Change the file status
                            $entry['status'] = "write_error";

                            return $v_result;
                        }

                        // Read the file by ARCHIVE_ZIP_READ_BLOCK_SIZE octets blocks
                        $v_size = $entry['compressed_size'];
                        while ($v_size != 0) {
                            $v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                            $v_buffer = fread($this->zip_fd, $v_read_size);

                            $v_binary_data = pack('a' . $v_read_size, $v_buffer);

                            @fwrite($v_dest_file, $v_binary_data, $v_read_size);
                            $v_size -= $v_read_size;
                        }

                        // Closing the destination file
                        fclose($v_dest_file);

                        // Change the file mtime
                        touch($entry['filename'], $entry['mtime']);
                    } else {
                        // Trace
                        // Opening destination file
                        if (($v_dest_file = @fopen($entry['filename'], 'wb')) == 0) {

                            // Change the file status
                            $entry['status'] = "write_error";

                            return $v_result;
                        }

                        // Read the compressed file in a buffer (one shot)
                        $v_buffer = @fread($this->zip_fd, $entry['compressed_size']);

                        // Decompress the file
                        $v_file_content = gzinflate($v_buffer);
                        unset($v_buffer);

                        // Write the uncompressed data
                        @fwrite($v_dest_file, $v_file_content, $entry['size']);
                        unset($v_file_content);

                        // Closing the destination file
                        @fclose($v_dest_file);

                        // Change the file mtime
                        touch($entry['filename'], $entry['mtime']);
                    }

                    // Look for chmod option
                    if ((isset($params[ARCHIVE_ZIP_PARAM_SET_CHMOD])) && ($params[ARCHIVE_ZIP_PARAM_SET_CHMOD] != 0)) {

                        // Change the mode of the file
                        chmod($entry['filename'], $params[ARCHIVE_ZIP_PARAM_SET_CHMOD]);
                    }
                }
            }

            // Look for post-extract callback
            if ((isset($params[ARCHIVE_ZIP_PARAM_POST_EXTRACT])) && ($params[ARCHIVE_ZIP_PARAM_POST_EXTRACT] != '')) {

                // Generate a local information
                $v_local_header = array();
                $this->_convertHeader2FileInfo($entry, $v_local_header);

                // Call the callback
                // Here I do not use call_user_func() because I need to send a reference to the
                // header.
                eval('$v_result = ' . $params[ARCHIVE_ZIP_PARAM_POST_EXTRACT] . '(ARCHIVE_ZIP_PARAM_POST_EXTRACT, $v_local_header);');
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _extractFileAsString()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_extractFileAsString()
         *
         * { Description }
         *
         * @return int
         */
        public function _extractFileAsString(&$entry, &$string)
        {
            $v_result = 1;

            // Read the file header
            $v_header = array();
            if (($v_result = $this->_readFileHeader($v_header)) != 1) {
                return $v_result;
            }

            // Check that the file header is coherent with $entry info
            // TBC
            // Trace
            // Do the extraction (if not a folder)
            if (!(($entry['external'] & 0x00000010) == 0x00000010)) {
                // Look for not compressed file
                if ($entry['compressed_size'] == $entry['size']) {
                    // Trace
                    // Reading the file
                    $string = fread($this->zip_fd, $entry['compressed_size']);
                } else {
                    // Trace
                    // Reading the file
                    $v_data = fread($this->zip_fd, $entry['compressed_size']);

                    // Decompress the file
                    $string = gzinflate($v_data);
                }

                // Trace
            } else {
                // TBC : error : can not extract a folder in a string
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _readFileHeader()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_readFileHeader()
         *
         * { Description }
         *
         * @return int
         */
        public function _readFileHeader(&$header)
        {
            $v_result = 1;

            // Read the 4 bytes signature
            $v_binary_data = @fread($this->zip_fd, 4);

            $v_data = unpack('Vid', $v_binary_data);

            // Check signature
            if ($v_data['id'] != 0x04034b50) {

                // Error log
                $this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT, 'Invalid archive structure');

                return ArchiveZip::errorCode();
            }

            // Read the first 42 bytes of the header
            $v_binary_data = fread($this->zip_fd, 26);

            // Look for invalid block size
            if (strlen($v_binary_data) != 26) {
                $header['filename'] = "";
                $header['status'] = "invalid_header";

                // Error log
                $this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT, "Invalid block size : " . strlen($v_binary_data));

                return ArchiveZip::errorCode();
            }

            // Extract the values
            $v_data = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $v_binary_data);

            // Get filename
            $header['filename'] = fread($this->zip_fd, $v_data['filename_len']);

            // Get extra_fields
            if ($v_data['extra_len'] != 0) {
                $header['extra'] = fread($this->zip_fd, $v_data['extra_len']);
            } else {
                $header['extra'] = '';
            }

            // Extract properties
            $header['compression'] = $v_data['compression'];
            $header['size'] = $v_data['size'];
            $header['compressed_size'] = $v_data['compressed_size'];
            $header['crc'] = $v_data['crc'];
            $header['flag'] = $v_data['flag'];

            // Recuperate date in UNIX format
            $header['mdate'] = $v_data['mdate'];
            $header['mtime'] = $v_data['mtime'];
            if ($header['mdate'] && $header['mtime']) {
                // Extract time
                $v_hour = ($header['mtime'] & 0xF800) >> 11;
                $v_minute = ($header['mtime'] & 0x07E0) >> 5;
                $v_seconde = ($header['mtime'] & 0x001F) * 2;

                // Extract date
                $v_year = (($header['mdate'] & 0xFE00) >> 9) + 1980;
                $v_month = ($header['mdate'] & 0x01E0) >> 5;
                $v_day = $header['mdate'] & 0x001F;

                // Get UNIX date format
                $header['mtime'] = mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);
            } else {
                $header['mtime'] = time();
            }

            // Other informations
            // TBC
            //for(reset($v_data); $key = key($v_data); next($v_data)) {
            //}
            // Set the stored filename
            $header['stored_filename'] = $header['filename'];

            // Set the status field
            $header['status'] = "ok";

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _readCentralFileHeader()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_readCentralFileHeader()
         *
         * { Description }
         *
         * @return int
         */
        public function _readCentralFileHeader(&$header)
        {
            $v_result = 1;

            // Read the 4 bytes signature
            $v_binary_data = @fread($this->zip_fd, 4);

            $v_data = unpack('Vid', $v_binary_data);

            // Check signature
            if ($v_data['id'] != 0x02014b50) {

                // Error log
                $this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT, 'Invalid archive structure');

                return ArchiveZip::errorCode();
            }

            // Read the first 42 bytes of the header
            $v_binary_data = fread($this->zip_fd, 42);

            // Look for invalid block size
            if (strlen($v_binary_data) != 42) {
                $header['filename'] = "";
                $header['status'] = "invalid_header";

                // Error log
                $this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT, "Invalid block size : " . strlen($v_binary_data));

                return ArchiveZip::errorCode();
            }

            // Extract the values
            $header = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $v_binary_data);

            // Get filename
            if ($header['filename_len'] != 0) {
                $header['filename'] = fread($this->zip_fd, $header['filename_len']);
            } else {
                $header['filename'] = '';
            }

            // Get extra
            if ($header['extra_len'] != 0) {
                $header['extra'] = fread($this->zip_fd, $header['extra_len']);
            } else {
                $header['extra'] = '';
            }

            // Get comment
            if ($header['comment_len'] != 0) {
                $header['comment'] = fread($this->zip_fd, $header['comment_len']);
            } else {
                $header['comment'] = '';
            }

            // Extract properties
            // Recuperate date in UNIX format
            if ($header['mdate'] && $header['mtime']) {
                // Extract time
                $v_hour = ($header['mtime'] & 0xF800) >> 11;
                $v_minute = ($header['mtime'] & 0x07E0) >> 5;
                $v_seconde = ($header['mtime'] & 0x001F) * 2;

                // Extract date
                $v_year = (($header['mdate'] & 0xFE00) >> 9) + 1980;
                $v_month = ($header['mdate'] & 0x01E0) >> 5;
                $v_day = $header['mdate'] & 0x001F;

                // Get UNIX date format
                $header['mtime'] = mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);
            } else {
                $header['mtime'] = time();
            }

            // Set the stored filename
            $header['stored_filename'] = $header['filename'];

            // Set default status to ok
            $header['status'] = 'ok';

            // Look if it is a directory
            if (substr($header['filename'], -1) == '/') {
                $header['external'] = 0x41FF0010;
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _readEndCentralDir()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_readEndCentralDir()
         *
         * { Description }
         *
         * @return int
         */
        public function _readEndCentralDir(&$central_dir)
        {
            $v_result = 1;

            // Go to the end of the zip file
            $v_size = filesize($this->zipname);
            @fseek($this->zip_fd, $v_size);

            if (@ftell($this->zip_fd) != $v_size) {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_BAD_FORMAT,
                    'Unable to go to the end of the archive \''
                        . $this->zipname . '\''
                );
                return ArchiveZip::errorCode();
            }

            // First try : look if this is an archive with no commentaries
            // (most of the time)
            // in this case the end of central dir is at 22 bytes of the file end
            $v_found = 0;
            if ($v_size > 26) {
                @fseek($this->zip_fd, $v_size - 22);

                if (($v_pos = @ftell($this->zip_fd)) != ($v_size - 22)) {
                    $this->_errorLog(
                        ARCHIVE_ZIP_ERR_BAD_FORMAT,
                        'Unable to seek back to the middle of the archive \''
                            . $this->zipname . '\''
                    );
                    return ArchiveZip::errorCode();
                }

                // Read for bytes
                $v_binary_data = @fread($this->zip_fd, 4);

                $v_data = unpack('Vid', $v_binary_data);

                // Check signature
                if ($v_data['id'] == 0x06054b50) {
                    $v_found = 1;
                }

                $v_pos = ftell($this->zip_fd);
            }

            // Go back to the maximum possible size of the Central Dir End Record
            if (!$v_found) {
                $v_maximum_size = 65557; // 0xFFFF + 22;
                if ($v_maximum_size > $v_size) {
                    $v_maximum_size = $v_size;
                }
                @fseek($this->zip_fd, $v_size - $v_maximum_size);
                if (@ftell($this->zip_fd) != ($v_size - $v_maximum_size)) {
                    $this->_errorLog(
                        ARCHIVE_ZIP_ERR_BAD_FORMAT,
                        'Unable to seek back to the middle of the archive \''
                            . $this->zipname . '\''
                    );
                    return ArchiveZip::errorCode();
                }

                // Read byte per byte in order to find the signature
                $v_pos = ftell($this->zip_fd);
                $v_bytes = 0x00000000;
                while ($v_pos < $v_size) {
                    // Read a byte
                    $v_byte = @fread($this->zip_fd, 1);

                    //  Add the byte
                    $v_bytes = ($v_bytes << 8) | Ord($v_byte);

                    // Compare the bytes
                    if ($v_bytes == 0x504b0506) {
                        $v_pos++;
                        break;
                    }

                    $v_pos++;
                }

                // Look if not found end of central dir
                if ($v_pos == $v_size) {
                    $this->_errorLog(
                        ARCHIVE_ZIP_ERR_BAD_FORMAT,
                        "Unable to find End of Central Dir Record signature"
                    );
                    return ArchiveZip::errorCode();
                }
            }

            // Read the first 18 bytes of the header
            $v_binary_data = fread($this->zip_fd, 18);

            // Look for invalid block size
            if (strlen($v_binary_data) != 18) {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_BAD_FORMAT,
                    "Invalid End of Central Dir Record size : "
                        . strlen($v_binary_data)
                );
                return ArchiveZip::errorCode();
            }

            // Extract the values
            $v_data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', $v_binary_data);

            // Check the global size
            if (($v_pos + $v_data['comment_size'] + 18) != $v_size) {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_BAD_FORMAT,
                    "Fail to find the right signature"
                );
                return ArchiveZip::errorCode();
            }

            // Get comment
            if ($v_data['comment_size'] != 0) {
                $central_dir['comment'] = fread($this->zip_fd, $v_data['comment_size']);
            } else {
                $central_dir['comment'] = '';
            }

            $central_dir['entries'] = $v_data['entries'];
            $central_dir['disk_entries'] = $v_data['disk_entries'];
            $central_dir['offset'] = $v_data['offset'];
            $central_dir['size'] = $v_data['size'];
            $central_dir['disk'] = $v_data['disk'];
            $central_dir['disk_start'] = $v_data['disk_start'];

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _deleteByRule()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_deleteByRule()
         *
         * { Description }
         *
         * @return int
         */
        public function _deleteByRule(&$result_list, &$params)
        {
            $v_result = 1;

            $v_list_detail = array();

            // Open the zip file
            if (($v_result = $this->_openFd('rb')) != 1) {

                return $v_result;
            }

            // Read the central directory informations
            $v_central_dir = array();
            if (($v_result = $this->_readEndCentralDir($v_central_dir)) != 1) {
                $this->_closeFd();
                return $v_result;
            }

            // Go to beginning of File
            @rewind($this->zip_fd);

            // Scan all the files
            // Start at beginning of Central Dir
            $v_pos_entry = $v_central_dir['offset'];
            @rewind($this->zip_fd);
            if (@fseek($this->zip_fd, $v_pos_entry)) {
                // Clean
                $this->_closeFd();

                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP,
                    'Invalid archive size'
                );
                return ArchiveZip::errorCode();
            }

            // Read each entry
            $v_header_list = array();

            $j_start = 0;
            for ($i = 0, $v_nb_extracted = 0; $i < $v_central_dir['entries']; $i++) {

                // Read the file header
                $v_header_list[$v_nb_extracted] = array();

                $v_result = $this->_readCentralFileHeader($v_header_list[$v_nb_extracted]);
                if ($v_result != 1) {
                    // Clean
                    $this->_closeFd();

                    return $v_result;
                }

                // Store the index
                $v_header_list[$v_nb_extracted]['index'] = $i;

                // Look for the specific extract rules
                $v_found = false;

                // Look for extract by name rule
                if ((isset($params[ARCHIVE_ZIP_PARAM_BY_NAME])) && ($params[ARCHIVE_ZIP_PARAM_BY_NAME] != 0)) {

                    // Look if the filename is in the list
                    for ($j = 0; ($j < sizeof($params[ARCHIVE_ZIP_PARAM_BY_NAME])) && (!$v_found); $j++) {

                        // Look for a directory
                        if (substr($params[ARCHIVE_ZIP_PARAM_BY_NAME][$j], -1) == "/") {

                            // Look if the directory is in the filename path
                            if ((strlen($v_header_list[$v_nb_extracted]['stored_filename']) > strlen($params[ARCHIVE_ZIP_PARAM_BY_NAME][$j])) && (substr($v_header_list[$v_nb_extracted]['stored_filename'], 0, strlen($params[ARCHIVE_ZIP_PARAM_BY_NAME][$j])) == $params[ARCHIVE_ZIP_PARAM_BY_NAME][$j])) {
                                $v_found = true;
                            } elseif ((($v_header_list[$v_nb_extracted]['external'] & 0x00000010) == 0x00000010) /* Indicates a folder */ && ($v_header_list[$v_nb_extracted]['stored_filename'] . '/' == $params[ARCHIVE_ZIP_PARAM_BY_NAME][$j])) {
                                $v_found = true;
                            }
                        } elseif ($v_header_list[$v_nb_extracted]['stored_filename'] == $params[ARCHIVE_ZIP_PARAM_BY_NAME][$j]) {
                            // Look for a filename
                            $v_found = true;
                        }
                    }
                } elseif ((isset($params[ARCHIVE_ZIP_PARAM_BY_PREG])) && ($params[ARCHIVE_ZIP_PARAM_BY_PREG] != "")) {
                    // Look for extract by preg rule
                    if (preg_match(
                        $params[ARCHIVE_ZIP_PARAM_BY_PREG],
                        $v_header_list[$v_nb_extracted]['stored_filename']
                    )) {
                        $v_found = true;
                    }
                } elseif ((isset($params[ARCHIVE_ZIP_PARAM_BY_INDEX])) && ($params[ARCHIVE_ZIP_PARAM_BY_INDEX] != 0)) {
                    // Look for extract by index rule
                    // Look if the index is in the list
                    for ($j = $j_start; ($j < sizeof($params[ARCHIVE_ZIP_PARAM_BY_INDEX])) && (!$v_found); $j++) {

                        if (($i >= $params[ARCHIVE_ZIP_PARAM_BY_INDEX][$j]['start']) && ($i <= $params[ARCHIVE_ZIP_PARAM_BY_INDEX][$j]['end'])) {
                            $v_found = true;
                        }
                        if ($i >= $params[ARCHIVE_ZIP_PARAM_BY_INDEX][$j]['end']) {
                            $j_start = $j + 1;
                        }

                        if ($params[ARCHIVE_ZIP_PARAM_BY_INDEX][$j]['start'] > $i) {
                            break;
                        }
                    }
                }

                // Look for deletion
                if ($v_found) {
                    unset($v_header_list[$v_nb_extracted]);
                } else {
                    $v_nb_extracted++;
                }
            }

            // Look if something need to be deleted
            if ($v_nb_extracted > 0) {

                // Creates a temporay file
                $v_zip_temp_name = ARCHIVE_ZIP_TEMPORARY_DIR . uniqid('archive_zip-')
                        . '.tmp';

                // Creates a temporary zip archive
                $v_temp_zip = new ArchiveZip($v_zip_temp_name);

                // Open the temporary zip file in write mode
                if (($v_result = $v_temp_zip->_openFd('wb')) != 1) {
                    $this->_closeFd();

                    return $v_result;
                }

                // Look which file need to be kept
                for ($i = 0; $i < sizeof($v_header_list); $i++) {

                    // Calculate the position of the header
                    @rewind($this->zip_fd);
                    if (@fseek($this->zip_fd, $v_header_list[$i]['offset'])) {
                        // Clean
                        $this->_closeFd();
                        $v_temp_zip->_closeFd();
                        @unlink($v_zip_temp_name);

                        $this->_errorLog(
                            ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP,
                            'Invalid archive size'
                        );
                        return ArchiveZip::errorCode();
                    }

                    // Read the file header
                    if (($v_result = $this->_readFileHeader($v_header_list[$i])) != 1) {
                        // Clean
                        $this->_closeFd();
                        $v_temp_zip->_closeFd();
                        @unlink($v_zip_temp_name);

                        return $v_result;
                    }

                    // Write the file header
                    $v_result = $v_temp_zip->_writeFileHeader($v_header_list[$i]);
                    if ($v_result != 1) {
                        // Clean
                        $this->_closeFd();
                        $v_temp_zip->_closeFd();
                        @unlink($v_zip_temp_name);

                        return $v_result;
                    }

                    // Read/write the data block
                    $v_result = $this->_tool_CopyBlock(
                        $this->zip_fd,
                        $v_temp_zip->_zip_fd,
                        $v_header_list[$i]['compressed_size']
                    );
                    if ($v_result != 1) {
                        // Clean
                        $this->_closeFd();
                        $v_temp_zip->_closeFd();
                        @unlink($v_zip_temp_name);

                        return $v_result;
                    }
                }

                // Store the offset of the central dir
                $v_offset = @ftell($v_temp_zip->_zip_fd);

                // Re-Create the Central Dir files header
                for ($i = 0; $i < sizeof($v_header_list); $i++) {
                    // Create the file header
                    $v_result = $v_temp_zip->_writeCentralFileHeader($v_header_list[$i]);
                    if ($v_result != 1) {
                        // Clean
                        $v_temp_zip->_closeFd();
                        $this->_closeFd();
                        @unlink($v_zip_temp_name);

                        return $v_result;
                    }

                    // Transform the header to a 'usable' info
                    $v_temp_zip->_convertHeader2FileInfo(
                        $v_header_list[$i],
                        $result_list[$i]
                    );
                }

                // Zip file comment
                $v_comment = '';

                // Calculate the size of the central header
                $v_size = @ftell($v_temp_zip->_zip_fd) - $v_offset;

                // Create the central dir footer
                $v_result = $v_temp_zip->_writeCentralHeader(
                    sizeof($v_header_list),
                    $v_size,
                    $v_offset,
                    $v_comment
                );
                if ($v_result != 1) {
                    // Clean
                    unset($v_header_list);
                    $v_temp_zip->_closeFd();
                    $this->_closeFd();
                    @unlink($v_zip_temp_name);

                    return $v_result;
                }

                // Close
                $v_temp_zip->_closeFd();
                $this->_closeFd();

                // Delete the zip file
                // TBC : I should test the result ...
                @unlink($this->zipname);

                // Rename the temporary file
                // TBC : I should test the result ...
                //@rename($v_zip_temp_name, $this->zipname);
                $this->_tool_Rename($v_zip_temp_name, $this->zipname);

                // Destroy the temporary archive
                unset($v_temp_zip);
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _dirCheck()
        // Description :
        //   Check if a directory exists, if not it creates it and all the parents directory
        //   which may be useful.
        // Parameters :
        //   $dir : Directory path to check.
        // Return Values :
        //    1 : OK
        //   -1 : Unable to create directory
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_dirCheck()
         *
         * { Description }
         *
         * @param [type] $is_dir
         * @return int
         */
        public function _dirCheck($dir, $is_dir = false)
        {
            $v_result = 1;

            // Remove the final '/'
            if (($is_dir) && (substr($dir, -1) == '/')) {
                $dir = substr($dir, 0, strlen($dir) - 1);
            }

            // Check the directory availability
            if ((is_dir($dir)) || ($dir == "")) {
                return 1;
            }

            // Extract parent directory
            $parent_dir = dirname($dir);

            // Just a check
            if ($parent_dir != $dir) {
                // Look for parent directory
                if ($parent_dir != "") {
                    if (($v_result = $this->_dirCheck($parent_dir)) != 1) {
                        return $v_result;
                    }
                }
            }

            // Create the directory
            if (!@mkdir($dir, 0777)) {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_DIR_CREATE_FAIL,
                    "Unable to create directory '$dir'"
                );
                return ArchiveZip::errorCode();
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _merge()
        // Description :
        //   If $archive_to_add does not exist, the function exit with a success result.
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_merge()
         *
         * { Description }
         *
         * @return int
         */
        public function _merge(&$archive_to_add)
        {
            $v_result = 1;

            // Look if the archive_to_add exists
            if (!is_file($archive_to_add->_zipname)) {
                // Nothing to merge, so merge is a success
                return 1;
            }

            // Look if the archive exists
            if (!is_file($this->zipname)) {
                // Do a duplicate
                $v_result = $this->_duplicate($archive_to_add->_zipname);

                return $v_result;
            }

            // Open the zip file
            if (($v_result = $this->_openFd('rb')) != 1) {
                return $v_result;
            }

            // Read the central directory informations
            $v_central_dir = array();
            if (($v_result = $this->_readEndCentralDir($v_central_dir)) != 1) {
                $this->_closeFd();
                return $v_result;
            }

            // Go to beginning of File
            @rewind($this->zip_fd);

            // Open the archive_to_add file
            if (($v_result = $archive_to_add->_openFd('rb')) != 1) {
                $this->_closeFd();
                return $v_result;
            }

            // Read the central directory informations
            $v_central_dir_to_add = array();

            $v_result = $archive_to_add->_readEndCentralDir($v_central_dir_to_add);
            if ($v_result != 1) {
                $this->_closeFd();
                $archive_to_add->_closeFd();
                return $v_result;
            }

            // Go to beginning of File
            @rewind($archive_to_add->_zip_fd);

            // Creates a temporay file
            $v_zip_temp_name = ARCHIVE_ZIP_TEMPORARY_DIR . uniqid('archive_zip-') . '.tmp';

            // Open the temporary file in write mode
            if (($v_zip_temp_fd = @fopen($v_zip_temp_name, 'wb')) == 0) {
                $this->_closeFd();
                $archive_to_add->_closeFd();
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
                    'Unable to open temporary file \''
                        . $v_zip_temp_name . '\' in binary write mode'
                );
                return ArchiveZip::errorCode();
            }

            // Copy the files from the archive to the temporary file
            // TBC : Here I should better append the file and go back to erase the
            // central dir
            $v_size = $v_central_dir['offset'];
            while ($v_size != 0) {
                $v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                $v_buffer = fread($this->zip_fd, $v_read_size);

                @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
                $v_size -= $v_read_size;
            }

            // Copy the files from the archive_to_add into the temporary file
            $v_size = $v_central_dir_to_add['offset'];
            while ($v_size != 0) {
                $v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                $v_buffer = fread($archive_to_add->_zip_fd, $v_read_size);

                @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
                $v_size -= $v_read_size;
            }

            // Store the offset of the central dir
            $v_offset = @ftell($v_zip_temp_fd);

            // Copy the block of file headers from the old archive
            $v_size = $v_central_dir['size'];
            while ($v_size != 0) {
                $v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                $v_buffer = @fread($this->zip_fd, $v_read_size);

                @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
                $v_size -= $v_read_size;
            }

            // Copy the block of file headers from the archive_to_add
            $v_size = $v_central_dir_to_add['size'];
            while ($v_size != 0) {
                $v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                $v_buffer = @fread($archive_to_add->_zip_fd, $v_read_size);

                @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
                $v_size -= $v_read_size;
            }

            // Zip file comment
            // TBC : I should merge the two comments
            $v_comment = '';

            // Calculate the size of the (new) central header
            $v_size = @ftell($v_zip_temp_fd) - $v_offset;

            // Swap the file descriptor
            // Here is a trick : I swap the temporary fd with the zip fd, in order to use
            // the following methods on the temporary fil and not the real archive fd
            $v_swap = $this->zip_fd;

            $this->zip_fd = $v_zip_temp_fd;
            $v_zip_temp_fd = $v_swap;

            // Create the central dir footer
            if (($v_result = $this->_writeCentralHeader(
                $v_central_dir['entries'] + $v_central_dir_to_add['entries'],
                $v_size,
                $v_offset,
                $v_comment
            )) != 1) {
                $this->_closeFd();
                $archive_to_add->_closeFd();
                @fclose($v_zip_temp_fd);
                $this->zip_fd = null;

                // Reset the file list
                unset($v_header_list);

                return $v_result;
            }

            // Swap back the file descriptor
            $v_swap = $this->zip_fd;

            $this->zip_fd = $v_zip_temp_fd;
            $v_zip_temp_fd = $v_swap;

            // Close
            $this->_closeFd();
            $archive_to_add->_closeFd();

            // Close the temporary file
            @fclose($v_zip_temp_fd);

            // Delete the zip file
            // TBC : I should test the result ...
            @unlink($this->zipname);

            // Rename the temporary file
            // TBC : I should test the result ...
            //@rename($v_zip_temp_name, $this->zipname);
            $this->_tool_Rename($v_zip_temp_name, $this->zipname);

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _duplicate()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_duplicate()
         *
         * { Description }
         * @return int
         */
        public function _duplicate($archive_filename)
        {
            $v_result = 1;

            // Look if the $archive_filename exists
            if (!is_file($archive_filename)) {

                // Nothing to duplicate, so duplicate is a success.
                $v_result = 1;

                return $v_result;
            }

            // Open the zip file
            if (($v_result = $this->_openFd('wb')) != 1) {

                return $v_result;
            }

            // Open the temporary file in write mode
            if (($v_zip_temp_fd = @fopen($archive_filename, 'rb')) == 0) {
                $this->_closeFd();
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
                    'Unable to open archive file \''
                        . $archive_filename . '\' in binary write mode'
                );
                return ArchiveZip::errorCode();
            }

            // Copy the files from the archive to the temporary file
            // TBC : Here I should better append the file and go back to erase the
            // central dir
            $v_size = filesize($archive_filename);
            while ($v_size != 0) {
                $v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                $v_buffer = fread($v_zip_temp_fd, $v_read_size);

                @fwrite($this->zip_fd, $v_buffer, $v_read_size);
                $v_size -= $v_read_size;
            }

            // Close
            $this->_closeFd();

            // Close the temporary file
            @fclose($v_zip_temp_fd);

            return $v_result;
        }

        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_check_parameters()
         *
         * { Description }
         *
         * @param integer $error_code
         * @param string $error_string
         *
         * @return int
         */
        public function _check_parameters(&$params, $default)
        {

            // Check that param is an array
            if (!is_array($params)) {
                $this->_errorLog(
                    ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
                    'Unsupported parameter, waiting for an array'
                );
                return ArchiveZip::errorCode();
            }

            // Check that all the params are valid
            foreach ($params as $v_key => $v_value) {
                if (!isset($default[$v_key])) {
                    $this->_errorLog(
                        ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
                        'Unsupported parameter with key \'' . $v_key . '\''
                    );
                    return ArchiveZip::errorCode();
                }
            }

            // Set the default values
            foreach ($default as $v_key => $v_value) {
                if (!isset($params[$v_key])) {
                    $params[$v_key] = $default[$v_key];
                }
            }

            // Check specific parameters
            $v_callback_list = array('callback_pre_add', 'callback_post_add',
                'callback_pre_extract', 'callback_post_extract');
            for ($i = 0; $i < sizeof($v_callback_list); $i++) {
                $v_key = $v_callback_list[$i];
                if ((isset($params[$v_key])) && ($params[$v_key] != '')) {
                    if (!function_exists($params[$v_key])) {
                        $this->_errorLog(
                            ARCHIVE_ZIP_ERR_INVALID_PARAM_VALUE,
                            "Callback '" . $params[$v_key]
                                . "()' is not an existing function for "
                                . "parameter '" . $v_key . "'"
                        );
                        return ArchiveZip::errorCode();
                    }
                }
            }

            return (1);
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _errorLog()
        // Description :
        // Parameters :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_errorLog()
         *
         * { Description }
         *
         * @param integer $error_code   Error code
         * @param string  $error_string Error message
         *
         * @return void
         */
        public function _errorLog($error_code = 0, $error_string = '')
        {
            $this->error_code = $error_code;
            $this->error_string = $error_string;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : _errorReset()
        // Description :
        // Parameters :
        // ---------------------------------------------------------------------------

        /**
         * ArchiveZip::_errorReset()
         *
         * { Description }
         *
         * @return void
         */
        public function _errorReset()
        {
            $this->error_code = 1;
            $this->error_string = '';
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : $this->_tool_PathReduction()
        // Description :
        // Parameters :
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * _tool_PathReduction()
         *
         * { Description }
         *
         * @return string
         */
        public function _tool_PathReduction($dir)
        {
            $v_result = "";

            // Look for not empty path
            if ($dir != "") {
                // Explode path by directory names
                $v_list = explode("/", $dir);

                // Study directories from last to first
                for ($i = sizeof($v_list) - 1; $i >= 0; $i--) {
                    // Look for current path
                    if ($v_list[$i] == ".") {
                        // Ignore this directory
                        // Should be the first $i = 0, but no check is done
                    } elseif ($v_list[$i] == "..") {
                        // Ignore it and ignore the $i-1
                        $i--;
                    } elseif (($v_list[$i] == "") && ($i != (sizeof($v_list) - 1)) && ($i != 0)) {
                        // Ignore only the double '//' in path,
                        // but not the first and last '/'
                    } else {
                        $v_result = $v_list[$i] . ($i != (sizeof($v_list) - 1) ? "/" . $v_result : "");
                    }
                }
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : $this->_tool_PathInclusion()
        // Description :
        //   This function indicates if the path $path is under the $dir tree. Or,
        //   said in an other way, if the file or sub-dir $path is inside the dir
        //   $dir.
        //   The function indicates also if the path is exactly the same as the dir.
        //   This function supports path with duplicated '/' like '//', but does not
        //   support '.' or '..' statements.
        // Parameters :
        // Return Values :
        //   0 if $path is not inside directory $dir
        //   1 if $path is inside directory $dir
        //   2 if $path is exactly the same as $dir
        // ---------------------------------------------------------------------------

        /**
         * _tool_PathInclusion()
         *
         * { Description }
         *
         * @return int
         */
        public function _tool_PathInclusion($dir, $path)
        {
            $v_result = 1;

            // Explode dir and path by directory separator
            $v_list_dir = explode("/", $dir);
            $v_list_path = explode("/", $path);

            $v_list_dir_size = sizeof($v_list_dir);
            $v_list_path_size = sizeof($v_list_path);

            // Study directories paths
            $i = 0;
            $j = 0;

            while (($i < $v_list_dir_size) && ($j < $v_list_path_size) && ($v_result)) {
                // Look for empty dir (path reduction)
                if ($v_list_dir[$i] == '') {
                    $i++;
                    continue;
                }

                if ($v_list_path[$j] == '') {
                    $j++;
                    continue;
                }

                // Compare the items
                if (($v_list_dir[$i] != $v_list_path[$j]) && ($v_list_dir[$i] != '') && ($v_list_path[$j] != '')) {
                    $v_result = 0;
                }

                // Next items
                $i++;
                $j++;
            }

            // Look if everything seems to be the same
            if ($v_result) {
                // Skip all the empty items
                while (($j < $v_list_path_size) && ($v_list_path[$j] == '')) {
                    $j++;
                }

                while (($i < $v_list_dir_size) && ($v_list_dir[$i] == '')) {
                    $i++;
                }

                if (($i >= $v_list_dir_size) && ($j >= $v_list_path_size)) {
                    // There are exactly the same
                    $v_result = 2;
                } elseif ($i < $v_list_dir_size) {
                    // The path is shorter than the dir
                    $v_result = 0;
                }
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : $this->_tool_CopyBlock()
        // Description :
        // Parameters :
        //   $mode : read/write compression mode
        //             0 : src & dest normal
        //             1 : src gzip, dest normal
        //             2 : src normal, dest gzip
        //             3 : src & dest gzip
        // Return Values :
        // ---------------------------------------------------------------------------

        /**
         * _tool_CopyBlock()
         *
         * { Description }
         *
         * @param integer $mode
         *
         * @return int
         */
        public function _tool_CopyBlock($src, $dest, $size, $mode = 0)
        {
            $v_result = 1;

            if ($mode == 0) {
                while ($size != 0) {
                    $v_read_size = ($size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                    $v_buffer = @fread($src, $v_read_size);

                    @fwrite($dest, $v_buffer, $v_read_size);
                    $size -= $v_read_size;
                }
            } elseif ($mode == 1) {
                while ($size != 0) {
                    $v_read_size = ($size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                    $v_buffer = @gzread($src, $v_read_size);

                    @fwrite($dest, $v_buffer, $v_read_size);
                    $size -= $v_read_size;
                }
            } elseif ($mode == 2) {
                while ($size != 0) {
                    $v_read_size = ($size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                    $v_buffer = @fread($src, $v_read_size);

                    @gzwrite($dest, $v_buffer, $v_read_size);
                    $size -= $v_read_size;
                }
            } elseif ($mode == 3) {
                while ($size != 0) {
                    $v_read_size = ($size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $size : ARCHIVE_ZIP_READ_BLOCK_SIZE);

                    $v_buffer = @gzread($src, $v_read_size);

                    @gzwrite($dest, $v_buffer, $v_read_size);
                    $size -= $v_read_size;
                }
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : $this->_tool_Rename()
        // Description :
        //   This function tries to do a simple rename() function. If it fails, it
        //   tries to copy the $src file in a new $dest file and then unlink the
        //   first one.
        // Parameters :
        //   $src : Old filename
        //   $dest : New filename
        // Return Values :
        //   1 on success, 0 on failure.
        // ---------------------------------------------------------------------------

        /**
         * _tool_Rename()
         *
         * { Description }
         * @return int
         */
        public function _tool_Rename($src, $dest)
        {
            $v_result = 1;

            // Try to rename the files
            if (!@rename($src, $dest)) {

                // Try to copy & unlink the src
                if (!@copy($src, $dest)) {
                    $v_result = 0;
                } elseif (!@unlink($src)) {
                    $v_result = 0;
                }
            }

            return $v_result;
        }

        // ---------------------------------------------------------------------------
        // ---------------------------------------------------------------------------
        // Function : $this->_tool_TranslateWinPath()
        // Description :
        //   Translate windows path by replacing '\' by '/' and optionally removing
        //   drive letter.
        // Parameters :
        //   $path : path to translate.
        //   $remove_disk_letter : true | false
        // Return Values :
        //   The path translated.
        // ---------------------------------------------------------------------------

        /**
         * _tool_TranslateWinPath()
         *
         * { Description }
         *
         * @param [type] $remove_disk_letter
         *
         * @return string
         */
        public function _tool_TranslateWinPath($path, $remove_disk_letter = true)
        {
            if (stristr(php_uname(), 'windows')) {
                // Look for potential disk letter
                if (($remove_disk_letter) && (($v_position = strpos($path, ':')) != false)) {
                    $path = substr($path, $v_position + 1);
                }
                // Change potential windows directory separator
                if ((strpos($path, '\\') > 0) || (substr($path, 0, 1) == '\\')) {
                    $path = strtr($path, '\\', '/');
                }
            }
            return $path;
        }
    }

}
