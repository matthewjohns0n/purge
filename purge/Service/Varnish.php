<?php

namespace KevinCupp\Addons\Purge\Service;

/**
 * Varnish Service
 */
class Varnish
{

    /**
     * Sends a PURGE request to the specified URL
     *
     * @param string $purge_url Full URL to send the Purge request to
     * @return string Response text from request
     */
    public function purge($purge_url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $purge_url);
        curl_setopt($ch, CURLOPT_PORT, $this->getPort());
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: '.$_SERVER['SERVER_NAME']]);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        $resp = curl_exec($ch);

        if (curl_errno($ch) > 1) {
            $resp = curl_error($ch);
        }

        curl_close($ch);

        // Extract message from Varnish response
        preg_match("/<p>([^<]*)<\/p>/", $resp, $parsed);

        return isset($parsed[1]) ? $parsed[1] : 'Unexpected response: '. $resp;
    }

    /**
     * Gets the port on which Varnish is listening; if it's not specified in the
     * config, we'll try to infer it
     *
     * @return int Varnish port number
     */
    public function getPort()
    {
        if (! $port = ee()->config->item('varnish_port')) {
            $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
        }

        return (int) $port;
    }

    /**
     * Gets the site url of the Varnish server
     * Defaults to site_url if there is no config set
     *
     * @return array Varnish site urls
     */
    public function getSiteUrls()
    {
        if (! $site_urls = ee()->config->item('varnish_site_url')) {
            $site_urls = ee()->config->item('site_url');
        }

        // If we get a string for our site url, we kow it is just one
        if (! is_array($site_urls)) {
            $site_urls = array($site_urls);
        }

        foreach ($site_urls as &$site_url) {
            $site_url = rtrim($site_url, '/') . '/';
        }

        return $site_urls;
    }

    public function purgeEntryWithRule($entry, $rule)
    {
        $responses = array();
        $site_urls = ee('purge:Varnish')->getSiteUrls();
        foreach ($site_urls as $site_url) {
            $purge_url = $site_url . ltrim($rule->pattern, '/');
            $purge_url = str_replace('{url_title}', $entry->url_title, $purge_url);

            $responses[$purge_url] = ee('purge:Varnish')->purge($purge_url);
        }
        return $responses;
    }

    public function purgeCustomPath($path)
    {
        $responses = array();
        $site_urls = ee('purge:Varnish')->getSiteUrls();
        foreach ($site_urls as $site_url) {
            $purge_url = $site_url . $path;

            $responses[$purge_url] = ee('purge:Varnish')->purge($purge_url);
        }
        return $responses;
    }
}

// EOF
