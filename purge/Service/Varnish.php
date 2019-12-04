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
    public function purge($purge_url=null)
    {
        // If the purge_url is not set, lets default to the site
        if (empty($purge_url)) {
            return ee('purge:Varnish')->purgeAll();
        }

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

    public function purgeAll($purge_urls = null)
    {
        // If we are not passed an array of purge urls, we get the default urls
        if (empty($purge_urls)) {
            $purge_urls = ee('purge:Varnish')->getSiteUrls();
        }

        $responses = array();

        foreach ($purge_urls as $purge_url) {
            $responses[$purge_url] = ee('purge:Varnish')->purge($purge_url);
        }

        return $responses;
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
     * Gets the site urls of the Varnish server
     * Defaults to site_url if there is no config set
     *
     * @return array Varnish site urls
     */
    public function getSiteUrls($path=null)
    {
        if (! $site_urls = ee()->config->item('varnish_site_url')) {
            $site_urls = ee()->config->item('site_url');
        }

        // If we get a string for our site url, we kow it is just one
        if (! is_array($site_urls)) {
            $site_urls = array($site_urls);
        }

        // Format each url properly
        foreach ($site_urls as &$site_url) {
            $site_url = rtrim($site_url, '/') . '/';

            // Add the path to the urls if it is set
            if (!empty($path)) {
                $site_url = . ltrim($path, '/');
            }
        }

        // Remove any empty elements from array
        $site_urls = array_filter($site_urls);

        return $site_urls;
    }

    public function purgeEntryWithRule($entry, $rule)
    {
        $path = str_replace('{url_title}', $entry->url_title, $rule->pattern);
        $site_urls = ee('purge:Varnish')->getSiteUrls($path);

        return $this->purgeAll($site_urls);
    }

    public function purgeCustomPath($path)
    {
        $site_urls = ee('purge:Varnish')->getSiteUrls($path);
        return $this->purgeAll($site_urls);
    }
}

// EOF
