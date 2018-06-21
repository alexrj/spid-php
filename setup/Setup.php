<?php

namespace setup;

use Composer\Script\Event;
use Colors;

class Setup {
    // configuration variables:
    private static $curDir;
    private static $wwwDir;
    private static $serviceName;
    private static $entityID;
    private static $acsIndex;
    private static $addTestIDP;
    private static $addExamples;
    private static $localTestIDP;

    // default values:
    private static $_homeDir;
    private static $_wwwDir;
    private static $_curDir;
    private static $_serviceName;
    private static $_entityID;
    private static $_acsIndex;

    public static function setup(Event  $event) {
        $colors = new Colors();

        echo shell_exec("clear");
        echo $colors->getColoredString("SPID PHP SDK Setup\nversion 1.0\n\n", "green");

        // set defaults
        self::$_homeDir = shell_exec('echo -n "$HOME"');
        self::$_wwwDir = shell_exec('echo -n "$HOME/public_html"');
        self::$_curDir = getcwd();
        self::$_serviceName = "myservice";
        self::$_entityID = "https://localhost";
        self::$_acsIndex = 0;

        self::acquireConfig();

        self::configure();
    }

    private static function acquireConfig() {
        $colors = new Colors();

        echo "Please insert path for current directory (" . $colors->getColoredString(self::$_curDir, "green") . "): ";
        self::$curDir = readline();
        if(self::$curDir==null || self::$curDir=="") self::$curDir = self::$_curDir;

        echo "Please insert path for web root directory (" . $colors->getColoredString(self::$_wwwDir, "green") . "): ";
        self::$wwwDir = readline();
        if(self::$wwwDir==null || self::$wwwDir=="") self::$wwwDir = self::$_wwwDir;

        echo "Please insert name for service endpoint (" . $colors->getColoredString(self::$_serviceName, "green") . "): ";
        self::$serviceName = readline();
        if(self::$serviceName==null || self::$serviceName=="") self::$serviceName = self::$_serviceName;

        echo "Please insert your EntityID (" . $colors->getColoredString(self::$_entityID, "green") . "): ";
        self::$entityID = readline();
        if(self::$entityID==null || self::$entityID=="") self::$entityID = self::$_entityID;

        echo "Please insert your Attribute Consuming Service Index (" . $colors->getColoredString(self::$_acsIndex, "green") . "): ";
        self::$acsIndex = readline();
        if(self::$acsIndex==null || self::$acsIndex=="") self::$acsIndex = self::$_acsIndex;

        echo "Add configuration for Public Test IDP idp.spid.gov.it ? (" . $colors->getColoredString("Y", "green") . "): ";
        self::$addTestIDP = readline();
        self::$addTestIDP = (self::$addTestIDP!=null && strtoupper(self::$addTestIDP)=="N")? false:true;

        echo "Optional URI for local Test IDP metadata endpoint (leave empty to skip) ? (): ";
        self::$localTestIDP = readline();
        self::$localTestIDP = self::$localTestIDP == null ? "" : self::$localTestIDP;

        echo "Add example php file to www ? (" . $colors->getColoredString("Y", "green") . "): ";
        self::$addExamples = readline();
        self::$addExamples = (self::$addExamples!=null && strtoupper(self::$addExamples)=="N")? false:true;
    }

