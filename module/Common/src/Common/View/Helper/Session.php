<?php

namespace Common\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Session extends AbstractHelper {

    protected $sm;
    protected $closeSessionOnBrowserClose = true;

    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $sm) {
        $this->sm = $sm;
    }

    public function __invoke($params=null) {
        return $this;
    }

    /**
     * @param array $args
     */
    public function warningBeforeSessionTimeout($args = array()) {
        $auth = $this->sm->get('zfcuser_auth_service');
        //_pre();
        if (!$auth->hasIdentity()) {
            return;
        }

        if (isset($_COOKIE["remember_me"])) {
            $sessionLength = $args['sls'] ? $args['sls'] : ini_get('session.cookie_lifetime') / 60;
            $sessionLength = $sessionLength + 240;
            $warningLength = $args['wls'] ? $args['wls'] : 5;
            $warningLength = $warningLength + 240;
            $logoutUrl = $args['logoutUrl'] ? $args['logoutUrl'] : BASE_URL . '/user/logout';
            
        }

        //sls: session length in seconds
        //wls: warning length in seconds
        else {
            $sessionLength = $args['sls'] ? $args['sls'] : ini_get('session.cookie_lifetime') / 60;
            $warningLength = $args['wls'] ? $args['wls'] : 5;
            $logoutUrl = $args['logoutUrl'] ? $args['logoutUrl'] : BASE_URL . '/user/logout';
        }
        ?>
        <script>
            var SESSION_LENGTH = <?php echo $sessionLength; ?>;
            var WARNING_LENGTH = <?php echo $warningLength ?>;
            var urlApp = window.location.pathname.split("/");
            var urlState = urlApp[3];
            var urlLocal = urlApp[4];
            var session_timer, warning_timer;
            function pingAuthentication() {
                var c = window.location.pathname.split("/");
                var d = c[1];
                var a = '<?php echo BASE_URL ?>/sess2/ex';
        <?php
//var a = "/" + d + "/CreateSaml2?actionId=extendSessionTimeout";
//var b = "/ee-rest/CreateSaml2?actionId=extendSessionTimeout"; 
        ?>
                $.post(a, function(f, g, e) {
        <?php //$.post(b, function(j, h, i) {   ?>
                    setSessionTimer();
                    setWarningTimer();
        <?php //})    ?>
                })
            }
            function setSessionTimer() {
                clearTimeout(session_timer);
                session_timer = setTimeout(doLogout, SESSION_LENGTH * 60 * 1000)
            }
            function setWarningTimer() {
                clearTimeout(warning_timer);
                warning_timer = setTimeout(doWarning,
                        (SESSION_LENGTH - WARNING_LENGTH) * 60 * 1000)
            }
            function doLogout() {
                //logoutFFE(urlState, urlLocal)
                window.location.href = "<?php echo $logoutUrl ?>";
            }
            if (SESSION_LENGTH > WARNING_LENGTH) {
                setSessionTimer();
                setWarningTimer();
            }

            function doWarning() {
                var text = $('<DIV>')
                        .html(
                        'Your session is about to expire.'
                        + '\nSelect "ok" to extend your session for ' + SESSION_LENGTH + ' minutes. Otherwise, select "Cancel" to end your session in 5 minutes');
                text = text.text();
                result = window.confirm(text);

                if (result === true) {
                    pingAuthentication();
                }
            }
        </script>		
        <?php
    }

    public function pingSAlive($closeSessionOnBrowserClose) {
        if ($closeSessionOnBrowserClose) {
            
        }
    }

}
