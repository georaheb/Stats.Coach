<?php
/**
 * Created by IntelliJ IDEA.
 * User: richardmiles
 * Date: 2/25/18
 * Time: 3:29 AM
 */

namespace App;

use CarbonPHP\Application;
use CarbonPHP\Error\PublicAlert;
use CarbonPHP\View;
use Controller\User;
use /** @noinspection PhpUndefinedClassInspection */
    Mustache_Engine;
use Tables\carbon_users;
use Tables\carbon_user_golf_stats as Stats;

class StatsCoach extends Application
{
    /**
     * Bootstrap constructor. Places basic variables
     * in our json response that will be needed by many pages.
     * @param null $structure
     * @throws \CarbonPHP\Error\PublicAlert
     */
    public function __construct($structure = null)
    {
        global $json;

        if (!\is_array($json)) {
            $json = array();
        }
        $json['SITE'] = SITE;
        $json['POST'] = $_POST;
        $json['HTTP'] = HTTP;
        $json['HTTPS'] = HTTPS;
        $json['SOCKET'] = SOCKET;
        $json['AJAX'] = AJAX;
        $json['PJAX'] = PJAX;
        $json['SITE_TITLE'] = SITE_TITLE;
        $json['APP_VIEW'] = APP_VIEW;
        $json['TEMPLATE'] = TEMPLATE;
        $json['COMPOSER'] = COMPOSER;
        $json['X_PJAX_Version'] = &$_SESSION['X_PJAX_Version'];
        $json['FACEBOOK_APP_ID'] = FACEBOOK_APP_ID;

        parent::__construct($structure);
    }

    public function defaultRoute()
    {
        // Sockets will not execute this function
        View::$forceWrapper = true; // this will hard refresh the wrapper

        if (!$_SESSION['id']):
            return $this->MVC()('User', 'login');
        else:
            return $this->MVC()('Golf', 'golf');
        endif;
    }

    /**
     * @param null|string $uri
     * @return bool
     * @throws PublicAlert
     */
    public function startApplication(string $uri): bool
    {
        static $count;

        if (empty($count)) {
            $count = 0;
        } else {
            $count++;
        }

        $this->userSettings();          // Update the current user

        if ('' !== $uri) {
            $this->changeURI($uri);
        } else if (empty($this->uri[0])) {
            if (SOCKET) {
                throw new PublicAlert('$_SERVER["REQUEST_URI"] MUST BE SET IN SOCKET REQUESTS');
            }
            $this->matched = true;
            return $this->defaultRoute();
        }

        $this->structure($this->MVC());

        if ($this->match('Contact', 'Messages', 'Mail')()) {
            return true;
        }

        ################################### MVC
        if (!$_SESSION['id']) {  // Signed out
            if ($this->match('Login/*', 'User', 'login')() ||
                $this->match('oAuth/{service}/{request?}/*', 'User', 'oAuth')() ||
                $this->match('Register/*', 'User', 'Register')() ||           // Register
                $this->match('Recover/{user_email?}/{user_generated_string?}/', 'User', 'recover')()) {     // Recover $userId
                return true;
            }
        } else {
            // Event
            if (((AJAX && !PJAX) || SOCKET) && (
                    $this->match('Search/{search}/', 'Search', 'all')() ||
                    $this->match('Messages/', 'Messages', 'navigation')() ||
                    $this->match('Messages/{user_uri}/', 'Messages', 'chat')() ||    // chat box widget
                    $this->structure($this->JSON())->match('Follow/{user_id}/', 'User', 'follow')() ||
                    $this->match('Unfollow/{user_id}/', 'User', 'unfollow')())) {
                return true;         // Event
            }

            // $url->match('Notifications/*', 'notifications/notifications', ['widget' => '#NavNotifications']);

            // $url->match('tasks/*', 'tasks/tasks', ['widget' => '#NavTasks']);

            if (SOCKET) {
                return false;
            }                // Sockets only get json

            ################################### MVC
            $this->structure($this->MVC());

            ################################### Golf Stuff + User

            if ($this->match('PostScore/Basic/{state?}/*', 'Golf', 'PostScoreBasic')() ||
                $this->match('PostScore/Color/{id}/*', 'Golf', 'PostScoreColor')() ||
                $this->match('PostScore/Distance/{id}/{color}/{hole?}/*', 'Golf', 'PostScoreDistance')()) {
                return true;
            }

            if ($this->match('AddCourse/Basic/{state?}/*', 'Golf', 'AddCourseBasic')() ||
                $this->match('AddCourse/Color/{id}/{box_number}/*', 'Golf', 'AddCourseColor')() ||
                $this->match('AddCourse/Distance/{id}/{box_number}/*', 'Golf', 'AddCourseDistance')()) {
                return true;
            }

            if ($this->match('Profile/{user_uri?}/', 'User', 'profile')() ||   // Profile $user
                $this->match('Messages/*', 'Messages', 'messages')() ||
                $this->match('Home/*', 'Golf', 'golf')() ||
                $this->match('Golf/*', 'Golf', 'golf')() ||
                $this->match('Team/{team_id}/*', 'Team', 'team')() ||
                $this->match('Rounds/{user_uri?}/', 'Golf', 'rounds')() ||
                $this->match('JoinTeam/', 'Team', 'joinTeam')() ||
                $this->match('CreateTeam/', 'Team', 'createTeam')() ||
                $this->match('Logout/*', function () {
                    User::logout();
                })) {
                return true;          // Logout
            }
        }

        return $this->structure($this->MVC())->match('Activate/{email?}/{email_code?}/', 'User', 'activate')() ||  // Activate $email $email_code
            $this->structure($this->wrap())->match('404/*', 'Error/404error.php')() ||
            $this->match('500/*', 'Error/500error.php')();

    }