    private static function configure() {
        $colors = new Colors();

        echo $colors->getColoredString("\nCurrent directory: " . self::$curDir, "yellow");
        echo $colors->getColoredString("\nWeb root directory: " . self::$wwwDir, "yellow");
        echo $colors->getColoredString("\nService Name: " . self::$serviceName, "yellow");
        echo $colors->getColoredString("\nEntity ID: " . self::$entityID, "yellow");
        echo $colors->getColoredString("\nAttribute Consuming Service Index: " . self::$acsIndex, "yellow");
        echo $colors->getColoredString("\nAdd configuration for Test IDP idp.spid.gov.it: ", "yellow");
        echo $colors->getColoredString((self::$addTestIDP)? "Y":"N", "yellow");
        echo $colors->getColoredString("\nURI for local Test IDP metadata endpoint: " . self::$localTestIDP, "yellow");

        echo "\n\n";

        // create vhost directory if not exists
        if(!file_exists(self::$wwwDir)) {
            echo $colors->getColoredString("\nWebroot directory not found. Making directory " . self::$wwwDir, "yellow");
            echo $colors->getColoredString("\nPlease remember to configure your virtual host.\n\n", "yellow");
            shell_exec("mkdir " . self::$wwwDir);
        }

        // create log directory
        shell_exec("mkdir " . self::$curDir . "/vendor/simplesamlphp/simplesamlphp/log");

        // create certificates
        shell_exec("mkdir " . self::$curDir . "/vendor/simplesamlphp/simplesamlphp/cert");
        shell_exec("openssl req -newkey rsa:2048 -new -x509 -days 3652 -nodes -out " .
                    self::$curDir . "/vendor/simplesamlphp/simplesamlphp/cert/spid-sp.crt -keyout " .
                    self::$curDir . "/vendor/simplesamlphp/simplesamlphp/cert/spid-sp.pem");

        shell_exec("mkdir " . self::$curDir . "/cert");
        shell_exec("cp " . self::$curDir . "/vendor/simplesamlphp/simplesamlphp/cert/*.crt " . self::$curDir . "/cert");

        echo $colors->getColoredString("\n\nReady to setup. Press a key to continue or CTRL-C to exit\n", "white");
        readline();

        // set link to simplesamlphp
        echo $colors->getColoredString("\nCreate symlink for simplesamlphp service... ", "white");
        symlink(self::$curDir . "/vendor/simplesamlphp/simplesamlphp/www", self::$wwwDir . "/" . self::$serviceName);
        echo $colors->getColoredString("OK", "green");

        // customize and copy config file
        echo $colors->getColoredString("\nWrite config file... ", "white");
        $vars = array("{{BASEURLPATH}}"=> "'".self::$serviceName."/'");
        $template = file_get_contents(self::$curDir.'/setup/config/config.tpl', true);
        $customized = str_replace(array_keys($vars), $vars, $template);
        file_put_contents(self::$curDir . "/vendor/simplesamlphp/simplesamlphp/config/config.php", $customized);
        echo $colors->getColoredString("OK", "green");

        // customize and copy authsources file
        echo $colors->getColoredString("\nWrite authsources file... ", "white");
        $vars = array("{{ENTITYID}}"=> "'".self::$entityID."'", "{{ACSINDEX}}"=> self::$acsIndex);
        $template = file_get_contents(self::$curDir.'/setup/config/authsources.tpl', true);
        $customized = str_replace(array_keys($vars), $vars, $template);
        file_put_contents(self::$curDir . "/vendor/simplesamlphp/simplesamlphp/config/authsources.php", $customized);
        echo $colors->getColoredString("OK", "green");

        // customize and copy metadata file
        $template = file_get_contents(self::$curDir.'/setup/metadata/saml20-idp-remote.tpl', true);

        // setup IDP configurations
        $IDPMetadata = "";

        // add configuration for public test IDP
        if(self::$addTestIDP) {
            echo $colors->getColoredString("\nWrite metadata for public test IDP... ", "white");
            $vars = array("{{ENTITYID}}"=> "'".self::$entityID."'");
            $template_idp_test = file_get_contents(self::$_curDir.'/setup/metadata/saml20-idp-remote-test.ptpl', true);
            $template_idp_test = str_replace(array_keys($vars), $vars, $template_idp_test);
            $IDPMetadata .= "\n\n" . $template_idp_test;
            echo $colors->getColoredString("OK", "green");
        }

        // retrieve IDP metadata
        echo $colors->getColoredString("\nRetrieve configurations for production IDPs... ", "white");
        $xml = file_get_contents('https://registry.spid.gov.it/metadata/idp/spid-entities-idps.xml');
        // remove tag prefixes
        $xml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$3", $xml);
        $xml = simplexml_load_string($xml);

        // add configuration for local test IDP metadata
        if(self::$localTestIDP != "") {
            echo $colors->getColoredString("\nRetrieve configuration for local test IDP... ", "white");
            // often test IDPs have snakeoil SSL certificates so let's skip the certificate validation
            $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ),
            );
            $xml1 = file_get_contents(self::$localTestIDP, false, stream_context_create($arrContextOptions));
            // remove tag prefixes
            $xml1 = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$3", $xml1);
            $xml1 = simplexml_load_string($xml1);
            // debug: echo ($xml1 !== FALSE ? 'Valid XML' : 'Parse Error'), PHP_EOL;

