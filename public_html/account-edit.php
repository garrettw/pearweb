<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2001-2003 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Authors:                                                             |
   +----------------------------------------------------------------------+
   $Id$
*/

auth_require();

require_once 'HTML/Form.php';

if (isset($_GET['handle'])) {
    $handle = strtolower($_GET['handle']);
} elseif (isset($_POST['handle'])) {
    $handle = strtolower($_POST['handle']);
} else {
    $handle = '';
}

ob_start();
response_header('Edit Account: ' . $handle);

print '<h1>Edit Account &quot;' . $handle . "&quot;</h1>\n";
print "<ul><li><a href=\"#password\">Manage your password</a></li></ul>";

$admin = $auth_user->isAdmin();
$user  = $auth_user->is($handle);

if (!$admin && !$user) {
    PEAR::raiseError("Only the user himself or PEAR administrators can edit the account information.");
    response_footer();
    exit();
}

if (empty($handle) && !isset($_POST['command'])) {
    PEAR::raiseError("No valid handle found!");
}

if (!isset($_POST['command'])) {
    $_POST['command'] = "display";
}

switch ($_POST['command']) {
    case 'update':
        if (isset($_POST['showemail'])) {
            $_POST['showemail'] = 1;
        } else {
            $_POST['showemail'] = 0;
        }
        $user = user::update($_POST);

        $old_acl = $dbh->getCol("SELECT path FROM cvs_acl ".
                                "WHERE username = ? AND access = 1", 0,
                                array($handle));
        $new_acl = preg_split("/[\r\n]+/", trim($cvs_acl));
        $lost_entries = array_diff($old_acl, $new_acl);
        $new_entries = array_diff($new_acl, $old_acl);
        if (sizeof($lost_entries) > 0) {
            $sth = $dbh->prepare("DELETE FROM cvs_acl WHERE username = ? ".
                                 "AND path = ?");
            foreach ($lost_entries as $ent) {
                $del = $dbh->affectedRows();
                print "Removing CVS access to $ent for $handle...<br />\n";
                $dbh->execute($sth, array($handle, $ent));
            }
        }
        if (sizeof($new_entries) > 0) {
            $sth = $dbh->prepare("INSERT INTO cvs_acl (username,path,access) ".
                                 "VALUES(?,?,?)");
            foreach ($new_entries as $ent) {
                print "Adding CVS access to $ent for $handle...<br />\n";
                $dbh->execute($sth, array($handle, $ent, 1));
            }
        }

        print '<div class="success">';
        print 'Your information was successfully updated.';
        print "</div>\n";
        break;

    case 'change_password':
        $user = &new PEAR_User($dbh, $handle);

        if (empty($_POST['password_old']) || empty($_POST['password']) ||
            empty($_POST['password2'])) {

            PEAR::raiseError('Please fill out all password fields.');
            break;
        }

        if ($user->get('password') != md5($_POST['password_old'])) {
            PEAR::raiseError('You provided a wrong old password.');
            break;
        }

        if ($_POST['password'] != $_POST['password2']) {
            PEAR::raiseError('The new passwords do not match.');
            break;
        }

        $user->set('password', md5($_POST['password']));
        if ($user->store()) {
            if (!empty($_POST['PEAR_PERSIST'])) {
                $expire = 2147483647;
            } else {
                $expire = 0;
            }
            setcookie('PEAR_PW', md5($_POST['password']), $expire, '/');

            print '<div class="success">';
            print 'Your password was successfully updated.';
            print "</div>\n";
        }
        break;
}


$dbh->setFetchmode(DB_FETCHMODE_ASSOC);

$row = $dbh->getRow('SELECT * FROM users WHERE handle = ?', array($handle));

$cvs_acl_arr = $dbh->getCol('SELECT path FROM cvs_acl'
                            . ' WHERE username = ? AND access = 1', 0,
                            array($handle));
$cvs_acl = implode("\n", $cvs_acl_arr);

if ($row === null) {
    PEAR::raiseError('No account information found!');
    response_footer();
    exit;
}


$th = 'class="form-label_left"';
$td = 'class="form-input"';

$form = new HTML_Form($_SERVER['PHP_SELF'], 'post');

$form->addPlaintext('Handle:', $handle,
        $th, $td);
$form->addText('name', '<span class="accesskey">N</span>ame:',
        $row['name'], 40, null, '',
        $th, $td);
$form->addText('email', 'Email:',
        $row['email'], 40, null, '',
        $th, $td);
$form->addText('pgpkeyid', 'PGP Key ID:'
        . '<p class="cell_note">(Without leading 0x)</p>',
        $row['pgpkeyid'], 40, 8, '',
        $th, $td);
$form->addText('homepage', 'Homepage:',
        $row['homepage'], 40, null, '',
        $th, $td);
$form->addTextarea('userinfo',
        'Additional User Information:',
        $row['userinfo'], 40, 5, null, '',
        $th, $td);
$form->addTextarea('cvs_acl',
        'CVS Access:',
        $cvs_acl, 40, 5, null, '',
        $th, $td);
$form->addText('wishlist', 'Wishlist URI:',
        $row['wishlist'], 40, null, '',
        $th, $td);
$form->addCheckbox('showemail', 'Show email address?',
        $row['showemail'], '',
        $th, $td);
$form->addSubmit('submit', 'Submit', '',
        $th, $td);
$form->addHidden('handle', $handle);
$form->addHidden('command', 'update');
$form->display('class="form-holder" style="margin-bottom: 2em;"'
               . ' cellspacing="1"',
               'Edit Your Information', 'class="form-caption"');


print '<a name="password"></a>' . "\n";


$form = new HTML_Form($_SERVER['PHP_SELF'], 'post');
$form->addPlaintext('<span class="accesskey">O</span>ld Password:',
        $form->returnPassword('password_old', '', 40, 0,
                              'accesskey="o"'),
        $th, $td);
$form->addPassword('password', 'Password:',
        '', 10, null, '',
        $th, $td);
$form->addCheckbox('PEAR_PERSIST', 'Remember username and password?',
        '', '',
        $th, $td);
$form->addSubmit('submit', 'Submit', '',
        $th, $td);
$form->addHidden('handle', $handle);
$form->addHidden('command', 'change_password');
$form->display('class="form-holder" cellspacing="1"',
               'Change Password', 'class="form-caption"');

ob_end_flush();
response_footer();

?>
