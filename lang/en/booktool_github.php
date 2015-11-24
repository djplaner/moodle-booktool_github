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
 * github booktool language strings
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djon.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Book github link';
$string['github'] = 'GitHub';

# booktool_github_view_comments
$string['commit_details'] = 'commit details';


# connection form
$string['repo_form_element'] = 'GitHub repository:';
$string['path_form_element'] = 'Path to file in repository:';



/******************************************************************
 * strings for show functions
 */

$string['instructions_what_header'] = '<h2>What?</h2>';
$string['instructions_what_body'] ='<p>This tool allows the content of this <a href="https://docs.moodle.org/29/en/Book_module">Moodle Book</a> to linked to a single file within a <a href="https://guides.github.com/activities/hello-world/#repository">GitHub repository</a>. This link allows the content of the Book to be based on the content of the file in the GitHub repository.</p>';

$string['instructions_why_header'] = '<h2>Why?</h2>';
$string['instructions_why_body'] = '<p>Doing this enhances the Book with features provided by <a href="http://github.com/">GitHub</a>, including:</p><ul> 
   <li> version control </li>
  <li> issue tracking </li>
  <li> open sharing beyond the LMS </li> </ul>';

$string['instructions_requirements_header'] = '<h2>Requirements?</h2>';
$string['instructions_requirements_body'] = '<p>Before you can use the GitHub tool with the Book module, you will need:</p><ol> <li> A <a href="https://github.com/join">GitHub account</a>. </li>
    <li> A <a href="https://help.github.com/articles/create-a-repo/">GitHub repository</a> 
<p>The repository can be a new repository you created, or an existing repository. The repository can be owned by anyone, but the GithHub account you use will need to have permission to commit changes to the repository. You may need to <a href="https://help.github.com/articles/fork-a-repo/">fork</a> an existing repository to get the necessary permissions.</p>
 </li> 
    <li> The path to a specific file within the respository. 
<p>The path indiciates which file within the repository will be connected to t
he Book activity.</p></li></ol>' ;

$string['instructions_whatnext_header'] = '<h2>What next?</h2>';
$string['instructions_whatnext_body'] = '<ol> <li> If you have all the requirements listed above, then <a href="{$a->git_url}">click here</a> to start using the GitHub book tool;<p>Your first task will be to login to your GitHub account.</p> </li> <li> If you want to wait till later, <a href="{$a->book_url}">return to the book</a>. </li> </ol> ';


#$string['eventbookexported'] = 'Book exported';
#$string['exportimscp:export'] = 'Export book as IMS content package';