            $to = $xml->addChild('EntityDescriptor', $xml1);
            foreach($xml1 as $from) {
                // https://stackoverflow.com/a/4778964
                $toDom = dom_import_simplexml($to);
                $fromDom = dom_import_simplexml($from);
                $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
            }
        }

        foreach($xml->EntityDescriptor as $entity) {
            $OrganizationName = trim($entity->Organization->OrganizationName);
            $OrganizationDisplayName = trim($entity->Organization->OrganizationDisplayName);
            $OrganizationURL = trim($entity->Organization->OrganizationURL);
            $IDPentityID = trim($entity->attributes()['entityID']);
            $X509Certificate = trim($entity->IDPSSODescriptor->KeyDescriptor->KeyInfo->X509Data->X509Certificate);
            $NameIDFormat = trim($entity->IDPSSODescriptor->NameIDFormat);

            $template_slo = file_get_contents(self::$curDir.'/setup/metadata/slo.ptpl', true);
            foreach($entity->IDPSSODescriptor->SingleLogoutService as $slo) {
                $SLOBinding = trim($slo->attributes()['Binding']);
                $SLOLocation = trim($slo->attributes()['Location']);

                if($SLOBinding=="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect") {
                    $vars = array("{{SLOREDIRECTLOCATION}}"=> $SLOLocation);
                    $template_slo = str_replace(array_keys($vars), $vars, $template_slo);
                }

                if($SLOBinding=="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST") {
                    $vars = array("{{SLOPOSTLOCATION}}"=> $SLOLocation);
                    $template_slo = str_replace(array_keys($vars), $vars, $template_slo);
                }
            }

            $template_sso = file_get_contents(self::$curDir.'/setup/metadata/sso.ptpl', true);
            foreach($entity->IDPSSODescriptor->SingleSignOnService as $sso) {
                $SSOBinding = trim($sso->attributes()['Binding']);
                $SSOLocation = trim($sso->attributes()['Location']);

                if($SSOBinding=="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect") {
                    $vars = array("{{SSOREDIRECTLOCATION}}"=> $SSOLocation);
                    $template_sso = str_replace(array_keys($vars), $vars, $template_sso);
                }

                if($SSOBinding=="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST") {
                    $vars = array("{{SSOPOSTLOCATION}}"=> $SSOLocation);
                    $template_sso = str_replace(array_keys($vars), $vars, $template_sso);
                }
            }

            /*
            foreach($entity->IDPSSODescriptor->Attribute as $attr) {
                $friendlyName = trim($attr->attributes()['FriendlyName']);
                $name = trim($attr->attributes()['Name']);
            }
            */

            $icon = "spid-idp-dummy.svg";
            switch($IDPentityID) {
                case "https://loginspid.aruba.it": $icon = "spid-idp-aruba.svg"; break;
                case "https://identity.infocert.it": $icon = "spid-idp-infocertid.svg"; break;
                case "https://spid.intesa.it": $icon = "spid-idp-intesaid.svg"; break;
                case "https://idp.namirialtsp.com/idp": $icon = "spid-idp-namirialid.svg"; break;
                case "https://posteid.poste.it": $icon = "spid-idp-posteid.svg"; break;
                case "https://identity.sieltecloud.it": $icon = "spid-idp-sielteid.svg"; break;
                case "https://spid.register.it": $icon = "spid-idp-spiditalia.svg"; break;
                case "https://login.id.tim.it/affwebservices/public/saml2sso": $icon = "spid-idp-timid.svg"; break;
            }

            $vars = array(
                "{{ENTITYID}}"=> $IDPentityID,
                "{{ICON}}"=> $icon,
                "{{SPENTITYID}}"=> self::$entityID,
                "{{ORGANIZATIONNAME}}"=> $OrganizationName,
                "{{ORGANIZATIONDISPLAYNAME}}"=> $OrganizationDisplayName,
                "{{ORGANIZATIONURL}}"=> $OrganizationURL,
                "{{SSO}}"=> $template_sso,
                "{{SLO}}"=> $template_slo,
                "{{NAMEIDFORMAT}}"=> $NameIDFormat,
                "{{X509CERTIFICATE}}"=> $X509Certificate
            );

            $template_idp = file_get_contents(self::$curDir.'/setup/metadata/saml20-idp-remote.ptpl', true);
            $template_idp = str_replace(array_keys($vars), $vars, $template_idp);

            $IDPMetadata .= "\n\n" . $template_idp;
        }
        echo $colors->getColoredString("OK", "green");

        echo $colors->getColoredString("\nWrite metadata for production IDPs... ", "white");
        $vars = array("{{IDPMETADATA}}"=> $IDPMetadata);
        $template = str_replace(array_keys($vars), $vars, $template);
        file_put_contents(self::$curDir . "/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-remote.php", $template);
        echo $colors->getColoredString("OK", "green");

        /*
        // customize and copy metadata file
        $vars = array("{{ENTITYID}}"=> "'".self::$entityID."'");
        $template = file_get_contents(self::$_curDir.'/setup/metadata/saml20-idp-remote.tpl', true);
        $customized = str_replace(array_keys($vars), $vars, $template);
        file_put_contents(self::$curDir . "/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-remote.php", $customized);
        */

        // overwrite template file
        echo $colors->getColoredString("\nWrite smart-button template... ", "white");
        $vars = array("{{SERVICENAME}}"=> self::$serviceName);
        $template = file_get_contents(self::$curDir.'/setup/templates/selectidp-links.tpl', true);
        $customized = str_replace(array_keys($vars), $vars, $template);
        file_put_contents(self::$curDir . "/vendor/simplesamlphp/simplesamlphp/templates/selectidp-links.php", $customized);

        // overwrite smart button js file
        $vars = array("{{SERVICENAME}}"=> self::$serviceName);
        $template = file_get_contents(self::$curDir.'/setup/www/js/agid-spid-enter.tpl', true);
        $customized = str_replace(array_keys($vars), $vars, $template);
        shell_exec("mkdir " . self::$curDir . "/vendor/simplesamlphp/simplesamlphp/www/js");
        file_put_contents(self::$curDir . "/vendor/simplesamlphp/simplesamlphp/www/js/agid-spid-enter.js", $customized);
        echo $colors->getColoredString("OK", "green");

        // copy smart button css and img
        echo $colors->getColoredString("\nCopy smart-button resurces... ", "white");
        shell_exec("cp -rf " . self::$curDir . "/vendor/italia/spid-smart-button/css " . self::$curDir . "/vendor/simplesamlphp/simplesamlphp/www/css");
        shell_exec("cp -rf " . self::$curDir . "/vendor/italia/spid-smart-button/img " . self::$curDir . "/vendor/simplesamlphp/simplesamlphp/www/img");
        echo $colors->getColoredString("OK", "green");

        // write sdk
        echo $colors->getColoredString("\nWrite sdk helper class... ", "white");
        $vars = array("{{SERVICENAME}}"=> self::$serviceName);
        $template = file_get_contents(self::$curDir.'/setup/sdk/spid-php.tpl', true);
        $customized = str_replace(array_keys($vars), $vars, $template);
        file_put_contents(self::$curDir . "/spid-php.php", $customized);
        echo $colors->getColoredString("OK", "green");

        // write example files
        if(self::$addExamples) {
            echo $colors->getColoredString("\nWrite example files to www (login.php & user.php)... ", "white");
            $vars = array("{{SDKHOME}}"=> self::$curDir);
            $template = file_get_contents(self::$curDir.'/setup/sdk/login.tpl', true);
            $customized = str_replace(array_keys($vars), $vars, $template);
            file_put_contents(self::$wwwDir . "/login.php", $customized);
            $template = file_get_contents(self::$curDir.'/setup/sdk/user.tpl', true);
            $customized = str_replace(array_keys($vars), $vars, $template);
            file_put_contents(self::$wwwDir . "/user.php", $customized);
            echo $colors->getColoredString("OK", "green");
        }

        // reset permissions
        echo $colors->getColoredString("\nSetting directories and files permissions... ", "white");
        shell_exec("find " . self::$curDir . "/. -type d -exec chmod 0755 {} \;");
        shell_exec("find " . self::$curDir . "/. -type f -exec chmod 0644 {} \;");
        shell_exec("chmod 777 " . self::$curDir . "/vendor/simplesamlphp/simplesamlphp/log");

        if(self::$addExamples) {
            shell_exec("chmod 0644 " . self::$wwwDir . "/login.php");
            shell_exec("chmod 0644 " . self::$wwwDir . "/user.php");
        }
        echo $colors->getColoredString("OK", "green");



        echo $colors->getColoredString("\n\nSPID PHP SDK successfully installed! Enjoy the identities\n\n", "green");
    }



    public static function remove() {
        $colors = new Colors();

        // retrieve path and inputs
        self::$_wwwDir = shell_exec('echo -n "$HOME/public_html"');
        $_installDir = getcwd();
        self::$_serviceName = "myservice";

        echo "Please insert root path where sdk is installed (" . $colors->getColoredString($_installDir, "green") . "): ";
        $installDir = readline();
        if($installDir==null || $installDir=="") $installDir = $_installDir;

        echo "Please insert path for www (" . $colors->getColoredString(self::$_wwwDir, "green") . "): ";
        self::$wwwDir = readline();
        if(self::$wwwDir==null || self::$wwwDir=="") self::$wwwDir = self::$_wwwDir;

        echo "Please insert name for service endpoint (" . $colors->getColoredString(self::$_serviceName, "green") . "): ";
        self::$serviceName = readline();
        if(self::$serviceName==null || self::$serviceName=="") self::$serviceName = self::$_serviceName;


        echo $colors->getColoredString("\nRemove vendor directory... ", "white");
        shell_exec("rm -Rf " . $installDir . "/vendor");
        echo $colors->getColoredString("OK", "green");
        echo $colors->getColoredString("\nRemove cert directory... ", "white");
        shell_exec("rm -Rf " . $installDir . "/cert");
        echo $colors->getColoredString("OK", "green");
        echo $colors->getColoredString("\nRemove simplesamlphp service symlink... ", "white");
        shell_exec("rm " . self::$wwwDir . "/" . self::$serviceName);
        echo $colors->getColoredString("OK", "green");
        echo $colors->getColoredString("\nRemove sdk file... ", "white");
        shell_exec("rm " . $installDir . "/spid-php.php");
        echo $colors->getColoredString("OK", "green");
        echo $colors->getColoredString("\nRemove composer lock file... ", "white");
        shell_exec("rm " . $installDir . "/composer.lock");
        echo $colors->getColoredString("OK", "green");


        echo $colors->getColoredString("\n\nSPID PHP SDK successfully removed\n\n", "green");
    }



}

?>
