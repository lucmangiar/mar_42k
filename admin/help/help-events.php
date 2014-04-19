<?php
function sigma_email_template_help(){
    $content  = '<h2>Email Template Tags</h2>';
    $content .= '<p>You can use email template tags in the email template.</p>';
    $content .= '<p>If you place <b>{{token}}</b> in your template,
        it will be translated to the actual <b>token of the registrant</b>.</p>';
    $content .= '<p>You can use the following tags in the email template.</p>';
    $content .= '<p> {{Fname}} - {{Lname}} - {{sigmalogo}} - {{eventlogo}} - {{eid}} -
        {{ename}} - {{econtent}} - {{id}} - {{token}} - {{reg_time}} -
        {{eid}} - {{fname}} - {{lname}} - {{argentinian}} - {{country}} -
        {{dni}} - {{email}} - {{gender}} - {{bday}} - {{phone}} - {{addr}} -
        {{club}} - {{ans}} - {{paid}} . ';
    $content .= 'You can also use {{reason}} in your payment not approval email, where applicable.';
    $content .= '<p>Some of the fields might be blank, if the registrant hasn\'t entered the
        the relevant information on registration.</p>';

    $help = array(
        'id'      => 'sigma-email-template-help',
        'title'   => 'Email Templates',
        'content' => $content
    );
    return $help;
}

function sigma_events_help_sidebar(){
    $sidebar = '<img class="sigma-help-sidebar-image" src="' . SIGMA_URL . 'assets/sigma-help-logo.png" >';
    return $sidebar;
}
?>
