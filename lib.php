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
 * github lib
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$owner = 'djplaner';
$repo = 'edc3100';
$path = 'A_2nd_new_file.html';

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $node The node to add module settings to
 */
function booktool_github_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $PAGE;

    if (has_capability('booktool/github:export', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/book/tool/github/index.php', array('id'=>$PAGE->cm->id));
        $icon = new pix_icon('github', '', 'booktool_github', array('class'=>'icon'));
        $node->add(get_string('github', 'booktool_github'), $url, navigation_node::TYPE_SETTING, null, null, $icon);
    }
}

/***************************************************
 * github client specific calls

/**
 * $commits = getCommits( );
 * - return an array of GitHubCommit objects
 */

function booktool_github_get_commits() {
    global $owner, $repo, $path;

    $client = new GitHubClient();
#    $client->setDebug( true );

    $before = memory_get_usage();

    try{
        $commits = $client->repos->commits->listCommitsOnRepository(
                                $owner, $repo, null, $path );
    } catch ( Exception $e ) {
        echo '<xmp>Caught exception ' , $e->getMessage(), "</xmp>";
    }

    return $commits;
}

