<?php
namespace phpjs\elements;

use phpjs\urls\HostFactory;
use phpjs\urls\URLInternal;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#htmlhyperlinkelementutils
 */
trait HTMLHyperlinkElementUtils
{
    protected $mUrl;

    private function HTMLHyperlinkElementUtilsDestructor()
    {
        $this->mUrl = null;
    }

    private function HTMLHyperlinkElementUtilsGetter($aName)
    {
        switch ($aName) {
            case 'hash':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    !($fragment = $this->mUrl->getFragment())
                ) {
                    return '';
                }

                return '#' . $fragment;

            case 'host':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    ($host = $this->mUrl->getHost()) === null
                ) {
                    return '';
                }

                if (($port = $this->mUrl->getPort()) === null) {
                    return HostFactory::serialize($host);
                }

                return HostFactory::serialize($host) . ':' . $port;

            case 'hostname':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    ($host = $this->mUrl->getHost()) === null
                ) {
                    return '';
                }

                return HostFactory::serialize($host);

            case 'href':
                $this->reinitialiseURL();

                if ($this->mUrl === null && !$this->hasAttribute('href')) {
                    return '';
                } elseif ($this->mUrl === null) {
                    return $this->getAttribute('href');
                }

                return $this->mUrl->serializeURL();

            case 'origin':
                $this->reinitialiseURL();

                if ($this->mUrl === null) {
                    return '';
                }

                return $this->mUrl->getOrigin()->serializeAsUnicode();

            case 'password':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    ($password = $this->mUrl->getPassword()) === null
                ) {
                    return '';
                }

                return $password;

            case 'pathname':
                $this->reinitialiseURL();

                if ($this->mUrl === null) {
                    return '';
                }

                if ($this->mUrl->isFlagSet(
                    URLInternal::FLAG_CANNOT_BE_A_BASE_URL
                )) {
                    return $this->mUrl->getPath()[0];
                }

                $output = '/';

                foreach ($this->mUrl->getPath() as $key => $path) {
                    if ($key > 0) {
                        $output .= '/';
                    }

                    $output .= $path;
                }

                return $output;

            case 'port':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    ($port = $this->mUrl->getPort()) === nul
                ) {
                    return '';
                }

                return $port;

            case 'protocol':
                $this->reinitialiseURL();

                return $this->mUrl === null ?
                    ':' : $this->mUrl->getScheme() . ':';

            case 'search':
                $this->reinitialiseURL();

                return $this->mUrl === null ||
                    !($query = $this->mUrl->getQuery()) ? '' : '?' . $query;

            case 'username':
                $this->reinitialiseURL();

                return $this->mUrl === null ? '' : $this->mUrl->getUsername();

            default:
                return 'HTMLHyperlinkElementUtilsGetter';
        }
    }

    private function HTMLHyperlinkElementUtilsSetter($aName, $aValue)
    {
        switch ($aName) {
            case 'hash':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    !$this->mUrl->getScheme() == 'javascript'
                ) {
                    // Terminate these steps
                    return;
                }

                if ($aValue === '') {
                    $this->mUrl->mUrl->setFragment(null);
                } else {
                    $input = $aValue[0] == '#' ? substr($aValue, 1) : $aValue;
                    $this->mUrl->setFragment('');
                    URLInternal::basicURLParser(
                        $input,
                        null,
                        null,
                        $this->mUrl,
                        URLInternal::FRAGMENT_STATE
                    );
                }

                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;

            case 'host':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    $this->mUrl->isFlagSet(
                        URLInternal::FLAG_CANNOT_BE_A_BASE_URL
                    )
                ) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser($aValue,
                    null,
                    null,
                    $this->mUrl,
                    URLInternal::HOST_STATE
                );
                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;

            case 'hostname':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    $this->mUrl->isFlagSet(
                        URLInternal::FLAG_CANNOT_BE_A_BASE_URL
                    )
                ) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser(
                    $aValue,
                    null,
                    null,
                    $this->mUrl,
                    URLInternal::HOSTNAME_STATE
                );
                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;

            case 'href':
                $this->mAttributesList->setAttrValue($this, 'href', $aValue);

                break;

            case 'password':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    $this->mUrl->getHost() === null ||
                    $this->mUrl->isFlagSet(
                        URLInternal::FLAG_CANNOT_BE_A_BASE_URL
                    )
                ) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setPasswordSteps($aValue);
                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;

            case 'pathname':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    $this->mUrl->isFlagSet(
                        URLInternal::FLAG_CANNOT_BE_A_BASE_URL
                    )
                ) {
                    // Terminate these steps
                    return;
                }

                while (!$this->mUrl->getPath()->isEmpty()) {
                    $this->mUrl->getPath()->pop();
                }

                URLInternal::basicURLParser(
                    $aValue,
                    null,
                    null,
                    $this->mUrl,
                    URLInternal::PATH_START_STATE
                );
                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;

            case 'port':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    $this->mUrl->getHost() === null ||
                    $this->mUrl->isFlagSet(
                        URLInternal::FLAG_CANNOT_BE_A_BASE_URL
                    ) ||
                    $this->mUrl->getScheme() == 'file'
                ) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser(
                    $aValue,
                    null,
                    null,
                    $this->mUrl,
                    URLInternal::PORT_STATE
                );
                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;

            case 'protocol':
                $this->reinitialiseURL();

                if ($this->mUrl === null) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser(
                    $aValue . ':',
                    null,
                    null,
                    $this->mUrl,
                    URLInternal::SCHEME_START_STATE
                );
                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;

            case 'search':
                $this->reinitialiseURL();

                if ($this->mUrl === null) {
                    // Terminate these steps
                    return;
                }

                if ($aValue === '') {
                    $this->mUrl->setQuery(null);
                } else {
                    $input = $aValue[0] == '?' ? substr($aValue, 1) : $aValue;
                    $this->mUrl->setQuery('');
                    URLInternal::basicURLParser(
                        $input,
                        null,
                        $this->mOwnerDocument->characterSet,
                        $this->mUrl,
                        URLInternal::QUERY_STATE
                    );
                }

                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;

            case 'username':
                $this->reinitialiseURL();

                if (
                    $this->mUrl === null ||
                    $this->mUrl->getHost() === null ||
                    $this->mUrl->isFlagSet(
                        URLInternal::FLAG_CANNOT_BE_A_BASE_URL
                    )
                ) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setUsernameSteps($aValue);
                $this->mAttributesList->setAttrValue(
                    $this,
                    'href',
                    $this->mUrl->serializeURL()
                );

                break;
        }
    }

    private function initHTMLHyperlinkElementUtils()
    {
        $this->mUrl = null;
    }

    /**
     * Reintialises the element's URL.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#reinitialise-url
     */
    protected function reinitialiseUrl()
    {
        $shouldTerminate = $this->mUrl && $this->mUrl->getScheme() === 'blob' &&
            $this->mUrl->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL);

        if ($shouldTerminate) {
            // Terminate these steps
            return;
        }

        $this->setURL();
    }

    /**
     * Sets this Element's URL to the result of parsing it's href content
     * attribute or null if parsing fails.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#concept-hyperlink-url-set
     */
    protected function setURL()
    {
        $href = $this->mAttributesList->getAttrValue($this, 'href', null);
        $url = $this->parseURL($href, $this->mOwnerDocument);
        $this->mUrl = $url === false ? null : $url['urlRecord'];
    }
}
