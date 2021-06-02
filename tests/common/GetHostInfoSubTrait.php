<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\common;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/common/get-host-info.sub.js
 */
trait GetHostInfoSubTrait
{
    /**
     * Host information for cross-origin tests.
     * @returns {Object} with properties for different host information.
     */
    public static function get_host_info()
    {
        $HTTP_PORT = '80';
        $HTTP_PORT2 = '8080';
        $HTTPS_PORT = '443';
        $HTTPS_PORT2 = '8443';
        // $PROTOCOL = self.location.protocol;
        $PROTOCOL = 'https:';
        $IS_HTTPS = ($PROTOCOL === "https:");
        $HTTP_PORT_ELIDED = $HTTP_PORT === "80" ? "" : (":" . $HTTP_PORT);
        $HTTP_PORT2_ELIDED = $HTTP_PORT2 === "80" ? "" : (":" . $HTTP_PORT2);
        $HTTPS_PORT_ELIDED = $HTTPS_PORT === "443" ? "" : (":" . $HTTPS_PORT);
        $PORT_ELIDED = $IS_HTTPS ? $HTTPS_PORT_ELIDED : $HTTP_PORT_ELIDED;
        $ORIGINAL_HOST = 'localhost';
        $REMOTE_HOST = ($ORIGINAL_HOST === 'localhost') ? '127.0.0.1' : ('www1.' . $ORIGINAL_HOST);
        $OTHER_HOST = 'example.com';
        $NOTSAMESITE_HOST = ($ORIGINAL_HOST === 'localhost') ? '127.0.0.1' : ('example.com');

        return [
            'HTTP_PORT' => $HTTP_PORT,
            'HTTP_PORT2' => $HTTP_PORT2,
            'HTTPS_PORT' => $HTTPS_PORT,
            'HTTPS_PORT2' => $HTTPS_PORT2,
            'ORIGINAL_HOST' => $ORIGINAL_HOST,
            'REMOTE_HOST' => $REMOTE_HOST,

            'ORIGIN' => $PROTOCOL . "//" . $ORIGINAL_HOST . $PORT_ELIDED,
            'HTTP_ORIGIN' => 'http://' . $ORIGINAL_HOST . $HTTP_PORT_ELIDED,
            'HTTPS_ORIGIN' => 'https://' . $ORIGINAL_HOST . $HTTPS_PORT_ELIDED,
            'HTTPS_ORIGIN_WITH_CREDS' => 'https://foo:bar@' . $ORIGINAL_HOST . $HTTPS_PORT_ELIDED,
            'HTTP_ORIGIN_WITH_DIFFERENT_PORT' => 'http://' . $ORIGINAL_HOST . $HTTP_PORT2_ELIDED,
            'REMOTE_ORIGIN' => $PROTOCOL . "//" . $REMOTE_HOST . $PORT_ELIDED,
            'HTTP_REMOTE_ORIGIN' => 'http://' . $REMOTE_HOST . $HTTP_PORT_ELIDED,
            'HTTP_NOTSAMESITE_ORIGIN' => 'http://' . $NOTSAMESITE_HOST . $HTTP_PORT_ELIDED,
            'HTTP_REMOTE_ORIGIN_WITH_DIFFERENT_PORT' => 'http://' . $REMOTE_HOST . $HTTP_PORT2_ELIDED,
            'HTTPS_REMOTE_ORIGIN' => 'https://' . $REMOTE_HOST . $HTTPS_PORT_ELIDED,
            'HTTPS_REMOTE_ORIGIN_WITH_CREDS' => 'https://foo:bar@' . $REMOTE_HOST . $HTTPS_PORT_ELIDED,
            'HTTPS_NOTSAMESITE_ORIGIN' => 'https://' . $NOTSAMESITE_HOST . $HTTPS_PORT_ELIDED,
            'UNAUTHENTICATED_ORIGIN' => 'http://' . $OTHER_HOST . $HTTP_PORT_ELIDED,
            'AUTHENTICATED_ORIGIN' => 'https://' . $OTHER_HOST . $HTTPS_PORT_ELIDED,
        ];
    }

    /**
     * When a default port is used, location.port returns the empty string.
     * This function attempts to provide an exact port, assuming we are running under wptserve.
     * @param {*} loc - can be Location/<a>/<area>/URL, but assumes http/https only.
     * @returns {string} The port number.
     */
    public static function get_port($loc) {
        if ($loc->port) {
            return $loc->port;
        }

        return $loc->protocol === 'https:' ? '443' : '80';
    }
}
