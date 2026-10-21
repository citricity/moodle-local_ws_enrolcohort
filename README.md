# Moodle Webservices for Cohort Enrolment.

> **This is a fork.** The original plugin was created and maintained by
> [LearningWorks](https://github.com/learningworks/moodle-local_ws_enrolcohort). This fork, maintained by
> [Citricity](https://github.com/citricity/moodle-local_ws_enrolcohort), brings it up to date for Moodle 4.5+.
> See [About this fork](#about-this-fork) for what has changed and who to thank.

* [About this fork](#about-this-fork)
* [What is this plugin and why was it developed?](#what-is-this-plugin-and-why-was-it-developed?)
* [What can this plugin do?](#what-can-this-plugin-do?)
* [What else can this plugin do?](#what-else-can-this-plugin-do?)
* [What can this plugin not do?](#what-can-this-plugin-not-do?)
* [How do I install this plugin?](#how-do-i-install-this-plugin?)
* [What version of Moodle can I install this on?](#what-version-of-moodle-can-i-install-this-on?)
* [How do I use this plugin?](#how-do-i-use-this-plugin?)
* [Examples JSON responses](#example-json-responses)
* [Credits and thanks](#credits-and-thanks)

About this fork
---------------

The original plugin was written by Donald Barrett at
[LearningWorks](https://github.com/learningworks/moodle-local_ws_enrolcohort) and last released as v3.3.3 for
Moodle 3.3 to 3.6. It has been forked to keep it working on Moodle 4.5+.

Changes in this fork so far:

* Requires Moodle 4.5 (`$plugin->requires = 2024100700`) and uses the `core_external` web service classes.
* The web service functions have been split into one trait per operation under `classes/external`. The service
  definitions and function names are unchanged, so existing integrations keep working.
* Fixed `add_instance()` ignoring a requested `status` of disabled.
* The exception `cohort_enrol_instance_already_synced_with_role_exception` has been renamed to
  `instance_role_synced_already_exception`. The `errorcode` (`enrolcohortalreadysyncedwithrole`) is unchanged, but
  clients that check the `exception` class name in error responses need updating.
* Additional PHPUnit tests, code tidied to the current Moodle coding standard, and a GitHub Actions workflow that runs
  moodle-plugin-ci on every pull request.

Please raise issues and pull requests for Moodle 4.5+ against
[this fork](https://github.com/citricity/moodle-local_ws_enrolcohort) rather than the original repository.

What is this plugin and why was it developed?
---------------------------------------------

This is a local plugin with webservice functions.

It was developed for <a href="https://www.twoa.ac.nz" target="_blank" alt="Link to Te Wānanga O Aotearoa website" title="Link to Te Wānanga O Aotearoa website">Te Wānanga O Aotearoa</a>
to streamline the enrolment of tauira (student, learner) into courses. Cohorts and courses are created using Moodle webservices but 
adding cohort enrolment instances to courses was a manual process. 

The overall goal is to have this webservice function integrated into Moodle core.


What can this plugin do?
-------------------------

This local plugin enables people to add a cohort enrolment instance to a course using webservices.

What else can this plugin do?
------------------------------

In addition to adding a cohort enrolment instance to a course using webservices, this plugin can also:

* Delete an existing cohort enrolment instance
* Update an existing cohort enrolment instance
    * The name of the cohort enrolment instance
    * The status of the cohort enrolment instance (i.e. active or inactive) 
    * The role that the users of the cohort enrolment instance is synchronised with
    * The group that users of the cohort enrolment instance are added to 
      (i.e. create a new group, an existing group that belongs to the course, none)
* Get a list of all cohort enrolment instances
* Get a list of cohort enrolment instances for a specific course

What can this plugin not do?
----------------------------

This plugin can not do anything that is not listed under [What can this plugin do?](#what-can-this-plugin-do?)
or [What else can this plugin do?](#what-else-can-this-plugin-do?)

How do I install this plugin?
-----------------------------

This plugin can be installed by following the official 
<a href="http://docs.moodle.org/en/Installing_plugins" target="_blank">Moodle documentation</a>. 


What version of Moodle can I install this on?
---------------------------------------------

This fork requires Moodle 4.5 or later and is tested against Moodle 4.5 on PHP 8.1 to 8.3.

For Moodle 3.3 to 3.6, use v3.3.3 of the
[original LearningWorks plugin](https://github.com/learningworks/moodle-local_ws_enrolcohort).

How do I use this plugin?
-------------------------

This plugin can be used in accordance with the official 
<a href="https://docs.moodle.org/en/Using_web_services" target="_blank">Moodle documentation</a>.

Example JSON responses.
-----------------------

This is an example of a JSON response to the webservice function get_instances().

`{
     "id": -1,
     "code": 200,
     "message": "Found 0 cohort enrolment instances in 2 courses (All courses).",
     "data": []
 }`
 
Here are some example JSON responses to the webservice function add_instance().
 
 `{
      "exception": "local_ws_enrolcohort\\exceptions\\cohort_not_found_exception",
      "errorcode": "objectnotfound",
      "message": "Object does not exist!",
      "debuginfo": "Could not find cohort with id 2"
  }`
  
 `{
      "id": 24,
      "code": 201,
      "message": "Cohort enrolment instance added.",
      "data": [
          {
              "object": "course",
              "id": 2,
              "name": "TestCourse1",
              "idnumber": "",
              "shortname": "TestCourse1",
              "visible": 1,
              "format": "topics"
          },
          {
              "object": "cohort",
              "id": 4,
              "name": "Test",
              "idnumber": "1",
              "visible": 1
          },
          {
              "object": "role",
              "id": 1,
              "shortname": "manager"
          },
          {
              "object": "data",
              "name": "",
              "cohortid": 4,
              "roleid": 1,
              "groupid": 0,
              "status": 0
          },
          {
              "object": "group",
              "id": 0,
              "name": "Enrol instance group none.",
              "courseid": -1
          },
          {
              "object": "enrol",
              "id": 24,
              "name": "Cohort sync (Test - Manager) - Using system generated name.",
              "courseid": 2,
              "cohortid": 4,
              "roleid": 1,
              "groupid": 0,
              "status": 0
          }
      ]
  }`

Credits and thanks
------------------

A big thank you to everyone who built and looked after this plugin before this fork:

* **[LearningWorks](https://github.com/learningworks/moodle-local_ws_enrolcohort)** for creating the plugin,
  maintaining it, and releasing it as open source under the GPL.
* **Donald Barrett**, the original author, who wrote the web service functions, the response objects and the original
  test suite that this fork still builds on.
* **[Te Wānanga o Aotearoa](https://www.twoa.ac.nz)**, for whom the plugin was originally developed.
* **Thomas ([thoschi](https://github.com/thoschi))** for fixing cohort enrolment on sites with more than 25 cohorts
  ([PR #5](https://github.com/learningworks/moodle-local_ws_enrolcohort/pull/5)).
* **Ondrej Galbavy ([OndroNR](https://github.com/OndroNR))** for fixing a problem with user deletion
  ([PR #7](https://github.com/learningworks/moodle-local_ws_enrolcohort/pull/7)).

Thanks also to everyone who has used the plugin and reported issues. If you rely on this fork, please consider
contributing fixes back the same way.
