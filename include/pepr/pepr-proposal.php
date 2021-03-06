<?php
/**
 * Establishes the procedures, objects and variables used throughout PEPr.
 *
 * The <var>$proposalStatiMap</var> is defined here.
 *
 * NOTE: Proposal constants are defined in pearweb/include/pear-config.php.
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  pearweb
 * @package   PEPr
 * @author    Tobias Schlitt <toby@php.net>
 * @author    Daniel Convissor <danielc@php.net>
 * @copyright Copyright (c) 1997-2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 */

global $proposalStatiMap;
$proposalStatiMap = array(
                          'draft'    => 'Draft',
                          'proposal' => 'Proposed',
                          'vote'     => 'Called for Votes',
                          'finished' => 'Finished'
                          );


class proposal
{
    var $id;
    var $pkg_category;
    var $pkg_name;
    var $pkg_license;
    var $pkg_description;
    var $pkg_deps;
    var $draft_date;
    var $proposal_date;
    var $vote_date;
    var $longened_date;
    var $status = 'draft';
    var $user_handle;
    var $links;
    var $votes;
    var $markup;

    function __construct($dbhResArr)
    {
        $this->fromArray($dbhResArr);
    }

    function fromArray($dbhResArr)
    {
        if (!is_array($dbhResArr)) {
            return false;
        }
        foreach ($dbhResArr as $name => $value) {
            if ($name == 'pkg_describtion') {
                $name = 'pkg_description';
            }
            $this->$name = $value;
        }
        return true;
    }

    function toRSSArray ($full = false)
    {
        return array(
            'title'         => 'PEPr Proposal ['.$this->id.']: '.$this->pkg_category.'::'.$this->pkg_name,
            'link'          => 'http://' . PEAR_CHANNELNAME . '/pepr/pepr-proposal-show.php?id='. $this->id,
            'desc'          => '
Proposed package:        '.$this->pkg_category.'::'.$this->pkg_name.'<br />
Proposer:                '.user_link($this->user_handle, true).'<br />
'.$this->getParsedDescription(),
            'date'          => $this->draft_date
         );
    }

    function getParsedDescription()
    {
        if (empty($this->pkg_description)) {
            return '';
        }
        // Switching markup types
        switch ($this->markup) {
            case 'wiki':
               include_once 'Text/Wiki.php';
               $wiki = new Text_Wiki();
               $wiki->disableRule('wikilink');
               $description = $wiki->transform($this->pkg_description);
               break;
            case 'bbcode':
            default:
               include_once 'HTML/BBCodeParser.php';
               $bbparser = new HTML_BBCodeParser(array('filters' => 'Basic,Images,Links,Lists,Extended'));
               $description = $bbparser->qparse(nl2br(htmlentities($this->pkg_description)));
               break;
        }
        return $description;
    }

    function fromOld($id)
    {
        global $dbh;
        if ($id === null) {
            return null;
        }

        if (!is_numeric($id)) {
            return new proposal(array());
        }

        $id  = (int)$id;
        $sql = "SELECT pkg_name, pkg_category, pkg_license, pkg_describtion, pkg_deps
                 FROM package_proposals WHERE id = ".$id;
        $res = $dbh->getRow($sql, null, DB_FETCHMODE_ASSOC);
        if (DB::isError($res)) {
            return new proposal(array());
        }

        if (!$res) {
            return new proposal(array());
        }

        return new proposal($res);
    }

    /**
     * Look up proposal information based on the proposal ID number
     *
     * @param object $dbh  the current DB object
     * @param int    $id   the ID number of the proposal being looked for
     *
     * @return object  a new proposal object.  false if the $id provided is
     *                 not numeric.  null if the $id doesn't refer to
     *                 an actual proposal.
     *
     * @access public
     */
    function get(&$dbh, $id)
    {
        if (!is_numeric($id)) {
            $res = false;
            return $res;
        }
        $id  = (int)$id;
        $sql = "SELECT *, UNIX_TIMESTAMP(draft_date) as draft_date,
                        UNIX_TIMESTAMP(proposal_date) as proposal_date,
                        UNIX_TIMESTAMP(vote_date) as vote_date,
                        UNIX_TIMESTAMP(longened_date) as longened_date
                 FROM package_proposals WHERE id = ".$id;
        $res = $dbh->getRow($sql, null, DB_FETCHMODE_ASSOC);
        if (DB::isError($res)) {
            return $res;
        }
        if (!$res) {
            return $res;
        }
        $t = new proposal($res);
        return $t;
    }

