<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
Design by Free CSS Templates
http://www.freecsstemplates.org
Released for free under a Creative Commons Attribution 3.0 License

Name       : Oceania
Description: A two-column, fixed-width design with a bright color scheme.
Version    : 1.0
Released   : 20120208
-->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="description" content="" />
<meta name="keywords" content="" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php get_page_title(); ?> </title>
<?php get_page_headers(); ?>
</head>
<body>
<?php get_page_scripts(); ?>
<div id="wrapper">
    <div id="header">
        <div id="logo">
            <h1>Mechanical TA <?php get_course_name_with_prefix();?></h1>
        </div>
        <div id="menu">
            <ul>
            <?php get_page_menu(); ?>
            </ul>
            <br class="clearfix" />
        </div>
        <!--<?//php if($authMgr->isLoggedIn()) { ?> Logged in as <//?php get_user_name(); }?> -->
    </div>
    <div id="page">
        <div id="content">
        <!--<table width='100%'><tr><td align='center'>Contact <a href='mailto:cwthornt@cs.ubc.ca'>Chris</a> if you are having any Mechanical TA issues, not the course instructor</td></tr></table>-->
            <div class="box">
                <?php get_page_content(); ?>
            </div>
            <br class="clearfix" />
        </div>
        <br class="clearfix" />
    </div>
</div>
<div id="footer">
    Copyright (c) 2013 Chris Thornton. Design by <a href="http://www.freecsstemplates.org">FCT</a>.<br>
    <?php get_contact_string(); ?>
</div>
</body>
</html>