    /**
     * App constructor. If no uri is set than
     * the Route constructor will execute the
     * defaultRoute method defined below.
     * @return void
     * @throws \CarbonPHP\Error\PublicAlert
     */

    public function userSettings() : void
    {
        global $user, $json;

        $id = &$_SESSION['id'];

        // If the user is signed in we need to get the
        if ($id ?? false) {
            if (!\is_array($user[$id] ?? false)) {
                $user[$id] = [];
            }

            if (!carbon_users::Get($user[$id], $id, [])) {
                $_SESSION['id'] = false;
                throw new PublicAlert('Failed to fetch user. This usually happens when a users id becomes invalid.');
            }

            if (empty($user[$id]['user_profile_pic']) || $user[$id]['user_profile_pic'] === null) {
                $user[$id]['user_profile_pic'] = '/view/img/Carbon-red.png';
            }

            $user[$id]['stats'] = [];
            if (!Stats::Get($user[$id]['stats'], $id, [])) {
                print 'Could not get stats!';
                exit(1);
            }

            $json['my'] = &$user[$id];
            $json['signedIn'] = true;
            $json['nav-bar'] = '';
            $json['user-layout'] = 'class="wrapper" style="background: rgba(0, 0, 0, 0.7)"';


            $mustache = function ($path) {      // This is our mustache template engine implemented in php, used for rendering user content
                global $json;
                static $mustache;
                if (empty($mustache)) {
                    $mustache = new \Mustache_Engine();
                }
                if (!file_exists($path)) {
                    print "<script>Carbon(() => carbon.alert('Content Buffer Failed ($path), Does Not Exist!', 'danger'))</script>";
                }
                return $mustache->render(file_get_contents($path), $json);
            };

            switch ($user[$id]['user_type'] ?? false) {
                case 'Athlete':
                    $json['body-layout'] = 'hold-transition skin-blue layout-top-nav';
                    $json['header'] = $mustache(APP_ROOT . APP_VIEW . 'Layout/AthleteLayout.hbs');
                    break;
                case 'Coach':
                    $json['body-layout'] = 'skin-green fixed sidebar-mini sidebar-collapse';
                    $json['header'] = $mustache(APP_ROOT . APP_VIEW . 'Layout/CoachLayout.hbs');
                    break;
                default:
                    throw new PublicAlert('No user type found!!!!');
            }
        } else {
            $json['body-layout'] = 'stats-wrap';
            $json['user-layout'] = 'class="container" id="pjax-content"';
        }
    }

}