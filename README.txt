Mechanical TA
=============

Thanks for trying Mechanical TA!  Here are some brief instructions to get you started.

Installation
------------
Mechanical TA is an Apache/PHP applicationthat also requires a SQL database as a backend.  MTA currently supports MySQL and SQLITE backends.

MTA reqires a number of config files to be set up.  Some of these, such as `config.php`, are MTA-specific; others, such as `.htaccess`, contain MTA-specific configuration for Apache.  Every config file comes with an example (with a `.template` extension). 

The easiest way to get MTA up and running is to run the `setup.py` script to create customized versions of all the config files:

1. Create a public_html directory in your home directory and place mta in it.

2. Run the python script
> python setup.py

3. Set up the "ticktock" cron job.

One step still requires manual intervention: you need to create a cron job that will periodically poll a "ticktock" page, to allow scheduled actions to occur automatically.  Here's an example cronjob line:
3,13,23,33,43,53 * * * * wget -O- https://www.example.com/~OWNER/mta/ticktock.php &> /dev/null

Creating a course
-----------------
Once MTA is up and running, you can use the admin interface to create a course.  The admin interface is available at the root MTA URL plus "/admin"; e.g., `https://www.example.com/~OWNER/mta/admin/`.

Courses can use either "PDO" authentication or "LDAP".  LDAP configuration varies by institution. PDO authentication means usernames and (hashed) passwords are stored in the database.

The short name of the course is used to construct its URL; this must be unique.  The long name of the course is displayed to students.

"Browseable" courses show up in a list at the root MTA URL.  Non-browseable courses are only available from their URL, which is the root URL plus the short coursename (e.g., `https://www.example.com/~OWNER/mta/cs1234`).

After you have created a course, you will want to create at least one user with instructor privileges using the User Manager.

Creating an assignment
----------------------
Create an assignment using the first button on the left (looks like a page with a green plus on it).  You almost always want the "Peer review assignment" type.

Here are some of the most important configuration options:

=== Datetimes ===

* The _Submission_ period is when students may view the assignment and submit their completed assignments for marking/peer review.
* The _Review_ period is when students receive other students' submissions for peer review.  Peer reviews are assigned at the beginning of the period, and must be completed by the end of the period.
* The _Calibration_ period only applies to assignments that have calibration submissions.  This is the period during which this assignments' calibration submissions will be available for students to practice on.

* Marks are posted on the Mark Post Date.

* Appeals are accepted until the Appeal Stop Date.

=== Calibration configuration ===
If you will be using calibration essays, you will need to fill in the calibration configuration:

* _Minimum number of calibration reviews for advancement_: Students can promoted from the Supervised pool to the Independent pool based on their reviewing performance on practice "calibration" assignments.  This option ensures that students must have reviewed a minimum number of calibrations assignments before advancing.  A good default value for this field is _3_.

* _Maximum score for a review_: The maximum score that a calibration review can receive.  We recommend setting this to the same value as the maximum score for regular reviews.

* _Threshold mean-square deviation for advancement_: Calibration reviews are judged by how far they are from the gold-standard "calibration key" review.  This difference is computed in terms of mean squared deviation (MSD) (since in general a review will have multiple different axes).  Students will advance when their average MSD is smaller than this value.  We have found _0.75_ to be a good MSD threshold.

* _Threshold score for advancement_: Students find it confusing to be told their mean squared deviation, so we translate into the same units as review marks.  Reviews with 0 MSD will be assigned the maximum review grade; reviews with the threshold MSD will be assigned this threshold score.  All other MSD's will be assigned by interpolation.  We typically use _8_ (out of 10) as the threshold score.

* _Extra calibrations for supervised students_: MTA allows the instructor to automatically assign calibrations to all supervised students; this value determines how many will be assigned.  In some assignments we set this value to _3_, in later assignments we set it to _0_.

=== Other important options  ===

* _Allow request of reviews_ allows students who did not submit an assignment for marking to nevertheless request a submission to review.  This should typically be left unchecked.

* _Require password_: In the Computers and Society course for which MTA was implemented, students are required to pass a quiz on the chapter (using a separate piece of software) before being allowed to submit an essay.  This is enforced by placing a password on the essay submission mechanism, and giving each student the password when they have passed the quiz.
