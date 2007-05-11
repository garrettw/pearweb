<?php response_header($title); ?>

<script src="/javascript/jquery.js"></script>
<script src="/javascript/thickbox.js"></script>
<script language="javascript">

function checkAll()
{
    $("input[@type='checkbox']").each(function() {
        if (this.checked) {
            this.checked = false;
            $('input[@id=submitButton]').attr({'value': 'Select All'}); 
            return true;
        }
        $('input[@id=submitButton]').attr({'value': 'Deselect All'});
        this.checked = true;
    });
}
</script>
<style type="text/css">
@import "/css/thickbox.css";
</style>

<h1>Notes Management Area</h1>
<h3><?php echo $title; ?></h3>
<?php include dirname(dirname(dirname(__FILE__))) . '/templates/notes/note-manage-links.tpl.php'; ?>
<?php if (strlen(trim($error)) > 0): // {{{ error ?>
<div class="errors"><?php echo $error; ?></div>
<?php endif; // }}} ?>
<?php if (isset($message) && strlen(trim($message)) > 0): // {{{ message?>
<div class="message"><?php echo $message; ?></div>
<?php endif; // }}} ?>

<?php
if (isset($url) && !empty($url)) {
    echo '<a href="/manual/en/', 
          urlencode(htmlspecialchars($url)), 
         '">Return to manual</a>';
}
?>

<form action="/notes/admin/trans.php" method="post">
 <input type="hidden" name="action" value="<?php echo $action ?>" />
 <input type="hidden" name="url" value="<?php echo htmlspecialchars($url) ?>" />
 <table class="form-holder" cellspacing="1">
  <tr>
   <th class="form-label_left">Status</th>
   <td class="form-input">Manual</td>
   <td class="form-input">Comment</td>
   <td class="form-input">Name/Email</td>
   <td class="form-input">View Note</td>
  </tr>
  <?php foreach ($pendingComments as $pendingComment): ?>
  <tr>
  <th class="form-label_left">
   <input type="checkbox" name="noteIds[]" value="<?php echo $pendingComment['note_id']; ?>" />
   <br /> 
    <input class="makeDocBug" type="button" 
           value="Transform into a Doc Bug" onclick="document.location.href='/notes/admin/trans.php?action=makeDocBug&noteId=<?php echo $pendingComment['note_id']; ?>&url=<?php echo $pendingComment['page_url']; ?>'"/>
   </th>
   <td class="form-input">
   <a href="/manual/en/<?php echo $pendingComment['page_url'] ?>"><?php
    echo $pendingComment['page_url'] ?></a>
   </td>
   <td class="form-input">
   <?php 
     if (strlen($pendingComment['unfiltered_note']) > 200) {
         echo substr(htmlspecialchars($pendingComment['unfiltered_note']), 0, 200) . '...';
     } else {
         echo $pendingComment['note_text'];
     } 
   ?>
   </td>
   <td class="form-input">
   <?php echo htmlspecialchars($pendingComment['user_name']); ?>
   </td>
   <td class="form-input">
    <a class="thickbox" href="view-note.php?height=300&width=450&ajax=yes&noteId=<?php echo $pendingComment['note_id'] ?>" 
       title="See full note">View</a> 
    | 
    <a href="view-note.php?ajax=no&status=<?php echo $status ?>&noteId=<?php echo $pendingComment['note_id'] ?>" 
       title="No JS View">(no js)</a>
 
  </tr>
  </tr>
 <?php endforeach; ?>
  <tr>
   <th class="form-label_left">
    <?php echo $caption ?></th>
   <td class="form-input" colspan="4">
    <input id="submitButton" type="button" onclick="checkAll()" value="Select All" />&nbsp;
    <input type="submit" name="<?php echo $name ?>" value="<?php echo $button ?>" />
   </td>
  </tr>
  <?php if ($name != 'undelete'): ?>
  <tr>
   <th class="form-label_left">Delete Notes</th>
   <td class="form-input" colspan="4">
    <div align="right"><input type="submit" name="delete" value="Delete" /></div>
   </td>
  </tr>   
  <?php endif; ?>
 </table>
</form>
<?php response_footer(); ?>