    function getRecent($dbh, $n) {
//'proposed'
       return self::getAll($dbh, null, $n, 'proposal_date DESC');
    }

    /**
     * Receive a complete bunch of proposals.
     *
     * @param object $dbh  the current DB object
     * @param string $status  the of the proposals to select
     * @param int    $limit  limit the number of proposals to receive
     * @param string $order  an SQL expression used by the "ORDER BY" statement
     *
     * @return array   an array of proposal objects (maybe with 0 elements,
     *                 if no proposals received)
     *
     * @access public
     */
    function &getAll(&$dbh, $status = null, $limit = null, $order = null)
    {
        $sql = "SELECT *, UNIX_TIMESTAMP(draft_date) as draft_date,
                        UNIX_TIMESTAMP(proposal_date) as proposal_date,
                        UNIX_TIMESTAMP(vote_date) as vote_date,
                        UNIX_TIMESTAMP(longened_date) as longened_date
                    FROM package_proposals";
        if (!empty($status)) {
            $sql .= " WHERE status = '".$status."'";
        }
        if (!isset($order)) {
            $sql .= " ORDER BY status ASC, draft_date DESC";
        } else {
            $sql .= " ORDER BY ".$order;
        }
        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }
        $res = $dbh->query($sql);
        if (DB::isError($res)) {
            return $res;
        }
        $result = array();
        while ($set = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $result[$set['id']] = new proposal($set);
        }
        return $result;
    }

    function &search($searchString)
    {
        global $dbh;
        $replacers = array(
            '/%/', '/_/', '/ /', '/\*/', '/\?/');
        $replacements = array(
            '\%', '\_', '%', '%', '_');
        $searchString = "%".preg_replace($replacers, $replacements, $searchString)."%";

        $sql = "SELECT *, UNIX_TIMESTAMP(draft_date) as draft_date,
                       UNIX_TIMESTAMP(proposal_date) as proposal_date,
                       UNIX_TIMESTAMP(vote_date) as vote_date,
                       UNIX_TIMESTAMP(longened_date) as longened_date
                FROM package_proposals
                WHERE pkg_describtion LIKE ".$dbh->quoteSmart($searchString)."
                      OR pkg_name LIKE ".$dbh->quoteSmart($searchString)."
                      OR pkg_category LIKE ".$dbh->quoteSmart($searchString)."
                ORDER BY status ASC, draft_date DESC";
        $res = $dbh->query($sql);
        if (DB::isError($res)) {
            return $res;
        }
        $result = array();
        while ($set = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $result[$set['id']] = new proposal($set);
        }
        return $result;
    }

    function getLinks(&$dbh)
    {
        if (empty($this->id)) {
            return PEAR::raiseError("Not initialized");
        }
        $this->links = & ppLink::getAll($dbh, $this->id);
        return true;
    }

    function getVotes(&$dbh)
    {
        if (empty($this->id)) {
            return PEAR::raiseError("Not initialized");
        }
        $this->votes = & ppVote::getAll($dbh, $this->id);
        return true;
    }

    function mayRePropose($handle, $checkid = false)
    {
        global $dbh;
        if (!$this->id) {
            $this->id = 0;
        }

        if ($checkid) {
            $test = $dbh->getOne('SELECT id FROM package_proposals WHERE pkg_category = ?
                AND pkg_name = ? AND user_handle = ? AND id <> ?', array($this->pkg_category,
                $this->pkg_name, $this->user_handle, $this->id));
            $next = $dbh->getAll('SELECT id, status FROM package_proposals WHERE pkg_category = ?
                    AND pkg_name = ? AND user_handle = ? AND id <> ?', array($this->pkg_category,
                    $this->pkg_name, $this->user_handle, $this->id));
        } else {
            $test = $dbh->getOne('SELECT id FROM package_proposals WHERE pkg_category = ?
                AND pkg_name = ? AND user_handle = ?', array($this->pkg_category,
                $this->pkg_name, $this->user_handle));
            $next = $dbh->getAll('SELECT id, status FROM package_proposals WHERE pkg_category = ?
                    AND pkg_name = ? AND user_handle = ?', array($this->pkg_category,
                    $this->pkg_name, $this->user_handle));
        }
        if ($test) {
            foreach ($next as $p) {
                if ($p[1] != 'finished') {
                    return false;
                }
                $votes = ppVote::getSum($dbh, $p[0]);
                if ($votes['all'] > 5) {
                    // proposal was accepted, can't repropose
                    return false;
                }
            }
            return true;
        }
        return true;
    }

    function store($dbh)
    {
        if (isset($this->id)) {
            $inf = $dbh->getAll('SELECT pkg_name, pkg_category FROM package_proposals
                WHERE id = ?', array($this->id));
            if ($inf[0] != $this->pkg_name || $inf[1] != $this->pkg_category) {
                if (!$this->mayRePropose($this->user_handle, true)) {
                    return PEAR::raiseError('A proposal with that Category -'
                            . ' Name combination already exists.');
                }
            }
            $sql = "UPDATE package_proposals SET
                    pkg_category = ".$dbh->quoteSmart($this->pkg_category).",
                    pkg_name = ".$dbh->quoteSmart($this->pkg_name).",
                    pkg_license = ".$dbh->quoteSmart($this->pkg_license).",
                    pkg_describtion = ".$dbh->quoteSmart($this->pkg_description).",
                    pkg_deps = ".$dbh->quoteSmart($this->pkg_deps).",
                    draft_date = FROM_UNIXTIME({$this->draft_date}),
                    proposal_date = FROM_UNIXTIME({$this->proposal_date}),
                    vote_date = FROM_UNIXTIME({$this->vote_date}),
                    longened_date = FROM_UNIXTIME({$this->longened_date}),
                    status = ".$dbh->quoteSmart($this->status).",
                    user_handle = ".$dbh->quoteSmart($this->user_handle).",
                    markup = ".$dbh->quoteSmart($this->markup)."
                    WHERE id = ".$this->id;
            $dbh->pushErrorHandling(PEAR_ERROR_RETURN);
            $res = $dbh->query($sql);
            $dbh->popErrorHandling();
            if (DB::isError($res)) {
                if ($res->getCode() == DB_ERROR_CONSTRAINT) {
                    return PEAR::raiseError('A proposal with that Category -'
                            . ' Name combination already exists.',
                            $res->getCode(), null, null, $res->getUserInfo());
                } else {
                    return PEAR::raiseError($res->getMessage(),
                                            $res->getCode(), null, null,
                                            $res->getUserInfo());
                }
            }
        } else {
            if ($dbh->getOne('SELECT id FROM package_proposals WHERE pkg_category = ?
                    AND pkg_name = ? AND user_handle <> ?', array($this->pkg_category,
                    $this->pkg_name, $this->user_handle))) {
                return PEAR::raiseError('A proposal with that Category -'
                        . ' Name combination already exists.');
            }
            if (!$this->mayRePropose($this->user_handle)) {
                // proposal was accepted, can't repropose
                return PEAR::raiseError('A non-rejected proposal with that Category -'
                        . ' Name combination already exists.');
            }
            $sql = "INSERT INTO package_proposals (pkg_category, pkg_name, pkg_license, pkg_describtion,
                        pkg_deps, draft_date, status, user_handle, markup) VALUES (
                        ".$dbh->quoteSmart($this->pkg_category).",
                        ".$dbh->quoteSmart($this->pkg_name).",
                        ".$dbh->quoteSmart($this->pkg_license).",
                        ".$dbh->quoteSmart($this->pkg_description).",
                        ".$dbh->quoteSmart($this->pkg_deps).",
                        FROM_UNIXTIME(".time()."),
                        ".$dbh->quoteSmart($this->status).",
                        ".$dbh->quoteSmart($this->user_handle).",
                        ".$dbh->quoteSmart($this->markup).")";
            $dbh->pushErrorHandling(PEAR_ERROR_RETURN);
            $res = $dbh->query($sql);
            $dbh->popErrorHandling();
            if (DB::isError($res)) {
                if ($res->getCode() == DB_ERROR_CONSTRAINT) {
                    return PEAR::raiseError('A proposal with that Catetory -'
                            . ' Name combination already exists.',
                            $res->getCode(), null, null, $res->getUserInfo());
                } else {
                    return PEAR::raiseError($res->getMessage(),
                                            $res->getCode(), null, null,
                                            $res->getUserInfo());
                }
            }
            $this->id = mysqli_insert_id($dbh->connection);
        }
        ppLink::deleteAll($dbh, $this->id);
        if (isset($this->links) && count($this->links)) {
            foreach ($this->links as $link) {
                if (!empty($link->url)) {
                    $res = $link->store($dbh, $this->id);
                    if (DB::isError($res)) {
                        return $res;
                    }
                }
            }
        }
        if (!empty($this->comment)) {
            $this->comment->store($dbh, $this->id);
            unset($this->comment);
        }
        return true;
    }

    function addVote($dbh, $vote)
    {
        if (!empty($this->votes[$vote->user_handle])) {
            return PEAR::raiseError("You already voted!");
        }
        $vote->pkg_propop_id = $this->id;
        $this->votes[$vote->user_handle] = $vote;
        $vote->store($dbh, $this->id);
        return true;
    }

    function addComment($comment, $table = 'package_proposal_changelog')
    {
        global $auth_user;

        $commentData = array("pkg_prop_id" => $this->id,
                             "user_handle" => $auth_user->handle,
                             "comment"     => $comment);
        $comment = new ppComment( $commentData, $table );
        $comment->store($this->id);
        return true;
    }

    function addLink($link)
    {
        $link = new ppLink($link);
        $link->pkg_prop_id = $this->id;
        $this->links[] = $link;
        return true;
    }

    function isOwner($handle)
    {
        if (strtolower($this->user_handle) != strtolower($handle)) {
            return false;
        }
        return true;
    }

    function mayEdit($handle = '')
    {
        global $dbh, $karma;

        if (empty($karma)) {
            $karma = new Damblan_Karma($dbh);
        }

        switch ($this->status) {
            case 'draft':
            case 'proposal':
                if ($this->isOwner($handle) || $karma->has($handle, 'pear.pepr.admin')) {
                    return true;
                }
              break;
            default:
                if (!$this->isOwner($handle) && $karma->has($handle, 'pear.pepr.admin')) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Determine if the current user can vote on the current proposal
     *
     * Rules:
     *   + Proposal must be in the "Called for Votes" phase.
     *   + User must be logged in.
     *   + User must be a full-featured PEAR developer.
     *   + Only one vote can be cast.
     *   + Proposers can't vote on their own package, though can for RFC's.
     *
     * @param object $dbh         the current DB object
     * @param string $userHandle  the user's handle
     *
     * @return bool
     *
     * @access public
     */
    function mayVote(&$dbh, $userHandle)
    {
        global $karma;

        if (empty($karma)) {
            $karma = new Damblan_Karma($dbh);
        }

        if ($this->getStatus() == 'vote' &&
            $karma->has($userHandle, 'pear.dev') &&
            !ppVote::hasVoted($dbh, $userHandle, $this->id) &&
            (!$this->isOwner($userHandle) ||
             ($this->isOwner($userHandle) &&
              $this->pkg_category == 'RFC')))
        {
            return true;
        }

        return false;
    }

    function getStatus($humanReadable = false)
    {
        if ($humanReadable) {
            return $GLOBALS['proposalStatiMap'][$this->status];
        }
        return $this->status;
    }

    /**
     * Answers the question "Is this proposal $operator than $status?"
     *
     * @param string $operator  the operator (<, <=, ==, >=, >, !=)
     * @param string $status    the status ('draft', 'vote', 'finished', etc)
     *
     * @return bool
     */
    function compareStatus($operator, $status)
    {
        $num = array(
            'draft'    => 1,
            'proposal' => 2,
            'vote'     => 3,
            'finished' => 4,
        );
        switch ($operator) {
            case '<':
                return ($num[$this->status] < $num[$status]);
            case '<=':
                return ($num[$this->status] <= $num[$status]);
            case '==':
                return ($num[$this->status] == $num[$status]);
            case '>=':
                return ($num[$this->status] >= $num[$status]);
            case '>':
                return ($num[$this->status] > $num[$status]);
            case '!=':
                return ($num[$this->status] != $num[$status]);
            default:
                PEAR::raiseError('Invalid $operator passed to compareStatus()');
        }
    }

    function isEditable()
    {
        switch ($this->status) {
        case 'draft':
        case 'proposal': return true;
        }
        return false;
    }

    function checkTimeline()
    {
        switch ($this->status) {
        case 'draft':
            return true;
        case 'proposal':
            if (($this->proposal_date + PROPOSAL_STATUS_PROPOSAL_TIMELINE) < time()) {
                return true;
            }
            return (int)($this->proposal_date + PROPOSAL_STATUS_PROPOSAL_TIMELINE);
        case 'vote':
            if (!empty($this->longened_date)) {
                if (($this->longened_date + PROPOSAL_STATUS_VOTE_TIMELINE) > time()) {
                    return (int)($this->longened_date + PROPOSAL_STATUS_VOTE_TIMELINE);
                }
            } else {
                if (($this->vote_date + PROPOSAL_STATUS_VOTE_TIMELINE) > time()) {
                    return (int)($this->vote_date + PROPOSAL_STATUS_VOTE_TIMELINE);
                }
            }
            return false;
        }
    }

    function delete(&$dbh)
    {
        if (empty($this->id)) {
            return PEAR::raiseError("Proposal does not exist!");
        }
        $sql = "DELETE FROM package_proposals WHERE id = ".$this->id;
        $res = $dbh->query($sql);
        if (DB::isError($res)) {
            return $res;
        }
        $sql = "DELETE FROM package_proposal_votes WHERE pkg_prop_id = ".$this->id;
        $res = $dbh->query($sql);
        if (DB::isError($res)) {
            return $res;
        }
        $sql = "DELETE FROM package_proposal_links WHERE pkg_prop_id = ".$this->id;
        $res = $dbh->query($sql);
        if (DB::isError($res)) {
            return $res;
        }
        $sql = "DELETE FROM package_proposal_changelog WHERE pkg_prop_id = ".$this->id;
        $res = $dbh->query($sql);
        if (DB::isError($res)) {
            return $res;
        }
        $sql = "DELETE FROM package_proposal_comments WHERE pkg_prop_id = ".$this->id;
        $res = $dbh->query($sql);
        if (DB::isError($res)) {
            return $res;
        }
        return true;
    }

    function sendActionEmail($event, $userType, $user_handle = null,
                             $comment = '')
    {
        global $dbh, $karma, $auth_user;

        if (empty($karma)) {
            $karma = new Damblan_Karma($dbh);
        }

        require 'pepr/pepr-emails.php';
        $email = $proposalEmailTexts[$event];
        if (empty($email)) {
            return PEAR::raiseError("Email template for $event not found");
        }
        switch ($userType) {
        case 'admin':
            $prefix = "[ADMIN]";
            break;
        case 'mixed':
            if ($karma->has($user_handle, "pear.pepr.admin") && ($this->user_handle != $user_handle)) {
                $prefix = "[ADMIN]";
            } else {
                $prefix = "";
            }
            break;
        default:
            $prefix = "";
        }
        $prefix = PROPOSAL_EMAIL_PREFIX . $prefix . " ";
        include_once 'pear-database-user.php';
        $actorinfo = user::info($user_handle);
        $ownerinfo = user::info($this->user_handle);
        $this->getVotes($dbh);
        $vote = @$this->votes[$user_handle];
        if (isset($vote)) {
            $vote->value = ($vote->value > 0) ? "+".$vote->value : $vote->value;
            if ($vote->is_conditional) {
                $vote_conditional = "\n\nThis vote is conditional. The condition is:\n\n".$vote->comment;
            } elseif ($vote->comment) {
                $comment = "\n\nComment:\n\n" . $vote->comment;
            }

            $vote_url = "http://" . PEAR_CHANNELNAME . "/pepr/pepr-vote-show.php?id=".$this->id."&handle=".$user_handle;
        }

        if ($event == 'change_status_finished') {
            $proposalVotesSum = ppVote::getSum($dbh, $this->id);

            $vote_result  = 'Sum of Votes: ' . $proposalVotesSum['all'];
            $vote_result .= ' (' . $proposalVotesSum['conditional']
                          . ' conditional)';

            if ($proposalVotesSum['all'] >= 5) {
                $vote_result .= "\nResult:       This proposal was accepted";
            } else {
                $vote_result .= "\nResult:       This proposal was rejected";
            }
        }

        $proposal_url = "http://" . PEAR_CHANNELNAME . "/pepr/pepr-proposal-show.php?id=".$this->id;
        $end_voting_time = (@$this->longened_date > 0) ? $this->longened_date + PROPOSAL_STATUS_VOTE_TIMELINE : @$this->vote_date + PROPOSAL_STATUS_VOTE_TIMELINE;

        if ($event == 'proposal_comment' && $user_handle == $this->user_handle) {
            $email['to'] = $email['to']['owner'];
        } else {
            if (!isset($user_handle)) {
                $email['to'] = $email['to']['pearweb'];
            } else if ($karma->has($user_handle, "pear.pepr.admin")) {
                $email['to'] = $email['to']['admin'];
            } else {
                $email['to'] = $email['to']['user'];
            }
        }

        $email['subject'] = $prefix . $email['subject'];
        $replace = array(
                         "/\{pkg_category\}/",
                         "/\{pkg_name\}/",
                         "/\{owner_name\}/",
                         "/\{owner_email\}/",
                         "/\{owner_link\}/",
                         "/\{actor_name\}/",
                         "/\{actor_email\}/",
                         "/\{actor_link\}/",
                         "/\{proposal_url\}/",
                         "/\{end_voting_time\}/",
                         "/\{vote_value\}/",
                         "/\{vote_url\}/",
                         "/\{email_pear_dev\}/",
                         "/\{email_pear_group\}/",
                         "/\{comment\}/",
                         "/\{vote_result\}/",
                         "/\{vote_conditional\}/"
                         );
        $replacements = array(
                              $this->pkg_category,
                              $this->pkg_name,
                              (isset($ownerinfo['name'])) ? $ownerinfo['name'] : "",
                              (isset($ownerinfo['email'])) ? "<{$ownerinfo['email']}>" : '',
                              (isset($ownerinfo['handle'])) ? user_link($ownerinfo['handle'], true) : "",
                              (isset($actorinfo['name'])) ? $actorinfo['name'] : "",
                              (isset($actorinfo['email'])) ? $actorinfo['email'] : "",
                              (isset($actorinfo['handle'])) ? "http://" . PEAR_CHANNELNAME . "/user/".$actorinfo['handle'] : "",
                              $proposal_url,
                              format_date($end_voting_time),
                              (isset($vote)) ? $vote->value : 0,
                              (isset($vote)) ? $vote_url : "",
                              PROPOSAL_MAIL_PEAR_DEV,
                              PROPOSAL_MAIL_PEAR_GROUP,
                              (isset($comment)) ? wordwrap($comment) : '',
                              (isset($vote_result)) ? $vote_result : '',
                              (isset($vote_conditional)) ? $vote_conditional : ""
                              );

        $email = preg_replace($replace, $replacements, $email);
        $email['text'] .= PROPOSAL_EMAIL_POSTFIX;

        if (is_object($auth_user)) {
            $from = '"' . $auth_user->name . '" <' . $auth_user->email . '>';
        } else {
            $from = PROPOSAL_MAIL_FROM ;
        }

        $to = explode(", ", $email['to']);
        $email['to'] = array_shift($to);
        $headers = "CC: ". implode(", ", $to) . "\n";
        $headers .= "From: " . $from . "\n";
        $headers .= "X-Mailer: " . "PEPr, PEAR Proposal System" . "\n";
        $headers .= "X-PEAR-Category: " . $this->pkg_category . "\n";
        $headers .= "X-PEAR-Package: " . $this->pkg_name . "\n";
        $headers .= "X-PEPr-Status: " . $this->getStatus() . "\n";

        if ($event == "change_status_proposal") {
            $headers .= "Message-ID: <proposal-" . $this->id . "@" . PEAR_CHANNELNAME . ">\n";
        } else {
            $headers .= "In-Reply-To: <proposal-" . $this->id . "@" . PEAR_CHANNELNAME . ">\n";
        }

        if (!DEVBOX) {
            $res = mail($email['to'], $email['subject'], $email['text'],
                        $headers, '-f ' . PEAR_BOUNCE_EMAIL);
        } else {
            $res = true;
        }
        if (!$res) {
            return PEAR::raiseError('Could not send notification email.');
        }
        return true;
    }
}
