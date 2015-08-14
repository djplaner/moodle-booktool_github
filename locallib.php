<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Book github export lib
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/mod/book/locallib.php');

/**
 * WHAT MIGHT GO HERE???
 *
 * @param stdClass $book book instance
 * @param context_module $context
 * @return bool|stored_file
 */
function booktool_github_some_function($book, $context) {
    global $DB;

#    $fs = get_file_storage();

#    if ($packagefile = $fs->get_file($context->id, 'booktool_exportimscp', 'package', $book->revision, '/', 'imscp.zip')) {
#        return $packagefile;
#    }

    // fix structure and test if chapters present
#    if (!book_preload_chapters($book)) {
#        print_error('nochapters', 'booktool_exportimscp');
#    }

    // prepare temp area with package contents
#    booktool_exportimscp_prepare_files($book, $context);

#    $packer = get_file_packer('application/zip');
#    $areafiles = $fs->get_area_files($context->id, 'booktool_exportimscp', 'temp', $book->revision, "sortorder, itemid, filepath, filename", false);
#    $files = array();
#    foreach ($areafiles as $file) {
#        $path = $file->get_filepath().$file->get_filename();
#        $path = ltrim($path, '/');
#        $files[$path] = $file;
#    }
#    unset($areafiles);
#    $packagefile = $packer->archive_to_storage($files, $context->id, 'booktool_exportimscp', 'package', $book->revision, '/', 'imscp.zip');
##
    // drop temp area
#    $fs->delete_area_files($context->id, 'booktool_exportimscp', 'temp', $book->revision);

    // delete older versions
#    $sql = "SELECT DISTINCT itemid
#              FROM {files}
#             WHERE contextid = :contextid AND component = 'booktool_exportimscp' AND itemid < :revision";
#    $params = array('contextid'=>$context->id, 'revision'=>$book->revision);
#    $revisions = $DB->get_records_sql($sql, $params);
#    foreach ($revisions as $rev => $unused) {
#        $fs->delete_area_files($context->id, 'booktool_exportimscp', 'temp', $rev);
#        $fs->delete_area_files($context->id, 'booktool_exportimscp', 'package', $rev);
#    }
#
#    return $packagefile;
}

