<?php // -*- C++ -*-

require_once "HTML/Form.php";

auth_require(1);

$display_form = true;

function displayError($msg)
{
    global $errorMsg;
    $errorMsg .= "<FONT COLOR=\"#CC0000\">$msg</FONT><BR>\n";
}

$jumpto = "handle";

do {
    if (isset($submit)) {
        $required = array("handle", "name", "email");
        while (list($i, $field) = each($required)) {
            if (!$$field) {
                displayError("Please enter $field");
                $jumpto = $field;
                break 2;
            }
        }

        if (!preg_match("/^[a-z][a-z0-9]+$/i", $handle)) {
            displayError("Handle must start with a letter and contain ".
                         "only letters and digits.");
            break;
        }

        if ($password != $password2) {
            displayError("Passwords did not match");
            $password = $password2 = "";
            $jumpto = "password";
            break;
        }

        if (!$password) {
            displayError("Empty passwords not allowed");
            $jumpto = "password";
            break;
        }

        $handle = strtoupper($handle);
        $obj = new Author(&$dbh);
        $err = $obj->insert($handle);
        if (DB::isError($err)) {
            displayError("$handle: " . DB::errorMessage($err));
            $jumpto = "handle";
            break;
        }

        $display_form = false;
        $obj->name      = $name;
        $obj->email     = $email;
        $obj->homepage  = $homepage;
        $obj->showemail = (bool)$showemail;
        $obj->admin     = (bool)$admin;
        $obj->password  = md5($password);
    }
} while (0);

if ($display_form) {

    response_header("PEAR: Add an author");

?>
<H1>Add an author</H1>
<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<TABLE>
<?php

    if (isset($errorMsg)) {
        print " <TR>\n";
        print "  <TD>&nbsp;</TD>\n";
        print "  <TD><B>$errorMsg</B></TD>\n";
        print " </TR>\n";
    } else {
        $handle = $name = $email = $homepage = $password = '';
    }
    $showemail = (isset($showemail)) ? true : false;
    $admin     = (isset($admin)) ? true : false;

    $width = 45;
    $form = new HTML_Form($PHP_SELF, "POST");
    $form->addText("handle", "Handle", $handle, 12);
    $form->addText("name", "Name", $name, $width);
    $form->addText("email", "Email", $email, $width);
    $form->addText("homepage", "Homepage", $homepage, $width);
    $form->addCheckbox("showemail", "Show Email Address?", $showemail);
    $form->addCheckbox("admin", "Administrator?", $admin);
    $form->addPassword("password", "Password", $password, 10);
    $form->addTextarea("info", "More info", '', $width, 5);
    $form->addSubmit("submit", "Add Author");

    $form->display();

    if ($jumpto) {
        print "<SCRIPT LANGUAGE=\"JavaScript\">\n<!--\n";
        print "document.forms[0].$jumpto.focus();\n";
        print "\n// -->\n</SCRIPT>\n";
    }
}

response_footer();

?>
